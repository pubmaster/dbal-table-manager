<?php

namespace DBALTableManager\Condition;

/**
 * Class RawSqlCondition
 *
 * @package DBALTableManager\Condition
 */
class RawSqlCondition
{
    /**
     * @var string
     */
    private $expression;

    /**
     * RawSqlCondition constructor.
     *
     * @param string $expression
     */
    public function __construct(string $expression)
    {
        $this->expression = $expression;
    }

    /**
     * @return string
     */
    public function getExpression(): string
    {
        return $this->expression;
    }
}
