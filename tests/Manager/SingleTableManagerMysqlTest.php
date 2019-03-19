<?php

namespace Tests\Manager;

use DBALTableManager\EntityTransformer\EntityTransformer;
use DBALTableManager\Factory\TableManagerFactory;
use DBALTableManager\Util\StringUtils;
use DBALTableManager\Util\TypeConverter;
use Tests\Support\DatabaseTableDataRetriever;
use Tests\Support\DefaultTestEntity;
use Tests\Support\WithMysqlConnection;

/**
 * Class SingleTableManagerMysqlTest
 *
 * @package Tests\Manager
 */
class SingleTableManagerMysqlTest extends SingleTableManagerTestFoundation
{
    use WithMysqlConnection;

    protected function setUp(): void
    {
        $dbalConnection = $this->makeConnection();

        $typeConverter = new TypeConverter();

        $this->dataRetriever = new DatabaseTableDataRetriever(
            $dbalConnection,
            $typeConverter,
            DefaultTestEntity::TABLE_NAME
        );

        $tableManagerFactory = new TableManagerFactory(
            $typeConverter,
            new StringUtils(),
            new EntityTransformer()
        );

        $this->manager = $tableManagerFactory->makeManagerForSingleTable(
            $dbalConnection,
            new DefaultTestEntity()
        );

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
}
