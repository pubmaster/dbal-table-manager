<?php

namespace DBALTableManager\QueryBuilder;

use DBALTableManager\Entity\EntityInterface;
use DBALTableManager\EntityValidator\EntityValidator;
use DBALTableManager\Exception\EntityDefinitionException;
use DBALTableManager\Exception\InvalidRequestException;
use DBALTableManager\Query\Condition\ColumnableCondition;
use DBALTableManager\Query\Condition\DeletedRowCondition;
use DBALTableManager\Query\Condition\NullableValueCondition;
use DBALTableManager\Query\Condition\RawSqlCondition;
use DBALTableManager\Query\Condition\ValueArrayCondition;
use DBALTableManager\Query\Condition\ValueComparisonCondition;
use DBALTableManager\Query\Condition\ValueLikeCondition;
use DBALTableManager\Query\FilterInterface;
use DBALTableManager\Query\SortingInterface;
use DBALTableManager\SchemaDescription\SchemaDescriptionInterface;
use DBALTableManager\Util\StringUtils;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ParameterType;
use Doctrine\DBAL\Query\QueryBuilder;

/**
 * Class QueryBuilderPreparer
 *
 * @package DBALTableManager\QueryBuilder
 */
class QueryBuilderPreparer
{
    /**
     * @var EntityInterface
     */
    private $entity;
    /**
     * @var SchemaDescriptionInterface
     */
    private $schemaDescription;
    /**
     * @var EntityValidator
     */
    private $entityValidator;
    /**
     * @var StringUtils
     */
    private $stringUtils;

    /**
     * QueryBuilderPreparer constructor.
     *
     * @param EntityInterface $entity
     * @param SchemaDescriptionInterface $schemaDescription
     * @param EntityValidator $entityValidator
     * @param StringUtils $stringUtils
     */
    public function __construct(
        EntityInterface $entity,
        SchemaDescriptionInterface $schemaDescription,
        EntityValidator $entityValidator,
        StringUtils $stringUtils
    )
    {
        $this->entity = $entity;
        $this->schemaDescription = $schemaDescription;
        $this->entityValidator = $entityValidator;
        $this->stringUtils = $stringUtils;
    }

    /**
     * @param QueryBuilder $query
     * @param FilterInterface|null $filter
     */
    public function applyFilters(QueryBuilder $query, ?FilterInterface $filter): void
    {
        $conditionList = [];
        if ($filter !== null) {
            $conditionList = $filter->getConditionList();
        }

        $columnList = [];
        foreach ($conditionList as $condition) {
            if ($condition instanceof ColumnableCondition) {
                $columnList[] = $condition->getColumn();
            }
        }
        $this->checkColumnList($columnList);

        $hasDeletedAtFilter = false;

        foreach ($conditionList as $condition) {
            if ($condition instanceof ValueComparisonCondition) {
                $query->andWhere(
                    $this->prepareColumnName($condition->getColumn())
                    . ' '
                    . $condition->getOperator()
                    . ' '
                    . $query->createNamedParameter($condition->getValue(), $this->getPdoType($condition->getColumn()))
                );
            }

            else if ($condition instanceof NullableValueCondition) {
                if ($condition->isNull()) {
                    $query->andWhere($this->prepareColumnName($condition->getColumn()) . ' IS NULL');
                } else {
                    $query->andWhere($this->prepareColumnName($condition->getColumn()) . ' IS NOT NULL');
                }
            }

            else if ($condition instanceof ValueArrayCondition) {
                if ($condition->getValues() !== [] || $condition->isEmptyAsNoFilter() === false) {
                    $pdoType = $this->getPdoType($condition->getColumn());
                    if ($pdoType === ParameterType::INTEGER) {
                        $type = Connection::PARAM_INT_ARRAY;
                    } else {
                        $type = Connection::PARAM_STR_ARRAY;
                    }

                    $param = $query->createNamedParameter($condition->getValues(), $type);
                    if ($condition->isIncluded()) {
                        $query->andWhere($this->prepareColumnName($condition->getColumn()) . ' IN (' . $param . ')');
                    } else {
                        $query->andWhere($this->prepareColumnName($condition->getColumn()) . ' NOT IN (' . $param . ')');
                    }
                }
            }

            else if ($condition instanceof ValueLikeCondition) {
                $value = ($condition->isStrictFromBeginning() ? '' : '%')
                    . $this->stringUtils->prepareSqlLikeOperator($condition->getValue())
                    . ($condition->isStrictToEnd() ? '' : '%');
                $query->andWhere(
                    $this->prepareColumnName($condition->getColumn())
                    . ' LIKE ' .
                    $query->createNamedParameter($value, $this->getPdoType($condition->getColumn()))
                );
            }

            else if ($condition instanceof RawSqlCondition) {
                $query->andWhere($condition->getExpression());
            }

            else if ($condition instanceof DeletedRowCondition) {
                $this->entityValidator->checkSoftDeletableEntity();

                $showNotDeleted = $condition->isShowNotDeleted();
                $showDeleted = $condition->isShowDeleted();
                if ($showNotDeleted && $showDeleted) {
                    // show all
                } else if ($showNotDeleted) {
                    $query->andWhere($this->prepareColumnName($this->entity->getDeletedAtField()) . ' IS NULL');
                } else if ($showDeleted) {
                    $query->andWhere($this->prepareColumnName($this->entity->getDeletedAtField()) . ' IS NOT NULL');
                }

                $hasDeletedAtFilter = true;
            }
        }

        if (!$hasDeletedAtFilter && $this->entity->isSoftDeletable()) {
            $query->andWhere($this->prepareColumnName($this->entity->getDeletedAtField()) . ' IS NULL');
        }
    }

