<?php

namespace Tests\Support;

use DBALTableManager\Entity\EntityInterface;

/**
 * Class DefaultTestEntity
 *
 * @package Tests\Support
 */
class DefaultTestEntity implements EntityInterface
{
    public const PK_COLUMN = 'id';
    public const TABLE_NAME = 'user';
    public const CREATED_AT_COLUMN = 'created_at';
    public const UPDATED_AT_COLUMN = 'updated_at';
    public const DELETED_AT_COLUMN = 'deleted_at';
    public const FIELD_MAP = [
        'id' => 'int',
        'name' => 'string',
        'birthday' => 'date',
        'age' => 'int',
        'weight' => 'float',
        'married' => 'bool',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * @return string
     */
    public function getTableName(): string
    {
        return self::TABLE_NAME;
    }

    /**
     * @return array
     */
    public function getPrimaryKey(): array
    {
        return [self::PK_COLUMN];
    }

    /**
     * @return array
     */
    public function getFieldMap(): array
    {
        return self::FIELD_MAP;
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
