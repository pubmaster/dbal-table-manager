<?php

namespace DBALTableManager;

/**
 * Class Sorting
 *
 * @package DBALTableManager
 */
// Все эксепшены в уникальный класс
class Sorting
{
    /** @var array */
    private $sortList = [];

    /**
     * @param string $fieldName
     * @param string $order
     */
    public function addSorting(string $fieldName, string $order = 'ASC'): void
    {
        if ($fieldName === '') {
            throw new \InvalidArgumentException('Field name must not be an empty string');
        }
        if ($order === '') {
            throw new \InvalidArgumentException('Order must not be an empty string');
        }
        if (!in_array(strtolower($order), ['asc', 'desc'])) { // OrderList и его значения в публичные константы
            throw new \InvalidArgumentException('Illegal order value');
        }

        $this->sortList[] = [$fieldName, $order];
    }

    /**
     * @return array
     */
    public function getSortList(): array
    {
        return $this->sortList;
    }
}
