<?php

namespace Tests;

use DBALTableManager\BaseConnectionInterface;
use DBALTableManager\BaseManager;
use DBALTableManager\Filter;
use DBALTableManager\Pagination;
use DBALTableManager\Sorting;
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

    protected function setUp(): void
    {
        /** @var BaseConnectionInterface $connection */
        $this->dbalConnection = DriverManager::getConnection([
            'pdo' => $this->getPdo(),
            'wrapperClass' => DBALDefaultConnection::class,
        ]);
//        $this->dbalConnection->getConfiguration()->setSQLLogger(new \Tests\Support\EchoSQLLogger());

        $this->manager = new DefaultTestManager($this->dbalConnection, new TypeConverter());

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

    protected function getPdo()
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
                    [
                        'id' => 1,
                        'name' => 'John',
                        'birthday' => '2010-01-02',
                        'age' => 22,
                        'weight' => 40.4,
                        'married' => 1,
                        'created_at' => '2018-04-04 12:44:22',
                        'updated_at' => '2018-04-04 12:44:22',
                        'deleted_at' => null,
                    ],
                    [
                        'id' => 2,
                        'name' => 'Mister X',
                        'birthday' => '2010-02-02',
                        'age' => 13,
                        'weight' => 30.4,
                        'married' => 0,
                        'created_at' => '2018-05-04 12:44:22',
                        'updated_at' => '2018-05-04 12:44:22',
                        'deleted_at' => null,
                    ],
                    [
                        'id' => 3,
                        'name' => 'Soft Deleted User',
                        'birthday' => '2010-03-02',
                        'age' => 33,
                        'weight' => 20.4,
                        'married' => 1,
                        'created_at' => '2018-06-04 12:44:22',
                        'updated_at' => '2018-06-04 12:44:22',
                        'deleted_at' => '2018-06-04 12:44:22',
                    ],
                    [
                        'id' => 4,
                        'name' => 'Someone',
                        'birthday' => null,
                        'age' => 44,
                        'weight' => 50.4,
                        'married' => 0,
                        'created_at' => '2018-05-04 12:44:22',
                        'updated_at' => '2018-05-04 12:44:22',
                        'deleted_at' => null,
                    ],
                ],
            ]
        );
    }

    public function testSuccessFindAllWithoutAnything(): void
    {
        // action
        $data = $this->manager->findAll(new Filter(), new Pagination(), new Sorting());

        // assert
        self::assertCount(3, $data);
    }

    public function testSuccessFindAllWithFilterEquals(): void
    {
        // arrange
        $filter = new Filter();
        $filter->equals('name', 'Someone');

        // action
        $data = $this->manager->findAll($filter, new Pagination(), new Sorting());

        // assert
        self::assertCount(1, $data);
        self::assertEquals(4, $data[0]['id']);
    }

    public function testSuccessFindAllWithFilterNotEquals(): void
    {
        // arrange
        $filter = new Filter();
        $filter->notEquals('name', 'Someone');

        // action
        $data = $this->manager->findAll($filter, new Pagination(), new Sorting());

        // assert
        self::assertCount(2, $data);
        self::assertEquals(1, $data[0]['id']);
        self::assertEquals(2, $data[1]['id']);
    }

    public function testSuccessFindAllWithFilterLessThan(): void
    {
        // arrange
        $filter = new Filter();
        $filter->lessThan('age', 22);

        // action
        $data = $this->manager->findAll($filter, new Pagination(), new Sorting());

        // assert
        self::assertCount(1, $data);
        self::assertEquals(2, $data[0]['id']);
    }

    public function testSuccessFindAllWithFilterLessOrEquals(): void
    {
        // arrange
        $filter = new Filter();
        $filter->lessOrEquals('age', 22);

        // action
        $data = $this->manager->findAll($filter, new Pagination(), new Sorting());

        // assert
        self::assertCount(2, $data);
        self::assertEquals(1, $data[0]['id']);
        self::assertEquals(2, $data[1]['id']);
    }

    public function testSuccessFindAllWithFilterGreaterThan(): void
    {
        // arrange
        $filter = new Filter();
        $filter->greaterThan('age', 22);

        // action
        $data = $this->manager->findAll($filter, new Pagination(), new Sorting());

        // assert
        self::assertCount(1, $data);
        self::assertEquals(4, $data[0]['id']);
    }

    public function testSuccessFindAllWithFilterGreaterOrEquals(): void
    {
        // arrange
        $filter = new Filter();
        $filter->greaterOrEquals('age', 22);

        // action
        $data = $this->manager->findAll($filter, new Pagination(), new Sorting());

        // assert
        self::assertCount(2, $data);
        self::assertEquals(1, $data[0]['id']);
        self::assertEquals(4, $data[1]['id']);
    }

    public function testSuccessFindAllWithFilterIsNull(): void
    {
        // arrange
        $filter = new Filter();
        $filter->isNull('birthday');

        // action
        $data = $this->manager->findAll($filter, new Pagination(), new Sorting());

        // assert
        self::assertCount(1, $data);
        self::assertEquals(4, $data[0]['id']);
    }

    public function testSuccessFindAllWithFilterIsNotNull(): void
    {
        // arrange
        $filter = new Filter();
        $filter->isNotNull('birthday');

        // action
        $data = $this->manager->findAll($filter, new Pagination(), new Sorting());

        // assert
        self::assertCount(2, $data);
        self::assertEquals(1, $data[0]['id']);
        self::assertEquals(2, $data[1]['id']);
    }

    public function testSuccessFindAllWithFilterInString(): void
    {
        // arrange
        $filter = new Filter();
        $filter->in('name', ['John', 'Mister X']);

        // action
        $data = $this->manager->findAll($filter, new Pagination(), new Sorting());

        // assert
        self::assertCount(2, $data);
        self::assertEquals(1, $data[0]['id']);
        self::assertEquals(2, $data[1]['id']);
    }

    public function testSuccessFindAllWithFilterNotInString(): void
    {
        // arrange
        $filter = new Filter();
        $filter->notIn('name', ['John', 'Mister X']);

        // action
        $data = $this->manager->findAll($filter, new Pagination(), new Sorting());

        // assert
        self::assertCount(1, $data);
        self::assertEquals(4, $data[0]['id']);
    }

    public function testSuccessFindAllWithFilterInInt(): void
    {
        // arrange
        $filter = new Filter();
        $filter->in('age', [22, 13]);

        // action
        $data = $this->manager->findAll($filter, new Pagination(), new Sorting());

        // assert
        self::assertCount(2, $data);
        self::assertEquals(1, $data[0]['id']);
        self::assertEquals(2, $data[1]['id']);
    }

    public function testSuccessFindAllWithFilterNotInInt(): void
    {
        // arrange
        $filter = new Filter();
        $filter->notIn('age', [22, 13]);

        // action
        $data = $this->manager->findAll($filter, new Pagination(), new Sorting());

        // assert
        self::assertCount(1, $data);
        self::assertEquals(4, $data[0]['id']);
    }

    public function testSuccessFindAllWithFilterDeleted(): void
    {
        // arrange
        $filter = new Filter();
        $filter->deleted([true, false]);

        // action
        $data = $this->manager->findAll($filter, new Pagination(), new Sorting());

        // assert
        self::assertCount(4, $data);
    }

    public function testSuccessFindAllWithPagination(): void
    {
        // arrange
        $pagination = new Pagination(1, 2);

        // action
        $data = $this->manager->findAll(new Filter(), $pagination, new Sorting());

        // assert
        self::assertCount(1, $data);
        self::assertEquals(4, $data[0]['id']);
    }

    public function testSuccessFindAllWithSortingAsc(): void
    {
        // arrange
        $sorting = new Sorting();
        $sorting->addSorting('age', 'asc');

        // action
        $data = $this->manager->findAll(new Filter(), new Pagination(), $sorting);

        // assert
        self::assertCount(3, $data);
        self::assertEquals(2, $data[0]['id']);
        self::assertEquals(1, $data[1]['id']);
        self::assertEquals(4, $data[2]['id']);
    }

    public function testSuccessFindAllWithSortingDesc(): void
    {
        // arrange
        $sorting = new Sorting();
        $sorting->addSorting('age', 'desc');

        // action
        $data = $this->manager->findAll(new Filter(), new Pagination(), $sorting);

        // assert
        self::assertCount(3, $data);
        self::assertEquals(4, $data[0]['id']);
        self::assertEquals(1, $data[1]['id']);
        self::assertEquals(2, $data[2]['id']);
    }

    public function testSuccessFindAllWithDoubleSorting(): void
    {
        // arrange
        $sorting = new Sorting();
        $sorting->addSorting('created_at', 'asc');
        $sorting->addSorting('age', 'desc');

        // action
        $data = $this->manager->findAll(new Filter(), new Pagination(), $sorting);

        // assert
        self::assertCount(3, $data);
        self::assertEquals(1, $data[0]['id']);
        self::assertEquals(4, $data[1]['id']);
        self::assertEquals(2, $data[2]['id']);
    }

    public function testSuccessFindOneWithoutAnything(): void
    {
        // action
        $data = $this->manager->findOne(new Filter(), new Sorting());

        // assert
        self::assertNotNull($data);
        self::assertEquals(1, $data['id']);
    }

    public function testSuccessFindOneWithFilterEquals(): void
    {
        // arrange
        $filter = new Filter();
        $filter->equals('name', 'Someone');

        // action
        $data = $this->manager->findOne($filter, new Sorting());

        // assert
        self::assertNotNull($data);
        self::assertEquals(4, $data['id']);
    }

    public function testSuccessFindOneWithFilterNotEquals(): void
    {
        // arrange
        $filter = new Filter();
        $filter->notEquals('name', 'Someone');

        // action
        $data = $this->manager->findOne($filter, new Sorting());

        // assert
        self::assertNotNull($data);
        self::assertEquals(1, $data['id']);
    }

    public function testSuccessFindOneWithFilterLessThan(): void
    {
        // arrange
        $filter = new Filter();
        $filter->lessThan('age', 22);

        // action
        $data = $this->manager->findOne($filter, new Sorting());

        // assert
        self::assertNotNull($data);
        self::assertEquals(2, $data['id']);
    }

    public function testSuccessFindOneWithFilterLessOrEquals(): void
    {
        // arrange
        $filter = new Filter();
        $filter->lessOrEquals('age', 22);

        // action
        $data = $this->manager->findOne($filter, new Sorting());

        // assert
        self::assertNotNull($data);
        self::assertEquals(1, $data['id']);
    }

    public function testSuccessFindOneWithFilterGreaterThan(): void
    {
        // arrange
        $filter = new Filter();
        $filter->greaterThan('age', 22);

        // action
        $data = $this->manager->findOne($filter, new Sorting());

        // assert
        self::assertNotNull($data);
        self::assertEquals(4, $data['id']);
    }

    public function testSuccessFindOneWithFilterGreaterOrEquals(): void
    {
        // arrange
        $filter = new Filter();
        $filter->greaterOrEquals('age', 22);

        // action
        $data = $this->manager->findOne($filter, new Sorting());

        // assert
        self::assertNotNull($data);
        self::assertEquals(1, $data['id']);
    }

    public function testSuccessFindOneWithFilterIsNull(): void
    {
        // arrange
        $filter = new Filter();
        $filter->isNull('birthday');

        // action
        $data = $this->manager->findOne($filter, new Sorting());

        // assert
        self::assertNotNull($data);
        self::assertEquals(4, $data['id']);
    }

    public function testSuccessFindOneWithFilterIsNotNull(): void
    {
        // arrange
        $filter = new Filter();
        $filter->isNotNull('birthday');

        // action
        $data = $this->manager->findOne($filter, new Sorting());

        // assert
        self::assertNotNull($data);
        self::assertEquals(1, $data['id']);
    }

    public function testSuccessFindOneWithFilterInString(): void
    {
        // arrange
        $filter = new Filter();
        $filter->in('name', ['John', 'Mister X']);

        // action
        $data = $this->manager->findOne($filter, new Sorting());

        // assert
        self::assertNotNull($data);
        self::assertEquals(1, $data['id']);
    }

    public function testSuccessFindOneWithFilterNotInString(): void
    {
        // arrange
        $filter = new Filter();
        $filter->notIn('name', ['John', 'Mister X']);

        // action
        $data = $this->manager->findOne($filter, new Sorting());

        // assert
        self::assertNotNull($data);
        self::assertEquals(4, $data['id']);
    }

    public function testSuccessFindOneWithFilterInInt(): void
    {
        // arrange
        $filter = new Filter();
        $filter->in('age', [22, 13]);

        // action
        $data = $this->manager->findOne($filter, new Sorting());

        // assert
        self::assertNotNull($data);
        self::assertEquals(1, $data['id']);
    }

    public function testSuccessFindOneWithFilterNotInInt(): void
    {
        // arrange
        $filter = new Filter();
        $filter->notIn('age', [22, 13]);

        // action
        $data = $this->manager->findOne($filter, new Sorting());

        // assert
        self::assertNotNull($data);
        self::assertEquals(4, $data['id']);
    }

    public function testSuccessFindOneWithFilterDeleted(): void
    {
        // arrange
        $filter = new Filter();
        $filter->deleted([true, false]);

        // action
        $data = $this->manager->findOne($filter, new Sorting());

        // assert
        self::assertNotNull($data);
        self::assertEquals(1, $data['id']);
    }

    public function testSuccessFindOneWithSortingAsc(): void
    {
        // arrange
        $sorting = new Sorting();
        $sorting->addSorting('age', 'asc');

        // action
        $data = $this->manager->findOne(new Filter(), $sorting);

        // assert
        self::assertNotNull($data);
        self::assertEquals(2, $data['id']);
    }

    public function testSuccessFindOneWithSortingDesc(): void
    {
        // arrange
        $sorting = new Sorting();
        $sorting->addSorting('age', 'desc');

        // action
        $data = $this->manager->findOne(new Filter(), $sorting);

        // assert
        self::assertNotNull($data);
        self::assertEquals(4, $data['id']);
    }

    public function testSuccessFindOneWithDoubleSorting(): void
    {
        // arrange
        $sorting = new Sorting();
        $sorting->addSorting('created_at', 'asc');
        $sorting->addSorting('age', 'desc');

        // action
        $data = $this->manager->findOne(new Filter(), $sorting);

        // assert
        self::assertNotNull($data);
        self::assertEquals(1, $data['id']);
    }

    public function testSuccessNotFound(): void
    {
        // arrange
        $filter = new Filter();
        $filter->equals(DefaultTestEntity::PK_COLUMN, 100000);

        // action
        $data = $this->manager->findOne($filter, new Sorting());

        // assert
        self::assertNull($data);
    }

    public function testSuccessFindByPk(): void
    {
        // action
        $data = $this->manager->findByPk(1);

        // assert
        self::assertNotNull($data);
        self::assertEquals(1, $data['id']);
    }

    public function testSuccessNotFindByPk(): void
    {
        // action
        $data = $this->manager->findByPk(10000);

        // assert
        self::assertNull($data);
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

        $insertedData = $this->manager->findByPk($id);
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
            $insertedData = $this->manager->findOne(
                (new Filter())->equals('name', $dataForInsert['name']),
                new Sorting()
            );
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
        $dataForUpdate = [
            'name' => 'Updated User',
            'birthday' => '2016-02-02',
            'age' => 44,
            'weight' => 32.4,
            'married' => 1,
        ];

        $filter = new Filter();
        $filter->equals('name', 'Someone');

        // action
        $count = $this->manager->update($dataForUpdate, $filter);

        // assert
        self::assertEquals(1, $count);

        $updatedData = $this->manager->findOne(
            (new Filter())->equals('name', $dataForUpdate['name']),
            new Sorting()
        );
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
        $id = 4;

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

        $updatedData = $this->manager->findByPk($id);
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

        $idList = [1, 2];

        $filterList = [];
        foreach ($idList as $id) {
            $filterList = [
                (new Filter())->equals(DefaultTestEntity::PK_COLUMN, 1),
                (new Filter())->equals(DefaultTestEntity::PK_COLUMN, 2),
            ];
        }

        // action
        $count = $this->manager->batchUpdate($dataForUpdateList, $filterList);

        // assert
        self::assertEquals(2, $count);

        foreach ($dataForUpdateList as $i => $dataForUpdate) {
            $insertedData = $this->manager->findByPk($idList[$i]);
            self::assertEquals($dataForUpdate['name'], $insertedData['name']);
            self::assertEquals($dataForUpdate['birthday'], $insertedData['birthday']);
            self::assertEquals($dataForUpdate['age'], $insertedData['age']);
            self::assertEquals($dataForUpdate['weight'], $insertedData['weight']);
            self::assertEquals($dataForUpdate['married'], $insertedData['married']);
        }
    }

    public function testSuccessDelete(): void
    {
        // arrange
        $filter = new Filter();
        $filter->equals('name', 'Someone');

        // action
        $count = $this->manager->delete($filter);

        // assert
        self::assertEquals(1, $count);

        $deletedRow = $this->manager->findOne($filter, new Sorting());
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
        $id = 4;

        // action
        $count = $this->manager->deleteByPk($id);

        // assert
        self::assertEquals(1, $count);

        $deletedRow = $this->manager->findByPk($id);
        self::assertNull($deletedRow);
    }

    public function testSuccessDeleteAll(): void
    {
        // action
        $count = $this->manager->deleteAll();

        // assert
        self::assertEquals(4, $count);

        $totalCount = $this->manager->getCount((new Filter())->deleted([true, false]));
        self::assertEquals(0, $totalCount);
    }

    public function testSuccessSoftDelete(): void
    {
        // arrange
        $filter = new Filter();
        $filter->equals('name', 'Someone');

        // action
        $count = $this->manager->softDelete($filter);

        // assert
        self::assertEquals(1, $count);

        $filter = new Filter();
        $filter->equals('name', 'Someone');
        $filter->deleted([true, false]);

        $deletedRow = $this->manager->findOne($filter, new Sorting());
        self::assertNotNull($deletedRow);
        self::assertNotNull($deletedRow[DefaultTestEntity::DELETED_AT_COLUMN]);
    }

    public function testSuccessSoftDeleteByPk(): void
    {
        // arrange
        $id = 4;

        // action
        $count = $this->manager->softDeleteByPk($id);

        // assert
        self::assertEquals(1, $count);

        $deletedRow = $this->manager->findByPk($id);
        self::assertNotNull($deletedRow);
        self::assertNotNull($deletedRow[DefaultTestEntity::DELETED_AT_COLUMN]);
    }

    public function testSuccessSoftDeleteAll(): void
    {
        // action
        $count = $this->manager->softDeleteAll();

        // assert
        self::assertEquals(4, $count);

        $totalCount = $this->manager->getCount((new Filter())->deleted([true, false]));
        self::assertEquals(4, $totalCount);

        $deletedRows = $this->manager->findAll((new Filter())->deleted([true, false]), new Pagination(), new Sorting());
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
        $filter = new Filter();
        $filter->equals('name', 'Someone');

        // action
        $count = $this->manager->getCount($filter);

        // assert
        self::assertEquals(1, $count);
    }

    public function testSuccessGetCountWithFilterNotEquals(): void
    {
        // arrange
        $filter = new Filter();
        $filter->notEquals('name', 'Someone');

        // action
        $count = $this->manager->getCount($filter);

        // assert
        self::assertEquals(2, $count);
    }

    public function testSuccessGetCountWithFilterLessThan(): void
    {
        // arrange
        $filter = new Filter();
        $filter->lessThan('age', 22);

        // action
        $count = $this->manager->getCount($filter);

        // assert
        self::assertEquals(1, $count);
    }

    public function testSuccessGetCountWithFilterLessOrEquals(): void
    {
        // arrange
        $filter = new Filter();
        $filter->lessOrEquals('age', 22);

        // action
        $count = $this->manager->getCount($filter);

        // assert
        self::assertEquals(2, $count);
    }

    public function testSuccessGetCountWithFilterGreaterThan(): void
    {
        // arrange
        $filter = new Filter();
        $filter->greaterThan('age', 22);

        // action
        $count = $this->manager->getCount($filter);

        // assert
        self::assertEquals(1, $count);
    }

    public function testSuccessGetCountWithFilterGreaterOrEquals(): void
    {
        // arrange
        $filter = new Filter();
        $filter->greaterOrEquals('age', 22);

        // action
        $count = $this->manager->getCount($filter);

        // assert
        self::assertEquals(2, $count);
    }

    public function testSuccessGetCountWithFilterIsNull(): void
    {
        // arrange
        $filter = new Filter();
        $filter->isNull('birthday');

        // action
        $count = $this->manager->getCount($filter);

        // assert
        self::assertEquals(1, $count);
    }

    public function testSuccessGetCountWithFilterIsNotNull(): void
    {
        // arrange
        $filter = new Filter();
        $filter->isNotNull('birthday');

        // action
        $count = $this->manager->getCount($filter);

        // assert
        self::assertEquals(2, $count);
    }

    public function testSuccessGetCountWithFilterInString(): void
    {
        // arrange
        $filter = new Filter();
        $filter->in('name', ['John', 'Mister X']);

        // action
        $count = $this->manager->getCount($filter);

        // assert
        self::assertEquals(2, $count);
    }

    public function testSuccessGetCountWithFilterNotInString(): void
    {
        // arrange
        $filter = new Filter();
        $filter->notIn('name', ['John', 'Mister X']);

        // action
        $count = $this->manager->getCount($filter);

        // assert
        self::assertEquals(1, $count);
    }

    public function testSuccessGetCountWithFilterInInt(): void
    {
        // arrange
        $filter = new Filter();
        $filter->in('age', [22, 13]);

        // action
        $count = $this->manager->getCount($filter);

        // assert
        self::assertEquals(2, $count);
    }

    public function testSuccessGetCountWithFilterNotInInt(): void
    {
        // arrange
        $filter = new Filter();
        $filter->notIn('age', [22, 13]);

        // action
        $count = $this->manager->getCount($filter);

        // assert
        self::assertEquals(1, $count);
    }

    public function testSuccessGetCountWithFilterDeleted(): void
    {
        // arrange
        $filter = new Filter();
        $filter->deleted([true, false]);

        // action
        $count = $this->manager->getCount($filter);

        // assert
        self::assertEquals(4, $count);
    }

    public function testSuccessTruncate(): void
    {
        // action
        $this->manager->truncate();

        // assert
        $totalCount = $this->manager->getCount((new Filter())->deleted([true, false]));
        self::assertEquals(0, $totalCount);
    }
}
