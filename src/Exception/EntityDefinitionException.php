<?php

namespace DBALTableManager\Exception;

/**
 * Class EntityDefinitionException
 *
 * @package DBALTableManager\Exception
 */
class EntityDefinitionException extends DBALTableManagerException
{
    /**
     * @return EntityDefinitionException
     */
    public static function withNoPrimaryKeyDefined(): self
    {
        return new static('Entity has no PK defined');
    }

    /**
     * @return EntityDefinitionException
     */
    public static function withNotSoftDeletable(): self
    {
        return new static('Entity is not soft deletable');
    }

    /**
     * @return EntityDefinitionException
     */
    public static function withNoCreatedAtColumnDefined(): self
    {
        return new static('Entity has no created at column defined');
    }

    /**
     * @return EntityDefinitionException
     */
    public static function withNoUpdatedAtColumnDefined(): self
    {
        return new static('Entity has no updated at column defined');
    }

    /**
     * @return EntityDefinitionException
     */
    public static function withNoDeletedAtColumnDefined(): self
    {
        return new static('Entity has no deleted at column defined');
    }
}
