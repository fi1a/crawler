<?php

declare(strict_types=1);

namespace Fi1a\Crawler\Proxy\StorageAdapters;

use Fi1a\Crawler\Proxy\ProxyCollectionInterface;
use Fi1a\Crawler\Proxy\ProxyInterface;

/**
 * Адаптер хранилища
 */
interface StorageAdapterInterface
{
    /**
     * Загрузить коллекцию с прокси из хранилища
     */
    public function load(): ProxyCollectionInterface;

    /**
     * Сохранить прокси в хранилище
     */
    public function save(ProxyInterface $proxy): bool;
}
