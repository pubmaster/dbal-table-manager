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
    public function getFieldList(): array
    {
        return [
            'id',
            'name',
            'birthday',
            'age',
            'weight',
            'married',
            'created_at',
            'updated_at',
            'deleted_at',
        ];
    }

    /**
     * @return array
     */
    public function getCastMap(): array
    {
        return [
            'id' => 'int',
            'age' => 'int',
            'weight' => 'float',
            'married' => 'bool',
        ];
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
        return 'created_at';
    }

    /**
     * @return string
     */
    public function getUpdatedAtField(): string
    {
        return 'updated_at';
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
        return 'deleted_at';
    }
}
