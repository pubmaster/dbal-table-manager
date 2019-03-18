<?php

namespace DBALTableManager\Query;

/**
 * Class SortingItem
 *
 * @package DBALTableManager\Query
 */
class SortingItem
{
    /**
     * @var string
     */
    public $column;
    /**
     * @var string
     */
    public $order;

    /**
     * SortingField constructor.
     *
     * @param string $column
     * @param string $order
     */
    public function __construct(string $column, string $order)
    {
        $this->column = $column;
        $this->order = $order;
    }

    /**
     * @return string
     */
    public function getColumn(): string
    {
        return $this->column;
    }

    /**
     * @return string
     */
    public function getOrder(): string
    {
        return $this->order;
    }
}
