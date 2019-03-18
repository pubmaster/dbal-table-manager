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
    public function getStaticPkField(): array
    {
        return ['user_id'];
    }

    /**
     * @return string
     */
    public function getEffectiveSinceField(): string
    {
        return 'effective_since';
    }

    /**
     * @return string
     */
    public function getCreatedAtField(): string
    {
        return 'created_at';
    }

    /**
     * @return array
     */
    public function getFieldMap(): array
    {
        return self::FIELD_MAP;
    }
}
