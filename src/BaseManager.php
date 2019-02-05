<?php

namespace DBALTableManager;

use DBALTableManager\Condition\ColumnableCondition;
use DBALTableManager\Condition\DeletedRowCondition;
use DBALTableManager\Condition\NullableValueCondition;
use DBALTableManager\Condition\RawSqlCondition;
use DBALTableManager\Condition\ValueArrayCondition;
use DBALTableManager\Condition\ValueComparisonCondition;
use DBALTableManager\Condition\ValueLikeCondition;
use DBALTableManager\Entity\EntityInterface;
use DBALTableManager\Exception\QueryExecutionException;
use DBALTableManager\Exception\EntityDefinitionException;
use DBALTableManager\Exception\InvalidRequestException;
use DBALTableManager\Query\BulkInsertQuery;
use DBALTableManager\Util\StringUtils;
use DBALTableManager\Util\TypeConverter;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\QueryBuilder;

/**
 * Class BaseManager
 *
 * @package DBALTableManager
 */
abstract class BaseManager
{
    /**
     * @var BaseConnectionInterface
     */
    protected $connection;
    /**
     * @var TypeConverter
     */
    protected $typeConverter;
    /**
     * @var StringUtils
     */
    private $stringUtils;

    /**
     * BaseManager constructor.
     *
     * @param BaseConnectionInterface $connection
     * @param TypeConverter $typeConverter
     * @param StringUtils $stringUtils
     */
    public function __construct(
        BaseConnectionInterface $connection,
        TypeConverter $typeConverter,
        StringUtils $stringUtils
    )
    {
        $this->connection = $connection;
        $this->typeConverter = $typeConverter;
        $this->stringUtils = $stringUtils;
    }

    /**
     * @param Filter $filter
     * @param Pagination $pagination
     * @param Sorting $sorting
     *
     * @return array
     */
    public function findAll(Filter $filter, Pagination $pagination, Sorting $sorting): array
    {
        $query = $this->connection->createQueryBuilder();
        $query->select('*');
        $query->from($this->getEntity()->getTableName());

        $this->applyFilters($query, $filter);
        $this->applyOrderBy($query, $sorting);

        if ($pagination->getOffset() !== null) {
            $query->setFirstResult($pagination->getOffset());
        }
        if ($pagination->getLimit() !== null) {
            $query->setMaxResults($pagination->getLimit());
        }

        $list = $query->execute()->fetchAll();

        $result = [];

        foreach ($list as $row) {
            $result[] = $this->prepareRow($row);
        }

        return $result;
    }

    /**
     * @param array $row
     *
     * @return array
     */
    protected function prepareRow(array $row): array
    {
        return $this->typeConverter->convert($row, $this->getEntity()->getFieldMap());
    }

