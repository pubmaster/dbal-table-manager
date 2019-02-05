<?php

namespace DBALTableManager\Condition;

/**
 * Class ValueLikeCondition
 *
 * @package DBALTableManager\Condition
 */
class ValueLikeCondition implements ColumnableCondition
{
    /**
     * @var string
     */
    private $column;
    /**
     * @var mixed
     */
    private $value;
    /**
     * @var bool
     */
    private $strictFromBeginning;
    /**
     * @var bool
     */
    private $strictToEnd;

    /**
     * ValueLikeCondition constructor.
     *
     * @param string $column
     * @param mixed $value
     * @param bool $strictFromBeginning
     * @param bool $strictToEnd
     */
    public function __construct(string $column, $value, bool $strictFromBeginning, bool $strictToEnd)
    {
        $this->column = $column;
        $this->value = $value;
        $this->strictFromBeginning = $strictFromBeginning;
        $this->strictToEnd = $strictToEnd;
    }

    /**
     * @return string
     */
    public function getColumn(): string
    {
        return $this->column;
    }

    /**
     * @return mixed
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @return bool
     */
    public function isStrictFromBeginning(): bool
    {
        return $this->strictFromBeginning;
    }

    /**
     * @return bool
     */
    public function isStrictToEnd(): bool
    {
        return $this->strictToEnd;
    }
}
