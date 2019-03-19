<?php

namespace Tests\Manager;

use DBALTableManager\Exception\InvalidRequestException;
use DBALTableManager\Manager\TemporalTableManager;
use DBALTableManager\Query\Filter;
use DBALTableManager\Query\Pagination;
use DBALTableManager\Query\Sorting;
use PHPUnit\DbUnit\Database\DefaultConnection as DbUnitDefaultConnection;
use PHPUnit\DbUnit\TestCase;
use Tests\Support\DatabaseTableDataRetriever;
use Tests\Support\DefaultTestEntity;
use Tests\Support\DefaultTestTemporalVersionEntity;

/**
 * Class TemporalTableManagerTestFoundation
 *
 * @package Tests\Manager
 */
abstract class TemporalTableManagerTestFoundation extends TestCase
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
    protected const USER_1_VERSION_1 = [
        'user_id' => 1,
        'effective_since' => '2018-04-04',
        'created_at' => '2018-04-04 12:44:22',
        'salary' => 20000,
        'fired' => 0,
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
    protected const USER_2_VERSION_1 = [
        'user_id' => 2,
        'effective_since' => '2018-05-04',
        'created_at' => '2018-05-04 12:44:22',
        'salary' => 30000,
        'fired' => 0,
    ];
    protected const USER_2_VERSION_2 = [
        'user_id' => 2,
        'effective_since' => '2019-01-04',
        'created_at' => '2019-01-04 08:13:22',
        'salary' => 35000,
        'fired' => 0,
    ];
    protected const USER_2_VERSION_3 = [
        'user_id' => 2,
        'effective_since' => '2044-01-04',
        'created_at' => '2019-01-04 08:13:22',
        'salary' => 35000,
        'fired' => 1,
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
    protected const USER_3_VERSION_1 = [
        'user_id' => 3,
        'effective_since' => '2018-06-04',
        'created_at' => '2018-06-04 12:44:22',
        'salary' => 10000,
        'fired' => 0,
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
    protected const USER_4_VERSION_1 = [
        'user_id' => 4,
        'effective_since' => '2018-05-04',
        'created_at' => '2018-05-04 12:44:22',
        'salary' => 10000,
        'fired' => 0,
    ];
    /**
     * @var DbUnitDefaultConnection
     */
    protected $phpUnitDbConnection;
    /**
     * @var TemporalTableManager
     */
    protected $manager;
    /**
     * @var DatabaseTableDataRetriever
     */
    protected $staticDataRetriever;
    /**
     * @var DatabaseTableDataRetriever
     */
    protected $versionDataRetriever;

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
                DefaultTestTemporalVersionEntity::TABLE_NAME => [
                    self::USER_1_VERSION_1,
                    self::USER_2_VERSION_1,
                    self::USER_2_VERSION_2,
                    self::USER_2_VERSION_3,
                    self::USER_3_VERSION_1,
                    self::USER_4_VERSION_1,
                ],
            ]
        );
    }

    /**
     * @dataProvider dataProviderForFilter
     *
     * @param array $args
     * @param array $expected
     * @param string $description
     */
    public function testSuccessGetCount(array $args, array $expected, string $description): void
    {
        // action
        $count = $this->manager->getCount($args['filter'], $args['as_of_time']);

        // assert
        self::assertEquals(count($expected['users']), $count, 'Failed: ' . $description);
    }

    /**
     * @dataProvider dataProviderForFilter
     *
     * @param array $args
     * @param array $expected
     * @param string $description
     */
    public function testSuccessFindAllWithFilter(array $args, array $expected, string $description): void
    {
        // action
        $resultUsers = $this->manager->findAll($args['filter'], null, null, $args['as_of_time']);

        // assert
        self::assertCount(count($expected['users']), $resultUsers, 'Failed: ' . $description);
        foreach ($resultUsers as $index => $resultUser) {
            self::assertEquals($expected['users'][$index]['id'], $resultUser['id'], 'Failed: ' . $description);
        }
    }

    /**
     * @dataProvider dataProviderForPagination
     *
     * @param array $args
     * @param array $expected
     * @param string $description
     */
    public function testSuccessFindAllWithPagination(array $args, array $expected, string $description): void
    {
        // action
        $resultUsers = $this->manager->findAll(null, $args['pagination'], null, $args['as_of_time']);

        // assert
        self::assertCount(count($expected['users']), $resultUsers, 'Failed: ' . $description);
        foreach ($resultUsers as $index => $resultUser) {
            self::assertEquals($expected['users'][$index]['id'], $resultUser['id'], 'Failed: ' . $description);
        }
    }

    /**
     * @dataProvider dataProviderForSorting
     *
     * @param array $args
     * @param array $expected
     * @param string $description
     */
    public function testSuccessFindAllWithSorting(array $args, array $expected, string $description): void
    {
        // action
        $resultUsers = $this->manager->findAll(null, null, $args['sorting'], $args['as_of_time']);

        // assert
        self::assertCount(count($expected['users']), $resultUsers, 'Failed: ' . $description);
        foreach ($resultUsers as $index => $resultUser) {
            self::assertEquals($expected['users'][$index]['id'], $resultUser['id'], 'Failed: ' . $description);
        }
    }

    /**
     * @dataProvider dataProviderForFilter
     *
     * @param array $args
     * @param array $expected
     * @param string $description
     */
    public function testSuccessFindOneWithFilter(array $args, array $expected, string $description): void
    {
        // action
        $resultUser = $this->manager->findOneByFilter($args['filter'] ?? Filter::newInstance(), null, $args['as_of_time']);

        // assert
        $exceptedUser = $expected['users'][0] ?? null;
        if ($exceptedUser === null) {
            self::assertNull($resultUser, 'Failed: ' . $description);
        } else {
            self::assertNotNull($resultUser, 'Failed: ' . $description);
            self::assertEquals($exceptedUser['id'], $resultUser['id'], 'Failed: ' . $description);
        }
    }

    /**
     * @dataProvider dataProviderForSorting
     *
     * @param array $args
     * @param array $expected
     * @param string $description
     */
    public function testSuccessFindOneWithSorting(array $args, array $expected, string $description): void
    {
        // action
        $resultUser = $this->manager->findOneByFilter(Filter::newInstance(), $args['sorting'], $args['as_of_time']);

        // assert
        $exceptedUser = $expected['users'][0] ?? null;
        if ($exceptedUser === null) {
            self::assertNull($resultUser, 'Failed: ' . $description);
        } else {
            self::assertNotNull($resultUser, 'Failed: ' . $description);
            self::assertEquals($exceptedUser['id'], $resultUser['id'], 'Failed: ' . $description);
        }
    }

    /**
     * @param string $description
     * @param Filter|null $filter
     * @param array $expectedUsersForCurrentTime
     *
     * @return array
     */
    private function makeDataProviderItemsForFilter(
        string $description,
        ?Filter $filter,
        array $expectedUsersForCurrentTime
    ): array
    {
        if (in_array(self::USER_1, $expectedUsersForCurrentTime, true)) {
            $expectedUsersAsOfTime = [self::USER_1];
        } else {
            $expectedUsersAsOfTime = [];
        }

        return [
            [
                'args' => [
                    'filter' => $filter,
                    'as_of_time' => null,
                ],
                'expected' => [
                    'users' => $expectedUsersForCurrentTime,
                ],
                'description' => $description . ' | as_of_time null',
            ],
            [
                'args' => [
                    'filter' => $filter,
                    'as_of_time' => self::USER_1['created_at'],
                ],
                'expected' => [
                    'users' => $expectedUsersAsOfTime,
                ],
                'description' => $description . ' | as_of_time not null',
            ],
        ];
    }

    /**
     * @param string $description
     * @param Sorting|null $sorting
     * @param array $expectedUsersForCurrentTime
     *
     * @return array
     */
    private function makeDataProviderItemsForSorting(
        string $description,
        ?Sorting $sorting,
        array $expectedUsersForCurrentTime
    ): array
    {
        if (in_array(self::USER_1, $expectedUsersForCurrentTime, true)) {
            $expectedUsersAsOfTime = [self::USER_1];
        } else {
            $expectedUsersAsOfTime = [];
        }

        return [
            [
                'args' => [
                    'sorting' => $sorting,
                    'as_of_time' => null,
                ],
                'expected' => [
                    'users' => $expectedUsersForCurrentTime,
                ],
                'description' => $description . ' | as_of_time null',
            ],
            [
                'args' => [
                    'sorting' => $sorting,
                    'as_of_time' => self::USER_1['created_at'],
                ],
                'expected' => [
                    'users' => $expectedUsersAsOfTime,
                ],
                'description' => $description . ' | as_of_time not null',
            ],
        ];
    }

    /**
     * @param string $description
     * @param Pagination|null $pagination
     * @param array $expectedUsersForCurrentTime
     *
     * @return array
     */
    private function makeDataProviderItemsForPagination(
        string $description,
        ?Pagination $pagination,
        array $expectedUsersForCurrentTime
    ): array
    {
        if (in_array(self::USER_1, $expectedUsersForCurrentTime, true)) {
            $expectedUsersAsOfTime = [self::USER_1];
        } else {
            $expectedUsersAsOfTime = [];
        }

        return [
            [
                'args' => [
                    'pagination' => $pagination,
                    'as_of_time' => null,
                ],
                'expected' => [
                    'users' => $expectedUsersForCurrentTime,
                ],
                'description' => $description . ' | as_of_time null',
            ],
            [
                'args' => [
                    'pagination' => $pagination,
                    'as_of_time' => self::USER_1['created_at'],
                ],
                'expected' => [
                    'users' => $expectedUsersAsOfTime,
                ],
                'description' => $description . ' | as_of_time not null',
            ],
        ];
    }

    /**
     * @return array
     */
    public function dataProviderForFilter(): array
    {
        return array_merge(
            $this->makeDataProviderItemsForFilter(
                'no filter',
                null,
                [
                    self::USER_1,
                    self::USER_2,
                    self::USER_4,
                ]
            ),

            $this->makeDataProviderItemsForFilter(
                'equals',
                Filter::newInstance()->equals('name', self::USER_4['name']),
                [
                    self::USER_4,
                ]
            ),

            $this->makeDataProviderItemsForFilter(
                'not equals',
                Filter::newInstance()->notEquals('name', self::USER_4['name']),
                [
                    self::USER_1,
                    self::USER_2,
                ]
            ),

            $this->makeDataProviderItemsForFilter(
                'less than',
                Filter::newInstance()->lessThan('age', 22),
                [
                    self::USER_2,
                ]
            ),

            $this->makeDataProviderItemsForFilter(
                'less or equals',
                Filter::newInstance()->lessOrEquals('age', 22),
                [
                    self::USER_1,
                    self::USER_2,
                ]
            ),

            $this->makeDataProviderItemsForFilter(
                'greater than',
                Filter::newInstance()->greaterThan('age', 22),
                [
                    self::USER_4,
                ]
            ),


            $this->makeDataProviderItemsForFilter(
                'greater or equals',
                Filter::newInstance()->greaterOrEquals('age', 22),
                [
                    self::USER_1,
                    self::USER_4,
                ]
            ),

            $this->makeDataProviderItemsForFilter(
                'is null',
                Filter::newInstance()->isNull('birthday'),
                [
                    self::USER_4,
                ]
            ),


            $this->makeDataProviderItemsForFilter(
                'is not null',
                Filter::newInstance()->isNotNull('birthday'),
                [
                    self::USER_1,
                    self::USER_2,
                ]
            ),

            $this->makeDataProviderItemsForFilter(
                'in (string)',
                Filter::newInstance()->in('name', ['John', 'Mister X']),
                [
                    self::USER_1,
                    self::USER_2,
                ]
            ),

            $this->makeDataProviderItemsForFilter(
                'not in (string)',
                Filter::newInstance()->notIn('name', ['John', 'Mister X']),
                [
                    self::USER_4,
                ]
            ),

            $this->makeDataProviderItemsForFilter(
                'in (int)',
                Filter::newInstance()->in('age', [22, 13]),
                [
                    self::USER_1,
                    self::USER_2,
                ]
            ),

            $this->makeDataProviderItemsForFilter(
                'not in (int)',
                Filter::newInstance()->notIn('age', [22, 13]),
                [
                    self::USER_4,
                ]
            ),

            $this->makeDataProviderItemsForFilter(
                'like (no bounds)',
                Filter::newInstance()->like('name', 'ster'),
                [
                    self::USER_2,
                ]
            ),

            $this->makeDataProviderItemsForFilter(
                'like (from beginning)',
                Filter::newInstance()->like('name', 'Mist', true),
                [
                    self::USER_2,
                ]
            ),

            $this->makeDataProviderItemsForFilter(
                'like (to end)',
                Filter::newInstance()->like('name', 'er X', false, true),
                [
                    self::USER_2,
                ]
            ),

            $this->makeDataProviderItemsForFilter(
                'like (all bounds)',
                Filter::newInstance()->like('name', 'Mister X', true, true),
                [
                    self::USER_2,
                ]
            ),

            $this->makeDataProviderItemsForFilter(
                'raw',
                Filter::newInstance()->rawSql('age > 40 OR age < 20'),
                [
                    self::USER_2,
                    self::USER_4,
                ]
            ),

            $this->makeDataProviderItemsForFilter(
                'deleted (true)',
                Filter::newInstance()->deleted([true]),
                [
                    self::USER_3,
                ]
            ),

            $this->makeDataProviderItemsForFilter(
                'deleted (false)',
                Filter::newInstance()->deleted([false]),
                [
                    self::USER_1,
                    self::USER_2,
                    self::USER_4,
                ]
            ),

            $this->makeDataProviderItemsForFilter(
                'deleted (true + false)',
                Filter::newInstance()->deleted([true, false]),
                [
                    self::USER_1,
                    self::USER_2,
                    self::USER_3,
                    self::USER_4,
                ]
            )
        );
    }

    /**
     * @return array
     */
    public function dataProviderForPagination(): array
    {
        return array_merge(
            $this->makeDataProviderItemsForPagination(
                'pagination',
                new Pagination(1, 2),
                [
                    self::USER_4,
                ]
            )
        );
    }

    /**
     * @return array
     */
    public function dataProviderForSorting(): array
    {
        return array_merge(
            $this->makeDataProviderItemsForSorting(
                'sorting (asc)',
                Sorting::newInstance()->addSorting('age', 'asc'),
                [
                    self::USER_2,
                    self::USER_1,
                    self::USER_4,
                ]
            ),

            $this->makeDataProviderItemsForSorting(
                'sorting (desc)',
                Sorting::newInstance()->addSorting('age', 'desc'),
                [
                    self::USER_4,
                    self::USER_1,
                    self::USER_2,
                ]
            ),

            $this->makeDataProviderItemsForSorting(
                'sorting (double)',
                Sorting::newInstance()->addSorting('created_at', 'asc')->addSorting('age', 'desc'),
                [
                    self::USER_1,
                    self::USER_4,
                    self::USER_2,
                ]
            )
        );
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

        $this->manager->findAll($filter);
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

    /**
     * @dataProvider dataProviderForTemporalData
     *
     * @param array $args
     * @param array|null $expected
     */
    public function testSuccessFindAllTemporalData(array $args, ?array $expected): void
    {
        // arrange
        $targetUser = self::USER_2;

        // action
        $resultUserList = $this->manager->findAll(
            Filter::newInstance()->equals('id', $targetUser['id']),
            null,
            null,
            $args['as_of_time']
        );

        // assert
        if ($expected === null) {
            self::assertCount(0, $resultUserList);
        } else {
            self::assertCount(1, $resultUserList);

            $resultUser = $resultUserList[0];

            self::assertEquals($targetUser['name'], $resultUser['name']);
            self::assertEquals($targetUser['birthday'], $resultUser['birthday']);
            self::assertEquals($targetUser['age'], $resultUser['age']);
            self::assertEquals($targetUser['weight'], $resultUser['weight']);
            self::assertEquals($targetUser['married'], $resultUser['married']);

            self::assertEquals($expected['salary'], $resultUser['salary']);
            self::assertEquals($expected['fired'], $resultUser['fired']);
        }
    }

    /**
     * @dataProvider dataProviderForTemporalData
     *
     * @param array $args
     * @param array|null $expected
     */
    public function testSuccessFindOneTemporalData(array $args, ?array $expected): void
    {
        // arrange
        $targetUser = self::USER_2;

        // action
        $resultUser = $this->manager->findOneByPk($targetUser['id'], true, $args['as_of_time']);

        // assert
        if ($expected === null) {
            self::assertNull($resultUser);
        } else {
            self::assertNotNull($resultUser);

            self::assertEquals($targetUser['name'], $resultUser['name']);
            self::assertEquals($targetUser['birthday'], $resultUser['birthday']);
            self::assertEquals($targetUser['age'], $resultUser['age']);
            self::assertEquals($targetUser['weight'], $resultUser['weight']);
            self::assertEquals($targetUser['married'], $resultUser['married']);

            self::assertEquals($expected['salary'], $resultUser['salary']);
            self::assertEquals($expected['fired'], $resultUser['fired']);
        }
    }

    /**
     * @return array
     */
    public function dataProviderForTemporalData(): array
    {
        return [
            [
                'args' => [
                    'as_of_time' => null,
                ],
                'expected' => self::USER_2_VERSION_2,
            ],
            [
                'args' => [
                    'as_of_time' => '2016-01-04 12:44:22',
                ],
                'expected' => null,
            ],
            [
                'args' => [
                    'as_of_time' => '2018-05-04 12:44:22',
                ],
                'expected' => self::USER_2_VERSION_1,
            ],
            [
                'args' => [
                    'as_of_time' => '2018-06-01 00:00:00',
                ],
                'expected' => self::USER_2_VERSION_1,
            ],
            [
                'args' => [
                    'as_of_time' => '2044-01-04 00:00:00',
                ],
                'expected' => self::USER_2_VERSION_3,
            ],
        ];
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

            'salary' => 1000,
            'fired' => 0,
        ];

        // action
        $id = $this->manager->insert($dataForInsert);

        // assert
        self::assertEquals(5, $id);

        $insertedData = $this->staticDataRetriever->getOneRowFromDB([
            'id' => $id,
        ]);
        self::assertEquals($dataForInsert['name'], $insertedData['name']);
        self::assertEquals($dataForInsert['birthday'], $insertedData['birthday']);
        self::assertEquals($dataForInsert['age'], $insertedData['age']);
        self::assertEquals($dataForInsert['weight'], $insertedData['weight']);
        self::assertEquals($dataForInsert['married'], $insertedData['married']);
        self::assertNotNull($insertedData[DefaultTestEntity::CREATED_AT_COLUMN]);
        self::assertNotNull($insertedData[DefaultTestEntity::UPDATED_AT_COLUMN]);

        $versionCount = $this->versionDataRetriever->getCountFromDB([
            'user_id' => $id,
        ]);
        self::assertEquals(1, $versionCount);

        $insertedData = $this->versionDataRetriever->getOneRowFromDB([
            'user_id' => $id,
        ]);
        self::assertEquals($dataForInsert['salary'], $insertedData['salary']);
        self::assertEquals($dataForInsert['fired'], $insertedData['fired']);
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

            'salary' => 1000,
            'fired' => 0,
        ];

        // action
        $id = $this->manager->insert($dataForInsert);

        // assert
        self::assertEquals(5, $id);

        $insertedData = $this->staticDataRetriever->getOneRowFromDB([
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

        $versionCount = $this->versionDataRetriever->getCountFromDB([
            'user_id' => $id,
        ]);
        self::assertEquals(1, $versionCount);

        $insertedData = $this->versionDataRetriever->getOneRowFromDB([
            'user_id' => $id,
        ]);
        self::assertEquals($dataForInsert['salary'], $insertedData['salary']);
        self::assertEquals($dataForInsert['fired'], $insertedData['fired']);
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

                'salary' => 1000,
                'fired' => 0,
            ],
            [
                'name' => 'Inserted User 2',
                'birthday' => '2014-02-02',
                'age' => 23,
                'weight' => 1.24,
                'married' => 0,

                'salary' => 2000,
                'fired' => 1,
            ],
        ];

        // action
        $count = $this->manager->batchInsert($dataForInsertList);

        // assert
        self::assertEquals(2, $count);

        foreach ($dataForInsertList as $dataForInsert) {
            $insertedData = $this->staticDataRetriever->getOneRowFromDB([
                'name' => $dataForInsert['name'],
            ]);

            self::assertEquals($dataForInsert['name'], $insertedData['name']);
            self::assertEquals($dataForInsert['birthday'], $insertedData['birthday']);
            self::assertEquals($dataForInsert['age'], $insertedData['age']);
            self::assertEquals($dataForInsert['weight'], $insertedData['weight']);
            self::assertEquals($dataForInsert['married'], $insertedData['married']);
            self::assertNotNull($insertedData[DefaultTestEntity::CREATED_AT_COLUMN]);
            self::assertNotNull($insertedData[DefaultTestEntity::UPDATED_AT_COLUMN]);

            $versionCount = $this->versionDataRetriever->getCountFromDB([
                'user_id' => $insertedData['id'],
            ]);
            self::assertEquals(1, $versionCount);

            $insertedData = $this->versionDataRetriever->getOneRowFromDB([
                'user_id' => $insertedData['id'],
            ]);
            self::assertEquals($dataForInsert['salary'], $insertedData['salary']);
            self::assertEquals($dataForInsert['fired'], $insertedData['fired']);
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

                'salary' => 1000,
                'fired' => 0,
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

                'salary' => 2000,
                'fired' => 1,
            ],
        ];

        // action
        $count = $this->manager->batchInsert($dataForInsertList);

        // assert
        self::assertEquals(2, $count);

        foreach ($dataForInsertList as $dataForInsert) {
            $insertedData = $this->staticDataRetriever->getOneRowFromDB([
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

            $versionCount = $this->versionDataRetriever->getCountFromDB([
                'user_id' => $insertedData['id'],
            ]);
            self::assertEquals(1, $versionCount);

            $insertedData = $this->versionDataRetriever->getOneRowFromDB([
                'user_id' => $insertedData['id'],
            ]);
            self::assertEquals($dataForInsert['salary'], $insertedData['salary']);
            self::assertEquals($dataForInsert['fired'], $insertedData['fired']);
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

            'salary' => 1000,
            'fired' => 0,
        ];

        $filter = new Filter();
        $filter->equals('name', $targetUser['name']);

        // action
        $count = $this->manager->updateByFilter($filter, $dataForUpdate);

        // assert
        self::assertEquals(1, $count);

        $updatedData = $this->staticDataRetriever->getOneRowFromDB([
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

        $versionCount = $this->versionDataRetriever->getCountFromDB([
            'user_id' => $targetUser['id'],
        ]);
        self::assertEquals(2, $versionCount);

        $updatedData = $this->versionDataRetriever->getOneRowFromDB([
            'user_id' => $targetUser['id'],
        ], [
            'created_at' => 'desc',
        ]);
        self::assertEquals($dataForUpdate['salary'], $updatedData['salary']);
        self::assertEquals($dataForUpdate['fired'], $updatedData['fired']);
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

            'salary' => 1000,
            'fired' => 0,
        ];

        $filter = new Filter();
        $filter->equals('name', $targetUser['name']);

        // action
        $count = $this->manager->updateByFilter($filter, $dataForUpdate);

        // assert
        self::assertEquals(1, $count);

        $updatedData = $this->staticDataRetriever->getOneRowFromDB([
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

        $versionCount = $this->versionDataRetriever->getCountFromDB([
            'user_id' => $targetUser['id'],
        ]);
        self::assertEquals(2, $versionCount);

        $updatedData = $this->versionDataRetriever->getOneRowFromDB([
            'user_id' => $targetUser['id'],
        ], [
            'created_at' => 'desc',
        ]);
        self::assertEquals($dataForUpdate['salary'], $updatedData['salary']);
        self::assertEquals($dataForUpdate['fired'], $updatedData['fired']);
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

            'salary' => 1000,
            'fired' => 0,
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

            'salary' => 1000,
            'fired' => 0,
        ];

        // action
        $count = $this->manager->updateByPk($id, $dataForUpdate);

        // assert
        self::assertEquals(1, $count);

        $updatedData = $this->staticDataRetriever->getOneRowFromDB([
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

        $versionCount = $this->versionDataRetriever->getCountFromDB([
            'user_id' => $targetUser['id'],
        ]);
        self::assertEquals(2, $versionCount);

        $updatedData = $this->versionDataRetriever->getOneRowFromDB([
            'user_id' => $targetUser['id'],
        ], [
            'created_at' => 'desc',
        ]);
        self::assertEquals($dataForUpdate['salary'], $updatedData['salary']);
        self::assertEquals($dataForUpdate['fired'], $updatedData['fired']);
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

            'salary' => 1000,
            'fired' => 0,
        ];

        // action
        $count = $this->manager->updateByPk($id, $dataForUpdate);

        // assert
        self::assertEquals(1, $count);

        $updatedData = $this->staticDataRetriever->getOneRowFromDB([
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

        $versionCount = $this->versionDataRetriever->getCountFromDB([
            'user_id' => $targetUser['id'],
        ]);
        self::assertEquals(2, $versionCount);

        $updatedData = $this->versionDataRetriever->getOneRowFromDB([
            'user_id' => $targetUser['id'],
        ], [
            'created_at' => 'desc',
        ]);
        self::assertEquals($dataForUpdate['salary'], $updatedData['salary']);
        self::assertEquals($dataForUpdate['fired'], $updatedData['fired']);
    }

    public function testSuccessUpdateByPkNoChangesInStatic(): void
    {
        // arrange
        $targetUser = self::USER_4;
        $id = $targetUser['id'];

        $dataForUpdate = [
            'name' => $targetUser['name'],
            'birthday' => $targetUser['birthday'],
            'age' => $targetUser['age'],
            'weight' => $targetUser['weight'],
            'married' => $targetUser['married'],

            'salary' => 1000,
            'fired' => 1,
        ];

        // action
        $count = $this->manager->updateByPk($id, $dataForUpdate);

        // assert
        self::assertEquals(1, $count);

        $updatedData = $this->staticDataRetriever->getOneRowFromDB([
            'id' => $id,
        ]);
        self::assertEquals($targetUser['name'], $updatedData['name']);
        self::assertEquals($targetUser['birthday'], $updatedData['birthday']);
        self::assertEquals($targetUser['age'], $updatedData['age']);
        self::assertEquals($targetUser['weight'], $updatedData['weight']);
        self::assertEquals($targetUser['married'], $updatedData['married']);
        self::assertEquals($targetUser['created_at'], $updatedData['created_at']);
        self::assertNotEquals($targetUser['updated_at'], $updatedData['updated_at']);
        self::assertNotNull($updatedData[DefaultTestEntity::UPDATED_AT_COLUMN]);

        $versionCount = $this->versionDataRetriever->getCountFromDB([
            'user_id' => $targetUser['id'],
        ]);
        self::assertEquals(2, $versionCount);

        $updatedData = $this->versionDataRetriever->getOneRowFromDB([
            'user_id' => $targetUser['id'],
        ], [
            'created_at' => 'desc',
        ]);
        self::assertEquals($dataForUpdate['salary'], $updatedData['salary']);
        self::assertEquals($dataForUpdate['fired'], $updatedData['fired']);
    }

    public function testSuccessUpdateByPkNoChangesInVersion(): void
    {
        // arrange
        $targetUser = self::USER_4;
        $id = $targetUser['id'];

        $targetUserVersion = self::USER_4_VERSION_1;

        $dataForUpdate = [
            'name' => 'Updated User',
            'birthday' => '2016-02-02',
            'age' => 44,
            'weight' => 32.4,
            'married' => 1,

            'salary' => $targetUserVersion['salary'],
            'fired' => $targetUserVersion['fired'],
        ];

        // action
        $count = $this->manager->updateByPk($id, $dataForUpdate);

        // assert
        self::assertEquals(1, $count);

        $updatedData = $this->staticDataRetriever->getOneRowFromDB([
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

        $versionCount = $this->versionDataRetriever->getCountFromDB([
            'user_id' => $targetUser['id'],
        ]);
        self::assertEquals(1, $versionCount);

        $updatedData = $this->versionDataRetriever->getOneRowFromDB([
            'user_id' => $targetUser['id'],
        ], [
            'created_at' => 'desc',
        ]);
        self::assertEquals($targetUserVersion['salary'], $updatedData['salary']);
        self::assertEquals($targetUserVersion['fired'], $updatedData['fired']);
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

            'salary' => 1000,
            'fired' => 0,
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

            'salary' => 1000,
            'fired' => 0,
        ];

        // action
        $count = $this->manager->updateByPk($id, $dataForUpdate);

        // assert
        self::assertEquals(0, $count);

        $updatedUser = $this->staticDataRetriever->getOneRowFromDB([
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

        $versionCount = $this->versionDataRetriever->getCountFromDB([
            'user_id' => $targetUser['id'],
        ]);
        self::assertEquals(1, $versionCount);
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

                'salary' => 1000,
                'fired' => 0,
            ],
            [
                'name' => 'Updated User 2',
                'birthday' => '2014-02-02',
                'age' => 23,
                'weight' => 1.24,
                'married' => 0,

                'salary' => 2000,
                'fired' => 1,
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
        $versionCountMap = [
            self::USER_1['id'] => count([
                self::USER_1_VERSION_1,
            ]),
            self::USER_2['id'] => count([
                self::USER_2_VERSION_1,
                self::USER_2_VERSION_2,
                self::USER_2_VERSION_3,
            ]),
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
            $updatedData = $this->staticDataRetriever->getOneRowFromDB([
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

            $userId = $targetUser['id'];
            $versionCount = $this->versionDataRetriever->getCountFromDB([
                'user_id' => $userId,
            ]);
            self::assertEquals(1 + $versionCountMap[$userId], $versionCount);

            $updatedData = $this->versionDataRetriever->getOneRowFromDB([
                'user_id' => $userId,
            ], [
                'created_at' => 'desc',
            ]);
            self::assertEquals($dataForUpdate['salary'], $updatedData['salary']);
            self::assertEquals($dataForUpdate['fired'], $updatedData['fired']);
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

                'salary' => 1000,
                'fired' => 0,
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

                'salary' => 2000,
                'fired' => 1,
            ],
        ];

        $idList = [
            self::USER_1['id'],
            self::USER_2['id'],
        ];

        $versionCountMap = [
            self::USER_1['id'] => count([
                self::USER_1_VERSION_1,
            ]),
            self::USER_2['id'] => count([
                self::USER_2_VERSION_1,
                self::USER_2_VERSION_2,
                self::USER_2_VERSION_3,
            ]),
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
            $userId = $idList[$i];

            $updatedData = $this->staticDataRetriever->getOneRowFromDB([
                'id' => $userId,
            ]);
            self::assertEquals($dataForUpdate['name'], $updatedData['name']);
            self::assertEquals($dataForUpdate['birthday'], $updatedData['birthday']);
            self::assertEquals($dataForUpdate['age'], $updatedData['age']);
            self::assertEquals($dataForUpdate['weight'], $updatedData['weight']);
            self::assertEquals($dataForUpdate['married'], $updatedData['married']);
            self::assertEquals($dataForUpdate['created_at'], $updatedData['created_at']);
            self::assertEquals($dataForUpdate['updated_at'], $updatedData['updated_at']);
            self::assertEquals($dataForUpdate['deleted_at'], $updatedData['deleted_at']);

            $versionCount = $this->versionDataRetriever->getCountFromDB([
                'user_id' => $userId,
            ]);
            self::assertEquals(1 + $versionCountMap[$userId], $versionCount);

            $updatedData = $this->versionDataRetriever->getOneRowFromDB([
                'user_id' => $userId,
            ], [
                'created_at' => 'desc',
            ]);
            self::assertEquals($dataForUpdate['salary'], $updatedData['salary']);
            self::assertEquals($dataForUpdate['fired'], $updatedData['fired']);
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

                'salary' => 1000,
                'fired' => 0,
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

        $deletedRow = $this->staticDataRetriever->getOneRowFromDB([
            'name' => $targetUser['name'],
        ]);
        self::assertNull($deletedRow);

        $versionCount = $this->versionDataRetriever->getCountFromDB([
            'user_id' => $targetUser['id'],
        ]);
        self::assertEquals(0, $versionCount);
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

        $deletedRow = $this->staticDataRetriever->getOneRowFromDB([
            'id' => $id,
        ]);
        self::assertNull($deletedRow);

        $versionCount = $this->versionDataRetriever->getCountFromDB([
            'user_id' => $targetUser['id'],
        ]);
        self::assertEquals(0, $versionCount);
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

        $deletedRow = $this->staticDataRetriever->getOneRowFromDB([
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

        $versionCount = $this->versionDataRetriever->getCountFromDB([
            'user_id' => $targetUser['id'],
        ]);
        self::assertEquals(1, $versionCount);
    }

    public function testSuccessDeleteAll(): void
    {
        // action
        $count = $this->manager->deleteAll();

        // assert
        self::assertEquals(4, $count);

        $totalCount = $this->staticDataRetriever->getCountFromDB();
        self::assertEquals(0, $totalCount);

        $totalCount = $this->versionDataRetriever->getCountFromDB();
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

        $deletedRow = $this->staticDataRetriever->getOneRowFromDB([
            'name' => $targetUser['name'],
        ]);
        self::assertNotNull($deletedRow);
        self::assertNotNull($deletedRow[DefaultTestEntity::DELETED_AT_COLUMN]);

        $versionCount = $this->versionDataRetriever->getCountFromDB([
            'user_id' => $targetUser['id'],
        ]);
        self::assertEquals(1, $versionCount);
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

        $deletedRow = $this->staticDataRetriever->getOneRowFromDB([
            'id' => $id,
        ]);
        self::assertNotNull($deletedRow);
        self::assertNotNull($deletedRow[DefaultTestEntity::DELETED_AT_COLUMN]);

        $versionCount = $this->versionDataRetriever->getCountFromDB([
            'user_id' => $id,
        ]);
        self::assertEquals(1, $versionCount);
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

        $deletedRow = $this->staticDataRetriever->getOneRowFromDB([
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

        $versionCount = $this->versionDataRetriever->getCountFromDB([
            'user_id' => $id,
        ]);
        self::assertEquals(1, $versionCount);
    }

    public function testSuccessSoftDeleteAll(): void
    {
        // action
        $count = $this->manager->softDeleteAll();

        // assert
        self::assertEquals(4, $count);

        $totalCount = $this->staticDataRetriever->getCountFromDB();
        self::assertEquals(4, $totalCount);

        $deletedRows = $this->staticDataRetriever->getRowsFromDB();
        foreach ($deletedRows as $deletedRow) {
            self::assertNotNull($deletedRow[DefaultTestEntity::DELETED_AT_COLUMN]);
        }
    }

    public function testSuccessTruncate(): void
    {
        // action
        $this->manager->truncate();

        // assert
        $totalCount = $this->staticDataRetriever->getCountFromDB();
        self::assertEquals(0, $totalCount);

        $totalCount = $this->versionDataRetriever->getCountFromDB();
        self::assertEquals(0, $totalCount);
    }
}
