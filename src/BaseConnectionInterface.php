<?php

namespace DBALTableManager;

use Doctrine\DBAL\Driver\Connection;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Query\QueryBuilder;

/**
 * Interface BaseConnectionInterface
 *
 * @package App\Core\Database\Connection
 */
interface BaseConnectionInterface extends Connection
{
    /**
     * @return QueryBuilder
     */
    public function createQueryBuilder();

    /**
     * @return bool
     */
    public function ping();

    /**
     * @return bool
     */
    public function close();

    /**
     * @param $tableExpression
     * @param array $data
     * @param array $types
     *
     * @return mixed
     */
    public function insert($tableExpression, array $data, array $types = []);

    /**
     * @param $tableExpression
     * @param array $data
     * @param array $identifier
     * @param array $types
     *
     * @return mixed
     */
    public function update($tableExpression, array $data, array $identifier, array $types = []);

    /**
     * @param $tableExpression
     * @param array $identifier
     * @param array $types
     *
     * @return mixed
     */
    public function delete($tableExpression, array $identifier, array $types = []);

    /**
     * Gets the DatabasePlatform for the connection.
     *
     * @return AbstractPlatform
     */
    public function getDatabasePlatform();
}
