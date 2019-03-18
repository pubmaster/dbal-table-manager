<?php

namespace DBALTableManager\Query\Condition;

/**
 * Class ValueComparisonCondition
 *
 * @package DBALTableManager\Query\Condition
 */
class ValueComparisonCondition implements ColumnableCondition
{
    /**
     * @var string
     */
    private $column;
    /**
     * @var string
     */
    private $operator;
    /**
     * @var mixed
     */
    private $value;

    /**
     * ValueComparisonCondition constructor.
     *
     * @param string $column
     * @param string $operator
     * @param mixed $value
     */
    public function __construct(string $column, string $operator, $value)
    {
        $this->column = $column;
        $this->operator = $operator;
        $this->value = $value;
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
    public function getOperator(): string
    {
        return $this->operator;
    }

    /**
     * @return mixed
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @param string $column
     * @param $value
     *
     * @return ValueComparisonCondition
     */
    public static function equals(string $column, $value): self
    {
        return new static($column, '=', $value);
    }

    /**
     * @param string $column
     * @param $value
     *
     * @return ValueComparisonCondition
     */
    public static function notEquals(string $column, $value): self
    {
        return new static($column, '<>', $value);
    }

    /**
     * @param string $column
     * @param $value
     *
     * @return ValueComparisonCondition
     */
    public static function lessThan(string $column, $value): self
    {
        return new static($column, '<', $value);
    }

    /**
     * @param string $column
     * @param $value
     *
     * @return ValueComparisonCondition
     */
    public static function lessOrEquals(string $column, $value): self
    {
        return new static($column, '<=', $value);
    }

    /**
     * @param string $column
     * @param $value
     *
     * @return ValueComparisonCondition
     */
    public static function greaterThan(string $column, $value): self
    {
        return new static($column, '>', $value);
    }

    /**
     * @param string $column
     * @param $value
     *
     * @return ValueComparisonCondition
     */
    public static function greaterOrEquals(string $column, $value): self
    {
        return new static($column, '>=', $value);
    }
}
