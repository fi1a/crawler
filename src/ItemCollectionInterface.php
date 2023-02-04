<?php

declare(strict_types=1);

namespace Fi1a\Crawler;

use Fi1a\Collection\CollectionInterface;

/**
 * Коллекция элементов
 */
interface ItemCollectionInterface extends CollectionInterface
{
    /**
     * Возвращает кол-во успешно загруженных
     *
     * @return static
     */
    public function getDownloaded();

    /**
     * Возвращает кол-во успешно обработанных
     *
     * @return static
     */
    public function getProcessed();

    /**
     * Возвращает кол-во успешно записанных
     *
     * @return static
     */
    public function getWrited();
}
