<?php

namespace DBALTableManager;

use DBALTableManager\Condition\DeletedRowCondition;
use DBALTableManager\Condition\NullableValueCondition;
use DBALTableManager\Condition\ValueArrayCondition;
use DBALTableManager\Condition\ValueComparisonCondition;
use DBALTableManager\Entity\EntityInterface;
use DBALTableManager\Query\BulkInsertQuery;
use DBALTableManager\Util\TypeConverter;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\QueryBuilder;

/**
 * Class BaseManager
 *
 * @package DBALTableManager
 */
//1) Все эксепшены в уникальные.
//2) Придумать что-нибудь с генерацией имен prepared_statements.
// Они плохо читаются и есть возможность накосячить с дублированием кода.
// Константы, методы генерации или доп. классы.
//3) Валидировать переданные поля фильтрации в каждом запросе на наличие их в EntityInterface.
// Так мы сможем создавать гибкие менеджеры, ответственные только за определенную часть полей у Entity.
// Например, менеджер отвечающий только за смену статусовых полей.
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
     * BaseManager constructor.
     *
     * @param BaseConnectionInterface $connection
     * @param TypeConverter $typeConverter
     */
    public function __construct(
        BaseConnectionInterface $connection,
        TypeConverter $typeConverter
    )
    {
        $this->connection = $connection;
        $this->typeConverter = $typeConverter;
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
        return $this->typeConverter->convert($row, $this->getEntity()->getCastMap());
    }

    /**
     * @param QueryBuilder $query
     * @param Filter $filter
     */
    protected function applyFilters(QueryBuilder $query, Filter $filter): void
    {
        // валидация всех полей на наличие в модели.
        $hasDeletedAtFilter = false;

        foreach ($filter->getConditionList() as $condition) {
            if ($condition instanceof ValueComparisonCondition) {
                $query->andWhere($condition->getColumn() . ' ' . $condition->getOperator() . ' :filter_' . $condition->getColumn())
                    ->setParameter(':filter_' . $condition->getColumn(), $condition->getValue());
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
                    $type = Connection::PARAM_STR_ARRAY; // я бы указывал тип в ValueArrayCondition.
                    if (is_int($condition->getValues()[0])) {
                        $type = Connection::PARAM_INT_ARRAY;
                    }

                    if ($condition->isIncluded()) {
                        $query->andWhere($condition->getColumn() . ' IN (:filter_' . $condition->getColumn() . ')');
                    } else {
                        $query->andWhere($condition->getColumn() . ' NOT IN (:filter_' . $condition->getColumn() . ')');
                    }

                    $query->setParameter(':filter_' . $condition->getColumn(), $condition->getValues(), $type);
                }
            }

            else if ($condition instanceof DeletedRowCondition) {
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

            // в этот блок можно еще добавить RawSqlCondition, где на чистом Sql писать сложные условия.
            // Тогда все станет гораздо гибче в принципе для расширения менеджера
        }

        if (!$hasDeletedAtFilter) { // а если ентити без поля deleted_at?
            $query->andWhere($this->getEntity()->getDeletedAtField() . ' IS NULL');
        }
    }

    /**
     * @param QueryBuilder $query
     * @param Sorting $sorting
     */
    protected function applyOrderBy(QueryBuilder $query, Sorting $sorting): void
    {
        foreach ($sorting->getSortList() as $sort) {
            // валидация поля и направления. Я бы обернул $sort в объект с двумя гетерами
            $query->addOrderBy($sort[0], $sort[1]);
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

        $result = $query->execute()->fetchAll(); // точно нет обычного fetch?
        if (!isset($result[0])) {
            return null;
        }

        return $this->prepareRow($result[0]);
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

        $result = $query->execute()->fetchAll();
        if (!isset($result[0])) {
            return null;
        }

        return $this->prepareRow($result[0]);
    }

    /**
     * @param QueryBuilder $query
     * @param $pk
     */
    protected function applyPkFilterToQuery(QueryBuilder $query, $pk): void
    {
        // валидация поля
        if (!is_array($pk)) {
            $firstPkColumn = $this->getEntity()->getPrimaryKey()[0]; // может быть нотис, который порушит функционал
            $query->andWhere($firstPkColumn . ' = :filter_' . $firstPkColumn)
                ->setParameter(':filter_' . $firstPkColumn, $pk);
        } else {
            foreach ($this->getEntity()->getPrimaryKey() as $pkColumn) {
                if (!isset($pk[$pkColumn])) {
                    throw new \RuntimeException('No value provided for PK column "' . $pkColumn . '"');
                }
                $query->andWhere($pkColumn . ' = :filter_' . $pkColumn)
                    ->set(':filter_' . $pkColumn, $pk[$pkColumn]);
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
            $currentTime = date('Y-m-d H:i:s');
            $createdAtField = $this->getEntity()->getCreatedAtField();
            $updatedAtField = $this->getEntity()->getUpdatedAtField();
            $data[$createdAtField] = $currentTime;
            $data[$updatedAtField] = $currentTime;
        }

        $values = [];
        foreach ($data as $key => $value) {
            $values[$key] = ':insert_' . $key;
            $query->setParameter(':insert_' . $key, $value);
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
            $currentTime = date('Y-m-d H:i:s');
            $createdAtField = $this->getEntity()->getCreatedAtField();
            $updatedAtField = $this->getEntity()->getUpdatedAtField();
            foreach ($data as &$row) {
                $row[$createdAtField] = $currentTime;
                $row[$updatedAtField] = $currentTime;
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
            $currentTime = date('Y-m-d H:i:s');
            $createdAtField = $this->getEntity()->getCreatedAtField();
            $updatedAtField = $this->getEntity()->getUpdatedAtField();
            $data[$createdAtField] = $currentTime;
            $data[$updatedAtField] = $currentTime;
        }

        $values = [];
        foreach ($data as $key => $value) {
            $query->set($key, ':update_' . $key);
            $query->setParameter(':update_' . $key, $value);
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
            throw new \InvalidArgumentException('Data count must be equal to filter count');
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
            throw new \RuntimeException('Can\'t soft delete from non-softdeletable table');
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
            throw new \RuntimeException('Can\'t soft delete from non-softdeletable table');
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
            throw new \RuntimeException('Can\'t soft delete from non-softdeletable table');
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

        $deletedAtColumn = $this->getEntity()->getDeletedAtField();
        $query->set($deletedAtColumn, ':ts_' . $deletedAtColumn);
        $query->setParameter(':ts_' . $deletedAtColumn, $currentTime);

        if ($this->getEntity()->isTimestampable()) {
            $updatedAtColumn = $this->getEntity()->getUpdatedAtField();
            $query->set($updatedAtColumn, ':ts_' . $updatedAtColumn);
            $query->setParameter(':ts_' . $updatedAtColumn, $currentTime);
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
        if ($result === null) {
            throw new \RuntimeException('Aggregation query returned no rows');
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
}
