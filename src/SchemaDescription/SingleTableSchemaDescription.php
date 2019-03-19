<?php

namespace DBALTableManager\SchemaDescription;

use DBALTableManager\Entity\EntityInterface;
use DBALTableManager\Exception\InvalidRequestException;

/**
 * Class SingleTableSchemaDescription
 *
 * @package DBALTableManager\SchemaDescription
 */
class SingleTableSchemaDescription implements SchemaDescriptionInterface
{
    /**
     * @var EntityInterface
     */
    private $entity;

    /**
     * SingleTableManagerSchemaDescription constructor.
     *
     * @param EntityInterface $entity
     */
    public function __construct(EntityInterface $entity)
    {
        $this->entity = $entity;
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
        return $this->entity->getFieldMap();
    }

    /**
     * @param string $column
     *
     * @return string
     */
    public function getPreparedColumnForQuery(string $column): string
    {
        return $column;
    }
}
