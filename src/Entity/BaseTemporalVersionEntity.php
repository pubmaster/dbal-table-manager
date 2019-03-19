<?php

namespace DBALTableManager\Entity;

/**
 * Class BaseTemporalVersionEntity
 *
 * @package DBALTableManager\Entity
 */
abstract class BaseTemporalVersionEntity implements TemporalVersionEntityInterface
{
    public const EFFECTIVE_SINCE_COLUMN = 'effective_since';
    public const CREATED_AT_COLUMN = 'created_at';

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
}
