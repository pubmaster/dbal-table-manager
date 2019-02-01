<?php

namespace DBALTableManager\Entity;

/**
 * Class BaseIdEntity
 *
 * @package DBALTableManager\Entity
 */
abstract class BaseIdEntity implements EntityInterface
{
    public const PK_NAME = 'id';
    public const CREATED_AT_COLUMN = 'created_at';
    public const UPDATED_AT_COLUMN = 'updated_at';
    public const DELETED_AT_COLUMN = 'deleted_at';
    /**
     * @return array
     */
    public function getPrimaryKey(): array
    {
        return [self::PK_NAME];
    }

    /**
     * @return bool
     */
    public function isTimestampable(): bool
    {
        return true;
    }

    /**
     * @return string
     */
    public function getCreatedAtField(): string
    {
        return self::CREATED_AT_COLUMN;
    }

    /**
     * @return string
     */
    public function getUpdatedAtField(): string
    {
        return self::UPDATED_AT_COLUMN;
    }

    /**
     * @return bool
     */
    public function isSoftDeletable(): bool
    {
        return true;
    }

    /**
     * @return string
     */
    public function getDeletedAtField(): string
    {
        return self::DELETED_AT_COLUMN;
    }
}
