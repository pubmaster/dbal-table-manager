<?php

namespace DBALTableManager\Query;

/**
 * Class Sorting
 *
 * @package DBALTableManager\Query
 */
class Sorting implements SortingInterface
{
    public const SORT_ASC = 'ASC';
    public const SORT_DESC = 'DESC';

    /** @var SortingItem[] */
    private $sortList = [];

    /**
     * @param string $column
     * @param string $order
     *
     * @return Sorting
     */
    public function addSorting(string $column, string $order = self::SORT_ASC): self
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

        return $this;
    }

    /**
     * @return SortingItem[]
     */
    public function getSortList(): array
    {
        return $this->sortList;
    }

    /**
     * @return Sorting
     */
    public static function newInstance(): self
    {
        return new static();
    }
}
