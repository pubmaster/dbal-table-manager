<?php

namespace Tests\DBALTableManager\Support;

use Doctrine\DBAL\DriverManager;
use Tests\DBALTableManager\Support\DefaultConnection as DBALDefaultConnection;

/**
 * Trait WithMysqlConnection
 *
 * @package Tests\DBALTableManager\Support
 */
trait WithMysqlConnection
{
    /**
     * @var \PDO
     */
    static protected $pdo;

    protected function makeConnection(): DBALDefaultConnection
    {
        /** @var DBALDefaultConnection $connection */
        $connection = DriverManager::getConnection([
            'pdo' => $this->getPdo(),
            'wrapperClass' => DBALDefaultConnection::class,
        ]);

        $connection->getConfiguration()->setSQLLogger(new EchoSQLLogger());

        return $connection;
    }

    /**
     * @return \PDO
     */
    protected function getPdo(): \PDO
    {
        if (static::$pdo !== null) {
            return static::$pdo;
        }

        $username = 'root';
        $password = 'nopassword';
        $host = 'mysql';
        $port = '3306';

        static::$pdo = new \PDO("mysql:host=$host;port=$port", $username, $password);

        return static::$pdo;
    }

    /**
     * @return string
     */
    protected function getDbNameForTests(): string
    {
        return 'db_for_tests';
    }
}
