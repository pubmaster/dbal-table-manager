<?php

namespace Tests\DBALTableManager\Manager;

use DBALTableManager\EntityTransformer\EntityTransformer;
use DBALTableManager\Factory\TableManagerFactory;
use DBALTableManager\Util\StringUtils;
use DBALTableManager\Util\TypeConverter;
use PHPUnit\DbUnit\Operation\Factory;
use PHPUnit\DbUnit\Operation\Operation;
use Tests\DBALTableManager\Support\CurrentTimeStub;
use Tests\DBALTableManager\Support\DatabaseTableDataRetriever;
use Tests\DBALTableManager\Support\DefaultTestEntity;
use Tests\DBALTableManager\Support\DefaultTestTemporalVersionEntity;
use Tests\DBALTableManager\Support\WithPostgresConnection;

/**
 * Class SingleTableManagerPostgresTest
 *
 * @package Tests\DBALTableManager\Manager
 */
class TemporalTableManagerPostgresTest extends TemporalTableManagerTestFoundation
{
    use WithPostgresConnection;

    protected function setUp(): void
    {
        $dbalConnection = $this->makeConnection();

        $this->currentTime = new CurrentTimeStub();

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
            new EntityTransformer(),
            $this->currentTime
        );

        $this->manager = $tableManagerFactory->makeManagerForTemporalTable(
            $dbalConnection,
            new DefaultTestEntity(),
            new DefaultTestTemporalVersionEntity()
        );

        $this->getPdo()->exec('DROP SEQUENCE IF EXISTS user_id_seq CASCADE;');
        $this->getPdo()->exec('DROP TABLE IF EXISTS "user_table_version"');
        $this->getPdo()->exec('DROP TABLE IF EXISTS "user_table"');

        $this->getPdo()->exec('CREATE SEQUENCE user_id_seq RESTART WITH 5;');
        $this->getPdo()->exec('
            CREATE TABLE IF NOT EXISTS "user_table" (
                "id" integer NOT NULL DEFAULT nextval(\'user_id_seq\'),
                "name" varchar,
                "birthday" DATE NULL DEFAULT NULL,
                "age" integer NOT NULL,
                "weight" float NOT NULL,
                "married" bool NOT NULL,
                "created_at" TIMESTAMP NULL DEFAULT NULL,
                "updated_at" TIMESTAMP NULL DEFAULT NULL,
                "deleted_at" TIMESTAMP NULL DEFAULT NULL,
                PRIMARY KEY (id)
            )
           ;'
        );
        $this->getPdo()->exec('ALTER SEQUENCE user_id_seq OWNED BY user_table.id;');

        $this->getPdo()->exec('
            CREATE TABLE IF NOT EXISTS "user_table_version" (
                "user_id" integer NOT NULL,
                "effective_since" DATE NOT NULL,
                "created_at" TIMESTAMP NOT NULL,
                "salary" integer NOT NULL,
                "fired" bool NOT NULL,
                PRIMARY KEY (user_id, effective_since, created_at),
                FOREIGN KEY (user_id) REFERENCES "user_table" (id) ON DELETE CASCADE 
            )
           ;'
        );

        parent::setUp();
    }

    protected function tearDown(): void
    {
        $this->currentTime->reset();

        parent::tearDown();
    }

    /**
     * Returns the database operation executed in test setup.
     *
     * @return Operation
     */
    protected function getSetUpOperation()
    {
        return Factory::CLEAN_INSERT(true);
    }
}
