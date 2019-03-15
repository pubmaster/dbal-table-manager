<?php

namespace DBALTableManager\Entity;

/**
 * Interface EntityVersionInterface
 *
 * @package DBALTableManager\Entity
 */
interface EntityVersionInterface
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
    public function getStaticPkField(): array;

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
