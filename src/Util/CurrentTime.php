<?php

namespace DBALTableManager\Util;

/**
 * Class CurrentTime
 *
 * @package DBALTableManager\Util
 */
class CurrentTime implements CurrentTimeInterface
{
    /**
     * @return \DateTime
     */
    public function getCurrentTime(): \DateTime
    {
        try {
            return new \DateTime();
        } catch (\Exception $e) {
            throw new \RuntimeException("Error during datetime generation", 0, $e);
        }
    }
}
