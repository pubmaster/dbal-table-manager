<?php

namespace DBALTableManager\Manager;

use DBALTableManager\BaseConnectionInterface;
use DBALTableManager\Entity\EntityInterface;
use DBALTableManager\EntityValidator\EntityValidator;
use DBALTableManager\Exception\EntityDefinitionException;
use DBALTableManager\Exception\InvalidRequestException;
use DBALTableManager\Exception\QueryExecutionException;
use DBALTableManager\Query\FilterInterface;
use DBALTableManager\Query\PaginationInterface;
use DBALTableManager\Query\SortingInterface;
use DBALTableManager\QueryBuilder\QueryBuilderPreparer;
use DBALTableManager\TableRowCaster\TableRowCaster;
use DBALTableManager\Util\BulkInsertQuery;
use DBALTableManager\Util\CurrentTimeInterface;
use Doctrine\DBAL\Platforms\MySqlPlatform;
use Doctrine\DBAL\Query\QueryBuilder;

/**
 * Class SingleTableManager
 *
 * @package DBALTableManager\Manager
 */
class SingleTableManager implements DataManipulationInterface
{
    /**
     * @var BaseConnectionInterface
     */
    protected $connection;
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
     * @var EntityValidator
     */
    protected $entityValidator;
    /**
     * @var EntityInterface
     */
    protected $entity;

    /**
     * SingleTableManager constructor.
     *
     * @param BaseConnectionInterface $connection
     * @param QueryBuilderPreparer $queryBuilderPreparer
     * @param TableRowCaster $tableRowCaster
     * @param CurrentTimeInterface $currentTime
     * @param EntityValidator $entityValidator
     * @param EntityInterface $entity
     */
    public function __construct(
        BaseConnectionInterface $connection, 
        QueryBuilderPreparer $queryBuilderPreparer,
        TableRowCaster $tableRowCaster,
        CurrentTimeInterface $currentTime,
        EntityValidator $entityValidator,
        EntityInterface $entity
    )
    {
        $this->connection = $connection;
        $this->queryBuilderPreparer = $queryBuilderPreparer;
        $this->tableRowCaster = $tableRowCaster;
        $this->currentTime = $currentTime;
        $this->entity = $entity;
        $this->entityValidator = $entityValidator;
    }

