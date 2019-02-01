<?php

namespace DBALTableManager\Util;

/**
 * Class TypeConverter
 *
 * @package DBALTableManager\Util
 */
class TypeConverter
{
    /**
     * @param array $data
     * @param array $castMap
     *
     * @return array
     */
    public function convert(array $data, array $castMap): array
    {
        $result = [];

        foreach ($data as $key => $value) {
            if ($value === null) {
                $result[$key] = null;
                continue;
            }

            switch ($castMap[$key] ?? null) {
                case 'int':
                case 'integer':
                    $value = (int) $value;
                    break;
                case 'float':
                case 'double':
                case 'numeric':
                    $value = (float) $value;
                    break;
                case 'bool':
                case 'boolean':
                    $value = 0 !== (int) $value;
                    break;
            }

            $result[$key] = $value;
        }

        return $result;
    }
}
