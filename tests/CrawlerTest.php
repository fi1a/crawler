<?php

declare(strict_types=1);

namespace Fi1a\Unit\Crawler;

use Fi1a\Console\IO\ConsoleOutput;
use Fi1a\Console\IO\Formatter;
use Fi1a\Console\IO\Stream;
use Fi1a\Crawler\Config;
use Fi1a\Crawler\ConfigInterface;
use Fi1a\Crawler\Crawler;
use Fi1a\Crawler\CrawlerInterface;
use Fi1a\Crawler\Restrictions\NotAllowRestriction;
use Fi1a\Crawler\UriCollection;
use Fi1a\Crawler\UriParsers\HtmlUriParser;
use Fi1a\Crawler\Writers\FileWriter;
use Fi1a\Http\MimeInterface;
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

        $config->addStartUri($this->getUrl('/index.html'));
        $config->addStartUri($this->getUrl('/link1.html'));
        $config->setVerbose(ConfigInterface::VERBOSE_NONE);

        $config->getHttpClientConfig()->setSslVerify(false);

        return $config;
    }

    /**
     * Web Crawler
     */
    protected function getCrawler(): CrawlerInterface
    {
        $crawler = new Crawler($this->getConfig());

        $crawler->setWriter(new FileWriter($this->runtimeFolder . '/web'));

        return $crawler;
    }

    /**
     * Исключение если не задана точка входа
     */
    public function testValidateEmptyStartUrls(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $config = new Config();
        $crawler = new Crawler($config);
        $crawler->run();
    }

    /**
     * Исключение если не задан класс записывающий результат обхода
     */
    public function testValidateEmptyWriter(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $config = new Config();
        $config->addStartUri($this->getUrl('/index.html'));
        $crawler = new Crawler($config);
        $crawler->run();
    }

    /**
     * Ограничения по умолчанию
     */
    public function testDefault(): void
    {
        $crawler = $this->getCrawler();

        $crawler->run();
        $this->assertCount(1, $crawler->getRestrictions());
        $this->assertTrue($crawler->hasUriParser());
    }

    /**
     * Web Crawler
     */
    public function testDefaultOutputMemory(): void
    {
        $config = $this->getConfig();
        $config->setVerbose(ConfigInterface::VERBOSE_DEBUG);
        $output = new ConsoleOutput(new Formatter());
        $output->setStream(new Stream('php://memory'));
        $crawler = new Crawler($config, $output);
        $crawler->setWriter(new FileWriter($this->runtimeFolder . '/web'));

        $crawler->run();
        $this->assertCount(1, $crawler->getRestrictions());
        $this->assertTrue($crawler->hasUriParser());
    }

    /**
     * Возвращает обойденные адреса
     */
    public function testBypassedUri(): void
    {
        $crawler = $this->getCrawler();

        $crawler->run();
        $this->assertCount(4, $crawler->getBypassedItems());
    }

    /**
     * Метолы по работе с парсерами uri
     */
    public function testUriParsersMethods(): void
    {
        $crawler = $this->getCrawler();
        $this->assertFalse($crawler->hasUriParser());
        $this->assertFalse($crawler->hasUriParser(MimeInterface::HTML));
        $crawler->setUriParser(new HtmlUriParser());
        $crawler->setUriParser(new HtmlUriParser(), MimeInterface::HTML);
        $this->assertTrue($crawler->hasUriParser());
        $this->assertTrue($crawler->hasUriParser(MimeInterface::HTML));
        $crawler->removeUriParser();
        $crawler->removeUriParser(MimeInterface::HTML);
        $crawler->removeUriParser();
        $crawler->removeUriParser(MimeInterface::HTML);
        $this->assertFalse($crawler->hasUriParser());
        $this->assertFalse($crawler->hasUriParser(MimeInterface::HTML));
    }

    /**
     * Вызов парсера uri по типу контента
     */
    public function testUriParsersCallByMime(): void
    {
        $uriParser = $this->getMockBuilder(HtmlUriParser::class)
            ->onlyMethods(['parse'])
            ->getMock();

        $uriParser->expects($this->exactly(2))
            ->method('parse')
            ->willReturn(new UriCollection());

        $crawler = $this->getCrawler();
        $crawler->addRestriction(new NotAllowRestriction())
            ->setUriParser($uriParser, MimeInterface::HTML);

        $crawler->run();
    }
}
