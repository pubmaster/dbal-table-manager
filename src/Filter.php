<?php

namespace DBALTableManager;

use DBALTableManager\Condition\DeletedRowCondition;
use DBALTableManager\Condition\NullableValueCondition;
use DBALTableManager\Condition\RawSqlCondition;
use DBALTableManager\Condition\ValueArrayCondition;
use DBALTableManager\Condition\ValueComparisonCondition;
use DBALTableManager\Condition\ValueLikeCondition;

/**
 * Class Filter
 *
 * @package DBALTableManager
 */
class Filter
{
    /**
     * @var array
     */
    private $conditionList = [];

    /**
     * @return array
     */
    public function getConditionList(): array
    {
        return $this->conditionList;
    }

    /**
     * @param string $column
     * @param $value
     *
     * @return Filter
     */
    public function equals(string $column, $value): self
    {
        $this->conditionList[] = ValueComparisonCondition::equals($column, $value);

        return $this;
    }

    /**
     * @param string $column
     * @param $value
     *
     * @return Filter
     */
    public function notEquals(string $column, $value): self
    {
        $this->conditionList[] = ValueComparisonCondition::notEquals($column, $value);

        return $this;
    }

    /**
     * @param string $column
     * @param $value
     *
     * @return Filter
     */
    public function lessThan(string $column, $value): self
    {
        $this->conditionList[] = ValueComparisonCondition::lessThan($column, $value);

        return $this;
    }

    /**
     * @param string $column
     * @param $value
     *
     * @return Filter
     */
    public function lessOrEquals(string $column, $value): self
    {
        $this->conditionList[] = ValueComparisonCondition::lessOrEquals($column, $value);

        return $this;
    }

    /**
     * @param string $column
     * @param $value
     *
     * @return Filter
     */
    public function greaterThan(string $column, $value): self
    {
        $this->conditionList[] = ValueComparisonCondition::greaterThan($column, $value);

        return $this;
    }

    /**
     * @param string $column
     * @param $value
     *
     * @return Filter
     */
    public function greaterOrEquals(string $column, $value): self
    {
        $this->conditionList[] = ValueComparisonCondition::greaterOrEquals($column, $value);

        return $this;
    }

    /**
     * @param string $column
     *
     * @return Filter
     */
    public function isNull(string $column): self
    {
        $this->conditionList[] = new NullableValueCondition($column, true);

        return $this;
    }

    /**
     * @param string $column
     *
     * @return Filter
     */
    public function isNotNull(string $column): self
    {
        $this->conditionList[] = new NullableValueCondition($column, false);

        return $this;
    }

    /**
     * @param string $column
     * @param array $valueList
     *
     * @return Filter
     */
    public function in(string $column, array $valueList): self
    {
        $this->conditionList[] = new ValueArrayCondition($column, $valueList, true);

        return $this;
    }

    /**
     * @param string $column
     * @param array $valueList
     *
     * @return Filter
     */
    public function notIn(string $column, array $valueList): self
    {
        $this->conditionList[] = new ValueArrayCondition($column, $valueList, false);

        return $this;
    }

    /**
     * @param string $column
     * @param string $value
     * @param bool $strictFromBeginning
     * @param bool $strictToEnd
     *
     * @return Filter
     */
    public function like(string $column, string $value, bool $strictFromBeginning = false, bool $strictToEnd = false): self
    {
        $this->conditionList[] = new ValueLikeCondition($column, $value, $strictFromBeginning, $strictToEnd);

        return $this;
    }

    /**
     * @param string $expression
     *
     * @return Filter
     */
    public function rawSql(string $expression): self
    {
        $this->conditionList[] = new RawSqlCondition($expression);

        return $this;
    }

    /**
     * @param array $boolList
     *
     * @return Filter
     */
    public function deleted(array $boolList): self
    {
        $this->conditionList[] = new DeletedRowCondition(
            in_array(false, $boolList, true),
            in_array(true, $boolList, true)
        );

        return $this;
    }
}
