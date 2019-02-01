<?php

namespace Tests\Support;

use Doctrine\DBAL\Logging\SQLLogger;

/**
 * A SQL logger that logs to the standard output using echo/var_dump.
 */
class EchoSQLLogger implements SQLLogger
{
    /**
     * {@inheritdoc}
     */
    public function startQuery($sql, ?array $params = null, ?array $types = null)
    {
        echo PHP_EOL . $sql . ' | ' . json_encode($params) . PHP_EOL;
    }

    /**
     * {@inheritdoc}
     */
    public function stopQuery()
    {
    }
}
