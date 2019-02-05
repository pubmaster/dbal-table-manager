<?php

namespace Tests;

use DBALTableManager\BaseConnectionInterface;
use DBALTableManager\BaseManager;
use DBALTableManager\Exception\InvalidRequestException;
use DBALTableManager\Filter;
use DBALTableManager\Pagination;
use DBALTableManager\Sorting;
use DBALTableManager\Util\StringUtils;
use DBALTableManager\Util\TypeConverter;
use Doctrine\DBAL\DriverManager;
use PHPUnit\DbUnit\Database\DefaultConnection as DbUnitDefaultConnection;
use PHPUnit\DbUnit\TestCase;
use Tests\Support\DefaultConnection as DBALDefaultConnection;
use Tests\Support\DefaultTestEntity;
use Tests\Support\DefaultTestManager;

/**
 * Class BaseManagerTest
 *
 * @package Tests
 */
class BaseManagerTest extends TestCase
{
    private const DB_FOR_TESTS = 'db_for_tests';
    private const USER_1 = [
        'id' => 1,
        'name' => 'John',
        'birthday' => '2010-01-02',
        'age' => 22,
        'weight' => 40.4,
        'married' => 1,
        'created_at' => '2018-04-04 12:44:22',
        'updated_at' => '2018-04-04 12:44:22',
        'deleted_at' => null,
    ];
    private const USER_2 = [
        'id' => 2,
        'name' => 'Mister X',
        'birthday' => '2010-02-02',
        'age' => 13,
        'weight' => 30.4,
        'married' => 0,
        'created_at' => '2018-05-04 12:44:22',
        'updated_at' => '2018-05-04 12:44:22',
        'deleted_at' => null,
    ];
    private const USER_3 = [
        'id' => 3,
        'name' => 'Soft Deleted User',
        'birthday' => '2010-03-02',
        'age' => 33,
        'weight' => 20.4,
        'married' => 1,
        'created_at' => '2018-06-04 12:44:22',
        'updated_at' => '2018-06-04 12:44:22',
        'deleted_at' => '2018-06-04 12:44:22',
    ];
    private const USER_4 = [
        'id' => 4,
        'name' => 'Someone',
        'birthday' => null,
        'age' => 44,
        'weight' => 50.4,
        'married' => 0,
        'created_at' => '2018-05-04 12:44:22',
        'updated_at' => '2018-05-04 12:44:22',
        'deleted_at' => null,
    ];
    /**
     * @var \PDO
     */
    static private $pdo;
    /**
     * @var BaseConnectionInterface
     */
    private $dbalConnection;
    /**
     * @var DbUnitDefaultConnection
     */
    private $phpUnitDbConnection;
    /**
     * @var BaseManager
     */
    private $manager;
    /**
     * @var TypeConverter
     */
    private $typeConverter;

