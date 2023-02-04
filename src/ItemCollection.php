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

    /**
     * @inheritDoc
     */
    public function getDownloaded()
    {
        return $this->filter(function ($item) {
            assert($item instanceof ItemInterface);

            return $item->getDownloadStatus() === true;
        });
    }

    /**
     * @inheritDoc
     */
    public function getProcessed()
    {
        return $this->filter(function ($item) {
            assert($item instanceof ItemInterface);

            return $item->getProcessStatus() === true;
        });
    }

    /**
     * @inheritDoc
     */
    public function getWrited()
    {
        return $this->filter(function ($item) {
            assert($item instanceof ItemInterface);

            return $item->getWriteStatus() === true;
        });
    }
}
