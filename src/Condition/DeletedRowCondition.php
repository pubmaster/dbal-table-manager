<?php

namespace DBALTableManager\Condition;

/**
 * Class DeletedRowCondition
 *
 * @package DBALTableManager\Condition
 */
class DeletedRowCondition
{
    /**
     * @var bool
     */
    private $showNotDeleted;
    /**
     * @var bool
     */
    private $showDeleted;

    public function __construct(bool $showNotDeleted, bool $showDeleted)
    {
        $this->showNotDeleted = $showNotDeleted;
        $this->showDeleted = $showDeleted;
    }

    /**
     * @return bool
     */
    public function isShowNotDeleted(): bool
    {
        return $this->showNotDeleted;
    }

    /**
     * @return bool
     */
    public function isShowDeleted(): bool
    {
        return $this->showDeleted;
    }
}
