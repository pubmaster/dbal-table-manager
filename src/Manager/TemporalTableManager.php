<?php

namespace DBALTableManager\Manager;

use DBALTableManager\BaseConnectionInterface;
use DBALTableManager\Entity\EntityInterface;
use DBALTableManager\Entity\TemporalVersionEntityInterface;
use DBALTableManager\Exception\EntityDefinitionException;
use DBALTableManager\Exception\InvalidRequestException;
use DBALTableManager\Exception\QueryExecutionException;
use DBALTableManager\Query\Filter;
use DBALTableManager\Query\FilterInterface;
use DBALTableManager\Query\PaginationInterface;
use DBALTableManager\Query\SortingInterface;
use DBALTableManager\QueryBuilder\QueryBuilderPreparer;
use DBALTableManager\TableRowCaster\TableRowCaster;
use DBALTableManager\Util\CurrentTimeInterface;
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
    private const TRANSACTION_TIME_PARAM = 'transaction_time';

    /**
     * @var BaseConnectionInterface
     */
    protected $connection;
    /**
     * @var SingleTableManager
     */
    protected $staticManager;
    /**
     * @var SingleTableManager
     */
    protected $versionManager;
    /**
     * @var QueryBuilderPreparer
     */
    protected $queryBuilderPreparer;
    /**
     * @var TableRowCaster
     */
    protected $tableRowCaster;
    /**
     * @var CurrentTimeInterface
     */
    protected $currentTime;
    /**
     * @var EntityInterface
     */
    protected $staticEntity;
    /**
     * @var TemporalVersionEntityInterface
     */
    protected $versionEntity;

    /**
     * TemporalTableManager constructor.
     *
     * @param BaseConnectionInterface $connection
     * @param SingleTableManager $staticManager
     * @param SingleTableManager $versionManager
     * @param QueryBuilderPreparer $queryBuilderPreparer
     * @param TableRowCaster $tableRowCaster
     * @param CurrentTimeInterface $currentTime
     * @param EntityInterface $staticEntity
     * @param TemporalVersionEntityInterface $versionEntity
     */
    public function __construct(
        BaseConnectionInterface $connection,
        SingleTableManager $staticManager,
        SingleTableManager $versionManager,
        QueryBuilderPreparer $queryBuilderPreparer,
        TableRowCaster $tableRowCaster,
        CurrentTimeInterface $currentTime,
        EntityInterface $staticEntity,
        TemporalVersionEntityInterface $versionEntity
    ) {
        $this->connection = $connection;
        $this->staticManager = $staticManager;
        $this->versionManager = $versionManager;
        $this->queryBuilderPreparer = $queryBuilderPreparer;
        $this->tableRowCaster = $tableRowCaster;
        $this->currentTime = $currentTime;
        $this->staticEntity = $staticEntity;
        $this->versionEntity = $versionEntity;
    }

    /**
     * @param FilterInterface|null $filter
     * @param string|null $asOfTime
     *
     * @return int
     */
    public function getCount(?FilterInterface $filter = null, ?string $asOfTime = null): int
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
     * @param FilterInterface|null $filter
     * @param PaginationInterface|null $pagination
     * @param SortingInterface|null $sorting
     * @param string|null $asOfTime
     *
     * @return array
     */
    public function findAll(
        ?FilterInterface $filter = null,
        ?PaginationInterface $pagination = null,
        ?SortingInterface $sorting = null,
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
     * @param FilterInterface $filter
     * @param SortingInterface|null $sorting
     * @param string|null $asOfTime
     *
     * @return array
     */
    public function findOneByFilter(
        FilterInterface $filter,
        ?SortingInterface $sorting = null,
        ?string $asOfTime = null
    ): ?array
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

        $now = $this->currentTime->getCurrentTime()->format('Y-m-d H:i:s');
        $query->setParameter(':' . self::AS_OF_TIME_PARAM, $asOfTime ?? $now);
        $query->setParameter(':' . self::TRANSACTION_TIME_PARAM, $now);

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
        $createdWhereConditionList[] = "v2.{$versionEntity->getCreatedAtField()} <= :" . self::TRANSACTION_TIME_PARAM;

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
        $staticData = $this->makeStaticDataForUpsert($data);

        $id = $this->staticManager->insert($staticData);

        $versionData = $this->makeVersionDataForInsert($id, $data);

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
     * @param FilterInterface $filter
     * @param array $data
     *
     * @return int
     */
    public function updateByFilter(FilterInterface $filter, array $data): int
    {
        $result = 0;

        $list = $this->staticManager->findAll($filter);
        foreach ($list as $row) {
            $staticPk = $this->extractStaticPkFromRow($row);
            $result += $this->updateByPk($staticPk, $data);
        }

        return $result;
    }

    /**
     * @param array $row
     *
     * @return array
     */
    private function extractStaticPkFromRow(array $row): array
    {
        $staticPk = [];

        foreach ($this->staticEntity->getPrimaryKey() as $staticPkField) {
            if (false === isset($row[$staticPkField])) {
                throw QueryExecutionException::withRequiredDataMissing($staticPkField);
            }

            $staticPk[$staticPkField] = $row[$staticPkField];
        }

        return $staticPk;
    }

    /**
     * @param $pk
     * @param array $data
     *
     * @return int
     */
    public function updateByPk($pk, array $data): int
    {
        $existingRow = $this->findOneByPk($pk);
        if ($existingRow === null) {
            return 0;
        }

        $staticData = $this->makeStaticDataForUpsert($data);
        $versionData = $this->makeVersionDataForInsert($pk, $data);

        $staticHasChanges = $this->hasChanges(
            $existingRow,
            $this->tableRowCaster->prepareRow($staticData)
        );
        $versionHasChanges = $this->hasChanges(
            $existingRow,
            $this->tableRowCaster->prepareRow($versionData),
            $this->versionEntity->getPrimaryKey()
        );

        if ($staticHasChanges || $versionHasChanges) {
            $this->staticManager->updateByPk($pk, $staticData);
        }
        if ($versionHasChanges) {
            $versionData = $this->addMissingVersionDataForUpdate($versionData, $existingRow);
            $this->versionManager->insert($versionData);
        }

        return 1;
    }

    /**
     * @param array $before
     * @param array $after
     * @param array $excludedFieldList
     *
     * @return bool
     */
    private function hasChanges(array $before, array $after, array $excludedFieldList = []): bool
    {
        foreach ($after as $key => $value) {
            if (true === in_array($key, $excludedFieldList, true)) {
                continue;
            }

            if (false === array_key_exists($key, $before)) {
                continue;
            }

            if ($before[$key] !== $value) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param array $data
     *
     * @return array
     */
    private function makeStaticDataForUpsert(array $data): array
    {
        $staticData = [];

        foreach ($data as $column => $value) {
            if (array_key_exists($column, $this->staticEntity->getFieldMap())) {
                $staticData[$column] = $value;
            }
        }

        return $staticData;
    }

    /**
     * @param $staticPk
     * @param array $data
     *
     * @return array
     */
    private function makeVersionDataForInsert($staticPk, array $data): array
    {
        $versionData = $this->makeVersionPkFromStaticPk($staticPk);

        foreach ($data as $column => $value) {
            if (array_key_exists($column, $this->versionEntity->getFieldMap())) {
                $versionData[$column] = $value;
            }
        }

        $now = $this->currentTime->getCurrentTime()->format('Y-m-d H:i:s');
        $versionData[$this->versionEntity->getCreatedAtField()] = $now;
        if (!array_key_exists($this->versionEntity->getEffectiveSinceField(), $versionData)) {
            $versionData[$this->versionEntity->getEffectiveSinceField()] = $now;
        }

        return $versionData;
    }

    /**
     * @param array $dataForUpdate
     * @param array $previousData
     *
     * @return array
     */
    private function addMissingVersionDataForUpdate(array $dataForUpdate, array $previousData): array
    {
        $versionFields = array_diff(
            array_keys($this->versionEntity->getFieldMap()), $this->versionEntity->getPrimaryKey()
        );
        $missingFields = array_diff($versionFields, array_keys($dataForUpdate));
        foreach ($missingFields as $key) {
            $dataForUpdate[$key] = $previousData[$key] ?? null;
        }

        return $dataForUpdate;
    }

    /**
     * @param $staticPk
     *
     * @return array
     */
    private function makeVersionPkFromStaticPk($staticPk): array
    {
        $versionPk = [];

        if (is_array($staticPk)) {
            foreach ($this->versionEntity->getForeignKeyMap() as $versionField => $staticField) {
                if (isset($staticPk[$staticField])) {
                    $versionPk[$versionField] = $staticPk[$staticField];
                }
            }
        } else {
            foreach ($this->versionEntity->getForeignKeyMap() as $versionField => $staticField) {
                $versionPk[$versionField] = $staticPk;
            }
        }

        return $versionPk;
    }

    /**
     * @param array $data
     * @param FilterInterface[] $filterList
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
     * @param FilterInterface $filter
     *
     * @return int
     */
    public function deleteByFilter(FilterInterface $filter): int
    {
        $result = 0;

        $list = $this->staticManager->findAll($filter);
        foreach ($list as $row) {
            $staticPk = $this->extractStaticPkFromRow($row);
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

        $filter = $this->makeVersionFilterFromStaticPk($pk);
        $this->versionManager->deleteByFilter($filter);

        return $this->staticManager->deleteByPk($pk);
    }

    /**
     * @param $staticPk
     *
     * @return FilterInterface
     */
    private function makeVersionFilterFromStaticPk($staticPk): FilterInterface
    {
        $filter = new Filter();

        if (is_array($staticPk)) {
            foreach ($this->versionEntity->getForeignKeyMap() as $versionField => $staticField) {
                if (!isset($staticPk[$staticField])) {
                    throw InvalidRequestException::withNoPrimaryKeyValue($staticField);
                }
                $filter->equals($versionField, $staticPk[$staticField]);
            }
        } else {
            foreach ($this->versionEntity->getForeignKeyMap() as $versionField => $staticField) {
                $filter->equals($versionField, $staticPk);
            }
        }

        return $filter;
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
     * @param FilterInterface $filter
     *
     * @return int
     */
    public function softDeleteByFilter(FilterInterface $filter): int
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

    /**
     * @param $pk
     *
     * @return int
     */
    public function restoreByPk($pk): int
    {
        return $this->staticManager->restoreByPk($pk);
    }

    public function truncate(): void
    {
        $this->versionManager->truncate();
        $this->staticManager->truncate();
    }
}
