<?php

namespace DBALTableManager\Query\Condition;

/**
 * Class RawSqlCondition
 *
 * @package DBALTableManager\Query\Condition
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
