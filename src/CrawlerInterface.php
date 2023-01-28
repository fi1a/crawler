<?php

declare(strict_types=1);

namespace Fi1a\Crawler;

use Fi1a\Crawler\PreparePage\PreparePageInterface;
use Fi1a\Crawler\Restrictions\RestrictionCollectionInterface;
use Fi1a\Crawler\Restrictions\RestrictionInterface;
use Fi1a\Crawler\UriConverters\UriConverterInterface;
use Fi1a\Crawler\UriParsers\UriParserInterface;

/**
 * Web Crawler
 */
interface CrawlerInterface
{
    public function __construct(ConfigInterface $config);

    /**
     * Запуск
     */
    public function run(): void;

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
     * Возвращает обойденные адреса
     */
    public function getBypassedPages(): PageCollectionInterface;

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
    public function setUriConverter(UriConverterInterface $uriConverter);

    /**
     * Установить класс подготавливающий страницу
     *
     * @return $this
     */
    public function setPreparePage(PreparePageInterface $preparePage);
}
