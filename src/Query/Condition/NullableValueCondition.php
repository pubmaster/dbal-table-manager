<?php

namespace DBALTableManager\Query\Condition;

/**
 * Class NullableValueCondition
 *
 * @package DBALTableManager\Query\Condition
 */
class NullableValueCondition implements ColumnableCondition
{
    /**
     * @var string
     */
    private $column;
    /**
     * @var bool
     */
    private $isNull;

    /**
     * NullableValueCondition constructor.
     *
     * @param string $column
     * @param bool $isNull
     */
    public function __construct(string $column, bool $isNull)
    {
        $this->column = $column;
        $this->isNull = $isNull;
    }

    /**
     * @return string
     */
    public function getColumn(): string
    {
        return $this->column;
    }

    /**
     * @return bool
     */
    public function isNull(): bool
    {
        return $this->isNull;
    }
}
