<?php

declare(strict_types=1);

namespace Fi1a\Crawler\ItemStorages;

use Fi1a\Crawler\ItemCollectionInterface;
use Fi1a\Crawler\ItemInterface;
use Fi1a\Crawler\ItemStorages\StorageAdapters\StorageAdapterInterface;

class ItemStorage implements ItemStorageInterface
{
    /**
     * @var StorageAdapterInterface
     */
    protected $adapter;

    public function __construct(StorageAdapterInterface $adapter)
    {
        $this->adapter = $adapter;
    }

    /**
     * @inheritDoc
     */
    public function load(): ItemCollectionInterface
    {
        $collection = $this->adapter->load();

        foreach ($collection as $item) {
            assert($item instanceof ItemInterface);
            if ($item->isExpired()) {
                $item->reset();
            }
        }

        return $collection;
    }

    /**
     * @inheritDoc
     */
    public function getBody(ItemInterface $item)
    {
        return $this->adapter->getBody($item);
    }

    /**
     * @inheritDoc
     */
    public function saveBody(ItemInterface $item, string $body): bool
    {
        return $this->adapter->saveBody($item, $body);
    }

    /**
     * @inheritDoc
     */
    public function save(ItemCollectionInterface $collection): bool
    {
        return $this->adapter->save($collection);
    }

    /**
     * @inheritDoc
     */
    public function clear(): bool
    {
        return $this->adapter->clear();
    }
}
