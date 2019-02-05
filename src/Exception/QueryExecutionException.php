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
}
