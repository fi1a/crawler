<?php

declare(strict_types=1);

namespace Fi1a\Unit\Crawler\Proxy;

use Fi1a\Crawler\Proxy\Proxy;
use Fi1a\Crawler\Proxy\ProxyCollection;
use Fi1a\Unit\Crawler\TestCases\TestCase;
use InvalidArgumentException;

/**
 * Коллекция прокси
 */
class ProxyCollectionTest extends TestCase
{
    /**
     * Коллекция прокси
     */
    public function testCollection(): void
    {
        $collection = new ProxyCollection();
        $collection[] = static::$httpProxy;
        $collection[] = static::$socks5Proxy;
        $collection[] = Proxy::factory(static::$httpProxy);
        $this->assertCount(3, $collection);
    }

    /**
     * Исключение при создании прокси в коллекции
     */
    public function testFactoryException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $collection = new ProxyCollection();
        $collection[] = 1;
    }
}
