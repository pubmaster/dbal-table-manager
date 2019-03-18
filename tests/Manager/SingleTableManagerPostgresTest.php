<?php


namespace Tests\Manager;

use DBALTableManager\EntityTransformer\EntityTransformer;
use DBALTableManager\Factory\TableManagerFactory;
use DBALTableManager\Util\StringUtils;
use DBALTableManager\Util\TypeConverter;
use Tests\Support\DatabaseTableDataRetriever;
use Tests\Support\DefaultTestEntity;
use Tests\Support\WithPostgresConnection;

/**
 * Class SingleTableManagerPostgresTest
 *
 * @package Tests\Manager
 */
class SingleTableManagerPostgresTest extends SingleTableManagerTestFoundation
{
    use WithPostgresConnection;

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

        $this->getPdo()->exec('DROP SEQUENCE IF EXISTS user_id_seq CASCADE;');
        $this->getPdo()->exec('CREATE SEQUENCE user_id_seq RESTART WITH 5;');
        $this->getPdo()->exec('DROP TABLE IF EXISTS "user_table"');
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

        parent::setUp();
    }
}
