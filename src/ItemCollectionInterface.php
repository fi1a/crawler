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
     * Добавление элементов из json
     *
     * @return $this
     */
    public function fromJson(string $jsonString);

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
