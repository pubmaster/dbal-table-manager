<?php

namespace DBALTableManager\Condition;

/**
 * Interface ColumnableCondition
 *
 * @package DBALTableManager\Condition
 */
interface ColumnableCondition
{
    /**
     * @return string
     */
    public function getColumn(): string;
}
