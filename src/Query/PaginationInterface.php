<?php

namespace DBALTableManager\Query;

/**
 * Interface PaginationInterface
 *
 * @package DBALTableManager\Query
 */
interface PaginationInterface
{
    /**
     * @return int|null
     */
    public function getLimit(): ?int;

    /**
     * @return int|null
     */
    public function getOffset(): ?int;
}