    /**
     * @param QueryBuilder $query
     * @param Filter $filter
     */
    protected function applyFilters(QueryBuilder $query, Filter $filter): void
    {
        $columnList = [];
        foreach ($filter->getConditionList() as $condition) {
            if ($condition instanceof ColumnableCondition) {
                $columnList[] = $condition->getColumn();
            }
        }
        $this->checkColumnList($columnList);

        $hasDeletedAtFilter = false;

        foreach ($filter->getConditionList() as $condition) {
            if ($condition instanceof ValueComparisonCondition) {
                $query->andWhere(
                    $condition->getColumn()
                    . ' '
                    . $condition->getOperator()
                    . ' '
                    . $query->createNamedParameter($condition->getValue())
                );
            }

            else if ($condition instanceof NullableValueCondition) {
                if ($condition->isNull()) {
                    $query->andWhere($condition->getColumn() . ' IS NULL');
                } else {
                    $query->andWhere($condition->getColumn() . ' IS NOT NULL');
                }
            }

            else if ($condition instanceof ValueArrayCondition) {
                if ($condition->getValues() !== []) {
                    $columnType = $this->getEntity()->getFieldMap()[$condition->getColumn()] ?? null;
                    if (in_array($columnType, ['int', 'integer'])) {
                        $type = Connection::PARAM_INT_ARRAY;
                    } else {
                        $type = Connection::PARAM_STR_ARRAY;
                    }

                    $param = $query->createNamedParameter($condition->getValues(), $type);
                    if ($condition->isIncluded()) {
                        $query->andWhere($condition->getColumn() . ' IN (' . $param . ')');
                    } else {
                        $query->andWhere($condition->getColumn() . ' NOT IN (' . $param . ')');
                    }
                }
            }

            else if ($condition instanceof ValueLikeCondition) {
                $value = ($condition->isStrictFromBeginning() ? '' : '%')
                    . $this->stringUtils->prepareSqlLikeOperator($condition->getValue())
                    . ($condition->isStrictToEnd() ? '' : '%');
                $query->andWhere($condition->getColumn() . ' LIKE ' . $query->createNamedParameter($value));
            }

            else if ($condition instanceof RawSqlCondition) {
                $query->andWhere($condition->getExpression());
            }

            else if ($condition instanceof DeletedRowCondition) {
                $this->checkSoftDeletableEntity();

                $showNotDeleted = $condition->isShowNotDeleted();
                $showDeleted = $condition->isShowDeleted();
                if ($showNotDeleted && $showDeleted) {
                    // show all
                } else if ($showNotDeleted) {
                    $query->andWhere($this->getEntity()->getDeletedAtField() . ' IS NOT NULL');
                } else if ($showDeleted) {
                    $query->andWhere($this->getEntity()->getDeletedAtField() . ' IS NULL');
                }

                $hasDeletedAtFilter = true;
            }
        }

        if (!$hasDeletedAtFilter && $this->getEntity()->isSoftDeletable()) {
            $query->andWhere($this->getEntity()->getDeletedAtField() . ' IS NULL');
        }
    }

    /**
     * @param string[] $columnList
     */
    protected function checkColumnList(array $columnList): void
    {
        $unknownColumns = array_diff($columnList, array_keys($this->getEntity()->getFieldMap()));
        if ($unknownColumns !== []) {
            throw InvalidRequestException::withUnknownColumnList($unknownColumns);
        }
    }

    /**
     * @param QueryBuilder $query
     * @param Sorting $sorting
     */
    protected function applyOrderBy(QueryBuilder $query, Sorting $sorting): void
    {
        $columnList = [];
        foreach ($sorting->getSortList() as $sort) {
            $columnList[] = $sort->getColumn();
        }
        $this->checkColumnList($columnList);

        foreach ($sorting->getSortList() as $sort) {
            $query->addOrderBy($sort->getColumn(), $sort->getOrder());
        }
    }

