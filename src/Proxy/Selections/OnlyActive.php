<?php

declare(strict_types=1);

namespace Fi1a\Crawler\Proxy\Selections;

use Fi1a\Crawler\ItemInterface;
use Fi1a\Crawler\Proxy\ProxyCollection;
use Fi1a\Crawler\Proxy\ProxyCollectionInterface;
use Fi1a\Crawler\Proxy\ProxyInterface;

/**
 * Фильтрация прокси по активности
 */
class OnlyActive implements ProxySelectionInterface
{
    /**
     * @var ProxySelectionInterface|null
     */
    protected $proxySelection;

    public function __construct(?ProxySelectionInterface $proxySelection = null)
    {
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

        $array = [];
        foreach ($collection as $proxy) {
            assert($proxy instanceof ProxyInterface);
            if ($proxy->isActive()) {
                $array[] = $proxy;
            }
        }

        return new ProxyCollection($array);
    }
}
