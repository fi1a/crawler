<?php

declare(strict_types=1);

namespace Fi1a\Crawler\ItemStorages;

use Fi1a\Crawler\ItemCollectionInterface;
use Fi1a\Crawler\ItemInterface;

/**
 * Адаптер хранения элементов
 */
interface StorageAdapterInterface
{
    /**
     * Загружает все элементы из хранилища
     */
    public function load(): ItemCollectionInterface;

    /**
     * Возвращает тело запроса для определенного элемента
     *
     * @return string|false
     */
    public function getBody(ItemInterface $item);

    /**
     * Сохраняет тело запроса для определенного элемента
     */
    public function saveBody(ItemInterface $item, string $body): bool;

    /**
     * Сохраняет элементы в хранилище
     */
    public function save(ItemCollectionInterface $collection): bool;

    /**
     * Очищает хранилище
     */
    public function clear(): bool;
}
