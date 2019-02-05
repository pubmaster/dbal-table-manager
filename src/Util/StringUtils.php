<?php

namespace DBALTableManager\Util;

/**
 * Class StringUtils
 *
 * @package DBALTableManager\Util
 */
class StringUtils
{
    /**
     * Sanitizes string for sql query
     *
     * @param string $string
     * @return string
     */
    public function prepareSqlLikeOperator(?string $string): string
    {
        if ($string === null) {
            return $string;
        }

        $string = (string) $string;
        $string = str_replace(['+', '%', '_'], ['\+', '\%', '\_'], $string);

        return $string;
    }
}
