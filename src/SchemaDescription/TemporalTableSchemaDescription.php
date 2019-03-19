<?php

namespace DBALTableManager\SchemaDescription;

use DBALTableManager\Entity\EntityInterface;
use DBALTableManager\Entity\TemporalVersionEntityInterface;
use DBALTableManager\Exception\InvalidRequestException;

/**
 * Class TemporalTableSchemaDescription
 *
 * @package DBALTableManager\SchemaDescription
 */
class TemporalTableSchemaDescription implements SchemaDescriptionInterface
{
    private const STATIC_TABLE_ALIAS = 'static';
    private const VERSION_TABLE_ALIAS = 'version';

    /**
     * @var EntityInterface
     */
    private $staticEntity;
    /**
     * @var TemporalVersionEntityInterface
     */
    private $versionEntity;

    /**
     * TemporalTableSchemaDescription constructor.
     *
     * @param EntityInterface $staticEntity
     * @param TemporalVersionEntityInterface $versionEntity
     */
    public function __construct(EntityInterface $staticEntity, TemporalVersionEntityInterface $versionEntity)
    {
        $this->staticEntity = $staticEntity;
        $this->versionEntity = $versionEntity;
    }

    /**
     * @param string $column
     *
     * @return bool
     */
    public function hasColumn(string $column): bool
    {
        return array_key_exists($column, $this->getFieldMap());
    }

    /**
     * @return string[]
     */
    public function getColumnList(): array
    {
        return array_keys($this->getFieldMap());
    }

    /**
     * @param string $column
     *
     * @return string
     */
    public function getColumnType(string $column): string
    {
        if (!array_key_exists($column, $this->getFieldMap())) {
            throw InvalidRequestException::withUnknownColumnList([$column]);
        }

        return $this->getFieldMap()[$column];
    }

    /**
     * @return array
     */
    public function getCastMap(): array
    {
        return $this->getFieldMap();
    }

    /**
     * @return array
     */
    protected function getFieldMap(): array
    {
        return array_merge(
            $this->staticEntity->getFieldMap(),
            $this->versionEntity->getFieldMap()
        );
    }

    /**
     * @param string $column
     *
     * @return string
     */
    public function getPreparedColumnForQuery(string $column): string
    {
        if (array_key_exists($column, $this->staticEntity->getFieldMap())) {
            return self::STATIC_TABLE_ALIAS . '.' . $column;
        }

        if (array_key_exists($column, $this->versionEntity->getFieldMap())) {
            return self::VERSION_TABLE_ALIAS . '.' . $column;
        }

        throw InvalidRequestException::withUnknownColumnList([$column]);
    }
}
