<?php

namespace Tests\Util;

use DBALTableManager\Util\TypeConverter;
use PHPUnit\Framework\TestCase;

/**
 * Class TypeConverterTest
 *
 * @package Tests\Util
 */
class TypeConverterTest extends TestCase
{
    /**
     * @dataProvider paramDataProvider
     *
     * @param array $params
     */
    public function testSuccessTypeCast(array $params): void
    {
        // arrange
        $typeConverter = new TypeConverter();

        $originalValue = $params['original'];
        $castType = $params['cast'];
        $expectedValue = $params['expected'];

        // action
        $result = $typeConverter->convert(['value' => $originalValue], ['value' => $castType]);

        // assert
        self::assertEquals($result['value'], $expectedValue);
    }

    public function testSuccessResultStructure(): void
    {
        // arrange
        $typeConverter = new TypeConverter();

        $originalValues = [
            'value_with_cast' => '123',
            'value_with_unknown_cast' => 'For the Lich-King!',
            'value_without_cast' => 'For Khaz-Modan!',
        ];

        $castMap = [
            'has_cast' => 'int',
            'value_with_unknown_cast' => 'unknown_cast',
        ];

        // action
        $result = $typeConverter->convert($originalValues, $castMap);

        // assert
        self::assertCount(count($originalValues), $result);
        foreach (array_keys($originalValues) as $key) {
            self::assertArrayHasKey($key, $result);
        }
    }

    /**
     * @return array
     */
    public function paramDataProvider(): array
    {
        return [
            [
                [
                    'original' => '25',
                    'cast' => 'int',
                    'expected' => 25,
                ]
            ],
            [
                [
                    'original' => '0',
                    'cast' => 'int',
                    'expected' => 0,
                ]
            ],
            [
                [
                    'original' => '-10',
                    'cast' => 'int',
                    'expected' => -10,
                ]
            ],
            [
                [
                    'original' => '25',
                    'cast' => 'integer',
                    'expected' => 25,
                ]
            ],
            [
                [
                    'original' => '25.25',
                    'cast' => 'float',
                    'expected' => 25.25,
                ]
            ],
            [
                [
                    'original' => '0',
                    'cast' => 'float',
                    'expected' => 0.0,
                ]
            ],
            [
                [
                    'original' => '-2.05',
                    'cast' => 'float',
                    'expected' => -2.05,
                ]
            ],
            [
                [
                    'original' => '25.25',
                    'cast' => 'double',
                    'expected' => 25.25,
                ]
            ],
            [
                [
                    'original' => '25.25',
                    'cast' => 'numeric',
                    'expected' => 25.25,
                ]
            ],
            [
                [
                    'original' => '1',
                    'cast' => 'bool',
                    'expected' => true,
                ]
            ],
            [
                [
                    'original' => '0',
                    'cast' => 'bool',
                    'expected' => false,
                ]
            ],
            [
                [
                    'original' => '333',
                    'cast' => 'bool',
                    'expected' => true,
                ]
            ],
            [
                [
                    'original' => '1',
                    'cast' => 'boolean',
                    'expected' => true,
                ]
            ],
            [
                [
                    'original' => '0',
                    'cast' => 'boolean',
                    'expected' => false,
                ]
            ],
            [
                [
                    'original' => '0',
                    'cast' => 'string',
                    'expected' => '0',
                ]
            ],
            [
                [
                    'original' => '555 Привет world!',
                    'cast' => 'string',
                    'expected' => '555 Привет world!',
                ]
            ],
            [
                [
                    'original' => 'Keep this as string 123',
                    'cast' => 'unknown_cast',
                    'expected' => 'Keep this as string 123',
                ]
            ],
            [
                [
                    'original' => '123',
                    'cast' => 'unknown_cast',
                    'expected' => '123',
                ]
            ],
        ];
    }
}
