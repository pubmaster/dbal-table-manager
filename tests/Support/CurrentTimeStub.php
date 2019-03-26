<?php

namespace Tests\DBALTableManager\Support;

use DBALTableManager\Util\CurrentTimeInterface;

/**
 * Class CurrentTimeStub
 *
 * @package Tests\DBALTableManager\Support
 */
class CurrentTimeStub implements CurrentTimeInterface
{
    /**
     * @var \DateTime
     */
    private $date;

    /**
     * @return \DateTime
     */
    public function getCurrentTime(): \DateTime
    {
        if ($this->date !== null) {
            return $this->date;
        }

        try {
            return new \DateTime();
        } catch (\Exception $e) {
            throw new \RuntimeException("Error during datetime generation", 0, $e);
        }
    }

    /**
     * @param string $dateTime
     */
    public function setDate(string $dateTime): void
    {
        try {
            $this->date = new \DateTime($dateTime);
        } catch (\Exception $e) {
            throw new \RuntimeException("Error during datetime generation", 0, $e);
        }
    }

    public function reset(): void
    {
        $this->date = null;
    }
}
