<?php

namespace DBALTableManager\Query;

/**
 * Interface FilterInterface
 *
 * @package DBALTableManager\Query
 */
interface FilterInterface
{
    /**
     * @return array
     */
    public function getConditionList(): array;
}
