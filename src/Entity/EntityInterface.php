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
    public function getFieldMap(): array;

    /**
     * @return bool
     */
    public function isTimestampable(): bool;

    /**
     * @return string|null
     */
    public function getCreatedAtField(): ?string;

    /**
     * @return string|null
     */
    public function getUpdatedAtField(): ?string;

    /**
     * @return bool
     */
    public function isSoftDeletable(): bool;

    /**
     * @return string|null
     */
    public function getDeletedAtField(): ?string;
}
