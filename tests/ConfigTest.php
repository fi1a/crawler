<?php

declare(strict_types=1);

namespace Fi1a\Unit\Crawler;

use Fi1a\Crawler\Config;
use Fi1a\Crawler\ConfigInterface;
use Fi1a\Http\Mime;
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
        $config->setSaveAfterQuantity(0);
        $this->assertEquals(0, $config->getSaveAfterQuantity());
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

    /**
     * Параметр время жизни элементов в хранилище
     */
    public function testLifetime(): void
    {
        $config = new Config();
        $this->assertEquals(24 * 60 * 60, $config->getLifetime());
        $config->setLifetime(2 * 24 * 60 * 60);
        $this->assertEquals(2 * 24 * 60 * 60, $config->getLifetime());
        $config->setLifetime(0);
        $this->assertEquals(0, $config->getLifetime());
    }

    /**
     * Параметр время жизни элементов в хранилище (исключение при установке значения)
     */
    public function testLifetimeException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $config = new Config();
        $config->setLifetime(-10);
    }

    /**
     * Задержка между запросами
     */
    public function testDelay(): void
    {
        $config = new Config();
        $this->assertEquals([0, 0], $config->getDelay());
        $config->setDelay(1);
        $this->assertEquals([1, 1], $config->getDelay());
        $config->setDelay([1, 2]);
        $this->assertEquals([1, 2], $config->getDelay());
    }

    /**
     * Задержка между запросами
     */
    public function testDelayStringDelayException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $config = new Config();
        $config->setDelay('abc');
    }

    /**
     * Задержка между запросами
     */
    public function testDelayArrayDelayException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $config = new Config();
        $config->setDelay([1, 2, 3]);
    }

    /**
     * Ограничение на загружаемый файл по типу контента
     */
    public function testSizeLimit(): void
    {
        $config = new Config();
        $this->assertEquals([], $config->getSizeLimits());
        $config->setSizeLimit(1);
        $config->setSizeLimit(2, Mime::HTML);
        $this->assertEquals(['*' => 1, Mime::HTML => 2], $config->getSizeLimits());
    }

    /**
     * Ограничение на загружаемый файл по типу контента
     */
    public function testSizeLimitAsText(): void
    {
        $config = new Config();
        $this->assertEquals([], $config->getSizeLimits());
        $config->setSizeLimit('1B');
        $this->assertEquals(['*' => 1,], $config->getSizeLimits());
        $config->setSizeLimit('1KB');
        $this->assertEquals(['*' => 1024,], $config->getSizeLimits());
        $config->setSizeLimit('1K');
        $this->assertEquals(['*' => 1024,], $config->getSizeLimits());
        $config->setSizeLimit('1MB');
        $this->assertEquals(['*' => pow(1024, 2),], $config->getSizeLimits());
        $config->setSizeLimit('1M');
        $this->assertEquals(['*' => pow(1024, 2),], $config->getSizeLimits());
        $config->setSizeLimit('1GB');
        $this->assertEquals(['*' => pow(1024, 3),], $config->getSizeLimits());
        $config->setSizeLimit('1G');
        $this->assertEquals(['*' => pow(1024, 3),], $config->getSizeLimits());
        $config->setSizeLimit('1TB');
        $this->assertEquals(['*' => pow(1024, 4),], $config->getSizeLimits());
        $config->setSizeLimit('1T');
        $this->assertEquals(['*' => pow(1024, 4),], $config->getSizeLimits());
        $config->setSizeLimit('1PB');
        $this->assertEquals(['*' => pow(1024, 5),], $config->getSizeLimits());
        $config->setSizeLimit('1P');
        $this->assertEquals(['*' => pow(1024, 5),], $config->getSizeLimits());
    }

    /**
     * Ограничение на загружаемый файл по типу контента
     */
    public function testSizeLimitAsTextException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $config = new Config();
        $config->setSizeLimit('1U');
    }

    /**
     * Кол-во попыток запросов к адресу при http ошибки
     */
    public function testRetry(): void
    {
        $config = new Config();
        $this->assertEquals(3, $config->getRetry());
        $config->setRetry(10);
        $this->assertEquals(10, $config->getRetry());
        $config->setRetry(0);
        $this->assertEquals(0, $config->getRetry());
    }

    /**
     * Кол-во попыток запросов к адресу при http ошибки (исключение при отрицательном значении)
     */
    public function testRetryException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $config = new Config();
        $config->setRetry(-1);
    }
}
