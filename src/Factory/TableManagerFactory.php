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
    protected $typeConverter;
    /**
     * @var StringUtils
     */
    protected $stringUtils;
    /**
     * @var EntityTransformer
     */
    private $entityTransformer;

    /**
     * BaseManagerFactory constructor.
     *
     * @param TypeConverter $typeConverter
     * @param StringUtils $stringUtils
     * @param EntityTransformer $entityTransformer
     */
    public function __construct(
        TypeConverter $typeConverter,
        StringUtils $stringUtils,
        EntityTransformer $entityTransformer
    ) {
        $this->typeConverter = $typeConverter;
        $this->stringUtils = $stringUtils;
        $this->entityTransformer = $entityTransformer;
    }

    /**
     * @param BaseConnectionInterface $connection
     * @param EntityInterface $entity
     *
     * @return SingleTableManager
     */
    public function makeManagerForSingleTable(
        BaseConnectionInterface $connection,
        EntityInterface $entity
    ): SingleTableManager
    {
        $schemaDescription = new SingleTableSchemaDescription($entity);
        $entityValidator = new EntityValidator($entity);
        $queryBuilderPreparer = new QueryBuilderPreparer($entity, $schemaDescription, $entityValidator, $this->stringUtils);
        $tableRowCaster = new TableRowCaster($this->typeConverter, $schemaDescription);

        return new SingleTableManager(
            $connection,
            $queryBuilderPreparer,
            $tableRowCaster,
            $entityValidator,
            $entity
        );
    }

    public function makeManagerForTemporalTable(
        BaseConnectionInterface $connection,
        EntityInterface $staticEntity,
        TemporalVersionEntityInterface $versionEntity
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

        return new TemporalTableManager(
            $connection,
            $staticManager,
            $versionManager,
            $queryBuilderPreparer,
            $tableRowCaster,
            $staticEntity,
            $versionEntity
        );
    }
}
