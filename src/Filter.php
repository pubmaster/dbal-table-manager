<?php

namespace DBALTableManager;

use DBALTableManager\Condition\DeletedRowCondition;
use DBALTableManager\Condition\NullableValueCondition;
use DBALTableManager\Condition\ValueArrayCondition;
use DBALTableManager\Condition\ValueComparisonCondition;

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
        $this->conditionList[] = new ValueComparisonCondition($column, '=', $value);

        return $this;
    }

    /**
     * @param $column
     * @param $value
     *
     * @return Filter
     */
    public function notEquals($column, $value): self
    {
        $this->conditionList[] = new ValueComparisonCondition($column, '<>', $value);

        return $this;
    }

    /**
     * @param $column
     * @param $value
     *
     * @return Filter
     */
    public function lessThan($column, $value): self
    {
        $this->conditionList[] = new ValueComparisonCondition($column, '<', $value);

        return $this;
    }

    /**
     * @param $column
     * @param $value
     *
     * @return Filter
     */
    public function lessOrEquals($column, $value): self
    {
        $this->conditionList[] = new ValueComparisonCondition($column, '<=', $value);

        return $this;
    }

    /**
     * @param $column
     * @param $value
     *
     * @return Filter
     */
    public function greaterThan($column, $value): self
    {
        $this->conditionList[] = new ValueComparisonCondition($column, '>', $value);

        return $this;
    }

    /**
     * @param $column
     * @param $value
     *
     * @return Filter
     */
    public function greaterOrEquals($column, $value): self
    {
        $this->conditionList[] = new ValueComparisonCondition($column, '>=', $value);

        return $this;
    }

    /**
     * @param $column
     *
     * @return Filter
     */
    public function isNull($column): self
    {
        $this->conditionList[] = new NullableValueCondition($column, true);

        return $this;
    }

    /**
     * @param $column
     *
     * @return Filter
     */
    public function isNotNull($column): self
    {
        $this->conditionList[] = new NullableValueCondition($column, false);

        return $this;
    }

    /**
     * @param $column
     * @param $valueList
     *
     * @return Filter
     */
    public function in($column, $valueList): self
    {
        $this->conditionList[] = new ValueArrayCondition($column, $valueList, true);

        return $this;
    }

    /**
     * @param $column
     * @param $valueList
     *
     * @return Filter
     */
    public function notIn($column, $valueList): self
    {
        $this->conditionList[] = new ValueArrayCondition($column, $valueList, false);

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