    /**
     * @param FilterInterface|null $filter
     *
     * @return int
     */
    public function getCount(?FilterInterface $filter = null): int
    {
        $query = $this->connection->createQueryBuilder();
        $query->select('count(*) as count');
        $query->from($this->entity->getTableName());

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
     *
     * @return array
     */
    public function findAll(
        ?FilterInterface $filter = null,
        ?PaginationInterface $pagination = null,
        ?SortingInterface $sorting = null
    ): array
    {
        $query = $this->connection->createQueryBuilder();
        $query->select('*');
        $query->from($this->entity->getTableName());

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
     * @param FilterInterface $filter
     * @param SortingInterface|null $sorting
     *
     * @return array
     */
    public function findOneByFilter(FilterInterface $filter, ?SortingInterface $sorting = null): ?array
    {
        $query = $this->connection->createQueryBuilder();
        $query->select('*');
        $query->from($this->entity->getTableName());

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
     * @param mixed $pk
     * @param bool $withDeleted
     *
     * @return array
     */
    public function findOneByPk($pk, bool $withDeleted = false): ?array
    {
        $query = $this->connection->createQueryBuilder();
        $query->select('*');
        $query->from($this->entity->getTableName());

        $this->queryBuilderPreparer->applyPkFilterToQuery($query, $pk, $withDeleted);

        $result = $query->execute()->fetch();
        if ($result === null || $result === false) {
            return null;
        }

        return $this->tableRowCaster->prepareRow($result);
    }

    /**
     * @param $data
     *
     * @return string
     */
    public function insert(array $data): string
    {
        $query = $this->connection->createQueryBuilder();
        $query->insert($this->entity->getTableName());

        if ($this->entity->isTimestampable()) {
            $this->entityValidator->checkTimestampableEntity();

            $this->setTimestampableValues($data, [
                $this->entity->getCreatedAtField(),
                $this->entity->getUpdatedAtField(),
            ]);
        }

        $this->queryBuilderPreparer->checkColumnList(array_keys($data));

        $values = [];
        foreach ($data as $key => $value) {
            $values[$key] = $query->createNamedParameter($value, $this->queryBuilderPreparer->getPdoType($key));
        }
        $query->values($values);

        $query->execute();

        if (false === $this->entity->isPkAutoGenerated()) {
            return '';
        }

        return $this->connection->lastInsertId();
    }

    /**
     * @param $data
     *
     * @return int
     */
    public function batchInsert(array $data): int
    {
        if ($data === []) {
            return 0;
        }

        if ($this->entity->isTimestampable()) {
            $this->entityValidator->checkTimestampableEntity();

            foreach ($data as &$row) {
                $this->setTimestampableValues($row, [
                    $this->entity->getCreatedAtField(),
                    $this->entity->getUpdatedAtField(),
                ]);

                $this->queryBuilderPreparer->checkColumnList(array_keys($row));
            }
            unset($row);
        }

        $columns = [];
        if (isset($data[0])) {
            $columns = array_keys($data[0]);
        }

        $q = new BulkInsertQuery($this->connection, $this->entity->getTableName(), $columns);
        foreach ($data as $row) {
            $q->addValues($row);
        }

        return $q->execute();
    }

    /**
     * @param FilterInterface $filter
     * @param array $data
     *
     * @return int
     */
    public function updateByFilter(FilterInterface $filter, array $data): int
    {
        $query = $this->connection->createQueryBuilder();
        $query->update($this->entity->getTableName());

        $this->setValuesForUpdateQuery($query, $data);
        $this->queryBuilderPreparer->applyFilters($query, $filter);

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
        $query->update($this->entity->getTableName());

        $this->setValuesForUpdateQuery($query, $data);
        $this->queryBuilderPreparer->applyPkFilterToQuery($query, $pk);

        return $query->execute();
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

        $count = 0;

        foreach ($data as $i => $row) {
            $count += $this->updateByFilter($filterList[$i], $row);
        }

        return $count;
    }

    /**
     * @param FilterInterface $filter
     *
     * @return int
     */
    public function deleteByFilter(FilterInterface $filter): int
    {
        $query = $this->connection->createQueryBuilder();
        $query->delete($this->entity->getTableName());

        $this->queryBuilderPreparer->applyFilters($query, $filter);

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
        $query->delete($this->entity->getTableName());

        $this->queryBuilderPreparer->applyPkFilterToQuery($query, $pk);

        return $query->execute();
    }

    /**
     * @return int
     */
    public function deleteAll(): int
    {
        $query = $this->connection->createQueryBuilder();
        $query->delete($this->entity->getTableName());

        return $query->execute();
    }

    /**
     * @param FilterInterface $filter
     *
     * @return int
     */
    public function softDeleteByFilter(FilterInterface $filter): int
    {
        if ($this->entity->isSoftDeletable() === false) {
            throw EntityDefinitionException::withNotSoftDeletable();
        }

        $query = $this->connection->createQueryBuilder();
        $query->update($this->entity->getTableName());

        $this->queryBuilderPreparer->applyFilters($query, $filter);

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
        if ($this->entity->isSoftDeletable() === false) {
            throw EntityDefinitionException::withNotSoftDeletable();
        }

        $query = $this->connection->createQueryBuilder();
        $query->update($this->entity->getTableName());

        $this->queryBuilderPreparer->applyPkFilterToQuery($query, $pk);

        $this->setSoftDeletedValues($query);

        return $query->execute();
    }

    /**
     * @return int
     */
    public function softDeleteAll(): int
    {
        if ($this->entity->isSoftDeletable() === false) {
            throw EntityDefinitionException::withNotSoftDeletable();
        }

        $query = $this->connection->createQueryBuilder();
        $query->update($this->entity->getTableName());

        $this->setSoftDeletedValues($query);

        return $query->execute();
    }

    /**
     * @param $pk
     *
     * @return int
     */
    public function restoreByPk($pk): int
    {
        if ($this->entity->isSoftDeletable() === false) {
            throw EntityDefinitionException::withNotSoftDeletable();
        }

        $query = $this->connection->createQueryBuilder();
        $query->update($this->entity->getTableName());

        $this->queryBuilderPreparer->applyPkFilterToQuery($query, $pk, true);

        $query->andWhere($this->entity->getDeletedAtField() . ' IS NOT NULL');

        $this->unsetSoftDeletedValues($query);

        return $query->execute();
    }

    public function truncate(): void
    {
        $dbPlatform = $this->connection->getDatabasePlatform();

        // TODO: вынести платформозависимую логику в отдельный класс
        if ($dbPlatform instanceof MySqlPlatform) {
            $this->connection->exec("SET FOREIGN_KEY_CHECKS = 0");
        }

        $q = $dbPlatform->getTruncateTableSQL($this->entity->getTableName(), true);
        $this->connection->exec($q);

        if ($dbPlatform instanceof MySqlPlatform) {
            $this->connection->exec("SET FOREIGN_KEY_CHECKS = 1");
        }
    }

    /**
     * @param QueryBuilder $query
     */
    private function setSoftDeletedValues(QueryBuilder $query): void
    {
        $currentTime = $this->currentTime->getCurrentTime()->format('Y-m-d H:i:s');

        $this->entityValidator->checkSoftDeletableEntity();
        $query->set(
            $this->queryBuilderPreparer->prepareColumnName($this->entity->getDeletedAtField()),
            $query->createNamedParameter($currentTime)
        );

        if ($this->entity->isTimestampable()) {
            $this->entityValidator->checkTimestampableEntity();
            $query->set(
                $this->queryBuilderPreparer->prepareColumnName($this->entity->getUpdatedAtField()),
                $query->createNamedParameter($currentTime)
            );
        }
    }

    /**
     * @param QueryBuilder $query
     */
    private function unsetSoftDeletedValues(QueryBuilder $query): void
    {
        $currentTime = $this->currentTime->getCurrentTime()->format('Y-m-d H:i:s');

        $this->entityValidator->checkSoftDeletableEntity();
        $query->set(
            $this->queryBuilderPreparer->prepareColumnName($this->entity->getDeletedAtField()),
            $query->createNamedParameter(null)
        );

        if ($this->entity->isTimestampable()) {
            $this->entityValidator->checkTimestampableEntity();
            $query->set(
                $this->queryBuilderPreparer->prepareColumnName($this->entity->getUpdatedAtField()),
                $query->createNamedParameter($currentTime)
            );
        }
    }

    /**
     * @param QueryBuilder $query
     * @param array $data
     */
    private function setValuesForUpdateQuery(QueryBuilder $query, array $data): void
    {
        if ($this->entity->isTimestampable()) {
            $this->entityValidator->checkTimestampableEntity();

            $this->setTimestampableValues($data, [
                $this->entity->getUpdatedAtField(),
            ]);
        }

        $this->queryBuilderPreparer->checkColumnList(array_keys($data));

        $values = [];
        foreach ($data as $key => $value) {
            $query->set(
                $this->queryBuilderPreparer->prepareColumnName($key),
                $query->createNamedParameter($value, $this->queryBuilderPreparer->getPdoType($key))
            );
        }
        $query->values($values);
    }

    /**
     * @param array $data
     * @param array $fieldList
     */
    private function setTimestampableValues(array &$data, array $fieldList): void
    {
        $currentTime = $this->currentTime->getCurrentTime()->format('Y-m-d H:i:s');
        foreach ($fieldList as $field) {
            if (!array_key_exists($field, $data)) {
                $data[$field] = $currentTime;
            }
        }
    }
}
