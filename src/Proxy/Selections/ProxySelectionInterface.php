<?php

declare(strict_types=1);

namespace Fi1a\Crawler\Proxy\Selections;

use Fi1a\Crawler\ItemInterface;
use Fi1a\Crawler\Proxy\ProxyCollectionInterface;

/**
 * Подбор подходящих прокси
 */
interface ProxySelectionInterface
{
    /**
     * Подбор подходящих прокси
     */
    public function selection(ProxyCollectionInterface $collection, ItemInterface $item): ProxyCollectionInterface;
}
