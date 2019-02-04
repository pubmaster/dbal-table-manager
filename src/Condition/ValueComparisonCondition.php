<?php

namespace DBALTableManager\Condition;

/**
 * Class ValueComparisonCondition
 *
 * @package DBALTableManager\Condition
 */
// Статичные методы на конструкторы сравнения.
// Или отдельные классы на каждый вид сравнения.
// Иначе есть возможность передать галиматью в оператор.
class ValueComparisonCondition
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
}