    /**
     * @param QueryBuilder $query
     * @param SortingInterface|null $sorting
     */
    public function applyOrderBy(QueryBuilder $query, ?SortingInterface $sorting): void
    {
        $sortList = [];
        if ($sorting !== null) {
            $sortList = $sorting->getSortList();
        }

        $columnList = [];
        foreach ($sortList as $sort) {
            $columnList[] = $sort->getColumn();
        }
        $this->checkColumnList($columnList);

        foreach ($sortList as $sort) {
            $query->addOrderBy($this->prepareColumnName($sort->getColumn()), $sort->getOrder());
        }
    }

    /**
     * @param QueryBuilder $query
     * @param mixed $pk
     * @param bool $withDeleted
     */
    public function applyPkFilterToQuery(QueryBuilder $query, $pk, bool $withDeleted = false): void
    {
        if ($this->entity->getPrimaryKey() === []) {
            throw EntityDefinitionException::withNoPrimaryKeyDefined();
        }

        if (!is_array($pk)) {
            $firstPkColumn = $this->entity->getPrimaryKey()[0];
            $query->andWhere($this->prepareColumnName($firstPkColumn) . ' = ' . $query->createNamedParameter($pk, $this->getPdoType($firstPkColumn)));
        } else {
            $this->checkColumnList(array_keys($pk));

            foreach ($this->entity->getPrimaryKey() as $pkColumn) {
                if (!isset($pk[$pkColumn])) {
                    throw InvalidRequestException::withNoPrimaryKeyValue($pkColumn);
                }
                $query->andWhere($this->prepareColumnName($pkColumn) . ' = ' . $query->createNamedParameter($pk[$pkColumn], $this->getPdoType($pkColumn)));
            }
        }

        if (false === $withDeleted && $this->entity->isSoftDeletable()) {
            $query->andWhere($this->prepareColumnName($this->entity->getDeletedAtField()) . ' IS NULL');
        }
    }

    /**
     * @param string $columnName
     *
     * @return string
     */
    public function prepareColumnName(string $columnName): string
    {
        return $this->schemaDescription->getPreparedColumnForQuery($columnName);
    }

    /**
     * @param string[] $columnList
     */
    public function checkColumnList(array $columnList): void
    {
        $unknownColumns = array_diff($columnList, $this->schemaDescription->getColumnList());
        if ($unknownColumns !== []) {
            throw InvalidRequestException::withUnknownColumnList($unknownColumns);
        }
    }

    /**
     * @param string $columnName
     *
     * @return int
     */
    public function getPdoType(string $columnName): int
    {
        $columnType = $this->schemaDescription->getColumnType($columnName);

        switch ($columnType) {
            case 'bool':
            case 'boolean':
                return ParameterType::BOOLEAN;
            case 'int':
            case 'integer':
                return ParameterType::INTEGER;
            default:
                return ParameterType::STRING;
        }
    }
}
