<?php

namespace DBALTableManager;

use DBALTableManager\Entity\EntityInterface;
use DBALTableManager\Entity\EntityVersionInterface;
use DBALTableManager\Exception\InvalidRequestException;
use DBALTableManager\Exception\QueryExecutionException;
use DBALTableManager\Util\StringUtils;
use DBALTableManager\Util\TypeConverter;
use Doctrine\DBAL\Query\QueryBuilder;

/**
 * Class BaseTemporalManager
 *
 * @package DBALTableManager
 */
abstract class BaseTemporalManager extends ManagerFoundation
{
    private const STATIC_TABLE_ALIAS = 'static';
    private const VERSION_TABLE_ALIAS = 'version';
    private const AS_OF_TIME_PARAM = 'as_of_time';

    /**
     * @var BaseManager
     */
    private $staticManager;
    /**
     * @var BaseManager
     */
    private $versionManager;

    /**
     * BaseTemporalManager constructor.
     *
     * @param BaseConnectionInterface $connection
     * @param TypeConverter $typeConverter
     * @param StringUtils $stringUtils
     * @param BaseManager $staticManager
     * @param BaseManager $versionManager
     */
    public function __construct(
        BaseConnectionInterface $connection,
        TypeConverter $typeConverter,
        StringUtils $stringUtils,
        BaseManager $staticManager,
        BaseManager $versionManager
    ) {
        parent::__construct($connection, $typeConverter, $stringUtils);

        $this->staticManager = $staticManager;
        $this->versionManager = $versionManager;
    }

    /**
     * @return EntityVersionInterface
     */
    abstract public function getVersionEntity(): EntityVersionInterface;

    /**
     * @return EntityInterface
     */
    private function getStaticEntity(): EntityInterface
    {
        return $this->getEntity();
    }

    /**
     * @return array
     */
    protected function getFieldMap(): array
    {
        return array_merge(
            $this->getStaticEntity()->getFieldMap(),
            $this->getVersionEntity()->getFieldMap()
        );
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

        $this->applyFilters($query, $filter);

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

        $this->applyFilters($query, $filter);
        $this->applyOrderBy($query, $sorting);

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
            $result[] = $this->prepareRow($row);
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

        $this->applyPkFilterToQuery($query, $pk, $withDeleted);

        $result = $query->execute()->fetch();
        if ($result === null || $result === false) {
            return null;
        }

        return $this->prepareRow($result);
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

        $this->applyFilters($query, $filter);
        $this->applyOrderBy($query, $sorting);

        $query->setMaxResults(1);

        $result = $query->execute()->fetch();
        if ($result === null || $result === false) {
            return null;
        }

        return $this->prepareRow($result);
    }

