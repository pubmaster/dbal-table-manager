<?php

namespace DBALTableManager\Query\Condition;

/**
 * Interface ColumnableCondition
 *
 * @package DBALTableManager\Query\Condition
 */
interface ColumnableCondition
{
    /**
     * @return string
     */
    public function getColumn(): string;
}
