<?php

namespace DBALTableManager\Query;

/**
 * Interface SortingInterface
 *
 * @package DBALTableManager\Query
 */
interface SortingInterface
{
    /**
     * @return SortingItem[]
     */
    public function getSortList(): array;
}