    /**
     * @param Filter $filter
     * @param Sorting $sorting
     *
     * @return array
     */
    public function findOne(Filter $filter, Sorting $sorting): ?array
    {
        $query = $this->connection->createQueryBuilder();
        $query->select('*');
        $query->from($this->getEntity()->getTableName());

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
     * @param $pk
     *
     * @return array
     */
    public function findByPk($pk): ?array
    {
        $query = $this->connection->createQueryBuilder();
        $query->select('*');
        $query->from($this->getEntity()->getTableName());

        $this->applyPkFilterToQuery($query, $pk);

        $result = $query->execute()->fetch();
        if ($result === null || $result === false) {
            return null;
        }

        return $this->prepareRow($result);
    }

    /**
     * @param QueryBuilder $query
     * @param $pk
     */
    protected function applyPkFilterToQuery(QueryBuilder $query, $pk): void
    {
        if ($this->getEntity()->getPrimaryKey() === []) {
            throw EntityDefinitionException::withNoPrimaryKeyDefined();
        }

        if (!is_array($pk)) {
            $firstPkColumn = $this->getEntity()->getPrimaryKey()[0];
            $query->andWhere($firstPkColumn . ' = ' . $query->createNamedParameter($pk));
        } else {
            $this->checkColumnList(array_keys($pk));

            foreach ($this->getEntity()->getPrimaryKey() as $pkColumn) {
                if (!isset($pk[$pkColumn])) {
                    throw InvalidRequestException::withNoPrimaryKeyValue($pkColumn);
                }
                $query->andWhere($pkColumn . ' = ' . $query->createNamedParameter($pk[$pkColumn]));
            }
        }
    }

    /**
     * @param $data
     *
     * @return string
     */
    public function insert(array $data): string
    {
        $query = $this->connection->createQueryBuilder();
        $query->insert($this->getEntity()->getTableName());

        if ($this->getEntity()->isTimestampable()) {
            $this->checkTimestampableEntity();

            $currentTime = date('Y-m-d H:i:s');
            $createdAtField = $this->getEntity()->getCreatedAtField();
            $updatedAtField = $this->getEntity()->getUpdatedAtField();
            $data[$createdAtField] = $currentTime;
            $data[$updatedAtField] = $currentTime;
        }

        $this->checkColumnList(array_keys($data));

        $values = [];
        foreach ($data as $key => $value) {
            $values[$key] = $query->createNamedParameter($value);
        }
        $query->values($values);

        $query->execute();

        return $this->connection->lastInsertId();
    }

    /**
     * @param $data
     *
     * @return int
     */
    public function batchInsert(array $data): int
    {
        if ($this->getEntity()->isTimestampable()) {
            $this->checkTimestampableEntity();

            $currentTime = date('Y-m-d H:i:s');
            $createdAtField = $this->getEntity()->getCreatedAtField();
            $updatedAtField = $this->getEntity()->getUpdatedAtField();
            foreach ($data as &$row) {
                $row[$createdAtField] = $currentTime;
                $row[$updatedAtField] = $currentTime;

                $this->checkColumnList(array_keys($row));
            }
            unset($row);
        }

        $columns = [];
        if (isset($data[0])) {
            $columns = array_keys($data[0]);
        }

        $q = new BulkInsertQuery($this->connection, $this->getEntity()->getTableName(), $columns);
        foreach ($data as $row) {
            $q->addValues($row);
        }

        return $q->execute();
    }

    /**
     * @param array $data
     * @param Filter $filter
     *
     * @return int
     */
    public function update(array $data, Filter $filter): int
    {
        $query = $this->connection->createQueryBuilder();
        $query->update($this->getEntity()->getTableName());

        $this->setValuesForUpdateQuery($query, $data);
        $this->applyFilters($query, $filter);

        return $query->execute();
    }

    /**
     * @param $pk
     * @param array $data
     *
     * @return int
     */
    public function updateByPk($pk, array $data): int
    {
        $query = $this->connection->createQueryBuilder();
        $query->update($this->getEntity()->getTableName());

        $this->setValuesForUpdateQuery($query, $data);
        $this->applyPkFilterToQuery($query, $pk);

        return $query->execute();
    }

    /**
     * @param QueryBuilder $query
     * @param array $data
     */
    protected function setValuesForUpdateQuery(QueryBuilder $query, array $data): void
    {
        if ($this->getEntity()->isTimestampable()) {
            $this->checkTimestampableEntity();

            $currentTime = date('Y-m-d H:i:s');
            $createdAtField = $this->getEntity()->getCreatedAtField();
            $updatedAtField = $this->getEntity()->getUpdatedAtField();
            $data[$createdAtField] = $currentTime;
            $data[$updatedAtField] = $currentTime;
        }

        $this->checkColumnList(array_keys($data));

        $values = [];
        foreach ($data as $key => $value) {
            $query->set($key, $query->createNamedParameter($value));
        }
        $query->values($values);
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

        $count = 0;

        foreach ($data as $i => $row) {
            $count += $this->update($row, $filterList[$i]);
        }

        return $count;
    }

    /**
     * @param $filter
     *
     * @return \Doctrine\DBAL\Driver\Statement|int
     */
    public function delete($filter)
    {
        $query = $this->connection->createQueryBuilder();
        $query->delete($this->getEntity()->getTableName());

        $this->applyFilters($query, $filter);

        return $query->execute();
    }

    /**
     * @param $pk
     *
     * @return int
     */
    public function deleteByPk($pk): int
    {
        $query = $this->connection->createQueryBuilder();
        $query->delete($this->getEntity()->getTableName());

        $this->applyPkFilterToQuery($query, $pk);

        return $query->execute();
    }

    /**
     * @return int
     */
    public function deleteAll(): int
    {
        $query = $this->connection->createQueryBuilder();
        $query->delete($this->getEntity()->getTableName());

        return $query->execute();
    }

    /**
     * @param Filter $filter
     *
     * @return int
     */
    public function softDelete(Filter $filter): int
    {
        if ($this->getEntity()->isSoftDeletable() === false) {
            throw EntityDefinitionException::withNotSoftDeletable();
        }

        $query = $this->connection->createQueryBuilder();
        $query->update($this->getEntity()->getTableName());

        $this->applyFilters($query, $filter);

        $this->setSoftDeletedValues($query);

        return $query->execute();
    }

    /**
     * @param $pk
     *
     * @return int
     */
    public function softDeleteByPk($pk): int
    {
        if ($this->getEntity()->isSoftDeletable() === false) {
            throw EntityDefinitionException::withNotSoftDeletable();
        }

        $query = $this->connection->createQueryBuilder();
        $query->update($this->getEntity()->getTableName());

        $this->applyPkFilterToQuery($query, $pk);

        $this->setSoftDeletedValues($query);

        return $query->execute();
    }

    /**
     * @return int
     */
    public function softDeleteAll(): int
    {
        if ($this->getEntity()->isSoftDeletable() === false) {
            throw EntityDefinitionException::withNotSoftDeletable();
        }

        $query = $this->connection->createQueryBuilder();
        $query->update($this->getEntity()->getTableName());

        $this->setSoftDeletedValues($query);

        return $query->execute();
    }

    /**
     * @param QueryBuilder $query
     */
    protected function setSoftDeletedValues(QueryBuilder $query): void
    {
        $currentTime = date('Y-m-d H:i:s');

        $this->checkSoftDeletableEntity();
        $query->set($this->getEntity()->getDeletedAtField(), $query->createNamedParameter($currentTime));

        if ($this->getEntity()->isTimestampable()) {
            $this->checkTimestampableEntity();
            $query->set($this->getEntity()->getUpdatedAtField(), $query->createNamedParameter($currentTime));
        }
    }

    /**
     * @param Filter $filter
     *
     * @return int
     */
    public function getCount(Filter $filter): int
    {
        $query = $this->connection->createQueryBuilder();
        $query->select('count(*) as count');
        $query->from($this->getEntity()->getTableName());

        $this->applyFilters($query, $filter);

        $result = $query->execute()->fetch();
        if ($result === null || $result === false) {
            throw QueryExecutionException::withAggregatedResultOfZeroRows();
        }

        return $result['count'];
    }

    public function truncate(): void
    {
        $dbPlatform = $this->connection->getDatabasePlatform();
        $q = $dbPlatform->getTruncateTableSQL($this->getEntity()->getTableName());
        $this->connection->exec($q);
    }

    /**
     * @return EntityInterface
     */
    abstract public function getEntity(): EntityInterface;

    private function checkTimestampableEntity(): void
    {
        if ($this->getEntity()->isTimestampable()) {
            return;
        }

        $createdAtField = $this->getEntity()->getCreatedAtField();
        if ($createdAtField === null || $createdAtField === '') {
            throw EntityDefinitionException::withNoCreatedAtColumnDefined();
        }

        $updatedAtField = $this->getEntity()->getUpdatedAtField();
        if ($updatedAtField === null || $updatedAtField === '') {
            throw EntityDefinitionException::withNoUpdatedAtColumnDefined();
        }
    }

    private function checkSoftDeletableEntity(): void
    {
        if ($this->getEntity()->isSoftDeletable()) {
            return;
        }

        $deletedAtField = $this->getEntity()->getDeletedAtField();
        if ($deletedAtField === null || $deletedAtField === '') {
            throw EntityDefinitionException::withNoDeletedAtColumnDefined();
        }
    }
}
