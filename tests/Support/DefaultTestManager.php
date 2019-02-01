<?php

namespace Tests\Support;

use DBALTableManager\BaseManager;
use DBALTableManager\Entity\EntityInterface;

/**
 * Class DefaultTestManager
 *
 * @package Tests\Support
 */
class DefaultTestManager extends BaseManager
{
    /**
     * @return EntityInterface
     */
    public function getEntity(): EntityInterface
    {
        return new DefaultTestEntity();
    }
}
