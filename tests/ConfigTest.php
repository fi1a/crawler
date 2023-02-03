<?php

declare(strict_types=1);

namespace Fi1a\Unit\Crawler;

use Fi1a\Crawler\Config;
use Fi1a\Crawler\ConfigInterface;
use Fi1a\HttpClient\Config as HttpClientConfig;
use Fi1a\HttpClient\ConfigInterface as HttpClientConfigInterface;
use Fi1a\HttpClient\Handlers\CurlHandler;
use Fi1a\HttpClient\Handlers\StreamHandler;
use Fi1a\Unit\Crawler\TestCases\TestCase;
use InvalidArgumentException;

/**
 * Конфигурация
 */
class ConfigTest extends TestCase
{
    /**
     * Добавить точку входа, с которой начинается обход
     */
    public function testStartUri(): void
    {
        $config = new Config();
        $this->assertCount(0, $config->getStartUri());
        $config->addStartUri($this->getUrl('/start1.html'));
        $config->addStartUri($this->getUrl('/start2.html'));
        $this->assertCount(2, $config->getStartUri());
    }

    /**
     * Добавить точку входа (исключение при пустом хосте)
     */
    public function testStartUriHostException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $config = new Config();
        $config->addStartUri('/start1.html');
    }

    /**
     * Конфигурация http-клиента
     */
    public function testHttpClientConfig(): void
    {
        $config = new Config();
        $this->assertInstanceOf(HttpClientConfigInterface::class, $config->getHttpClientConfig());
        $config->setHttpClientConfig(new HttpClientConfig());
        $this->assertInstanceOf(HttpClientConfigInterface::class, $config->getHttpClientConfig());
    }

    /**
     * Обработчик запросов
     */
    public function testHttpClientHandler(): void
    {
        $config = new Config();
        $this->assertEquals(StreamHandler::class, $config->getHttpClientHandler());
        $config->setHttpClientHandler(CurlHandler::class);
        $this->assertEquals(CurlHandler::class, $config->getHttpClientHandler());
    }

    /**
     * Уровень подробности
     */
    public function testVerbose(): void
    {
        $config = new Config();
        $this->assertEquals(ConfigInterface::VERBOSE_NORMAL, $config->getVerbose());
        $config->setVerbose(ConfigInterface::VERBOSE_DEBUG);
        $this->assertEquals(ConfigInterface::VERBOSE_DEBUG, $config->getVerbose());
    }

    /**
     * Исключение при ошибке значения уровня подробности
     */
    public function testVerboseInvalidArgumentLow(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $config = new Config();
        $config->setVerbose(-1);
    }

    /**
     * Исключение при ошибке значения уровня подробности
     */
    public function testVerboseInvalidArgumentHight(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $config = new Config();
        $config->setVerbose(100);
    }

    /**
     * Канал логирования
     */
    public function testLogChannel(): void
    {
        $config = new Config();
        $this->assertEquals('crawler', $config->getLogChannel());
        $config->setLogChannel('channel1');
        $this->assertEquals('channel1', $config->getLogChannel());
    }

    /**
     * Параметр определяющий через какое новое кол-во элементов сохранять элементы в хранилище
     */
    public function testSaveAfterQuantity(): void
    {
        $config = new Config();
        $this->assertEquals(10, $config->getSaveAfterQuantity());
        $config->setSaveAfterQuantity(20);
        $this->assertEquals(20, $config->getSaveAfterQuantity());
        $config->setSaveAfterQuantity(-1);
        $this->assertEquals(-1, $config->getSaveAfterQuantity());
    }

    /**
     * Параметр определяющий через какое новое кол-во элементов сохранять элементы в хранилище (исключение
     * при установке значения)
     */
    public function testSaveAfterQuantityException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $config = new Config();
        $config->setSaveAfterQuantity(-10);
    }
}
