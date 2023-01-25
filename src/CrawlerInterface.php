<?php

declare(strict_types=1);

namespace Fi1a\Crawler;

use Fi1a\Crawler\Restrictions\RestrictionCollectionInterface;
use Fi1a\Crawler\Restrictions\RestrictionInterface;

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
}
