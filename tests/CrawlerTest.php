<?php

declare(strict_types=1);

namespace Fi1a\Unit\Crawler;

use Fi1a\Crawler\Config;
use Fi1a\Crawler\ConfigInterface;
use Fi1a\Crawler\Crawler;
use Fi1a\Crawler\CrawlerInterface;
use Fi1a\Unit\Crawler\TestCases\TestCase;
use InvalidArgumentException;

/**
 * Web Crawler
 */
class CrawlerTest extends TestCase
{
    /**
     * Возвращает конфиг
     */
    protected function getConfig(): ConfigInterface
    {
        $config = new Config();

        $config->addStartUrl($this->getUrl('/index.html'));
        $config->addStartUrl($this->getUrl('/link1.html'));

        return $config;
    }

    /**
     * Web Crawler
     */
    protected function getCrawler(): CrawlerInterface
    {
        return new Crawler($this->getConfig());
    }

    /**
     * Исключение если не задана точка входа
     */
    public function testValidateConfigEmptyStartUrls(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $config = new Config();
        $crawler = new Crawler($config);
        $crawler->run();
    }

    public function testDefaultRestrictions(): void
    {
        $crawler = $this->getCrawler();

        $crawler->run();
        $this->assertCount(1, $crawler->getRestrictions());
    }
}
