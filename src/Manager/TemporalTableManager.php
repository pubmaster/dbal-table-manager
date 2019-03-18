<?php

namespace DBALTableManager\Manager;

use DBALTableManager\BaseConnectionInterface;
use DBALTableManager\Entity\EntityInterface;
use DBALTableManager\Entity\TemporalVersionEntityInterface;
use DBALTableManager\Exception\InvalidRequestException;
use DBALTableManager\Exception\QueryExecutionException;
use DBALTableManager\Query\Filter;
use DBALTableManager\Query\Pagination;
use DBALTableManager\Query\Sorting;
use DBALTableManager\QueryBuilder\QueryBuilderPreparer;
use DBALTableManager\TableRowCaster\TableRowCaster;
use Doctrine\DBAL\Query\QueryBuilder;

/**
 * Class TemporalTableManager
 *
 * @package DBALTableManager
 */
class TemporalTableManager implements DataManipulationInterface
{
    private const STATIC_TABLE_ALIAS = 'static';
    private const VERSION_TABLE_ALIAS = 'version';
    private const AS_OF_TIME_PARAM = 'as_of_time';

    /**
     * @var BaseConnectionInterface
     */
    private $connection;
    /**
     * @var SingleTableManager
     */
    private $staticManager;
    /**
     * @var SingleTableManager
     */
    private $versionManager;
    /**
     * @var QueryBuilderPreparer
     */
    private $queryBuilderPreparer;
    /**
     * @var TableRowCaster
     */
    private $tableRowCaster;
    /**
     * @var EntityInterface
     */
    private $staticEntity;
    /**
     * @var TemporalVersionEntityInterface
     */
    private $versionEntity;

    /**
     * TemporalTableManager constructor.
     *
     * @param BaseConnectionInterface $connection
     * @param SingleTableManager $staticManager
     * @param SingleTableManager $versionManager
     * @param QueryBuilderPreparer $queryBuilderPreparer
     * @param TableRowCaster $tableRowCaster
     * @param EntityInterface $staticEntity
     * @param TemporalVersionEntityInterface $versionEntity
     */
    public function __construct(
        BaseConnectionInterface $connection,
        SingleTableManager $staticManager,
        SingleTableManager $versionManager,
        QueryBuilderPreparer $queryBuilderPreparer,
        TableRowCaster $tableRowCaster,
        EntityInterface $staticEntity,
        TemporalVersionEntityInterface $versionEntity
    ) {
        $this->connection = $connection;
        $this->staticManager = $staticManager;
        $this->versionManager = $versionManager;
        $this->queryBuilderPreparer = $queryBuilderPreparer;
        $this->tableRowCaster = $tableRowCaster;
        $this->staticEntity = $staticEntity;
        $this->versionEntity = $versionEntity;
    }

    /**
     * @param Filter|null $filter
     * @param string|null $asOfTime
     *
     * @return int
     */
    public function getCount(?Filter $filter = null, ?string $asOfTime = null): int
    {
        $query = $this->makeQuery($asOfTime);
        $query->select('count(*) as count');

        $this->queryBuilderPreparer->applyFilters($query, $filter);

        $result = $query->execute()->fetch();
        if ($result === null || $result === false) {
            throw QueryExecutionException::withAggregatedResultOfZeroRows();
        }

        return $result['count'];
    }

    /**
     * @param Filter|null $filter
     * @param Pagination|null $pagination
     * @param Sorting|null $sorting
     * @param string|null $asOfTime
     *
     * @return array
     */
    public function findAll(
        ?Filter $filter = null,
        ?Pagination $pagination = null,
        ?Sorting $sorting = null,
        ?string $asOfTime = null
    ): array
    {
        $query = $this->makeQuery($asOfTime);
        $this->applySelectAllColumns($query);

        $this->queryBuilderPreparer->applyFilters($query, $filter);
        $this->queryBuilderPreparer->applyOrderBy($query, $sorting);

        if ($pagination !== null) {
            if ($pagination->getOffset() !== null) {
                $query->setFirstResult($pagination->getOffset());
            }
            if ($pagination->getLimit() !== null) {
                $query->setMaxResults($pagination->getLimit());
            }
        }

        $list = $query->execute()->fetchAll();

        $result = [];

        foreach ($list as $row) {
            $result[] = $this->tableRowCaster->prepareRow($row);
        }

        return $result;
    }

