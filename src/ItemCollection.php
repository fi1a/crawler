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
    public function fromJson(string $jsonString)
    {
        /** @var array<int, array<array-key, mixed>>|false $json */
        $json = json_decode($jsonString, true);

        if (is_array($json)) {
            foreach ($json as $jsonItem) {
                $item = Item::fromArray($jsonItem);
                $this->set($item->getItemUri()->uri(), $item);
            }
        }

        return $this;
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
