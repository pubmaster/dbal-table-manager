<?php

namespace DBALTableManager\SchemaDescription;

/**
 * Interface SchemaDescriptionInterface
 *
 * @package DBALTableManager\SchemaDescription
 */
interface SchemaDescriptionInterface
{
    /**
     * @param string $column
     *
     * @return bool
     */
    public function hasColumn(string $column): bool;

    /**
     * @return string[]
     */
    public function getColumnList(): array;

    /**
     * @param string $column
     *
     * @return string
     */
    public function getColumnType(string $column): string;

    /**
     * @return array
     */
    public function getCastMap(): array;

    /**
     * @param string $column
     *
     * @return string
     */
    public function getPreparedColumnForQuery(string $column): string;
}
