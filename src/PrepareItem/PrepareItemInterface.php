<?php

declare(strict_types=1);

namespace Fi1a\Crawler\PrepareItem;

use Fi1a\Crawler\ItemCollectionInterface;
use Fi1a\Crawler\ItemInterface;

/**
 * Подготавливает элемент
 */
interface PrepareItemInterface
{
    /**
     * Подготавливает элемент
     *
     * @return mixed
     */
    public function prepare(ItemInterface $item, ItemCollectionInterface $items);
}