    /**
     * @param string|null $asOfTime
     *
     * @return QueryBuilder
     */
    private function makeQuery(?string $asOfTime = null): QueryBuilder
    {
        $staticEntity = $this->getStaticEntity();
        $versionEntity = $this->getVersionEntity();

        $query = $this->connection->createQueryBuilder();
        $query->from($staticEntity->getTableName(), self::STATIC_TABLE_ALIAS);
        $query->join(
            self::STATIC_TABLE_ALIAS,
            $versionEntity->getTableName(),
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

        $versionEntity = $this->getVersionEntity();
        $versionColumnList = array_keys($versionEntity->getFieldMap());
        $versionColumnList = array_diff($versionColumnList, $versionEntity->getPrimaryKey());
        foreach ($versionColumnList as $column) {
            $select[] = self::VERSION_TABLE_ALIAS . '.' . $column;
        }

        $query->addSelect($select);
    }

    /**
     * @param string $columnName
     *
     * @return string
     */
    protected function prepareColumnName(string $columnName): string
    {
        $staticEntity = $this->getStaticEntity();
        if (array_key_exists($columnName, $staticEntity->getFieldMap())) {
            return self::STATIC_TABLE_ALIAS . '.' . $columnName;
        }

        $versionEntity = $this->getVersionEntity();
        if (array_key_exists($columnName, $versionEntity->getFieldMap())) {
            return self::VERSION_TABLE_ALIAS . '.' . $columnName;
        }

        throw InvalidRequestException::withUnknownColumnList([$columnName]);
    }

    /**
     * @return string
     */
    private function makeJoinCondition(): string
    {
        $joinConditionList = [];

        $staticEntity = $this->getStaticEntity();
        $versionEntity = $this->getVersionEntity();

        $staticToVersionPkMap = [];
        foreach ($versionEntity->getStaticPkField() as $versionField) {
            foreach ($staticEntity->getPrimaryKey() as $staticField) {
                $staticToVersionPkMap[$staticField] = $versionField;
            }
        }

        foreach ($staticToVersionPkMap as $staticField => $versionField) {
            $joinConditionList[] = self::STATIC_TABLE_ALIAS . '.' . $staticField
                . " = "
                . self::VERSION_TABLE_ALIAS . '.' . $versionField;
        }

        $effectiveWhereConditionList = [];
        foreach ($staticToVersionPkMap as $staticField => $versionField) {
            $effectiveWhereConditionList[] = self::STATIC_TABLE_ALIAS . ".{$staticField} = v1.{$versionField}";
        }
        $effectiveWhereConditionList[] = "v1.{$versionEntity->getEffectiveSinceField()} <= :" . self::AS_OF_TIME_PARAM;

        $joinConditionList[] = self::VERSION_TABLE_ALIAS . ".{$versionEntity->getEffectiveSinceField()} = (
            SELECT MAX(v1.{$versionEntity->getEffectiveSinceField()})
            FROM {$versionEntity->getTableName()} v1
            WHERE " . implode(" AND ", $effectiveWhereConditionList) . "
        )";

        $createdWhereConditionList = [];
        foreach ($versionEntity->getStaticPkField() as $versionField) {
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

        $staticEntity = $this->getStaticEntity();
        $versionEntity = $this->getVersionEntity();

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

        foreach ($versionEntity->getStaticPkField() as $pk) {
            $versionData[$pk] = $id;
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

        $list = $this->findAll($filter);
        foreach ($list as $row) {
            $pk = [];
            foreach ($this->getStaticEntity()->getPrimaryKey() as $pkField) {
                // todo: exception?
                $pk[$pkField] = $row[$pkField] ?? null;
            }
            $result += $this->updateByPk($pk, $data);
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

        $staticEntity = $this->getStaticEntity();
        $versionEntity = $this->getVersionEntity();

        foreach ($data as $column => $value) {
            if (array_key_exists($column, $staticEntity->getFieldMap())) {
                $staticData[$column] = $value;
            } else if (array_key_exists($column, $versionEntity->getFieldMap())) {
                $versionData[$column] = $value;
            } else {
                throw InvalidRequestException::withUnknownColumnList([$column]);
            }
        }

        // todo: do not update if now changes
        $result = $this->staticManager->updateByPk($pk, $staticData);

        if (is_array($pk)) {
            foreach ($versionEntity->getStaticPkField() as $staticPkField) {
                foreach ($pk as $column => $value) {
                    if ($staticPkField === $column) {
                        $versionData[$staticPkField] = $value;
                    }
                }
            }
        } else {
            foreach ($versionEntity->getStaticPkField() as $staticPkField) {
                $versionData[$staticPkField] = $pk;
            }
        }
        $versionData[$versionEntity->getCreatedAtField()] = date('Y-m-d H:i:s');
        if (!array_key_exists($versionEntity->getEffectiveSinceField(), $versionData)) {
            $versionData[$versionEntity->getEffectiveSinceField()] = date('Y-m-d');
        }

        // todo: do not insert if now changes
        $this->versionManager->insert($versionData);
        $result++;

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
        return $this->staticManager->deleteByFilter($filter);
    }

    /**
     * @param $pk
     *
     * @return int
     */
    public function deleteByPk($pk): int
    {
        return $this->staticManager->deleteByPk($pk);
    }

    /**
     * @return int
     */
    public function deleteAll(): int
    {
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
        $this->staticManager->truncate();
        $this->versionManager->truncate();
    }
}
