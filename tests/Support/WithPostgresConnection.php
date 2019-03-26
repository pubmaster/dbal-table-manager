<?php

namespace Tests\DBALTableManager\Support;

use Doctrine\DBAL\DriverManager;
use Tests\DBALTableManager\Support\DefaultConnection as DBALDefaultConnection;

/**
 * Trait WithPostgresConnection
 *
 * @package Tests\DBALTableManager\Support
 */
trait WithPostgresConnection
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

        $username = 'postgres';
        $password = 'postgres';
        $host = 'postgres';
        $port = '5432';
        $dbName = 'postgres';

        static::$pdo = new \PDO("pgsql:host=$host;dbname=$dbName;port=$port", $username, $password);

        return static::$pdo;
    }

    /**
     * @return string
     */
    protected function getDbNameForTests(): string
    {
        return 'postgres';
    }
}
