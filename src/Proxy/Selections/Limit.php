<?php

declare(strict_types=1);

namespace Fi1a\Crawler\Proxy\Selections;

use Fi1a\Crawler\ItemInterface;
use Fi1a\Crawler\Proxy\ProxyCollection;
use Fi1a\Crawler\Proxy\ProxyCollectionInterface;
use InvalidArgumentException;

/**
 * Ограничение на кол-во подобранных прокси
 */
class Limit implements ProxySelectionInterface
{
    /**
     * @var ProxySelectionInterface|null
     */
    protected $proxySelection;

    /**
     * @var positive-int
     */
    protected $limit;

    public function __construct(?ProxySelectionInterface $proxySelection = null, int $limit = 3)
    {
        $this->proxySelection = $proxySelection;
        if ($limit <= 0) {
            throw new InvalidArgumentException('Ограничение может быть только положительным числом');
        }
        $this->limit = $limit;
    }

    /**
     * @inheritDoc
     */
    public function selection(ProxyCollectionInterface $collection, ItemInterface $item): ProxyCollectionInterface
    {
        if ($this->proxySelection) {
            $collection = $this->proxySelection->selection($collection, $item);
        }

        $array = array_slice($collection->getArrayCopy(), 0, $this->limit);

        return new ProxyCollection($array);
    }
}
