<?php

namespace DBALTableManager;

use DBALTableManager\Exception\EntityDefinitionException;
use DBALTableManager\Exception\InvalidRequestException;
use DBALTableManager\Exception\QueryExecutionException;
use DBALTableManager\Query\BulkInsertQuery;
use Doctrine\DBAL\Query\QueryBuilder;

/**
 * Class BaseManager
 *
 * @package DBALTableManager
 */
abstract class BaseManager extends ManagerFoundation
{
    /**
     * @return array
     */
    protected function getFieldMap(): array
    {
        return $this->getEntity()->getFieldMap();
    }

    /**
     * @param Filter|null $filter
     *
     * @return int
     */
    public function getCount(?Filter $filter = null): int
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

    /**
     * @param Filter|null $filter
     * @param Pagination|null $pagination
     * @param Sorting|null $sorting
     *
     * @return array
     */
    public function findAll(
        ?Filter $filter = null,
        ?Pagination $pagination = null,
        ?Sorting $sorting = null
    ): array
    {
        $query = $this->connection->createQueryBuilder();
        $query->select('*');
        $query->from($this->getEntity()->getTableName());

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
     * @param Filter $filter
     * @param Sorting|null $sorting
     *
     * @return array
     */
    public function findOneByFilter(Filter $filter, ?Sorting $sorting = null): ?array
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
     * @param mixed $pk
     * @param bool $withDeleted
     *
     * @return array
     */
    public function findOneByPk($pk, bool $withDeleted = false): ?array
    {
        $query = $this->connection->createQueryBuilder();
        $query->select('*');
        $query->from($this->getEntity()->getTableName());

        $this->applyPkFilterToQuery($query, $pk, $withDeleted);

        $result = $query->execute()->fetch();
        if ($result === null || $result === false) {
            return null;
        }

        return $this->prepareRow($result);
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

            $this->setTimestampableValues($data, [
                $this->getEntity()->getCreatedAtField(),
                $this->getEntity()->getUpdatedAtField(),
            ]);
        }

        $this->checkColumnList(array_keys($data));

        $values = [];
        foreach ($data as $key => $value) {
            $values[$key] = $query->createNamedParameter($value, $this->getPdoType($key));
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

            foreach ($data as &$row) {
                $this->setTimestampableValues($row, [
                    $this->getEntity()->getCreatedAtField(),
                    $this->getEntity()->getUpdatedAtField(),
                ]);

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
     * @param Filter $filter
     * @param array $data
     *
     * @return int
     */
    public function updateByFilter(Filter $filter, array $data): int
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
            $count += $this->updateByFilter($filterList[$i], $row);
        }

        return $count;
    }

    /**
     * @param Filter $filter
     *
     * @return int
     */
    public function deleteByFilter(Filter $filter): int
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
    public function softDeleteByFilter(Filter $filter): int
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

    public function truncate(): void
    {
        $dbPlatform = $this->connection->getDatabasePlatform();
        $q = $dbPlatform->getTruncateTableSQL($this->getEntity()->getTableName());
        $this->connection->exec($q);
    }

    /**
     * @param QueryBuilder $query
     */
    private function setSoftDeletedValues(QueryBuilder $query): void
    {
        $currentTime = date('Y-m-d H:i:s');

        $this->checkSoftDeletableEntity();
        $query->set($this->prepareColumnName($this->getEntity()->getDeletedAtField()), $query->createNamedParameter($currentTime));

        if ($this->getEntity()->isTimestampable()) {
            $this->checkTimestampableEntity();
            $query->set($this->prepareColumnName($this->getEntity()->getUpdatedAtField()), $query->createNamedParameter($currentTime));
        }
    }

    /**
     * @param QueryBuilder $query
     * @param array $data
     */
    private function setValuesForUpdateQuery(QueryBuilder $query, array $data): void
    {
        if ($this->getEntity()->isTimestampable()) {
            $this->checkTimestampableEntity();

            $this->setTimestampableValues($data, [
                $this->getEntity()->getUpdatedAtField(),
            ]);
        }

        $this->checkColumnList(array_keys($data));

        $values = [];
        foreach ($data as $key => $value) {
            $query->set($this->prepareColumnName($key), $query->createNamedParameter($value, $this->getPdoType($key)));
        }
        $query->values($values);
    }

    /**
     * @param array $data
     * @param array $fieldList
     */
    private function setTimestampableValues(array &$data, array $fieldList): void
    {
        $currentTime = date('Y-m-d H:i:s');
        foreach ($fieldList as $field) {
            if (!array_key_exists($field, $data)) {
                $data[$field] = $currentTime;
            }
        }
    }
}
