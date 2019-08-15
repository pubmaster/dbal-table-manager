<?php

namespace DBALTableManager\Factory;

use DBALTableManager\BaseConnectionInterface;
use DBALTableManager\Entity\EntityInterface;
use DBALTableManager\Entity\TemporalVersionEntityInterface;
use DBALTableManager\EntityTransformer\EntityTransformer;
use DBALTableManager\EntityValidator\EntityValidator;
use DBALTableManager\Manager\SingleTableManager;
use DBALTableManager\Manager\TemporalTableManager;
use DBALTableManager\QueryBuilder\QueryBuilderPreparer;
use DBALTableManager\SchemaDescription\SingleTableSchemaDescription;
use DBALTableManager\SchemaDescription\TemporalTableSchemaDescription;
use DBALTableManager\TableRowCaster\TableRowCaster;
use DBALTableManager\Util\CurrentTimeInterface;
use DBALTableManager\Util\StringUtils;
use DBALTableManager\Util\TypeConverter;

/**
 * Class TableManagerFactory
 *
 * @package DBALTableManager\Factory
 */
class TableManagerFactory
{
    /**
     * @var TypeConverter
     */
    private $typeConverter;
    /**
     * @var StringUtils
     */
    private $stringUtils;
    /**
     * @var EntityTransformer
     */
    private $entityTransformer;
    /**
     * @var CurrentTimeInterface
     */
    private $currentTime;

    /**
     * BaseManagerFactory constructor.
     *
     * @param TypeConverter $typeConverter
     * @param StringUtils $stringUtils
     * @param EntityTransformer $entityTransformer
     * @param CurrentTimeInterface $currentTime
     */
    public function __construct(
        TypeConverter $typeConverter,
        StringUtils $stringUtils,
        EntityTransformer $entityTransformer,
        CurrentTimeInterface $currentTime
    ) {
        $this->typeConverter = $typeConverter;
        $this->stringUtils = $stringUtils;
        $this->entityTransformer = $entityTransformer;
        $this->currentTime = $currentTime;
    }

    /**
     * @param BaseConnectionInterface $connection
     * @param EntityInterface $entity
     *
     * @param string|null $managerClass
     *
     * @return SingleTableManager
     */
    public function makeManagerForSingleTable(
        BaseConnectionInterface $connection,
        EntityInterface $entity,
        string $managerClass = null
    ): SingleTableManager
    {
        $schemaDescription = new SingleTableSchemaDescription($entity);
        $entityValidator = new EntityValidator($entity);
        $queryBuilderPreparer = new QueryBuilderPreparer($entity, $schemaDescription, $entityValidator, $this->stringUtils);
        $tableRowCaster = new TableRowCaster($this->typeConverter, $schemaDescription);

        $class = $managerClass ?: SingleTableManager::class;

        return new $class(
            $connection,
            $queryBuilderPreparer,
            $tableRowCaster,
            $this->currentTime,
            $entityValidator,
            $entity
        );
    }

    public function makeManagerForTemporalTable(
        BaseConnectionInterface $connection,
        EntityInterface $staticEntity,
        TemporalVersionEntityInterface $versionEntity,
        string $managerClass = null
    ): TemporalTableManager
    {
        $schemaDescription = new TemporalTableSchemaDescription($staticEntity, $versionEntity);
        $entityValidator = new EntityValidator($staticEntity);
        $queryBuilderPreparer = new QueryBuilderPreparer($staticEntity, $schemaDescription, $entityValidator, $this->stringUtils);
        $tableRowCaster = new TableRowCaster($this->typeConverter, $schemaDescription);

        $staticManager = $this->makeManagerForSingleTable($connection, $staticEntity);
        $versionManager = $this->makeManagerForSingleTable(
            $connection,
            $this->entityTransformer->transformVersionToCommon($versionEntity)
        );

        $class = $managerClass ?: TemporalTableManager::class;

        return new $class(
            $connection,
            $staticManager,
            $versionManager,
            $queryBuilderPreparer,
            $tableRowCaster,
            $this->currentTime,
            $staticEntity,
            $versionEntity
        );
    }
}
