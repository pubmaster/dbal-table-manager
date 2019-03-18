<?php

namespace DBALTableManager\EntityValidator;

use DBALTableManager\Entity\EntityInterface;
use DBALTableManager\Exception\EntityDefinitionException;

/**
 * Class EntityValidator
 *
 * @package DBALTableManager\EntityValidator
 */
class EntityValidator
{
    /**
     * @var EntityInterface
     */
    private $entity;

    /**
     * EntityValidator constructor.
     *
     * @param EntityInterface $entity
     */
    public function __construct(EntityInterface $entity)
    {
        $this->entity = $entity;
    }

    public function checkTimestampableEntity(): void
    {
        if ($this->entity->isTimestampable()) {
            return;
        }

        $createdAtField = $this->entity->getCreatedAtField();
        if ($createdAtField === null || $createdAtField === '') {
            throw EntityDefinitionException::withNoCreatedAtColumnDefined();
        }

        $updatedAtField = $this->entity->getUpdatedAtField();
        if ($updatedAtField === null || $updatedAtField === '') {
            throw EntityDefinitionException::withNoUpdatedAtColumnDefined();
        }
    }

    public function checkSoftDeletableEntity(): void
    {
        if ($this->entity->isSoftDeletable()) {
            return;
        }

        $deletedAtField = $this->entity->getDeletedAtField();
        if ($deletedAtField === null || $deletedAtField === '') {
            throw EntityDefinitionException::withNoDeletedAtColumnDefined();
        }
    }
}
