<?php

namespace Tests;

use DBALTableManager\BaseConnectionInterface;
use DBALTableManager\Util\StringUtils;
use DBALTableManager\Util\TypeConverter;
use Doctrine\DBAL\DriverManager;
use Tests\Support\DefaultConnection as DBALDefaultConnection;
use Tests\Support\DefaultTestManager;

/**
 * Class BaseManagerMysqlTest
 * @package Tests
 */
class BaseManagerMysqlTest extends BaseManagerTestFoundation
{
    /**
     * @var \PDO
     */
    static protected $pdo;

    /**
     * @return string
     */
    public function getDbNameForTests(): string
    {
        return 'db_for_tests';
    }

    protected function setUp(): void
    {
        /** @var BaseConnectionInterface $connection */
        $this->dbalConnection = DriverManager::getConnection([
            'pdo' => $this->getPdo(),
            'wrapperClass' => DBALDefaultConnection::class,
        ]);

        $this->typeConverter = new TypeConverter();
        $this->manager = new DefaultTestManager($this->dbalConnection, $this->typeConverter, new StringUtils());

        $this->getPdo()->exec('CREATE DATABASE IF NOT EXISTS `' . $this->getDbNameForTests() . '`');
        $this->getPdo()->exec('USE `' . $this->getDbNameForTests() . '`');
        $this->getPdo()->exec("
            CREATE TABLE IF NOT EXISTS `user_table` (
                `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
                `name` VARCHAR(255) NOT NULL COLLATE 'utf8mb4_unicode_ci',
                `birthday` DATE NULL DEFAULT NULL,
                `age` INT(11) NOT NULL,
                `weight` FLOAT NOT NULL,
                `married` TINYINT(4) NOT NULL,
                `created_at` TIMESTAMP NULL DEFAULT NULL,
                `updated_at` TIMESTAMP NULL DEFAULT NULL,
                `deleted_at` TIMESTAMP NULL DEFAULT NULL,
                PRIMARY KEY (`id`)
            )
            ;"
        );

        parent::setUp();
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
}
