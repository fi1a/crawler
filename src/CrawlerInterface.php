<?php

declare(strict_types=1);

namespace Fi1a\Crawler;

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
}