    /**
     * @param mixed $pk
     * @param bool $withDeleted
     * @param string|null $asOfTime
     *
     * @return array
     */
    public function findOneByPk($pk, bool $withDeleted = false, ?string $asOfTime = null): ?array
    {
        $query = $this->makeQuery($asOfTime);
        $this->applySelectAllColumns($query);

        $this->queryBuilderPreparer->applyPkFilterToQuery($query, $pk, $withDeleted);

        $result = $query->execute()->fetch();
        if ($result === null || $result === false) {
            return null;
        }

        return $this->tableRowCaster->prepareRow($result);
    }

    /**
     * @param Filter $filter
     * @param Sorting|null $sorting
     * @param string|null $asOfTime
     *
     * @return array
     */
    public function findOneByFilter(Filter $filter, ?Sorting $sorting = null, ?string $asOfTime = null): ?array
    {
        $query = $this->makeQuery($asOfTime);
        $this->applySelectAllColumns($query);

        $this->queryBuilderPreparer->applyFilters($query, $filter);
        $this->queryBuilderPreparer->applyOrderBy($query, $sorting);

        $query->setMaxResults(1);

        $result = $query->execute()->fetch();
        if ($result === null || $result === false) {
            return null;
        }

        return $this->tableRowCaster->prepareRow($result);
    }

    /**
     * @param string|null $asOfTime
     *
     * @return QueryBuilder
     */
    private function makeQuery(?string $asOfTime = null): QueryBuilder
    {
        $query = $this->connection->createQueryBuilder();
        $query->from($this->staticEntity->getTableName(), self::STATIC_TABLE_ALIAS);
        $query->join(
            self::STATIC_TABLE_ALIAS,
            $this->versionEntity->getTableName(),
            self::VERSION_TABLE_ALIAS,
            $this->makeJoinCondition()
        );

        $query->setParameter(':' . self::AS_OF_TIME_PARAM, $asOfTime ?? date('Y-m-d H:i:s'));

        return $query;
    }

    /**
     * @param QueryBuilder $query
     */
    private function applySelectAllColumns(QueryBuilder $query): void
    {
        $select = [];

        $select[] = self::STATIC_TABLE_ALIAS . '.*';

        $versionColumnList = array_keys($this->versionEntity->getFieldMap());
        $versionColumnList = array_diff($versionColumnList, $this->versionEntity->getPrimaryKey());
        foreach ($versionColumnList as $column) {
            $select[] = self::VERSION_TABLE_ALIAS . '.' . $column;
        }

        $query->addSelect($select);
    }

    /**
     * @return string
     */
    private function makeJoinCondition(): string
    {
        $joinConditionList = [];

        $versionEntity = $this->versionEntity;

        foreach ($versionEntity->getForeignKeyMap() as $versionField => $staticField) {
            $joinConditionList[] = self::STATIC_TABLE_ALIAS . '.' . $staticField
                . " = "
                . self::VERSION_TABLE_ALIAS . '.' . $versionField;
        }

        $effectiveWhereConditionList = [];
        foreach ($versionEntity->getForeignKeyMap() as $versionField => $staticField) {
            $effectiveWhereConditionList[] = self::STATIC_TABLE_ALIAS . ".{$staticField} = v1.{$versionField}";
        }
        $effectiveWhereConditionList[] = "v1.{$versionEntity->getEffectiveSinceField()} <= :" . self::AS_OF_TIME_PARAM;

        $joinConditionList[] = self::VERSION_TABLE_ALIAS . ".{$versionEntity->getEffectiveSinceField()} = (
            SELECT MAX(v1.{$versionEntity->getEffectiveSinceField()})
            FROM {$versionEntity->getTableName()} v1
            WHERE " . implode(" AND ", $effectiveWhereConditionList) . "
        )";

