<?php

declare(strict_types=1);

namespace Fi1a\Crawler\Proxy\Selections;

use Fi1a\Crawler\ItemInterface;
use Fi1a\Crawler\Proxy\ProxyCollection;
use Fi1a\Crawler\Proxy\ProxyCollectionInterface;
use Fi1a\Crawler\Proxy\ProxyInterface;

/**
 * Отсортированные по времени использования
 */
class SortedByTime implements ProxySelectionInterface
{
    public const ORDER_BY_ASC = 'asc';

    public const ORDER_BY_DESC = 'desc';

    /**
     * @var string
     */
    protected $orderBy;

    /**
     * @var ProxySelectionInterface|null
     */
    protected $proxySelection;

    public function __construct(?ProxySelectionInterface $proxySelection = null, string $orderBy = self::ORDER_BY_ASC)
    {
        $this->orderBy = $orderBy;
        $this->proxySelection = $proxySelection;
    }

    /**
     * @inheritDoc
     */
    public function selection(ProxyCollectionInterface $collection, ItemInterface $item): ProxyCollectionInterface
    {
        if ($this->proxySelection) {
            $collection = $this->proxySelection->selection($collection, $item);
        }

        /** @var ProxyInterface[] $array */
        $array = $collection->getArrayCopy();
        usort($array, [$this, 'sort']);

        return new ProxyCollection($array);
    }

    /**
     * Метод сортировки
     */
    protected function sort(ProxyInterface $a, ProxyInterface $b): int
    {
        $lastUseA = $a->getLastUse();
        $lastUseB = $b->getLastUse();
        if (!$lastUseA && !$lastUseB) {
            return 0;
        }
        if (!$lastUseA) {
            return $this->orderBy === self::ORDER_BY_ASC ? -1 : 1;
        }
        if (!$lastUseB) {
            return $this->orderBy === self::ORDER_BY_ASC ? 1 : -1;
        }

        return ($this->orderBy === self::ORDER_BY_ASC ? 1 : -1)
            * ($lastUseA->getTimestamp() - $lastUseB->getTimestamp());
    }
}
