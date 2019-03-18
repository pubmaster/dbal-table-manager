<?php

namespace DBALTableManager\Manager;

use DBALTableManager\Query\Filter;

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
     * @param Filter $filter
     * @param array $data
     *
     * @return int
     */
    public function updateByFilter(Filter $filter, array $data): int;

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
     * @param Filter $filter
     *
     * @return int
     */
    public function deleteByFilter(Filter $filter): int;

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
     * @param Filter $filter
     *
     * @return int
     */
    public function softDeleteByFilter(Filter $filter): int;

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
