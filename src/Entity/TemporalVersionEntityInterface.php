<?php

namespace DBALTableManager\Entity;

/**
 * Interface TemporalVersionEntityInterface
 *
 * @package DBALTableManager\Entity
 */
interface TemporalVersionEntityInterface
{
    /**
     * @return string
     */
    public function getTableName(): string;

    /**
     * @return array
     */
    public function getPrimaryKey(): array;

    /**
     * @return array
     */
    public function getForeignKeyMap(): array;

    /**
     * @return string
     */
    public function getEffectiveSinceField(): string;

    /**
     * @return string
     */
    public function getCreatedAtField(): string;

    /**
     * @return array
     */
    public function getFieldMap(): array;
}
