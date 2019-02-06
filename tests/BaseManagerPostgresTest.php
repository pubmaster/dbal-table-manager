<?php


namespace Tests;

use DBALTableManager\BaseConnectionInterface;
use DBALTableManager\Util\StringUtils;
use DBALTableManager\Util\TypeConverter;
use Doctrine\DBAL\DriverManager;
use Tests\Support\DefaultConnection as DBALDefaultConnection;
use Tests\Support\DefaultTestManager;


/**
 * Class BaseManagerPostgresTest
 * @package Tests
 */
class BaseManagerPostgresTest extends BaseManagerTestFoundation
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
        return 'postgres';
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
        $dbName = $this->getDbNameForTests();

        static::$pdo = new \PDO("pgsql:host=$host;dbname=$dbName;port=$port", $username, $password);

        return static::$pdo;
    }
}
