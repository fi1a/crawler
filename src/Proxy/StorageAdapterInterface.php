<?php

declare(strict_types=1);

namespace Fi1a\Crawler\Proxy;

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
     * Сохранить коллекцию прокси в хранилище
     */
    public function save(ProxyCollectionInterface $collection): bool;
}
