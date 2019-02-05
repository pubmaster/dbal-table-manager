<?php

namespace DBALTableManager;

/**
 * Class Sorting
 *
 * @package DBALTableManager
 */
class Sorting
{
    public const SORT_ASC = 'ASC';
    public const SORT_DESC = 'DESC';

    /** @var SortingItem[] */
    private $sortList = [];

    /**
     * @param string $column
     * @param string $order
     */
    public function addSorting(string $column, string $order = self::SORT_ASC): void
    {
        if ($column === '') {
            throw new \InvalidArgumentException('Column must not be an empty string');
        }
        if ($order === '') {
            throw new \InvalidArgumentException('Order must not be an empty string');
        }
        if (!in_array(mb_strtoupper($order), [self::SORT_ASC, self::SORT_DESC], true)) {
            throw new \InvalidArgumentException('Illegal order value');
        }

        $this->sortList[] = new SortingItem($column, $order);
    }

    /**
     * @return SortingItem[]
     */
    public function getSortList(): array
    {
        return $this->sortList;
    }
}
