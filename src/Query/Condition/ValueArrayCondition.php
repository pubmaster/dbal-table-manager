<?php

namespace DBALTableManager\Query\Condition;

/**
 * Class ValueArrayCondition
 *
 * @package DBALTableManager\Query\Condition
 */
class ValueArrayCondition implements ColumnableCondition
{
    /**
     * @var string
     */
    private $column;
    /**
     * @var array
     */
    private $values;
    /**
     * @var boolean
     */
    private $isIncluded;
    /**
     * @var boolean
     */
    private $emptyAsNoFilter;

    /**
     * ValueArrayCondition constructor.
     *
     * @param string $column
     * @param array $values
     * @param bool $isIncluded
     * @param bool $emptyAsNoFilter
     */
    public function __construct(string $column, array $values, bool $isIncluded, bool $emptyAsNoFilter)
    {
        $this->column = $column;
        $this->values = $values;
        $this->isIncluded = $isIncluded;
        $this->emptyAsNoFilter = $emptyAsNoFilter;
    }

    /**
     * @return string
     */
    public function getColumn(): string
    {
        return $this->column;
    }

    /**
     * @return array
     */
    public function getValues(): array
    {
        return $this->values;
    }

    /**
     * @return bool
     */
    public function isIncluded(): bool
    {
        return $this->isIncluded;
    }

    /**
     * @return bool
     */
    public function isEmptyAsNoFilter(): bool
    {
        return $this->emptyAsNoFilter;
    }
}
