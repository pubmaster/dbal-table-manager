<?php

namespace DBALTableManager\EntityTransformer;

use DBALTableManager\Entity\EntityInterface;
use DBALTableManager\Entity\TemporalVersionEntityInterface;

/**
 * Class EntityTransformer
 *
 * @package DBALTableManager\EntityTransformer
 */
class EntityTransformer
{
    /**
     * @param TemporalVersionEntityInterface $entityVersion
     *
     * @return EntityInterface
     */
    public function transformVersionToCommon(TemporalVersionEntityInterface $entityVersion): EntityInterface
    {
        return new class ($entityVersion) implements EntityInterface
        {
            /**
             * @var TemporalVersionEntityInterface
             */
            private $entityVersion;

            /**
             *  constructor.
             *
             * @param TemporalVersionEntityInterface $entityVersion
             */
            public function __construct(TemporalVersionEntityInterface $entityVersion)
            {
                $this->entityVersion = $entityVersion;
            }

            /**
             * @return string
             */
            public function getTableName(): string
            {
                return $this->entityVersion->getTableName();
            }

            /**
             * @return array
             */
            public function getPrimaryKey(): array
            {
                return $this->entityVersion->getPrimaryKey();
            }

            /**
             * @return bool
             */
            public function isPkAutoGenerated(): bool
            {
                return false;
            }

            /**
             * @return array
             */
            public function getFieldMap(): array
            {
                return $this->entityVersion->getFieldMap();
            }

            /**
             * @return bool
             */
            public function isTimestampable(): bool
            {
                return false;
            }

            /**
             * @return string|null
             */
            public function getCreatedAtField(): ?string
            {
                return null;
            }

            /**
             * @return string|null
             */
            public function getUpdatedAtField(): ?string
            {
                return null;
            }

            /**
             * @return bool
             */
            public function isSoftDeletable(): bool
            {
                return false;
            }

            /**
             * @return string|null
             */
            public function getDeletedAtField(): ?string
            {
                return null;
            }
        };
    }
}
