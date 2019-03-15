<?php

namespace DBALTableManager;

use DBALTableManager\Condition\ColumnableCondition;
use DBALTableManager\Condition\DeletedRowCondition;
use DBALTableManager\Condition\NullableValueCondition;
use DBALTableManager\Condition\RawSqlCondition;
use DBALTableManager\Condition\ValueArrayCondition;
use DBALTableManager\Condition\ValueComparisonCondition;
use DBALTableManager\Condition\ValueLikeCondition;
use DBALTableManager\Entity\EntityInterface;
use DBALTableManager\Exception\EntityDefinitionException;
use DBALTableManager\Exception\InvalidRequestException;
use DBALTableManager\Util\StringUtils;
use DBALTableManager\Util\TypeConverter;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ParameterType;
use Doctrine\DBAL\Query\QueryBuilder;

/**
 * Class ManagerFoundation
 *
 * @package DBALTableManager
 */
abstract class ManagerFoundation
{
    /**
     * @var BaseConnectionInterface
     */
    protected $connection;
    /**
     * @var TypeConverter
     */
    protected $typeConverter;
    /**
     * @var StringUtils
     */
    protected $stringUtils;

    /**
     * FoundationManager constructor.
     *
     * @param BaseConnectionInterface $connection
     * @param TypeConverter $typeConverter
     * @param StringUtils $stringUtils
     */
    public function __construct(
        BaseConnectionInterface $connection,
        TypeConverter $typeConverter,
        StringUtils $stringUtils
    ) {
        $this->connection = $connection;
        $this->typeConverter = $typeConverter;
        $this->stringUtils = $stringUtils;
    }

    /**
     * @return EntityInterface
     */
    abstract public function getEntity(): EntityInterface;

    /**
     * @param array $row
     *
     * @return array
     */
    protected function prepareRow(array $row): array
    {
        return $this->typeConverter->convert($row, $this->getFieldMap());
    }

    /**
     * @return array
     */
    abstract protected function getFieldMap(): array;

    /**
     * @param QueryBuilder $query
     * @param Filter|null $filter
     */
    protected function applyFilters(QueryBuilder $query, ?Filter $filter): void
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
                if ($condition->getValues() !== []) {
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
                $this->checkSoftDeletableEntity();

                $showNotDeleted = $condition->isShowNotDeleted();
                $showDeleted = $condition->isShowDeleted();
                if ($showNotDeleted && $showDeleted) {
                    // show all
                } else if ($showNotDeleted) {
                    $query->andWhere($this->prepareColumnName($this->getEntity()->getDeletedAtField()) . ' IS NULL');
                } else if ($showDeleted) {
                    $query->andWhere($this->prepareColumnName($this->getEntity()->getDeletedAtField()) . ' IS NOT NULL');
                }

                $hasDeletedAtFilter = true;
            }
        }

        if (!$hasDeletedAtFilter && $this->getEntity()->isSoftDeletable()) {
            $query->andWhere($this->prepareColumnName($this->getEntity()->getDeletedAtField()) . ' IS NULL');
        }
    }

    /**
     * @param QueryBuilder $query
     * @param Sorting|null $sorting
     */
    protected function applyOrderBy(QueryBuilder $query, ?Sorting $sorting): void
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
    protected function applyPkFilterToQuery(QueryBuilder $query, $pk, bool $withDeleted = false): void
    {
        if ($this->getEntity()->getPrimaryKey() === []) {
            throw EntityDefinitionException::withNoPrimaryKeyDefined();
        }

        if (!is_array($pk)) {
            $firstPkColumn = $this->getEntity()->getPrimaryKey()[0];
            $query->andWhere($this->prepareColumnName($firstPkColumn) . ' = ' . $query->createNamedParameter($pk, $this->getPdoType($firstPkColumn)));
        } else {
            $this->checkColumnList(array_keys($pk));

            foreach ($this->getEntity()->getPrimaryKey() as $pkColumn) {
                if (!isset($pk[$pkColumn])) {
                    throw InvalidRequestException::withNoPrimaryKeyValue($pkColumn);
                }
                $query->andWhere($this->prepareColumnName($pkColumn) . ' = ' . $query->createNamedParameter($pk[$pkColumn], $this->getPdoType($pkColumn)));
            }
        }

        if (false === $withDeleted && $this->getEntity()->isSoftDeletable()) {
            $query->andWhere($this->prepareColumnName($this->getEntity()->getDeletedAtField()) . ' IS NULL');
        }
    }

    /**
     * @param string $columnName
     *
     * @return string
     */
    protected function prepareColumnName(string $columnName): string
    {
        return $columnName;
    }

    /**
     * @param string[] $columnList
     */
    protected function checkColumnList(array $columnList): void
    {
        $unknownColumns = array_diff($columnList, array_keys($this->getFieldMap()));
        if ($unknownColumns !== []) {
            throw InvalidRequestException::withUnknownColumnList($unknownColumns);
        }
    }

    protected function checkTimestampableEntity(): void
    {
        if ($this->getEntity()->isTimestampable()) {
            return;
        }

        $createdAtField = $this->getEntity()->getCreatedAtField();
        if ($createdAtField === null || $createdAtField === '') {
            throw EntityDefinitionException::withNoCreatedAtColumnDefined();
        }

        $updatedAtField = $this->getEntity()->getUpdatedAtField();
        if ($updatedAtField === null || $updatedAtField === '') {
            throw EntityDefinitionException::withNoUpdatedAtColumnDefined();
        }
    }

    protected function checkSoftDeletableEntity(): void
    {
        if ($this->getEntity()->isSoftDeletable()) {
            return;
        }

        $deletedAtField = $this->getEntity()->getDeletedAtField();
        if ($deletedAtField === null || $deletedAtField === '') {
            throw EntityDefinitionException::withNoDeletedAtColumnDefined();
        }
    }

    /**
     * @param string $columnName
     *
     * @return int
     */
    protected function getPdoType(string $columnName): int
    {
        $this->checkColumnList([$columnName]);
        $columnType = $this->getFieldMap()[$columnName];

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
