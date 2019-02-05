<?php

namespace DBALTableManager\Exception;

/**
 * Class InvalidQueryException
 *
 * @package DBALTableManager\Exception
 */
class InvalidRequestException extends DBALTableManagerException
{
    /**
     * @param array $columnList
     *
     * @return InvalidRequestException
     */
    public static function withUnknownColumnList(array $columnList): self
    {
        return new static('Unknown columns: [' . implode(', ', $columnList) . ']');
    }

    /**
     * @param string $pkColumn
     *
     * @return InvalidRequestException
     */
    public static function withNoPrimaryKeyValue(string $pkColumn): self
    {
        return new static('No value provided for PK column "' . $pkColumn . '"');
    }

    /**
     * @return InvalidRequestException
     */
    public static function withDataAndFilterCountNotEqual(): self
    {
        return new static('Data count must be equal to filter count');
    }
}
