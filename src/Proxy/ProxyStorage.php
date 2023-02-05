<?php

declare(strict_types=1);

namespace Fi1a\Crawler\Proxy;

use Fi1a\Crawler\Proxy\StorageAdapters\StorageAdapterInterface;

/**
 * Хранилище прокси
 */
class ProxyStorage implements ProxyStorageInterface
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
    public function load(): ProxyCollectionInterface
    {
        return $this->adapter->load();
    }

    /**
     * @inheritDoc
     */
    public function save(ProxyInterface $proxy): bool
    {
        return $this->adapter->save($proxy);
    }
}
