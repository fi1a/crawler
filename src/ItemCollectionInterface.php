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

    /**
     * Возвращает все элементы изображений
     *
     * @return static
     */
    public function getImages();

    /**
     * Возвращает все элементы файлов
     *
     * @return static
     */
    public function getFiles();

    /**
     * Возвращает все элементы страниц
     *
     * @return static
     */
    public function getPages();

    /**
     * Возвращает все элементы css файлов
     *
     * @return static
     */
    public function getCss();

    /**
     * Возвращает все элементы js файлов
     *
     * @return static
     */
    public function getJs();
}
