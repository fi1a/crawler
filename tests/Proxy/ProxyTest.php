<?php

declare(strict_types=1);

namespace Fi1a\Unit\Crawler\Proxy;

use Fi1a\Crawler\Proxy\Proxy;
use Fi1a\Crawler\Proxy\ProxyInterface;
use Fi1a\Unit\Crawler\TestCases\TestCase;
use InvalidArgumentException;

/**
 * Прокси
 */
class ProxyTest extends TestCase
{
    /**
     * @var array<array-key, mixed>
     */
    protected static $httpProxy = [
        'type' => 'http',
        'host' => HTTP_PROXY_HOST,
        'port' => HTTP_PROXY_PORT,
        'userName' => HTTP_PROXY_USERNAME,
        'password' => HTTP_PROXY_PASSWORD,
        'attempts' => 0,
        'active' => true,
    ];

    /**
     * @var array<array-key, mixed>
     */
    protected static $socks5Proxy = [
        'type' => 'socks5',
        'host' => SOCKS5_PROXY_HOST,
        'port' => SOCKS5_PROXY_PORT,
        'userName' => SOCKS5_PROXY_USERNAME,
        'password' => SOCKS5_PROXY_PASSWORD,
        'attempts' => 0,
        'active' => true,
    ];

    /**
     * Провайдер данных полей прокси
     *
     * @return array<array-key, array<array-key, mixed>>
     */
    public function proxyDataProvider(): array
    {
        return [
            [static::$httpProxy,],
            [static::$socks5Proxy,],
        ];
    }

    /**
     * Фабричный метод
     *
     * @param array<array-key, mixed> $proxyFields
     *
     * @dataProvider proxyDataProvider
     */
    public function testFactory(array $proxyFields): void
    {
        $proxy = Proxy::factory($proxyFields);
        $this->assertInstanceOf(ProxyInterface::class, $proxy);
    }

    /**
     * Исключение при пустом типе прокси
     */
    public function testFactoryTypeException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        Proxy::factory([]);
    }

    /**
     * Исключение при пустом типе прокси
     */
    public function testFactoryUnknownTypeException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        Proxy::factory([
            'type' => 'unknown',
        ]);
    }

    /**
     * Преобразование в массив
     *
     * @param array<array-key, mixed> $proxyFields
     *
     * @dataProvider proxyDataProvider
     */
    public function testToArray(array $proxyFields): void
    {
        $proxy = Proxy::factory($proxyFields);
        $this->assertInstanceOf(ProxyInterface::class, $proxy);
        $this->assertEquals($proxyFields, $proxy->toArray());
    }

    /**
     * Хост
     */
    public function testHost(): void
    {
        $proxy = Proxy::factory(static::$httpProxy);
        $this->assertEquals(static::$httpProxy['host'], $proxy->getHost());
        $proxy->setHost('localhost');
        $this->assertEquals('localhost', $proxy->getHost());
    }

    /**
     * Порт
     */
    public function testPort(): void
    {
        $proxy = Proxy::factory(static::$httpProxy);
        $this->assertEquals(static::$httpProxy['port'], $proxy->getPort());
        $proxy->setPort(100);
        $this->assertEquals(100, $proxy->getPort());
    }

    /**
     * Пользователь
     */
    public function testUserName(): void
    {
        $proxy = Proxy::factory(static::$httpProxy);
        $this->assertEquals(static::$httpProxy['userName'], $proxy->getUserName());
        $proxy->setUserName('test');
        $this->assertEquals('test', $proxy->getUserName());
    }

    /**
     * Пароль
     */
    public function testPassword(): void
    {
        $proxy = Proxy::factory(static::$httpProxy);
        $this->assertEquals(static::$httpProxy['password'], $proxy->getPassword());
        $proxy->setPassword('password');
        $this->assertEquals('password', $proxy->getPassword());
    }

    /**
     * Число попыток с ошибкой
     */
    public function testAttempts(): void
    {
        $proxy = Proxy::factory(static::$httpProxy);
        $this->assertEquals(0, $proxy->getAttempts());
        $proxy->setAttempts(1);
        $this->assertEquals(1, $proxy->getAttempts());
    }

    /**
     * Увеличить число попыток с ошибкой на 1
     */
    public function testAttemptsIncrement(): void
    {
        $proxy = Proxy::factory(static::$httpProxy);
        $this->assertEquals(0, $proxy->getAttempts());
        $proxy->incrementAttempts();
        $this->assertEquals(1, $proxy->getAttempts());
    }

    /**
     * Сбросить число попыток с ошибкой
     */
    public function testResetAttempts(): void
    {
        $proxy = Proxy::factory(static::$httpProxy);
        $this->assertEquals(0, $proxy->getAttempts());
        $proxy->setAttempts(1);
        $this->assertEquals(1, $proxy->getAttempts());
        $proxy->resetAttempts();
        $this->assertEquals(0, $proxy->getAttempts());
    }

    /**
     * Сбросить число попыток с ошибкой
     */
    public function testActive(): void
    {
        $proxy = Proxy::factory(static::$httpProxy);
        $this->assertTrue($proxy->isActive());
        $proxy->setActive(false);
        $this->assertFalse($proxy->isActive());
    }
}
