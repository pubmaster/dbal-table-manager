<?php

namespace DBALTableManager\Manager;

use DBALTableManager\Query\Filter;
use DBALTableManager\Query\FilterInterface;

/**
 * Interface DataManipulationInterface
 *
 * @package DBALTableManager\Manager
 */
interface DataManipulationInterface
{
    /**
     * @param $data
     *
     * @return string
     */
    public function insert(array $data): string;

    /**
     * @param $data
     *
     * @return int
     */
    public function batchInsert(array $data): int;

    /**
     * @param FilterInterface $filter
     * @param array $data
     *
     * @return int
     */
    public function updateByFilter(FilterInterface $filter, array $data): int;

    /**
     * @param $pk
     * @param array $data
     *
     * @return int
     */
    public function updateByPk($pk, array $data): int;

    /**
     * @param array $data
     * @param Filter[] $filterList
     *
     * @return int
     */
    public function batchUpdate(array $data, array $filterList): int;

    /**
     * @param FilterInterface $filter
     *
     * @return int
     */
    public function deleteByFilter(FilterInterface $filter): int;

    /**
     * @param $pk
     *
     * @return int
     */
    public function deleteByPk($pk): int;

    /**
     * @return int
     */
    public function deleteAll(): int;

    /**
     * @param FilterInterface $filter
     *
     * @return int
     */
    public function softDeleteByFilter(FilterInterface $filter): int;

    /**
     * @param $pk
     *
     * @return int
     */
    public function softDeleteByPk($pk): int;

    /**
     * @return int
     */
    public function softDeleteAll(): int;

    public function truncate(): void;
}
