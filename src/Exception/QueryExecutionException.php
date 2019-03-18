<?php

namespace DBALTableManager\Exception;

/**
 * Class QueryExecutionException
 *
 * @package DBALTableManager\Exception
 */
class QueryExecutionException extends DBALTableManagerException
{
    /**
     * @return QueryExecutionException
     */
    public static function withAggregatedResultOfZeroRows(): self
    {
        return new static('Aggregation query returned no rows');
    }

    /**
     * @param string $key
     *
     * @return QueryExecutionException
     */
    public static function withRequiredDataMissing(string $key): self
    {
        return new static("Required data missing with key [{$key}]");
    }
}
