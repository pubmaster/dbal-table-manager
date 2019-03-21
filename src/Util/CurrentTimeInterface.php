<?php

namespace DBALTableManager\Util;

/**
 * Interface CurrentTimeInterface
 *
 * @package DBALTableManager\Util
 */
interface CurrentTimeInterface
{
    /**
     * @return \DateTime
     */
    public function getCurrentTime(): \DateTime;
}
