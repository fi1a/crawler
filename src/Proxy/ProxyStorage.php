<?php

declare(strict_types=1);

namespace Fi1a\Crawler\Proxy;

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
    public function save(ProxyCollectionInterface $collection): bool
    {
        return $this->adapter->save($collection);
    }
}