    protected function setUp(): void
    {
        /** @var BaseConnectionInterface $connection */
        $this->dbalConnection = DriverManager::getConnection([
            'pdo' => $this->getPdo(),
            'wrapperClass' => DBALDefaultConnection::class,
        ]);
//        $this->dbalConnection->getConfiguration()->setSQLLogger(new \Tests\Support\EchoSQLLogger());

        $this->typeConverter = new TypeConverter();
        $this->manager = new DefaultTestManager($this->dbalConnection, $this->typeConverter, new StringUtils());

        $this->getPdo()->exec('CREATE DATABASE IF NOT EXISTS `' . self::DB_FOR_TESTS . '`');
        $this->getPdo()->exec('USE `' . self::DB_FOR_TESTS . '`');
        $this->getPdo()->exec(<<<SQL
CREATE TABLE IF NOT EXISTS `user` (
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
COLLATE='utf8mb4_unicode_ci'
ENGINE=InnoDB
;
SQL
        );

        parent::setUp();
    }

    /**
     * @return \PDO
     */
    protected function getPdo(): \PDO
    {
        if (self::$pdo !== null) {
            return self::$pdo;
        }

        $username = 'root';
        $password = 'nopassword';
        $host = 'mysql';
        $port = '3306';

        self::$pdo = new \PDO("mysql:host=$host;port=$port", $username, $password);

        return self::$pdo;
    }

    protected function getConnection()
    {
        if ($this->phpUnitDbConnection !== null) {
            return $this->phpUnitDbConnection;
        }

        if (self::$pdo === null) {
            self::$pdo = new \PDO('sqlite::memory:');
        }

        $this->phpUnitDbConnection = $this->createDefaultDBConnection($this->getPdo(), self::DB_FOR_TESTS);

        return $this->phpUnitDbConnection;
    }

    public function getDataSet()
    {
        return $this->createArrayDataSet(
            [
                DefaultTestEntity::TABLE_NAME => [
                    self::USER_1,
                    self::USER_2,
                    self::USER_3,
                    self::USER_4,
                ],
            ]
        );
    }

    public function testSuccessFindAllWithoutAnything(): void
    {
        // arrange
        $notDeletedUsers = [
            self::USER_1,
            self::USER_2,
            self::USER_4,
        ];

        // action
        $resultUsers = $this->manager->findAll(new Filter(), new Pagination(), new Sorting());

        // assert
        self::assertCount(count($notDeletedUsers), $resultUsers);
    }

    public function testSuccessFindAllWithFilterEquals(): void
    {
        // arrange
        $targetUsers = [
            self::USER_4,
        ];

        $filter = new Filter();
        $filter->equals('name', 'Someone');

        // action
        $resultUsers = $this->manager->findAll($filter, new Pagination(), new Sorting());

        // assert
        self::assertCount(count($targetUsers), $resultUsers);
        foreach ($resultUsers as $index => $resultUser) {
            self::assertEquals($targetUsers[$index]['id'], $resultUser['id']);
        }
    }

    public function testSuccessFindAllWithFilterNotEquals(): void
    {
        // arrange
        $excludedUser = self::USER_4;
        $targetUsers = [
            self::USER_1,
            self::USER_2,
        ];

        $filter = new Filter();
        $filter->notEquals('name', $excludedUser['name']);

        // action
        $resultUsers = $this->manager->findAll($filter, new Pagination(), new Sorting());

        // assert
        self::assertCount(count($targetUsers), $resultUsers);
        foreach ($resultUsers as $index => $resultUser) {
            self::assertEquals($targetUsers[$index]['id'], $resultUser['id']);
        }
    }

    public function testSuccessFindAllWithFilterLessThan(): void
    {
        // arrange
        $targetUsers = [
            self::USER_2,
        ];

        $filter = new Filter();
        $filter->lessThan('age', 22);

        // action
        $resultUsers = $this->manager->findAll($filter, new Pagination(), new Sorting());

        // assert
        self::assertCount(count($targetUsers), $resultUsers);
        foreach ($resultUsers as $index => $resultUser) {
            self::assertEquals($targetUsers[$index]['id'], $resultUser['id']);
        }
    }

    public function testSuccessFindAllWithFilterLessOrEquals(): void
    {
        // arrange
        $targetUsers = [
            self::USER_1,
            self::USER_2,
        ];

        $filter = new Filter();
        $filter->lessOrEquals('age', 22);

        // action
        $resultUsers = $this->manager->findAll($filter, new Pagination(), new Sorting());

        // assert
        self::assertCount(count($targetUsers), $resultUsers);
        foreach ($resultUsers as $index => $resultUser) {
            self::assertEquals($targetUsers[$index]['id'], $resultUser['id']);
        }
    }

    public function testSuccessFindAllWithFilterGreaterThan(): void
    {
        // arrange
        $targetUsers = [
            self::USER_4,
        ];

        $filter = new Filter();
        $filter->greaterThan('age', 22);

        // action
        $resultUsers = $this->manager->findAll($filter, new Pagination(), new Sorting());

        // assert
        self::assertCount(count($targetUsers), $resultUsers);
        foreach ($resultUsers as $index => $resultUser) {
            self::assertEquals($targetUsers[$index]['id'], $resultUser['id']);
        }
    }

    public function testSuccessFindAllWithFilterGreaterOrEquals(): void
    {
        // arrange
        $targetUsers = [
            self::USER_1,
            self::USER_4,
        ];

        $filter = new Filter();
        $filter->greaterOrEquals('age', 22);

        // action
        $resultUsers = $this->manager->findAll($filter, new Pagination(), new Sorting());

        // assert
        self::assertCount(count($targetUsers), $resultUsers);
        foreach ($resultUsers as $index => $resultUser) {
            self::assertEquals($targetUsers[$index]['id'], $resultUser['id']);
        }
    }

    public function testSuccessFindAllWithFilterIsNull(): void
    {
        // arrange
        $targetUsers = [
            self::USER_4,
        ];

        $filter = new Filter();
        $filter->isNull('birthday');

        // action
        $resultUsers = $this->manager->findAll($filter, new Pagination(), new Sorting());

        // assert
        self::assertCount(count($targetUsers), $resultUsers);
        foreach ($resultUsers as $index => $resultUser) {
            self::assertEquals($targetUsers[$index]['id'], $resultUser['id']);
        }
    }

    public function testSuccessFindAllWithFilterIsNotNull(): void
    {
        // arrange
        $targetUsers = [
            self::USER_1,
            self::USER_2,
        ];

        $filter = new Filter();
        $filter->isNotNull('birthday');

        // action
        $resultUsers = $this->manager->findAll($filter, new Pagination(), new Sorting());

        // assert
        self::assertCount(count($targetUsers), $resultUsers);
        foreach ($resultUsers as $index => $resultUser) {
            self::assertEquals($targetUsers[$index]['id'], $resultUser['id']);
        }
    }

    public function testSuccessFindAllWithFilterInString(): void
    {
        // arrange
        $targetUsers = [
            self::USER_1,
            self::USER_2,
        ];

        $filter = new Filter();
        $filter->in('name', ['John', 'Mister X']);

        // action
        $resultUsers = $this->manager->findAll($filter, new Pagination(), new Sorting());

        // assert
        self::assertCount(count($targetUsers), $resultUsers);
        foreach ($resultUsers as $index => $resultUser) {
            self::assertEquals($targetUsers[$index]['id'], $resultUser['id']);
        }
    }

    public function testSuccessFindAllWithFilterNotInString(): void
    {
        // arrange
        $targetUsers = [
            self::USER_4,
        ];

        $filter = new Filter();
        $filter->notIn('name', ['John', 'Mister X']);

        // action
        $resultUsers = $this->manager->findAll($filter, new Pagination(), new Sorting());

        // assert
        self::assertCount(count($targetUsers), $resultUsers);
        foreach ($resultUsers as $index => $resultUser) {
            self::assertEquals($targetUsers[$index]['id'], $resultUser['id']);
        }
    }

    public function testSuccessFindAllWithFilterInInt(): void
    {
        // arrange
        $targetUsers = [
            self::USER_1,
            self::USER_2,
        ];

        $filter = new Filter();
        $filter->in('age', [22, 13]);

        // action
        $resultUsers = $this->manager->findAll($filter, new Pagination(), new Sorting());

        // assert
        self::assertCount(count($targetUsers), $resultUsers);
        foreach ($resultUsers as $index => $resultUser) {
            self::assertEquals($targetUsers[$index]['id'], $resultUser['id']);
        }
    }

    public function testSuccessFindAllWithFilterNotInInt(): void
    {
        // arrange
        $targetUsers = [
            self::USER_4,
        ];

        $filter = new Filter();
        $filter->notIn('age', [22, 13]);

        // action
        $resultUsers = $this->manager->findAll($filter, new Pagination(), new Sorting());

        // assert
        self::assertCount(count($targetUsers), $resultUsers);
        foreach ($resultUsers as $index => $resultUser) {
            self::assertEquals($targetUsers[$index]['id'], $resultUser['id']);
        }
    }

    public function testSuccessFindAllWithFilterLikeNoBounds(): void
    {
        // arrange
        $targetUsers = [
            self::USER_2,
        ];

        $filter = new Filter();
        $filter->like('name', 'ster');

        // action
        $resultUsers = $this->manager->findAll($filter, new Pagination(), new Sorting());

        // assert
        self::assertCount(count($targetUsers), $resultUsers);
        foreach ($resultUsers as $index => $resultUser) {
            self::assertEquals($targetUsers[$index]['id'], $resultUser['id']);
        }
    }

    public function testSuccessFindAllWithFilterLikeFromBeginning(): void
    {
        // arrange
        $targetUsers = [
            self::USER_2,
        ];

        $filter = new Filter();
        $filter->like('name', 'Mist', true);

        // action
        $resultUsers = $this->manager->findAll($filter, new Pagination(), new Sorting());

        // assert
        self::assertCount(count($targetUsers), $resultUsers);
        foreach ($resultUsers as $index => $resultUser) {
            self::assertEquals($targetUsers[$index]['id'], $resultUser['id']);
        }
    }

    public function testSuccessFindAllWithFilterLikeToEnd(): void
    {
        // arrange
        $targetUsers = [
            self::USER_2,
        ];

        $filter = new Filter();
        $filter->like('name', 'er X', false, true);

        // action
        $resultUsers = $this->manager->findAll($filter, new Pagination(), new Sorting());

        // assert
        self::assertCount(count($targetUsers), $resultUsers);
        foreach ($resultUsers as $index => $resultUser) {
            self::assertEquals($targetUsers[$index]['id'], $resultUser['id']);
        }
    }

    public function testSuccessFindAllWithFilterLikeAllBounds(): void
    {
        // arrange
        $targetUsers = [
            self::USER_2,
        ];

        $filter = new Filter();
        $filter->like('name', 'Mister X', true, true);

        // action
        $resultUsers = $this->manager->findAll($filter, new Pagination(), new Sorting());

        // assert
        self::assertCount(count($targetUsers), $resultUsers);
        foreach ($resultUsers as $index => $resultUser) {
            self::assertEquals($targetUsers[$index]['id'], $resultUser['id']);
        }
    }

    public function testSuccessFindAllWithFilterRawSql(): void
    {
        // arrange
        $targetUsers = [
            self::USER_2,
            self::USER_4,
        ];

        $filter = new Filter();
        $filter->rawSql('age > 40 OR age < 20');

        // action
        $resultUsers = $this->manager->findAll($filter, new Pagination(), new Sorting());

        // assert
        self::assertCount(count($targetUsers), $resultUsers);
        foreach ($resultUsers as $index => $resultUser) {
            self::assertEquals($targetUsers[$index]['id'], $resultUser['id']);
        }
    }

    public function testSuccessFindAllWithFilterDeleted(): void
    {
        // arrange
        $targetUsers = [
            self::USER_1,
            self::USER_2,
            self::USER_3,
            self::USER_4,
        ];

        $filter = new Filter();
        $filter->deleted([true, false]);

        // action
        $resultUsers = $this->manager->findAll($filter, new Pagination(), new Sorting());

        // assert
        self::assertCount(count($targetUsers), $resultUsers);
        foreach ($resultUsers as $index => $resultUser) {
            self::assertEquals($targetUsers[$index]['id'], $resultUser['id']);
        }
    }

    public function testSuccessFindAllWithPagination(): void
    {
        // arrange
        $targetUsers = [
            self::USER_4,
        ];

        $pagination = new Pagination(1, 2);

        // action
        $resultUsers = $this->manager->findAll(new Filter(), $pagination, new Sorting());

        // assert
        self::assertCount(count($targetUsers), $resultUsers);
        foreach ($resultUsers as $index => $resultUser) {
            self::assertEquals($targetUsers[$index]['id'], $resultUser['id']);
        }
    }

    public function testSuccessFindAllWithSortingAsc(): void
    {
        // arrange
        $targetUsers = [
            self::USER_2,
            self::USER_1,
            self::USER_4,
        ];

        $sorting = new Sorting();
        $sorting->addSorting('age', 'asc');

        // action
        $resultUsers = $this->manager->findAll(new Filter(), new Pagination(), $sorting);

        // assert
        self::assertCount(count($targetUsers), $resultUsers);
        foreach ($resultUsers as $index => $resultUser) {
            self::assertEquals($targetUsers[$index]['id'], $resultUser['id']);
        }
    }

    public function testSuccessFindAllWithSortingDesc(): void
    {
        // arrange
        $targetUsers = [
            self::USER_4,
            self::USER_1,
            self::USER_2,
        ];

        $sorting = new Sorting();
        $sorting->addSorting('age', 'desc');

        // action
        $resultUsers = $this->manager->findAll(new Filter(), new Pagination(), $sorting);

        // assert
        self::assertCount(count($targetUsers), $resultUsers);
        foreach ($resultUsers as $index => $resultUser) {
            self::assertEquals($targetUsers[$index]['id'], $resultUser['id']);
        }
    }

    public function testSuccessFindAllWithDoubleSorting(): void
    {
        // arrange
        $targetUsers = [
            self::USER_1,
            self::USER_4,
            self::USER_2,
        ];

        $sorting = new Sorting();
        $sorting->addSorting('created_at', 'asc');
        $sorting->addSorting('age', 'desc');

        // action
        $resultUsers = $this->manager->findAll(new Filter(), new Pagination(), $sorting);

        // assert
        self::assertCount(count($targetUsers), $resultUsers);
        foreach ($resultUsers as $index => $resultUser) {
            self::assertEquals($targetUsers[$index]['id'], $resultUser['id']);
        }
    }

    public function testFailFindAllUnknownColumnList(): void
    {
        // arrange
        $filter = new Filter();
        $filter->equals('UNKNOWN_FIELD', 1);

        // assert
        // action
        $this->expectException(InvalidRequestException::class);
        $this->expectExceptionMessage('Unknown columns: [UNKNOWN_FIELD]');

        $this->manager->findAll($filter, new Pagination(), new Sorting());
    }

    public function testSuccessFindOneWithoutAnything(): void
    {
        // arrange
        $targetUser = self::USER_1;

        // action
        $resultUser = $this->manager->findOne(new Filter(), new Sorting());

        // assert
        self::assertNotNull($resultUser);
        self::assertEquals($targetUser['id'], $resultUser['id']);
    }

    public function testSuccessFindOneWithFilterEquals(): void
    {
        // arrange
        $targetUser = self::USER_4;

        $filter = new Filter();
        $filter->equals('name', 'Someone');

        // action
        $resultUser = $this->manager->findOne($filter, new Sorting());

        // assert
        self::assertNotNull($resultUser);
        self::assertEquals($targetUser['id'], $resultUser['id']);
    }

    public function testSuccessFindOneWithFilterNotEquals(): void
    {
        // arrange
        $targetUser = self::USER_1;

        $filter = new Filter();
        $filter->notEquals('name', 'Someone');

        // action
        $resultUser = $this->manager->findOne($filter, new Sorting());

        // assert
        self::assertNotNull($resultUser);
        self::assertEquals($targetUser['id'], $resultUser['id']);
    }

    public function testSuccessFindOneWithFilterLessThan(): void
    {
        // arrange
        $targetUser = self::USER_2;

        $filter = new Filter();
        $filter->lessThan('age', 22);

        // action
        $resultUser = $this->manager->findOne($filter, new Sorting());

        // assert
        self::assertNotNull($resultUser);
        self::assertEquals($targetUser['id'], $resultUser['id']);
    }

    public function testSuccessFindOneWithFilterLessOrEquals(): void
    {
        // arrange
        $targetUser = self::USER_1;

        $filter = new Filter();
        $filter->lessOrEquals('age', 22);

        // action
        $resultUser = $this->manager->findOne($filter, new Sorting());

        // assert
        self::assertNotNull($resultUser);
        self::assertEquals($targetUser['id'], $resultUser['id']);
    }

    public function testSuccessFindOneWithFilterGreaterThan(): void
    {
        // arrange
        $targetUser = self::USER_4;

        $filter = new Filter();
        $filter->greaterThan('age', 22);

        // action
        $resultUser = $this->manager->findOne($filter, new Sorting());

        // assert
        self::assertNotNull($resultUser);
        self::assertEquals($targetUser['id'], $resultUser['id']);
    }

    public function testSuccessFindOneWithFilterGreaterOrEquals(): void
    {
        // arrange
        $targetUser = self::USER_1;

        $filter = new Filter();
        $filter->greaterOrEquals('age', 22);

        // action
        $resultUser = $this->manager->findOne($filter, new Sorting());

        // assert
        self::assertNotNull($resultUser);
        self::assertEquals($targetUser['id'], $resultUser['id']);
    }

    public function testSuccessFindOneWithFilterIsNull(): void
    {
        // arrange
        $targetUser = self::USER_4;

        $filter = new Filter();
        $filter->isNull('birthday');

        // action
        $resultUser = $this->manager->findOne($filter, new Sorting());

        // assert
        self::assertNotNull($resultUser);
        self::assertEquals($targetUser['id'], $resultUser['id']);
    }

    public function testSuccessFindOneWithFilterIsNotNull(): void
    {
        // arrange
        $targetUser = self::USER_1;

        $filter = new Filter();
        $filter->isNotNull('birthday');

        // action
        $resultUser = $this->manager->findOne($filter, new Sorting());

        // assert
        self::assertNotNull($resultUser);
        self::assertEquals($targetUser['id'], $resultUser['id']);
    }

    public function testSuccessFindOneWithFilterInString(): void
    {
        // arrange
        $targetUser = self::USER_1;

        $filter = new Filter();
        $filter->in('name', ['John', 'Mister X']);

        // action
        $resultUser = $this->manager->findOne($filter, new Sorting());

        // assert
        self::assertNotNull($resultUser);
        self::assertEquals($targetUser['id'], $resultUser['id']);
    }

    public function testSuccessFindOneWithFilterNotInString(): void
    {
        // arrange
        $targetUser = self::USER_4;

        $filter = new Filter();
        $filter->notIn('name', ['John', 'Mister X']);

        // action
        $resultUser = $this->manager->findOne($filter, new Sorting());

        // assert
        self::assertNotNull($resultUser);
        self::assertEquals($targetUser['id'], $resultUser['id']);
    }

    public function testSuccessFindOneWithFilterInInt(): void
    {
        // arrange
        $targetUser = self::USER_1;

        $filter = new Filter();
        $filter->in('age', [22, 13]);

        // action
        $resultUser = $this->manager->findOne($filter, new Sorting());

        // assert
        self::assertNotNull($resultUser);
        self::assertEquals($targetUser['id'], $resultUser['id']);
    }

    public function testSuccessFindOneWithFilterNotInInt(): void
    {
        // arrange
        $targetUser = self::USER_4;

        $filter = new Filter();
        $filter->notIn('age', [22, 13]);

        // action
        $resultUser = $this->manager->findOne($filter, new Sorting());

        // assert
        self::assertNotNull($resultUser);
        self::assertEquals($targetUser['id'], $resultUser['id']);
    }

    public function testSuccessFindOneWithFilterLikeNoBounds(): void
    {
        // arrange
        $targetUser = self::USER_2;

        $filter = new Filter();
        $filter->like('name', 'ster');

        // action
        $resultUser = $this->manager->findOne($filter, new Sorting());

        // assert
        self::assertNotNull($resultUser);
        self::assertEquals($targetUser['id'], $resultUser['id']);
    }

    public function testSuccessFindOneWithFilterLikeFromBeginning(): void
    {
        // arrange
        $targetUser = self::USER_2;

        $filter = new Filter();
        $filter->like('name', 'Mist', true);

        // action
        $resultUser = $this->manager->findOne($filter, new Sorting());

        // assert
        self::assertNotNull($resultUser);
        self::assertEquals($targetUser['id'], $resultUser['id']);
    }

    public function testSuccessFindOneWithFilterLikeToEnd(): void
    {
        // arrange
        $targetUser = self::USER_2;

        $filter = new Filter();
        $filter->like('name', 'er X', false, true);

        // action
        $resultUser = $this->manager->findOne($filter, new Sorting());

        // assert
        self::assertNotNull($resultUser);
        self::assertEquals($targetUser['id'], $resultUser['id']);
    }

    public function testSuccessFindOneWithFilterLikeAllBounds(): void
    {
        // arrange
        $targetUser = self::USER_2;

        $filter = new Filter();
        $filter->like('name', 'Mister X', true, true);

        // action
        $resultUser = $this->manager->findOne($filter, new Sorting());

        // assert
        self::assertNotNull($resultUser);
        self::assertEquals($targetUser['id'], $resultUser['id']);
    }

    public function testSuccessFindOneWithFilterRawSql(): void
    {
        // arrange
        $targetUser = self::USER_2;

        $filter = new Filter();
        $filter->rawSql('age > 40 OR age < 20');

        // action
        $resultUser = $this->manager->findOne($filter, new Sorting());

        // assert
        self::assertNotNull($resultUser);
        self::assertEquals($targetUser['id'], $resultUser['id']);
    }

    public function testSuccessFindOneWithFilterDeleted(): void
    {
        // arrange
        $targetUser = self::USER_1;

        $filter = new Filter();
        $filter->deleted([true, false]);

        // action
        $resultUser = $this->manager->findOne($filter, new Sorting());

        // assert
        self::assertNotNull($resultUser);
        self::assertEquals($targetUser['id'], $resultUser['id']);
    }

    public function testSuccessFindOneWithSortingAsc(): void
    {
        // arrange
        $targetUser = self::USER_2;

        $sorting = new Sorting();
        $sorting->addSorting('age', 'asc');

        // action
        $resultUser = $this->manager->findOne(new Filter(), $sorting);

        // assert
        self::assertNotNull($resultUser);
        self::assertEquals($targetUser['id'], $resultUser['id']);
    }

    public function testSuccessFindOneWithSortingDesc(): void
    {
        // arrange
        $targetUser = self::USER_4;

        $sorting = new Sorting();
        $sorting->addSorting('age', 'desc');

        // action
        $resultUser = $this->manager->findOne(new Filter(), $sorting);

        // assert
        self::assertNotNull($resultUser);
        self::assertEquals($targetUser['id'], $resultUser['id']);
    }

    public function testSuccessFindOneWithDoubleSorting(): void
    {
        // arrange
        $targetUser = self::USER_1;

        $sorting = new Sorting();
        $sorting->addSorting('created_at', 'asc');
        $sorting->addSorting('age', 'desc');

        // action
        $resultUser = $this->manager->findOne(new Filter(), $sorting);

        // assert
        self::assertNotNull($resultUser);
        self::assertEquals($targetUser['id'], $resultUser['id']);
    }

    public function testSuccessNotFound(): void
    {
        // arrange
        $filter = new Filter();
        $filter->equals(DefaultTestEntity::PK_COLUMN, 100000);

        // action
        $resultUser = $this->manager->findOne($filter, new Sorting());

        // assert
        self::assertNull($resultUser);
    }

    public function testSuccessFindByPk(): void
    {
        // arrange
        $targetUser = self::USER_1;

        // action
        $resultUser = $this->manager->findByPk($targetUser['id']);

        // assert
        self::assertNotNull($resultUser);
        self::assertEquals($targetUser['id'], $resultUser['id']);
    }

    public function testSuccessNotFindByPk(): void
    {
        // action
        $data = $this->manager->findByPk(10000);

        // assert
        self::assertNull($data);
    }

    public function testFailFindByPkNoPrimaryKeyValue(): void
    {
        // assert
        // action
        $this->expectException(InvalidRequestException::class);
        $this->expectExceptionMessage('No value provided for PK column "id"');

        $this->manager->findByPk([
            'name' => 1,
        ]);
    }

    public function testSuccessInsert(): void
    {
        // arrange
        $dataForInsert = [
            'name' => 'Inserted User',
            'birthday' => '2016-02-02',
            'age' => 44,
            'weight' => 32.4,
            'married' => 1,
        ];

        // action
        $id = $this->manager->insert($dataForInsert);

        // assert
        self::assertEquals(5, $id);

        $insertedData = $this->getOneRowFromDB([
            'id' => $id,
        ]);
        self::assertEquals($dataForInsert['name'], $insertedData['name']);
        self::assertEquals($dataForInsert['birthday'], $insertedData['birthday']);
        self::assertEquals($dataForInsert['age'], $insertedData['age']);
        self::assertEquals($dataForInsert['weight'], $insertedData['weight']);
        self::assertEquals($dataForInsert['married'], $insertedData['married']);
        self::assertNotNull($insertedData[DefaultTestEntity::CREATED_AT_COLUMN]);
        self::assertNotNull($insertedData[DefaultTestEntity::UPDATED_AT_COLUMN]);
    }

    public function testSuccessBatchInsert(): void
    {
        // arrange
        $dataForInsertList = [
            [
                'name' => 'Inserted User',
                'birthday' => '2016-02-02',
                'age' => 44,
                'weight' => 32.4,
                'married' => 1,
            ],
            [
                'name' => 'Inserted User 2',
                'birthday' => '2014-02-02',
                'age' => 23,
                'weight' => 1.24,
                'married' => 0,
            ],
        ];

        // action
        $count = $this->manager->batchInsert($dataForInsertList);

        // assert
        self::assertEquals(2, $count);

        foreach ($dataForInsertList as $dataForInsert) {
            $insertedData = $this->getOneRowFromDB([
                'name' => $dataForInsert['name'],
            ]);

            self::assertEquals($dataForInsert['name'], $insertedData['name']);
            self::assertEquals($dataForInsert['birthday'], $insertedData['birthday']);
            self::assertEquals($dataForInsert['age'], $insertedData['age']);
            self::assertEquals($dataForInsert['weight'], $insertedData['weight']);
            self::assertEquals($dataForInsert['married'], $insertedData['married']);
            self::assertNotNull($insertedData[DefaultTestEntity::CREATED_AT_COLUMN]);
            self::assertNotNull($insertedData[DefaultTestEntity::UPDATED_AT_COLUMN]);
        }
    }

    public function testFailBatchInsertEmptyList(): void
    {
        // assert
        // action
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('You need to add at least one set of values before generating the SQL.');

        $this->manager->batchInsert([]);
    }

    public function testSuccessUpdate(): void
    {
        // arrange
        $targetUser = self::USER_4;

        $dataForUpdate = [
            'name' => 'Updated User',
            'birthday' => '2016-02-02',
            'age' => 44,
            'weight' => 32.4,
            'married' => 1,
        ];

        $filter = new Filter();
        $filter->equals('name', $targetUser['name']);

        // action
        $count = $this->manager->update($dataForUpdate, $filter);

        // assert
        self::assertEquals(1, $count);

        $updatedData = $this->getOneRowFromDB([
            'name' => $dataForUpdate['name'],
        ]);
        self::assertEquals($targetUser['id'], $updatedData['id']);
        self::assertEquals($dataForUpdate['name'], $updatedData['name']);
        self::assertEquals($dataForUpdate['birthday'], $updatedData['birthday']);
        self::assertEquals($dataForUpdate['age'], $updatedData['age']);
        self::assertEquals($dataForUpdate['weight'], $updatedData['weight']);
        self::assertEquals($dataForUpdate['married'], $updatedData['married']);
        self::assertNotNull($updatedData[DefaultTestEntity::UPDATED_AT_COLUMN]);
    }

    public function testSuccessUpdateNotExistingRow(): void
    {
        // arrange
        $dataForUpdate = [
            'name' => 'Updated User',
            'birthday' => '2016-02-02',
            'age' => 44,
            'weight' => 32.4,
            'married' => 1,
        ];

        $filter = new Filter();
        $filter->equals('name', 'NOT EXISTING NAME');

        // action
        $count = $this->manager->update($dataForUpdate, $filter);

        // assert
        self::assertEquals(0, $count);
    }

    public function testSuccessUpdateByPk(): void
    {
        // arrange
        $targetUser = self::USER_4;
        $id = $targetUser['id'];

        $dataForUpdate = [
            'name' => 'Updated User',
            'birthday' => '2016-02-02',
            'age' => 44,
            'weight' => 32.4,
            'married' => 1,
        ];

        // action
        $count = $this->manager->updateByPk($id, $dataForUpdate);

        // assert
        self::assertEquals(1, $count);

        $updatedData = $this->getOneRowFromDB([
            'id' => $id,
        ]);
        self::assertEquals($dataForUpdate['name'], $updatedData['name']);
        self::assertEquals($dataForUpdate['birthday'], $updatedData['birthday']);
        self::assertEquals($dataForUpdate['age'], $updatedData['age']);
        self::assertEquals($dataForUpdate['weight'], $updatedData['weight']);
        self::assertEquals($dataForUpdate['married'], $updatedData['married']);
        self::assertNotNull($updatedData[DefaultTestEntity::UPDATED_AT_COLUMN]);
    }

    public function testSuccessUpdateByPkNotExistingRow(): void
    {
        // arrange
        $id = 4000;

        $dataForUpdate = [
            'name' => 'Updated User',
            'birthday' => '2016-02-02',
            'age' => 44,
            'weight' => 32.4,
            'married' => 1,
        ];

        // action
        $count = $this->manager->updateByPk($id, $dataForUpdate);

        // assert
        self::assertEquals(0, $count);
    }

    public function testSuccessBatchUpdate(): void
    {
        // arrange
        $dataForUpdateList = [
            [
                'name' => 'Updated User',
                'birthday' => '2016-02-02',
                'age' => 44,
                'weight' => 32.4,
                'married' => 1,
            ],
            [
                'name' => 'Updated User 2',
                'birthday' => '2014-02-02',
                'age' => 23,
                'weight' => 1.24,
                'married' => 0,
            ],
        ];

        $idList = [
            self::USER_1['id'],
            self::USER_2['id'],
        ];

        $filterList = [];
        foreach ($idList as $id) {
            $filterList[] = (new Filter())->equals('id', $id);
        }

        // action
        $count = $this->manager->batchUpdate($dataForUpdateList, $filterList);

        // assert
        self::assertEquals(count($idList), $count);

        foreach ($dataForUpdateList as $i => $dataForUpdate) {
            $insertedData = $this->manager->findByPk($idList[$i]);
            self::assertEquals($dataForUpdate['name'], $insertedData['name']);
            self::assertEquals($dataForUpdate['birthday'], $insertedData['birthday']);
            self::assertEquals($dataForUpdate['age'], $insertedData['age']);
            self::assertEquals($dataForUpdate['weight'], $insertedData['weight']);
            self::assertEquals($dataForUpdate['married'], $insertedData['married']);
        }
    }

    public function testFailBatchUpdateDataAndFilterCountNotEqual(): void
    {
        // arrange
        $dataForUpdateList = [
            [
                'name' => 'Updated User',
                'birthday' => '2016-02-02',
                'age' => 44,
                'weight' => 32.4,
                'married' => 1,
            ],
        ];

        $idList = [
            self::USER_1['id'],
            self::USER_2['id'],
        ];

        $filterList = [];
        foreach ($idList as $id) {
            $filterList[] = (new Filter())->equals('id', $id);
        }

        // assert
        // action
        $this->expectException(InvalidRequestException::class);
        $this->expectExceptionMessage('Data count must be equal to filter count');

        $this->manager->batchUpdate($dataForUpdateList, $filterList);
    }

    public function testSuccessDelete(): void
    {
        // arrange
        $targetUser = self::USER_2;

        $filter = new Filter();
        $filter->equals('name', $targetUser['name']);

        // action
        $count = $this->manager->delete($filter);

        // assert
        self::assertEquals(1, $count);

        $deletedRow = $this->getOneRowFromDB([
            'name' => $targetUser['name'],
        ]);
        self::assertNull($deletedRow);
    }

    public function testSuccessDeleteNotExistingRow(): void
    {
        // arrange
        $filter = new Filter();
        $filter->equals('name', 'NOT EXISTING NAME');

        // action
        $count = $this->manager->delete($filter);

        // assert
        self::assertEquals(0, $count);
    }

    public function testSuccessDeleteByPk(): void
    {
        // arrange
        $targetUser = self::USER_4;
        $id = $targetUser['id'];

        // action
        $count = $this->manager->deleteByPk($id);

        // assert
        self::assertEquals(1, $count);

        $deletedRow = $this->getOneRowFromDB([
            'id' => $id,
        ]);
        self::assertNull($deletedRow);
    }

    public function testSuccessDeleteAll(): void
    {
        // action
        $count = $this->manager->deleteAll();

        // assert
        self::assertEquals(4, $count);

        $totalCount = $this->getCountFromDB();
        self::assertEquals(0, $totalCount);
    }

    public function testSuccessSoftDelete(): void
    {
        // arrange
        $targetUser = self::USER_4;

        $filter = new Filter();
        $filter->equals('name', $targetUser['name']);

        // action
        $count = $this->manager->softDelete($filter);

        // assert
        self::assertEquals(1, $count);

        $deletedRow = $this->getOneRowFromDB([
            'name' => $targetUser['name'],
        ]);
        self::assertNotNull($deletedRow);
        self::assertNotNull($deletedRow[DefaultTestEntity::DELETED_AT_COLUMN]);
    }

    public function testSuccessSoftDeleteByPk(): void
    {
        // arrange
        $targetUser = self::USER_4;
        $id = $targetUser['id'];

        // action
        $count = $this->manager->softDeleteByPk($id);

        // assert
        self::assertEquals(1, $count);

        $deletedRow = $this->getOneRowFromDB([
            'id' => $id,
        ]);
        self::assertNotNull($deletedRow);
        self::assertNotNull($deletedRow[DefaultTestEntity::DELETED_AT_COLUMN]);
    }

    public function testSuccessSoftDeleteAll(): void
    {
        // action
        $count = $this->manager->softDeleteAll();

        // assert
        self::assertEquals(4, $count);

        $totalCount = $this->getCountFromDB();
        self::assertEquals(4, $totalCount);

        $deletedRows = $this->getRowsFromDB();
        foreach ($deletedRows as $deletedRow) {
            self::assertNotNull($deletedRow[DefaultTestEntity::DELETED_AT_COLUMN]);
        }
    }

    public function testSuccessGetCountWithoutAnything(): void
    {
        // action
        $count = $this->manager->getCount(new Filter());

        // assert
        self::assertEquals(3, $count);
    }

    public function testSuccessGetCountWithFilterEquals(): void
    {
        // arrange
        $targetUser = self::USER_4;

        $filter = new Filter();
        $filter->equals('name', $targetUser['name']);

        // action
        $count = $this->manager->getCount($filter);

        // assert
        self::assertEquals(1, $count);
    }

    public function testSuccessGetCountWithFilterNotEquals(): void
    {
        // arrange
        $excludedUser = self::USER_4;
        $targetUsers = [
            self::USER_1,
            self::USER_2,
        ];

        $filter = new Filter();
        $filter->notEquals('name', $excludedUser['name']);

        // action
        $count = $this->manager->getCount($filter);

        // assert
        self::assertEquals(count($targetUsers), $count);
    }

    public function testSuccessGetCountWithFilterLessThan(): void
    {
        // arrange
        $targetUsers = [
            self::USER_2,
        ];

        $filter = new Filter();
        $filter->lessThan('age', 22);

        // action
        $count = $this->manager->getCount($filter);

        // assert
        self::assertEquals(count($targetUsers), $count);
    }

    public function testSuccessGetCountWithFilterLessOrEquals(): void
    {
        // arrange
        $targetUsers = [
            self::USER_1,
            self::USER_2,
        ];

        $filter = new Filter();
        $filter->lessOrEquals('age', 22);

        // action
        $count = $this->manager->getCount($filter);

        // assert
        self::assertEquals(count($targetUsers), $count);
    }

    public function testSuccessGetCountWithFilterGreaterThan(): void
    {
        // arrange
        $targetUsers = [
            self::USER_4,
        ];

        $filter = new Filter();
        $filter->greaterThan('age', 22);

        // action
        $count = $this->manager->getCount($filter);

        // assert
        self::assertEquals(count($targetUsers), $count);
    }

    public function testSuccessGetCountWithFilterGreaterOrEquals(): void
    {
        // arrange
        $targetUsers = [
            self::USER_1,
            self::USER_4,
        ];

        $filter = new Filter();
        $filter->greaterOrEquals('age', 22);

        // action
        $count = $this->manager->getCount($filter);

        // assert
        self::assertEquals(count($targetUsers), $count);
    }

    public function testSuccessGetCountWithFilterIsNull(): void
    {
        // arrange
        $targetUsers = [
            self::USER_4,
        ];

        $filter = new Filter();
        $filter->isNull('birthday');

        // action
        $count = $this->manager->getCount($filter);

        // assert
        self::assertEquals(count($targetUsers), $count);
    }

    public function testSuccessGetCountWithFilterIsNotNull(): void
    {
        // arrange
        $targetUsers = [
            self::USER_1,
            self::USER_2,
        ];

        $filter = new Filter();
        $filter->isNotNull('birthday');

        // action
        $count = $this->manager->getCount($filter);

        // assert
        self::assertEquals(count($targetUsers), $count);
    }

    public function testSuccessGetCountWithFilterInString(): void
    {
        // arrange
        $targetUsers = [
            self::USER_1,
            self::USER_2,
        ];

        $filter = new Filter();
        $filter->in('name', ['John', 'Mister X']);

        // action
        $count = $this->manager->getCount($filter);

        // assert
        self::assertEquals(count($targetUsers), $count);
    }

    public function testSuccessGetCountWithFilterNotInString(): void
    {
        // arrange
        $targetUsers = [
            self::USER_4,
        ];

        $filter = new Filter();
        $filter->notIn('name', ['John', 'Mister X']);

        // action
        $count = $this->manager->getCount($filter);

        // assert
        self::assertEquals(count($targetUsers), $count);
    }

    public function testSuccessGetCountWithFilterInInt(): void
    {
        // arrange
        $targetUsers = [
            self::USER_1,
            self::USER_2,
        ];

        $filter = new Filter();
        $filter->in('age', [22, 13]);

        // action
        $count = $this->manager->getCount($filter);

        // assert
        self::assertEquals(count($targetUsers), $count);
    }

    public function testSuccessGetCountWithFilterNotInInt(): void
    {
        // arrange
        $targetUsers = [
            self::USER_4,
        ];

        $filter = new Filter();
        $filter->notIn('age', [22, 13]);

        // action
        $count = $this->manager->getCount($filter);

        // assert
        self::assertEquals(count($targetUsers), $count);
    }

    public function testSuccessGetCountWithFilterLikeNoBounds(): void
    {
        // arrange
        $targetUsers = [
            self::USER_2,
        ];

        $filter = new Filter();
        $filter->like('name', 'ster');

        // action
        $count = $this->manager->getCount($filter);

        // assert
        self::assertEquals(count($targetUsers), $count);
    }

    public function testSuccessGetCountWithFilterLikeFromBeginning(): void
    {
        // arrange
        $targetUsers = [
            self::USER_2,
        ];

        $filter = new Filter();
        $filter->like('name', 'Mist', true);

        // action
        $count = $this->manager->getCount($filter);

        // assert
        self::assertEquals(count($targetUsers), $count);
    }

    public function testSuccessGetCountWithFilterLikeToEnd(): void
    {
        // arrange
        $targetUsers = [
            self::USER_2,
        ];

        $filter = new Filter();
        $filter->like('name', 'er X', false, true);

        // action
        $count = $this->manager->getCount($filter);

        // assert
        self::assertEquals(count($targetUsers), $count);
    }

    public function testSuccessGetCountWithFilterLikeAllBounds(): void
    {
        // arrange
        $targetUsers = [
            self::USER_2,
        ];

        $filter = new Filter();
        $filter->like('name', 'Mister X', true, true);

        // action
        $count = $this->manager->getCount($filter);

        // assert
        self::assertEquals(count($targetUsers), $count);
    }

    public function testSuccessGetCountWithFilterRawSql(): void
    {
        // arrange
        $targetUsers = [
            self::USER_2,
            self::USER_4,
        ];

        $filter = new Filter();
        $filter->rawSql('age > 40 OR age < 20');

        // action
        $count = $this->manager->getCount($filter);

        // assert
        self::assertEquals(count($targetUsers), $count);
    }

    public function testSuccessGetCountWithFilterDeleted(): void
    {
        // arrange
        $targetUsers = [
            self::USER_1,
            self::USER_2,
            self::USER_3,
            self::USER_4,
        ];

        $filter = new Filter();
        $filter->deleted([true, false]);

        // action
        $count = $this->manager->getCount($filter);

        // assert
        self::assertEquals(count($targetUsers), $count);
    }

    public function testSuccessTruncate(): void
    {
        // action
        $this->manager->truncate();

        // assert
        $totalCount = $this->getCount();
        self::assertEquals(0, $totalCount);
    }

    /**
     * @param array $filters
     *
     * @return array
     */
    private function getRowsFromDB(array $filters = []): array
    {
        $query = $this->dbalConnection->createQueryBuilder();
        $query->select('*');
        $query->from(DefaultTestEntity::TABLE_NAME);

        foreach ($filters as $column => $value) {
            $query->andWhere($column . ' = ' . $query->createNamedParameter($value));
        }

        $list = $query->execute()->fetchAll();

        $result = [];

        foreach ($list as $row) {
            $result[] = $this->typeConverter->convert($row, DefaultTestEntity::FIELD_MAP);
        }

        return $result;
    }

    /**
     * @param array $filters
     *
     * @return array|null
     */
    private function getOneRowFromDB(array $filters = []): ?array
    {
        $query = $this->dbalConnection->createQueryBuilder();
        $query->select('*');
        $query->from(DefaultTestEntity::TABLE_NAME);

        foreach ($filters as $column => $value) {
            $query->andWhere($column . ' = ' . $query->createNamedParameter($value));
        }

        $query->setMaxResults(1);

        $result = $query->execute()->fetch();
        if ($result === null || $result === false) {
            return null;
        }

        return $this->typeConverter->convert($result, DefaultTestEntity::FIELD_MAP);
    }

    /**
     * @param array $filters
     *
     * @return int
     */
    private function getCountFromDB(array $filters = []): int
    {
        $query = $this->dbalConnection->createQueryBuilder();
        $query->select('count(*) as count');
        $query->from(DefaultTestEntity::TABLE_NAME);

        foreach ($filters as $column => $value) {
            $query->andWhere($column . ' = ' . $query->createNamedParameter($value));
        }

        $result = $query->execute()->fetch();
        if ($result === null || $result === false) {
            throw new \RuntimeException('Aggregation query returned no rows');
        }

        return $result['count'];
    }
}
