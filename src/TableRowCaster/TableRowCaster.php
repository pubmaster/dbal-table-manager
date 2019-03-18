<?php

namespace DBALTableManager\TableRowCaster;

use DBALTableManager\SchemaDescription\SchemaDescriptionInterface;
use DBALTableManager\Util\TypeConverter;

/**
 * Class TableRowCaster
 *
 * @package DBALTableManager\TableRowCaster
 */
class TableRowCaster
{
    /**
     * @var TypeConverter
     */
    private $typeConverter;
    /**
     * @var SchemaDescriptionInterface
     */
    private $schemaDescription;

    /**
     * TableRowCaster constructor.
     *
     * @param SchemaDescriptionInterface $schemaDescription
     * @param TypeConverter $typeConverter
     */
    public function __construct(
        TypeConverter $typeConverter,
        SchemaDescriptionInterface $schemaDescription
    )
    {
        $this->typeConverter = $typeConverter;
        $this->schemaDescription = $schemaDescription;
    }

    /**
     * @param array $row
     *
     * @return array
     */
    public function prepareRow(array $row): array
    {
        return $this->typeConverter->convert($row, $this->schemaDescription->getCastMap());
    }
}