        $createdWhereConditionList = [];
        foreach ($versionEntity->getForeignKeyMap() as $versionField => $staticField) {
            $createdWhereConditionList[] = "v2.{$versionField} = " . self::VERSION_TABLE_ALIAS . ".{$versionField}";
        }
        $createdWhereConditionList[] = "v2.{$versionEntity->getEffectiveSinceField()} = " . self::VERSION_TABLE_ALIAS . ".{$versionEntity->getEffectiveSinceField()}";
        $createdWhereConditionList[] = "v2.{$versionEntity->getCreatedAtField()} <= :" . self::AS_OF_TIME_PARAM;

        $joinConditionList[] = self::VERSION_TABLE_ALIAS . ".{$versionEntity->getCreatedAtField()} = (
            SELECT MAX(v2.{$versionEntity->getCreatedAtField()})
            FROM {$versionEntity->getTableName()} v2
            WHERE " . implode(" AND ", $createdWhereConditionList) . "
        )";

        return implode(" AND ", $joinConditionList);
    }

    /**
     * @param $data
     *
     * @return string
     */
    public function insert(array $data): string
    {
        $staticData = [];
        $versionData = [];

        $staticEntity = $this->staticEntity;
        $versionEntity = $this->versionEntity;

        foreach ($data as $column => $value) {
            if (array_key_exists($column, $staticEntity->getFieldMap())) {
                $staticData[$column] = $value;
            } else if (array_key_exists($column, $versionEntity->getFieldMap())) {
                $versionData[$column] = $value;
            } else {
                throw InvalidRequestException::withUnknownColumnList([$column]);
            }
        }

        $id = $this->staticManager->insert($staticData);

        foreach ($versionEntity->getForeignKeyMap() as $versionField => $staticField) {
            $versionData[$versionField] = $id;
        }
        $versionData[$versionEntity->getCreatedAtField()] = date('Y-m-d H:i:s');
        if (!array_key_exists($versionEntity->getEffectiveSinceField(), $versionData)) {
            $versionData[$versionEntity->getEffectiveSinceField()] = date('Y-m-d');
        }

        $this->versionManager->insert($versionData);

        return $id;
    }

    /**
     * @param $data
     *
     * @return int
     */
    public function batchInsert(array $data): int
    {
        $result = 0;

        foreach ($data as $row) {
            $this->insert($row);
            $result++;
        }

        return $result;
    }

    /**
     * @param Filter $filter
     * @param array $data
     *
     * @return int
     */
    public function updateByFilter(Filter $filter, array $data): int
    {
        $result = 0;

        $list = $this->staticManager->findAll($filter);
        foreach ($list as $row) {
            $staticPk = [];
            foreach ($this->staticEntity->getPrimaryKey() as $staticPkField) {
                if (false === isset($row[$staticPkField])) {
                    throw QueryExecutionException::withRequiredDataMissing($staticPkField);
                }

                $staticPk[$staticPkField] = $row[$staticPkField];
            }
            $result += $this->updateByPk($staticPk, $data);
        }

        return $result;
    }

    /**
     * @param $pk
     * @param array $data
     *
     * @return int
     */
    public function updateByPk($pk, array $data): int
    {
        $staticData = [];
        $versionData = [];

        $staticEntity = $this->staticEntity;
        $versionEntity = $this->versionEntity;

        foreach ($data as $column => $value) {
            if (array_key_exists($column, $staticEntity->getFieldMap())) {
                $staticData[$column] = $value;
            } else if (array_key_exists($column, $versionEntity->getFieldMap())) {
                $versionData[$column] = $value;
            } else {
                throw InvalidRequestException::withUnknownColumnList([$column]);
            }
        }

        $existingRow = $this->staticManager->findOneByPk($pk);
        if ($existingRow === null) {
            return 0;
        }

        $result = $this->staticManager->updateByPk($pk, $staticData);

        if (is_array($pk)) {
            foreach ($versionEntity->getForeignKeyMap() as $versionField => $staticField) {
                if (isset($pk[$staticField])) {
                    $versionData[$versionField] = $pk[$staticField];
                }
            }
        } else {
            foreach ($versionEntity->getForeignKeyMap() as $versionField => $staticField) {
                $versionData[$versionField] = $pk;
            }
        }
        $versionData[$versionEntity->getCreatedAtField()] = date('Y-m-d H:i:s');
        if (!array_key_exists($versionEntity->getEffectiveSinceField(), $versionData)) {
            $versionData[$versionEntity->getEffectiveSinceField()] = date('Y-m-d');
        }

        $this->versionManager->insert($versionData);
        if ($result === 0) {
            $result++;
        }

        return $result;
    }

    /**
     * @param array $data
     * @param Filter[] $filterList
     *
     * @return int
     */
    public function batchUpdate(array $data, array $filterList): int
    {
        if (count($data) !== count($filterList)) {
            throw InvalidRequestException::withDataAndFilterCountNotEqual();
        }

        $result = 0;

        foreach ($data as $i => $row) {
            $result += $this->updateByFilter($filterList[$i], $row);
        }

        return $result;
    }

    /**
     * @param Filter $filter
     *
     * @return int
     */
    public function deleteByFilter(Filter $filter): int
    {
        $result = 0;

        $list = $this->staticManager->findAll($filter);
        foreach ($list as $row) {
            $staticPk = [];
            foreach ($this->staticEntity->getPrimaryKey() as $staticPkField) {
                if (!isset($row[$staticPkField])) {
                    throw QueryExecutionException::withRequiredDataMissing($staticPkField);
                }

                $staticPk[$staticPkField] = $row[$staticPkField];
            }
            $result += $this->deleteByPk($staticPk);
        }

        return $result;
    }

    /**
     * @param $pk
     *
     * @return int
     */
    public function deleteByPk($pk): int
    {
        $existingRow = $this->staticManager->findOneByPk($pk);
        if ($existingRow === null) {
            return 0;
        }

        $versionEntity = $this->versionEntity;

        $filter = new Filter();
        if (is_array($pk)) {
            foreach ($versionEntity->getForeignKeyMap() as $versionField => $staticField) {
                if (!isset($pk[$staticField])) {
                    throw InvalidRequestException::withNoPrimaryKeyValue($staticField);
                }
                $filter->equals($versionField, $pk[$staticField]);
            }
        } else {
            foreach ($versionEntity->getForeignKeyMap() as $versionField => $staticField) {
                $filter->equals($versionField, $pk);
            }
        }
        $this->versionManager->deleteByFilter($filter);

        return $this->staticManager->deleteByPk($pk);
    }

    /**
     * @return int
     */
    public function deleteAll(): int
    {
        $this->versionManager->deleteAll();

        return $this->staticManager->deleteAll();
    }

    /**
     * @param Filter $filter
     *
     * @return int
     */
    public function softDeleteByFilter(Filter $filter): int
    {
        return $this->staticManager->softDeleteByFilter($filter);
    }

    /**
     * @param $pk
     *
     * @return int
     */
    public function softDeleteByPk($pk): int
    {
        return $this->staticManager->softDeleteByPk($pk);
    }

    /**
     * @return int
     */
    public function softDeleteAll(): int
    {
        return $this->staticManager->softDeleteAll();
    }

    public function truncate(): void
    {
        $this->versionManager->truncate();
        $this->staticManager->truncate();
    }
}
