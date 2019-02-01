<?php

namespace DBALTableManager\Entity;

/**
 * Interface EntityInterface
 *
 * @package DBALTableManager\Entity
 */
interface EntityInterface
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
    public function getFieldList(): array;

    /**
     * @return array
     */
    public function getCastMap(): array;

    /**
     * @return bool
     */
    public function isTimestampable(): bool;

    /**
     * @return string
     */
    public function getCreatedAtField(): string;

    /**
     * @return string
     */
    public function getUpdatedAtField(): string;

    /**
     * @return bool
     */
    public function isSoftDeletable(): bool;

    /**
     * @return string
     */
    public function getDeletedAtField(): string;
}
