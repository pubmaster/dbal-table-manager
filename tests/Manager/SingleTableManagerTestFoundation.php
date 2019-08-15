<?php

namespace Tests\DBALTableManager\Manager;

use DBALTableManager\Exception\InvalidRequestException;
use DBALTableManager\Manager\SingleTableManager;
use DBALTableManager\Query\Filter;
use DBALTableManager\Query\Pagination;
use DBALTableManager\Query\Sorting;
use PHPUnit\DbUnit\Database\DefaultConnection as DbUnitDefaultConnection;
use PHPUnit\DbUnit\TestCase;
use Tests\DBALTableManager\Support\CurrentTimeStub;
use Tests\DBALTableManager\Support\DatabaseTableDataRetriever;
use Tests\DBALTableManager\Support\DefaultTestEntity;

/**
 * Class SingleTableManagerTestFoundation
 *
 * @package Tests\DBALTableManager\Manager
 */
abstract class SingleTableManagerTestFoundation extends TestCase
{
    protected const USER_1 = [
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
    protected const USER_2 = [
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
    protected const USER_3 = [
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
    protected const USER_4 = [
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
     * @var DbUnitDefaultConnection
     */
    protected $phpUnitDbConnection;
    /**
     * @var SingleTableManager
     */
    protected $manager;
    /**
     * @var DatabaseTableDataRetriever
     */
    protected $dataRetriever;
    /**
     * @var CurrentTimeStub
     */
    protected $currentTime;

    /**
     * @return \PDO
     */
    abstract protected function getPdo(): \PDO;

    /**
     * @return string
     */
    abstract protected function getDbNameForTests(): string;

    protected function getConnection()
    {
        if ($this->phpUnitDbConnection !== null) {
            return $this->phpUnitDbConnection;
        }

        $this->phpUnitDbConnection = $this->createDefaultDBConnection($this->getPdo(), $this->getDbNameForTests());

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

    public function testSuccessGetCountWithoutAnything(): void
    {
        // action
        $count = $this->manager->getCount();

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

    public function testSuccessGetCountWithFilterInEmptyArray(): void
    {
        // arrange
        $targetUsers = [];

        $filter = new Filter();
        $filter->in('age', []);

        // action
        $count = $this->manager->getCount($filter);

        // assert
        self::assertEquals(count($targetUsers), $count);
    }

    public function testSuccessGetCountWithFilterNotInEmptyArray(): void
    {
        // arrange
        $targetUsers = [];

        $filter = new Filter();
        $filter->notIn('age', []);

        // action
        $count = $this->manager->getCount($filter);

        // assert
        self::assertEquals(count($targetUsers), $count);
    }

    public function testSuccessGetCountWithFilterInEmptyArrayAsNoFilter(): void
    {
        // arrange
        $targetUsers = [
            self::USER_1,
            self::USER_2,
            self::USER_4,
        ];

        $filter = new Filter();
        $filter->in('age', [], true);

        // action
        $count = $this->manager->getCount($filter);

        // assert
        self::assertEquals(count($targetUsers), $count);
    }

    public function testSuccessGetCountWithFilterNotInEmptyArrayAsNoFilter(): void
    {
        // arrange
        $targetUsers = [
            self::USER_1,
            self::USER_2,
            self::USER_4,
        ];

        $filter = new Filter();
        $filter->notIn('age', [], true);

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

    public function testSuccessGetCountWithFilterDeletedTrue(): void
    {
        // arrange
        $targetUsers = [
            self::USER_3,
        ];

        $filter = new Filter();
        $filter->deleted([true]);

        // action
        $count = $this->manager->getCount($filter);

        // assert
        self::assertEquals(count($targetUsers), $count);
    }

    public function testSuccessGetCountWithFilterDeletedFalse(): void
    {
        // arrange
        $targetUsers = [
            self::USER_1,
            self::USER_2,
            self::USER_4,
        ];

        $filter = new Filter();
        $filter->deleted([false]);

        // action
        $count = $this->manager->getCount($filter);

        // assert
        self::assertEquals(count($targetUsers), $count);
    }

    public function testSuccessGetCountWithFilterDeletedTrueFalse(): void
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

    public function testSuccessFindAllWithoutAnything(): void
    {
        // arrange
        $notDeletedUsers = [
            self::USER_1,
            self::USER_2,
            self::USER_4,
        ];

        // action
        $resultUsers = $this->manager->findAll();

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
        $resultUsers = $this->manager->findAll($filter);

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
        $resultUsers = $this->manager->findAll($filter);

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
        $resultUsers = $this->manager->findAll($filter);

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
        $resultUsers = $this->manager->findAll($filter);

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
        $resultUsers = $this->manager->findAll($filter);

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
        $resultUsers = $this->manager->findAll($filter);

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
        $resultUsers = $this->manager->findAll($filter);

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
        $resultUsers = $this->manager->findAll($filter);

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
        $resultUsers = $this->manager->findAll($filter);

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
        $resultUsers = $this->manager->findAll($filter);

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
        $resultUsers = $this->manager->findAll($filter);

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
        $resultUsers = $this->manager->findAll($filter);

        // assert
        self::assertCount(count($targetUsers), $resultUsers);
        foreach ($resultUsers as $index => $resultUser) {
            self::assertEquals($targetUsers[$index]['id'], $resultUser['id']);
        }
    }

    public function testSuccessFindAllWithFilterInEmptyArray(): void
    {
        // arrange
        $targetUsers = [];

        $filter = new Filter();
        $filter->in('age', []);

        // action
        $resultUsers = $this->manager->findAll($filter);

        // assert
        self::assertCount(count($targetUsers), $resultUsers);
        foreach ($resultUsers as $index => $resultUser) {
            self::assertEquals($targetUsers[$index]['id'], $resultUser['id']);
        }
    }

    public function testSuccessFindAllWithFilterNotInEmptyArray(): void
    {
        // arrange
        $targetUsers = [];

        $filter = new Filter();
        $filter->notIn('age', []);

        // action
        $resultUsers = $this->manager->findAll($filter);

        // assert
        self::assertCount(count($targetUsers), $resultUsers);
        foreach ($resultUsers as $index => $resultUser) {
            self::assertEquals($targetUsers[$index]['id'], $resultUser['id']);
        }
    }

    public function testSuccessFindAllWithFilterInEmptyArrayAsNoFilter(): void
    {
        // arrange
        $targetUsers = [
            self::USER_1,
            self::USER_2,
            self::USER_4,
        ];

        $filter = new Filter();
        $filter->in('age', [], true);

        // action
        $resultUsers = $this->manager->findAll($filter);

        // assert
        self::assertCount(count($targetUsers), $resultUsers);
        foreach ($resultUsers as $index => $resultUser) {
            self::assertEquals($targetUsers[$index]['id'], $resultUser['id']);
        }
    }

    public function testSuccessFindAllWithFilterNotInEmptyArrayAsNoFilter(): void
    {
        // arrange
        $targetUsers = [
            self::USER_1,
            self::USER_2,
            self::USER_4,
        ];

        $filter = new Filter();
        $filter->notIn('age', [], true);

        // action
        $resultUsers = $this->manager->findAll($filter);

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
        $resultUsers = $this->manager->findAll($filter);

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
        $resultUsers = $this->manager->findAll($filter);

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
        $resultUsers = $this->manager->findAll($filter);

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
        $resultUsers = $this->manager->findAll($filter);

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
        $resultUsers = $this->manager->findAll($filter);

        // assert
        self::assertCount(count($targetUsers), $resultUsers);
        foreach ($resultUsers as $index => $resultUser) {
            self::assertEquals($targetUsers[$index]['id'], $resultUser['id']);
        }
    }

    public function testSuccessFindAllWithFilterDeletedTrue(): void
    {
        // arrange
        $targetUsers = [
            self::USER_3,
        ];

        $filter = new Filter();
        $filter->deleted([true]);

        // action
        $resultUsers = $this->manager->findAll($filter);

        // assert
        self::assertCount(count($targetUsers), $resultUsers);
        foreach ($resultUsers as $index => $resultUser) {
            self::assertEquals($targetUsers[$index]['id'], $resultUser['id']);
        }
    }

    public function testSuccessFindAllWithFilterDeletedFalse(): void
    {
        // arrange
        $targetUsers = [
            self::USER_1,
            self::USER_2,
            self::USER_4,
        ];

        $filter = new Filter();
        $filter->deleted([false]);

        // action
        $resultUsers = $this->manager->findAll($filter);

        // assert
        self::assertCount(count($targetUsers), $resultUsers);
        foreach ($resultUsers as $index => $resultUser) {
            self::assertEquals($targetUsers[$index]['id'], $resultUser['id']);
        }
    }

    public function testSuccessFindAllWithFilterDeletedTrueFalse(): void
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
        $resultUsers = $this->manager->findAll($filter);

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
        $resultUsers = $this->manager->findAll(null, $pagination);

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
        $resultUsers = $this->manager->findAll(null, null, $sorting);

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
        $resultUsers = $this->manager->findAll(null, null, $sorting);

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
        $resultUsers = $this->manager->findAll(null, null, $sorting);

        // assert
        self::assertCount(count($targetUsers), $resultUsers);
        foreach ($resultUsers as $index => $resultUser) {
            self::assertEquals($targetUsers[$index]['id'], $resultUser['id']);
        }
    }

    public function testSuccessFindOneByFilterWithEmptyFilter(): void
    {
        // arrange
        $targetUser = self::USER_1;

        // action
        $resultUser = $this->manager->findOneByFilter(new Filter());

        // assert
        self::assertNotNull($resultUser);
        self::assertEquals($targetUser['id'], $resultUser['id']);
    }

    public function testSuccessFindOneByFilterWithFilterEquals(): void
    {
        // arrange
        $targetUser = self::USER_4;

        $filter = new Filter();
        $filter->equals('name', 'Someone');

        // action
        $resultUser = $this->manager->findOneByFilter($filter);

        // assert
        self::assertNotNull($resultUser);
        self::assertEquals($targetUser['id'], $resultUser['id']);
    }

    public function testSuccessFindOneByFilterWithFilterNotEquals(): void
    {
        // arrange
        $targetUser = self::USER_1;

        $filter = new Filter();
        $filter->notEquals('name', 'Someone');

        // action
        $resultUser = $this->manager->findOneByFilter($filter);

        // assert
        self::assertNotNull($resultUser);
        self::assertEquals($targetUser['id'], $resultUser['id']);
    }

    public function testSuccessFindOneByFilterWithFilterLessThan(): void
    {
        // arrange
        $targetUser = self::USER_2;

        $filter = new Filter();
        $filter->lessThan('age', 22);

        // action
        $resultUser = $this->manager->findOneByFilter($filter);

        // assert
        self::assertNotNull($resultUser);
        self::assertEquals($targetUser['id'], $resultUser['id']);
    }

    public function testSuccessFindOneByFilterWithFilterLessOrEquals(): void
    {
        // arrange
        $targetUser = self::USER_1;

        $filter = new Filter();
        $filter->lessOrEquals('age', 22);

        // action
        $resultUser = $this->manager->findOneByFilter($filter);

        // assert
        self::assertNotNull($resultUser);
        self::assertEquals($targetUser['id'], $resultUser['id']);
    }

    public function testSuccessFindOneByFilterWithFilterGreaterThan(): void
    {
        // arrange
        $targetUser = self::USER_4;

        $filter = new Filter();
        $filter->greaterThan('age', 22);

        // action
        $resultUser = $this->manager->findOneByFilter($filter);

        // assert
        self::assertNotNull($resultUser);
        self::assertEquals($targetUser['id'], $resultUser['id']);
    }

    public function testSuccessFindOneByFilterWithFilterGreaterOrEquals(): void
    {
        // arrange
        $targetUser = self::USER_1;

        $filter = new Filter();
        $filter->greaterOrEquals('age', 22);

        // action
        $resultUser = $this->manager->findOneByFilter($filter);

        // assert
        self::assertNotNull($resultUser);
        self::assertEquals($targetUser['id'], $resultUser['id']);
    }

    public function testSuccessFindOneByFilterWithFilterIsNull(): void
    {
        // arrange
        $targetUser = self::USER_4;

        $filter = new Filter();
        $filter->isNull('birthday');

        // action
        $resultUser = $this->manager->findOneByFilter($filter);

        // assert
        self::assertNotNull($resultUser);
        self::assertEquals($targetUser['id'], $resultUser['id']);
    }

    public function testSuccessFindOneByFilterWithFilterIsNotNull(): void
    {
        // arrange
        $targetUser = self::USER_1;

        $filter = new Filter();
        $filter->isNotNull('birthday');

        // action
        $resultUser = $this->manager->findOneByFilter($filter);

        // assert
        self::assertNotNull($resultUser);
        self::assertEquals($targetUser['id'], $resultUser['id']);
    }

    public function testSuccessFindOneByFilterWithFilterInString(): void
    {
        // arrange
        $targetUser = self::USER_1;

        $filter = new Filter();
        $filter->in('name', ['John', 'Mister X']);

        // action
        $resultUser = $this->manager->findOneByFilter($filter);

        // assert
        self::assertNotNull($resultUser);
        self::assertEquals($targetUser['id'], $resultUser['id']);
    }

    public function testSuccessFindOneByFilterWithFilterNotInString(): void
    {
        // arrange
        $targetUser = self::USER_4;

        $filter = new Filter();
        $filter->notIn('name', ['John', 'Mister X']);

        // action
        $resultUser = $this->manager->findOneByFilter($filter);

        // assert
        self::assertNotNull($resultUser);
        self::assertEquals($targetUser['id'], $resultUser['id']);
    }

    public function testSuccessFindOneByFilterWithFilterInInt(): void
    {
        // arrange
        $targetUser = self::USER_1;

        $filter = new Filter();
        $filter->in('age', [22, 13]);

        // action
        $resultUser = $this->manager->findOneByFilter($filter);

        // assert
        self::assertNotNull($resultUser);
        self::assertEquals($targetUser['id'], $resultUser['id']);
    }

    public function testSuccessFindOneByFilterWithFilterNotInInt(): void
    {
        // arrange
        $targetUser = self::USER_4;

        $filter = new Filter();
        $filter->notIn('age', [22, 13]);

        // action
        $resultUser = $this->manager->findOneByFilter($filter);

        // assert
        self::assertNotNull($resultUser);
        self::assertEquals($targetUser['id'], $resultUser['id']);
    }

    public function testSuccessFindOneByFilterInEmptyArray(): void
    {
        // arrange
        $filter = new Filter();
        $filter->in('age', []);

        // action
        $resultUser = $this->manager->findOneByFilter($filter);

        // assert
        self::assertNull($resultUser);
    }

    public function testSuccessFindOneByFilterNotInEmptyArray(): void
    {
        // arrange
        $filter = new Filter();
        $filter->notIn('age', []);

        // action
        $resultUser = $this->manager->findOneByFilter($filter);

        // assert
        self::assertNull($resultUser);
    }

    public function testSuccessFindOneByFilterInEmptyArrayAsNoFilter(): void
    {
        // arrange
        $targetUser = self::USER_1;

        $filter = new Filter();
        $filter->in('age', [], true);

        // action
        $resultUser = $this->manager->findOneByFilter($filter);

        // assert
        self::assertNotNull($resultUser);
        self::assertEquals($targetUser['id'], $resultUser['id']);
    }

    public function testSuccessFindOneByFilterNotInEmptyArrayAsNoFilter(): void
    {
        // arrange
        $targetUser = self::USER_1;

        $filter = new Filter();
        $filter->notIn('age', [], true);

        // action
        $resultUser = $this->manager->findOneByFilter($filter);

        // assert
        self::assertNotNull($resultUser);
        self::assertEquals($targetUser['id'], $resultUser['id']);
    }

    public function testSuccessFindOneByFilterWithFilterLikeNoBounds(): void
    {
        // arrange
        $targetUser = self::USER_2;

        $filter = new Filter();
        $filter->like('name', 'ster');

        // action
        $resultUser = $this->manager->findOneByFilter($filter);

        // assert
        self::assertNotNull($resultUser);
        self::assertEquals($targetUser['id'], $resultUser['id']);
    }

    public function testSuccessFindOneByFilterWithFilterLikeFromBeginning(): void
    {
        // arrange
        $targetUser = self::USER_2;

        $filter = new Filter();
        $filter->like('name', 'Mist', true);

        // action
        $resultUser = $this->manager->findOneByFilter($filter);

        // assert
        self::assertNotNull($resultUser);
        self::assertEquals($targetUser['id'], $resultUser['id']);
    }

    public function testSuccessFindOneByFilterWithFilterLikeToEnd(): void
    {
        // arrange
        $targetUser = self::USER_2;

        $filter = new Filter();
        $filter->like('name', 'er X', false, true);

        // action
        $resultUser = $this->manager->findOneByFilter($filter);

        // assert
        self::assertNotNull($resultUser);
        self::assertEquals($targetUser['id'], $resultUser['id']);
    }

    public function testSuccessFindOneByFilterWithFilterLikeAllBounds(): void
    {
        // arrange
        $targetUser = self::USER_2;

        $filter = new Filter();
        $filter->like('name', 'Mister X', true, true);

        // action
        $resultUser = $this->manager->findOneByFilter($filter);

        // assert
        self::assertNotNull($resultUser);
        self::assertEquals($targetUser['id'], $resultUser['id']);
    }

    public function testSuccessFindOneByFilterWithFilterRawSql(): void
    {
        // arrange
        $targetUser = self::USER_2;

        $filter = new Filter();
        $filter->rawSql('age > 40 OR age < 20');

        // action
        $resultUser = $this->manager->findOneByFilter($filter);

        // assert
        self::assertNotNull($resultUser);
        self::assertEquals($targetUser['id'], $resultUser['id']);
    }

    public function testSuccessFindOneByFilterWithFilterDeletedTrue(): void
    {
        // arrange
        $targetUser = self::USER_3;

        $filter = new Filter();
        $filter->deleted([true]);

        // action
        $resultUser = $this->manager->findOneByFilter($filter);

        // assert
        self::assertNotNull($resultUser);
        self::assertEquals($targetUser['id'], $resultUser['id']);
    }

    public function testSuccessFindOneByFilterWithFilterDeletedFalse(): void
    {
        // arrange
        $targetUser = self::USER_1;

        $filter = new Filter();
        $filter->deleted([false]);

        // action
        $resultUser = $this->manager->findOneByFilter($filter);

        // assert
        self::assertNotNull($resultUser);
        self::assertEquals($targetUser['id'], $resultUser['id']);
    }

    public function testSuccessFindOneByFilterWithFilterDeletedTrueFalse(): void
    {
        // arrange
        $targetUser = self::USER_1;

        $filter = new Filter();
        $filter->deleted([true, false]);

        // action
        $resultUser = $this->manager->findOneByFilter($filter);

        // assert
        self::assertNotNull($resultUser);
        self::assertEquals($targetUser['id'], $resultUser['id']);
    }

    public function testSuccessFindOneByFilterWithSortingAsc(): void
    {
        // arrange
        $targetUser = self::USER_2;

        $sorting = new Sorting();
        $sorting->addSorting('age', 'asc');

        // action
        $resultUser = $this->manager->findOneByFilter(new Filter(), $sorting);

        // assert
        self::assertNotNull($resultUser);
        self::assertEquals($targetUser['id'], $resultUser['id']);
    }

    public function testSuccessFindOneByFilterWithSortingDesc(): void
    {
        // arrange
        $targetUser = self::USER_4;

        $sorting = new Sorting();
        $sorting->addSorting('age', 'desc');

        // action
        $resultUser = $this->manager->findOneByFilter(new Filter(), $sorting);

        // assert
        self::assertNotNull($resultUser);
        self::assertEquals($targetUser['id'], $resultUser['id']);
    }

    public function testSuccessFindOneByFilterWithDoubleSorting(): void
    {
        // arrange
        $targetUser = self::USER_1;

        $sorting = new Sorting();
        $sorting->addSorting('created_at', 'asc');
        $sorting->addSorting('age', 'desc');

        // action
        $resultUser = $this->manager->findOneByFilter(new Filter(), $sorting);

        // assert
        self::assertNotNull($resultUser);
        self::assertEquals($targetUser['id'], $resultUser['id']);
    }

    public function testSuccessFindOneByFilterNotFound(): void
    {
        // arrange
        $filter = new Filter();
        $filter->equals(DefaultTestEntity::PK_COLUMN, 100000);

        // action
        $resultUser = $this->manager->findOneByFilter($filter);

        // assert
        self::assertNull($resultUser);
    }

    public function testSuccessFindOneByPk(): void
    {
        // arrange
        $targetUser = self::USER_1;

        // action
        $resultUser = $this->manager->findOneByPk($targetUser['id']);

        // assert
        self::assertNotNull($resultUser);
        self::assertEquals($targetUser['id'], $resultUser['id']);
    }

    public function testSuccessNotFindOneByPk(): void
    {
        // action
        $data = $this->manager->findOneByPk(10000);

        // assert
        self::assertNull($data);
    }

    public function testSuccessFindOneByPkWithDeletedFalse(): void
    {
        // arrange
        $targetUser = self::USER_3;

        // action
        $resultUser = $this->manager->findOneByPk($targetUser['id']);

        // assert
        self::assertNull($resultUser);
    }

    public function testSuccessFindOneByPkWithDeletedTrue(): void
    {
        // arrange
        $targetUser = self::USER_3;

        // action
        $resultUser = $this->manager->findOneByPk($targetUser['id'], true);

        // assert
        self::assertNotNull($resultUser);
        self::assertEquals($targetUser['id'], $resultUser['id']);
    }

    public function testFailFindOneByPkNoPrimaryKeyValue(): void
    {
        // assert
        // action
        $this->expectException(InvalidRequestException::class);
        $this->expectExceptionMessage('No value provided for PK column "id"');

        $this->manager->findOneByPk([
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

        $insertedData = $this->dataRetriever->getOneRowFromDB([
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

    public function testSuccessInsertWithExplicitTimestamps(): void
    {
        // arrange
        $dataForInsert = [
            'name' => 'Inserted User',
            'birthday' => '2016-02-02',
            'age' => 44,
            'weight' => 32.4,
            'married' => 1,
            'created_at' => '2018-01-02 13:12:23',
            'updated_at' => '2018-02-02 23:33:21',
            'deleted_at' => '2018-03-02 21:00:11',
        ];

        // action
        $id = $this->manager->insert($dataForInsert);

        // assert
        self::assertEquals(5, $id);

        $insertedData = $this->dataRetriever->getOneRowFromDB([
            'id' => $id,
        ]);
        self::assertEquals($dataForInsert['name'], $insertedData['name']);
        self::assertEquals($dataForInsert['birthday'], $insertedData['birthday']);
        self::assertEquals($dataForInsert['age'], $insertedData['age']);
        self::assertEquals($dataForInsert['weight'], $insertedData['weight']);
        self::assertEquals($dataForInsert['married'], $insertedData['married']);
        self::assertEquals($dataForInsert['created_at'], $insertedData['created_at']);
        self::assertEquals($dataForInsert['updated_at'], $insertedData['updated_at']);
        self::assertEquals($dataForInsert['deleted_at'], $insertedData['deleted_at']);
    }

    public function testFailInsertUnknownColumnList(): void
    {
        // arrange
        $dataForInsert = [
            'UNKNOWN_FIELD' => 'Inserted User',
        ];

        // assert
        // action
        $this->expectException(InvalidRequestException::class);
        $this->expectExceptionMessage('Unknown columns: [UNKNOWN_FIELD]');

        $this->manager->insert($dataForInsert);
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
            $insertedData = $this->dataRetriever->getOneRowFromDB([
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

    public function testSuccessBatchInsertWithExplicitTimestamps(): void
    {
        // arrange
        $dataForInsertList = [
            [
                'name' => 'Inserted User',
                'birthday' => '2016-02-02',
                'age' => 44,
                'weight' => 32.4,
                'married' => 1,
                'created_at' => '2018-01-02 13:12:23',
                'updated_at' => '2018-02-02 23:33:21',
                'deleted_at' => '2018-03-02 21:00:11',
            ],
            [
                'name' => 'Inserted User 2',
                'birthday' => '2014-02-02',
                'age' => 23,
                'weight' => 1.24,
                'married' => 0,
                'created_at' => '2018-05-04 13:12:23',
                'updated_at' => '2018-06-04 23:33:21',
                'deleted_at' => null,
            ],
        ];

        // action
        $count = $this->manager->batchInsert($dataForInsertList);

        // assert
        self::assertEquals(2, $count);

        foreach ($dataForInsertList as $dataForInsert) {
            $insertedData = $this->dataRetriever->getOneRowFromDB([
                'name' => $dataForInsert['name'],
            ]);

            self::assertEquals($dataForInsert['name'], $insertedData['name']);
            self::assertEquals($dataForInsert['birthday'], $insertedData['birthday']);
            self::assertEquals($dataForInsert['age'], $insertedData['age']);
            self::assertEquals($dataForInsert['weight'], $insertedData['weight']);
            self::assertEquals($dataForInsert['married'], $insertedData['married']);
            self::assertEquals($dataForInsert['created_at'], $insertedData['created_at']);
            self::assertEquals($dataForInsert['updated_at'], $insertedData['updated_at']);
            self::assertEquals($dataForInsert['deleted_at'], $insertedData['deleted_at']);
        }
    }

    public function testSuccessUpdateByFilter(): void
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
        $count = $this->manager->updateByFilter($filter, $dataForUpdate);

        // assert
        self::assertEquals(1, $count);

        $updatedData = $this->dataRetriever->getOneRowFromDB([
            'name' => $dataForUpdate['name'],
        ]);
        self::assertEquals($targetUser['id'], $updatedData['id']);
        self::assertEquals($dataForUpdate['name'], $updatedData['name']);
        self::assertEquals($dataForUpdate['birthday'], $updatedData['birthday']);
        self::assertEquals($dataForUpdate['age'], $updatedData['age']);
        self::assertEquals($dataForUpdate['weight'], $updatedData['weight']);
        self::assertEquals($dataForUpdate['married'], $updatedData['married']);
        self::assertEquals($targetUser['created_at'], $updatedData['created_at']);
        self::assertNotEquals($targetUser['updated_at'], $updatedData['updated_at']);
        self::assertNotNull($updatedData[DefaultTestEntity::UPDATED_AT_COLUMN]);
    }

    public function testSuccessUpdateByFilterWithExplicitTimestamps(): void
    {
        // arrange
        $targetUser = self::USER_4;

        $dataForUpdate = [
            'name' => 'Updated User',
            'birthday' => '2016-02-02',
            'age' => 44,
            'weight' => 32.4,
            'married' => 1,
            'created_at' => '2018-01-02 13:12:23',
            'updated_at' => '2018-02-02 23:33:21',
            'deleted_at' => '2018-03-02 21:00:11',
        ];

        $filter = new Filter();
        $filter->equals('name', $targetUser['name']);

        // action
        $count = $this->manager->updateByFilter($filter, $dataForUpdate);

        // assert
        self::assertEquals(1, $count);

        $updatedData = $this->dataRetriever->getOneRowFromDB([
            'name' => $dataForUpdate['name'],
        ]);
        self::assertEquals($targetUser['id'], $updatedData['id']);
        self::assertEquals($dataForUpdate['name'], $updatedData['name']);
        self::assertEquals($dataForUpdate['birthday'], $updatedData['birthday']);
        self::assertEquals($dataForUpdate['age'], $updatedData['age']);
        self::assertEquals($dataForUpdate['weight'], $updatedData['weight']);
        self::assertEquals($dataForUpdate['married'], $updatedData['married']);
        self::assertEquals($dataForUpdate['created_at'], $updatedData['created_at']);
        self::assertEquals($dataForUpdate['updated_at'], $updatedData['updated_at']);
        self::assertEquals($dataForUpdate['deleted_at'], $updatedData['deleted_at']);
    }

    public function testSuccessUpdateByFilterNotExistingRow(): void
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
        $count = $this->manager->updateByFilter($filter, $dataForUpdate);

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

        $updatedData = $this->dataRetriever->getOneRowFromDB([
            'id' => $id,
        ]);
        self::assertEquals($dataForUpdate['name'], $updatedData['name']);
        self::assertEquals($dataForUpdate['birthday'], $updatedData['birthday']);
        self::assertEquals($dataForUpdate['age'], $updatedData['age']);
        self::assertEquals($dataForUpdate['weight'], $updatedData['weight']);
        self::assertEquals($dataForUpdate['married'], $updatedData['married']);
        self::assertEquals($targetUser['created_at'], $updatedData['created_at']);
        self::assertNotEquals($targetUser['updated_at'], $updatedData['updated_at']);
        self::assertNotNull($updatedData[DefaultTestEntity::UPDATED_AT_COLUMN]);
    }

    public function testSuccessUpdateByPkWithExplicitTimestamps(): void
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
            'created_at' => '2018-01-02 13:12:23',
            'updated_at' => '2018-02-02 23:33:21',
            'deleted_at' => '2018-03-02 21:00:11',
        ];

        // action
        $count = $this->manager->updateByPk($id, $dataForUpdate);

        // assert
        self::assertEquals(1, $count);

        $updatedData = $this->dataRetriever->getOneRowFromDB([
            'id' => $id,
        ]);
        self::assertEquals($dataForUpdate['name'], $updatedData['name']);
        self::assertEquals($dataForUpdate['birthday'], $updatedData['birthday']);
        self::assertEquals($dataForUpdate['age'], $updatedData['age']);
        self::assertEquals($dataForUpdate['weight'], $updatedData['weight']);
        self::assertEquals($dataForUpdate['married'], $updatedData['married']);
        self::assertEquals($dataForUpdate['created_at'], $updatedData['created_at']);
        self::assertEquals($dataForUpdate['updated_at'], $updatedData['updated_at']);
        self::assertEquals($dataForUpdate['deleted_at'], $updatedData['deleted_at']);
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

    public function testSuccessUpdateByPkSoftDeletedRow(): void
    {
        // arrange
        $targetUser = self::USER_3;
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
        self::assertEquals(0, $count);

        $updatedUser = $this->dataRetriever->getOneRowFromDB([
            'id' => $id,
        ]);
        self::assertNotNull($updatedUser);
        self::assertNotNull($updatedUser[DefaultTestEntity::DELETED_AT_COLUMN]);

        self::assertEquals($targetUser['id'], $updatedUser['id']);
        self::assertEquals($targetUser['name'], $updatedUser['name']);
        self::assertEquals($targetUser['birthday'], $updatedUser['birthday']);
        self::assertEquals($targetUser['age'], $updatedUser['age']);
        self::assertEquals($targetUser['weight'], $updatedUser['weight']);
        self::assertEquals($targetUser['married'], $updatedUser['married']);
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

        $targetUserList = [
            self::USER_1,
            self::USER_2,
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
            $updatedData = $this->dataRetriever->getOneRowFromDB([
                'id' => $idList[$i],
            ]);
            $targetUser = $targetUserList[$i];
            self::assertEquals($dataForUpdate['name'], $updatedData['name']);
            self::assertEquals($dataForUpdate['birthday'], $updatedData['birthday']);
            self::assertEquals($dataForUpdate['age'], $updatedData['age']);
            self::assertEquals($dataForUpdate['weight'], $updatedData['weight']);
            self::assertEquals($dataForUpdate['married'], $updatedData['married']);
            self::assertEquals($targetUser['created_at'], $updatedData['created_at']);
            self::assertNotEquals($targetUser['updated_at'], $updatedData['updated_at']);
        }
    }

    public function testSuccessBatchUpdateWithExplicitTimestamps(): void
    {
        // arrange
        $dataForUpdateList = [
            [
                'name' => 'Updated User',
                'birthday' => '2016-02-02',
                'age' => 44,
                'weight' => 32.4,
                'married' => 1,
                'created_at' => '2018-01-02 13:12:23',
                'updated_at' => '2018-02-02 23:33:21',
                'deleted_at' => '2018-03-02 21:00:11',
            ],
            [
                'name' => 'Updated User 2',
                'birthday' => '2014-02-02',
                'age' => 23,
                'weight' => 1.24,
                'married' => 0,
                'created_at' => '2018-05-04 13:12:23',
                'updated_at' => '2018-06-04 23:33:21',
                'deleted_at' => null,
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
            $updatedData = $this->dataRetriever->getOneRowFromDB([
                'id' => $idList[$i],
            ]);
            self::assertEquals($dataForUpdate['name'], $updatedData['name']);
            self::assertEquals($dataForUpdate['birthday'], $updatedData['birthday']);
            self::assertEquals($dataForUpdate['age'], $updatedData['age']);
            self::assertEquals($dataForUpdate['weight'], $updatedData['weight']);
            self::assertEquals($dataForUpdate['married'], $updatedData['married']);
            self::assertEquals($dataForUpdate['created_at'], $updatedData['created_at']);
            self::assertEquals($dataForUpdate['updated_at'], $updatedData['updated_at']);
            self::assertEquals($dataForUpdate['deleted_at'], $updatedData['deleted_at']);
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

    public function testSuccessDeleteByFilter(): void
    {
        // arrange
        $targetUser = self::USER_2;

        $filter = new Filter();
        $filter->equals('name', $targetUser['name']);

        // action
        $count = $this->manager->deleteByFilter($filter);

        // assert
        self::assertEquals(1, $count);

        $deletedRow = $this->dataRetriever->getOneRowFromDB([
            'name' => $targetUser['name'],
        ]);
        self::assertNull($deletedRow);
    }

    public function testSuccessDeleteByFilterNotExistingRow(): void
    {
        // arrange
        $filter = new Filter();
        $filter->equals('name', 'NOT EXISTING NAME');

        // action
        $count = $this->manager->deleteByFilter($filter);

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

        $deletedRow = $this->dataRetriever->getOneRowFromDB([
            'id' => $id,
        ]);
        self::assertNull($deletedRow);
    }

    public function testSuccessDeleteByPkSoftDeletedRow(): void
    {
        // arrange
        $targetUser = self::USER_3;
        $id = $targetUser['id'];

        // action
        $count = $this->manager->deleteByPk($id);

        // assert
        self::assertEquals(0, $count);

        $deletedRow = $this->dataRetriever->getOneRowFromDB([
            'id' => $id,
        ]);
        self::assertNotNull($deletedRow);
        self::assertNotNull($deletedRow[DefaultTestEntity::DELETED_AT_COLUMN]);

        self::assertEquals($targetUser['id'], $deletedRow['id']);
        self::assertEquals($targetUser['name'], $deletedRow['name']);
        self::assertEquals($targetUser['birthday'], $deletedRow['birthday']);
        self::assertEquals($targetUser['age'], $deletedRow['age']);
        self::assertEquals($targetUser['weight'], $deletedRow['weight']);
        self::assertEquals($targetUser['married'], $deletedRow['married']);
    }

    public function testSuccessDeleteAll(): void
    {
        // action
        $count = $this->manager->deleteAll();

        // assert
        self::assertEquals(4, $count);

        $totalCount = $this->dataRetriever->getCountFromDB();
        self::assertEquals(0, $totalCount);
    }

    public function testSuccessSoftDeleteByFilter(): void
    {
        // arrange
        $targetUser = self::USER_4;

        $filter = new Filter();
        $filter->equals('name', $targetUser['name']);

        // action
        $count = $this->manager->softDeleteByFilter($filter);

        // assert
        self::assertEquals(1, $count);

        $deletedRow = $this->dataRetriever->getOneRowFromDB([
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

        $deletedRow = $this->dataRetriever->getOneRowFromDB([
            'id' => $id,
        ]);
        self::assertNotNull($deletedRow);
        self::assertNotNull($deletedRow[DefaultTestEntity::DELETED_AT_COLUMN]);
    }

    public function testSuccessSoftDeleteByPkAlreadySoftDeletedRow(): void
    {
        // arrange
        $targetUser = self::USER_3;
        $id = $targetUser['id'];

        // action
        $count = $this->manager->softDeleteByPk($id);

        // assert
        self::assertEquals(0, $count);

        $deletedRow = $this->dataRetriever->getOneRowFromDB([
            'id' => $id,
        ]);
        self::assertNotNull($deletedRow);
        self::assertNotNull($deletedRow[DefaultTestEntity::DELETED_AT_COLUMN]);

        self::assertEquals($targetUser['id'], $deletedRow['id']);
        self::assertEquals($targetUser['name'], $deletedRow['name']);
        self::assertEquals($targetUser['birthday'], $deletedRow['birthday']);
        self::assertEquals($targetUser['age'], $deletedRow['age']);
        self::assertEquals($targetUser['weight'], $deletedRow['weight']);
        self::assertEquals($targetUser['married'], $deletedRow['married']);
    }

    public function testSuccessSoftDeleteAll(): void
    {
        // action
        $count = $this->manager->softDeleteAll();

        // assert
        self::assertEquals(4, $count);

        $totalCount = $this->dataRetriever->getCountFromDB();
        self::assertEquals(4, $totalCount);

        $deletedRows = $this->dataRetriever->getRowsFromDB();
        foreach ($deletedRows as $deletedRow) {
            self::assertNotNull($deletedRow[DefaultTestEntity::DELETED_AT_COLUMN]);
        }
    }

    public function testSuccessRestoreByPk(): void
    {
        // arrange
        $targetUser = self::USER_3;
        $id = $targetUser['id'];

        // action
        $count = $this->manager->restoreByPk($id);

        // assert
        self::assertEquals(1, $count);

        $deletedRow = $this->dataRetriever->getOneRowFromDB([
            'id' => $id,
        ]);
        self::assertNotNull($deletedRow);
        self::assertNull($deletedRow[DefaultTestEntity::DELETED_AT_COLUMN]);
        self::assertNotEquals($targetUser[DefaultTestEntity::UPDATED_AT_COLUMN], $deletedRow[DefaultTestEntity::UPDATED_AT_COLUMN]);
    }

    public function testSuccessRestoreByPkAlreadyNotSoftDeletedRow(): void
    {
        // arrange
        $targetUser = self::USER_4;
        $id = $targetUser['id'];

        // action
        $count = $this->manager->restoreByPk($id);

        // assert
        self::assertEquals(0, $count);

        $deletedRow = $this->dataRetriever->getOneRowFromDB([
            'id' => $id,
        ]);
        self::assertNotNull($deletedRow);
        self::assertNull($deletedRow[DefaultTestEntity::DELETED_AT_COLUMN]);
        self::assertEquals($targetUser[DefaultTestEntity::UPDATED_AT_COLUMN], $deletedRow[DefaultTestEntity::UPDATED_AT_COLUMN]);
    }

    public function testSuccessTruncate(): void
    {
        // action
        $this->manager->truncate();

        // assert
        $totalCount = $this->dataRetriever->getCountFromDB();
        self::assertEquals(0, $totalCount);
    }
}
