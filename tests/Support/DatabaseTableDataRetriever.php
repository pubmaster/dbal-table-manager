<?php

namespace Tests\Support;

use DBALTableManager\BaseConnectionInterface;
use DBALTableManager\Util\TypeConverter;

/**
 * Class DatabaseTableDataRetriever
 *
 * @package Tests\Support
 */
class DatabaseTableDataRetriever
{
    /**
     * @var BaseConnectionInterface
     */
    private $connection;
    /**
     * @var TypeConverter
     */
    private $typeConverter;
    /**
     * @var string
     */
    private $tableName;

    /**
     * DatabaseTableDataRetriever constructor.
     *
     * @param BaseConnectionInterface $connection
     * @param TypeConverter $typeConverter
     * @param string $tableName
     */
    public function __construct(
        BaseConnectionInterface $connection,
        TypeConverter $typeConverter,
        string $tableName
    )
    {
        $this->connection = $connection;
        $this->tableName = $tableName;
        $this->typeConverter = $typeConverter;
    }

    /**
     * @param array $filters
     *
     * @return array
     */
    public function getRowsFromDB(array $filters = []): array
    {
        $query = $this->connection->createQueryBuilder();
        $query->select('*');
        $query->from($this->tableName);

        foreach ($filters as $column => $value) {
            $query->andWhere($column . ' = ' . $query->createNamedParameter($value));
        }

        $list = $query->execute()->fetchAll();

        $result = [];

        foreach ($list as $row) {
            $result[] = $this->typeConverter->convert($row, DefaultTestEntity::FIELD_MAP);
        }

        return $result;
    }

    /**
     * @param array $filters
     *
     * @return array|null
     */
    public function getOneRowFromDB(array $filters = []): ?array
    {
        $query = $this->connection->createQueryBuilder();
        $query->select('*');
        $query->from($this->tableName);

        foreach ($filters as $column => $value) {
            $query->andWhere($column . ' = ' . $query->createNamedParameter($value));
        }

        $query->setMaxResults(1);

        $result = $query->execute()->fetch();
        if ($result === null || $result === false) {
            return null;
        }

        return $this->typeConverter->convert($result, DefaultTestEntity::FIELD_MAP);
    }

    /**
     * @param array $filters
     *
     * @return int
     */
    public function getCountFromDB(array $filters = []): int
    {
        $query = $this->connection->createQueryBuilder();
        $query->select('count(*) as count');
        $query->from($this->tableName);

        foreach ($filters as $column => $value) {
            $query->andWhere($column . ' = ' . $query->createNamedParameter($value));
        }

        $result = $query->execute()->fetch();
        if ($result === null || $result === false) {
            throw new \RuntimeException('Aggregation query returned no rows');
        }

        return $result['count'];
    }
}
