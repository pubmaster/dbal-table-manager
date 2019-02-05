<?php

namespace DBALTableManager\Condition;

/**
 * Class ValueComparisonCondition
 *
 * @package DBALTableManager\Condition
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
     * ValueArrayCondition constructor.
     *
     * @param string $column
     * @param array $values
     * @param bool $isIncluded
     */
    public function __construct(string $column, array $values, bool $isIncluded)
    {
        $this->column = $column;
        $this->values = $values;
        $this->isIncluded = $isIncluded;
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
}
