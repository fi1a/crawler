<?php

declare(strict_types=1);

namespace Fi1a\Crawler;

use Fi1a\Crawler\PrepareItem\PrepareItemInterface;
use Fi1a\Crawler\Restrictions\RestrictionCollectionInterface;
use Fi1a\Crawler\Restrictions\RestrictionInterface;
use Fi1a\Crawler\UriParsers\UriParserInterface;
use Fi1a\Crawler\UriTransformers\UriTransformerInterface;
use Fi1a\Crawler\Writers\WriterInterface;

/**
 * Web Crawler
 */
interface CrawlerInterface
{
    /**
     * Запуск
     *
     * @return $this
     */
    public function run();

    /**
     * Скачивание данных
     *
     * @return $this
     */
    public function download();

    /**
     * Обработка скаченных данных
     *
     * @return $this
     */
    public function process();

    /**
     * Запись скаченных данных
     *
     * @return $this
     */
    public function write();

    /**
     * Добавить ограничение
     *
     * @return $this
     */
    public function addRestriction(RestrictionInterface $restriction);

    /**
     * Возвращает ограничения
     */
    public function getRestrictions(): RestrictionCollectionInterface;

    /**
     * Возвращает адреса
     */
    public function getItems(): ItemCollectionInterface;

    /**
     * Устанавливает парсер uri для обхода (в зависимости от типа контента)
     *
     * @return $this
     */
    public function setUriParser(UriParserInterface $parser, ?string $mime = null);

    /**
     * Проверяет наличие парсера uri (в зависимости от типа контента)
     */
    public function hasUriParser(?string $mime = null): bool;

    /**
     * Удаляет парсер uri (в зависимости от типа контента)
     *
     * @return $this
     */
    public function removeUriParser(?string $mime = null);

    /**
     * Установить класс преобразователь адресов из внешних во внутренние
     *
     * @return $this
     */
    public function setUriTransformer(UriTransformerInterface $uriTransformer);

    /**
     * Установить класс подготавливающий элемент
     *
     * @return $this
     */
    public function setPrepareItem(PrepareItemInterface $prepareItem);

    /**
     * Установить класс записывающий результат обхода
     *
     * @return $this
     */
    public function setWriter(WriterInterface $writer);

    /**
     * Очищает данные хранилища
     *
     * @return $this
     */
    public function clearStorageData();
}
