<?php

namespace DBALTableManager\Entity;

/**
 * Class BaseManyToManyEntity
 *
 * @package DBALTableManager\Entity
 */
abstract class BaseManyToManyEntity implements EntityInterface
{
    /**
     * @return bool
     */
    public function isPkAutoGenerated(): bool
    {
        return false;
    }

    /**
     * @return bool
     */
    public function isTimestampable(): bool
    {
        return false;
    }

    /**
     * @return string|null
     */
    public function getCreatedAtField(): ?string
    {
        return null;
    }

    /**
     * @return string|null
     */
    public function getUpdatedAtField(): ?string
    {
        return null;
    }

    /**
     * @return bool
     */
    public function isSoftDeletable(): bool
    {
        return false;
    }

    /**
     * @return string|null
     */
    public function getDeletedAtField(): ?string
    {
        return null;
    }
}