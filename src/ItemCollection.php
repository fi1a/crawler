<?php

declare(strict_types=1);

namespace Fi1a\Crawler;

use Fi1a\Collection\Collection;

/**
 * Коллекция элементов
 */
class ItemCollection extends Collection implements ItemCollectionInterface
{
    /**
     * @inheritDoc
     */
    public function __construct(?array $data = null)
    {
        parent::__construct(ItemInterface::class, $data);
    }
}
