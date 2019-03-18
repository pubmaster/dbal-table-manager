<?php

namespace DBALTableManager\Query;

/**
 * Class Pagination
 *
 * @package DBALTableManager\Query
 */
class Pagination
{
    /** @var int|null */
    private $limit;
    /** @var int|null */
    private $offset;

    /**
     * Pagination constructor.
     *
     * @param int|null $limit
     * @param int|null $offset
     */
    public function __construct(?int $limit = null, ?int $offset = null)
    {
        $this->limit = $limit;
        $this->offset = $offset;
    }

    /**
     * @return int|null
     */
    public function getLimit(): ?int
    {
        return $this->limit;
    }

    /**
     * @return int|null
     */
    public function getOffset(): ?int
    {
        return $this->offset;
    }
}
