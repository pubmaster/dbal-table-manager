<?php

namespace Tests\Support;

use DBALTableManager\Entity\TemporalVersionEntityInterface;

/**
 * Class DefaultTestVersionEntity
 *
 * @package Tests\Support
 */
class DefaultTestTemporalVersionEntity implements TemporalVersionEntityInterface
{
    public const TABLE_NAME = 'user_table_version';
    public const EFFECTIVE_SINCE_COLUMN = 'effective_since';
    public const CREATED_AT_COLUMN = 'created_at';
    public const PK_COLUMN = [
        'user_id',
        'effective_since',
        'created_at',
    ];
    public const FIELD_MAP = [
        'user_id' => 'int',
        'effective_since' => 'string',
        'created_at' => 'string',
        'salary' => 'int',
        'fired' => 'bool',
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
        return self::PK_COLUMN;
    }

    /**
     * @return array
     */
    public function getForeignKeyMap(): array
    {
        return [
            'user_id' => 'id',
        ];
    }

    /**
     * @return string
     */
    public function getEffectiveSinceField(): string
    {
        return self::EFFECTIVE_SINCE_COLUMN;
    }

    /**
     * @return string
     */
    public function getCreatedAtField(): string
    {
        return self::CREATED_AT_COLUMN;
    }

    /**
     * @return array
     */
    public function getFieldMap(): array
    {
        return self::FIELD_MAP;
    }
}
