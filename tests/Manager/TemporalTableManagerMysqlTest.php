<?php

namespace Tests\Manager;

use DBALTableManager\EntityTransformer\EntityTransformer;
use DBALTableManager\Factory\TableManagerFactory;
use DBALTableManager\Util\StringUtils;
use DBALTableManager\Util\TypeConverter;
use Tests\Support\DatabaseTableDataRetriever;
use Tests\Support\DefaultTestEntity;
use Tests\Support\DefaultTestTemporalVersionEntity;
use Tests\Support\WithMysqlConnection;

/**
 * Class TemporalTableManagerMysqlTest
 *
 * @package Tests\Manager
 */
class TemporalTableManagerMysqlTest extends TemporalTableManagerTestFoundation
{
    use WithMysqlConnection;

    protected function setUp(): void
    {
        $dbalConnection = $this->makeConnection();

        $typeConverter = new TypeConverter();

        $this->staticDataRetriever = new DatabaseTableDataRetriever(
            $dbalConnection,
            $typeConverter,
            DefaultTestEntity::TABLE_NAME
        );
        $this->versionDataRetriever = new DatabaseTableDataRetriever(
            $dbalConnection,
            $typeConverter,
            DefaultTestTemporalVersionEntity::TABLE_NAME
        );

        $tableManagerFactory = new TableManagerFactory(
            $typeConverter,
            new StringUtils(),
            new EntityTransformer()
        );

        $this->manager = $tableManagerFactory->makeManagerForTemporalTable(
            $dbalConnection,
            new DefaultTestEntity(),
            new DefaultTestTemporalVersionEntity()
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
        $this->getPdo()->exec("
            CREATE TABLE IF NOT EXISTS `user_table_version` (
                `user_id` INT(10) UNSIGNED NOT NULL,
                `effective_since` DATE NOT NULL,
                `created_at` DATETIME NOT NULL,
                `salary` INT(11) NOT NULL,
                `fired` TINYINT(4) NOT NULL,
                PRIMARY KEY (`user_id`, `effective_since`, `created_at`),
                CONSTRAINT `FK_user_table_version_user_table` FOREIGN KEY (`user_id`) REFERENCES `user_table` (`id`) ON DELETE CASCADE
            )
            COLLATE='utf8mb4_unicode_ci'
            ENGINE=InnoDB
            ;
            "
        );

        parent::setUp();
    }
}
