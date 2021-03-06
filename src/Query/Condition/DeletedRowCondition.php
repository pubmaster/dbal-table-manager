<?php

namespace DBALTableManager\Query\Condition;

/**
 * Class DeletedRowCondition
 *
 * @package DBALTableManager\Query\Condition
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

    /**
     * DeletedRowCondition constructor.
     *
     * @param bool $showNotDeleted
     * @param bool $showDeleted
     */
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
