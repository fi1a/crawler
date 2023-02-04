<?php

declare(strict_types=1);

namespace Fi1a\Unit\Crawler\Proxy;

use Fi1a\Crawler\Proxy\ProxyInterface;
use Fi1a\Unit\Crawler\TestCases\TestCase;

/**
 * Хранилище прокси
 */
class ProxyStorageTest extends TestCase
{
    /**
     * Сохранение и загрузка прокси из хранилища
     */
    public function testSaveAndLoad(): void
    {
        $storage = $this->getProxyStorage();

        $collection = $this->getProxyCollection();
        $this->assertCount(10, $collection);
        foreach ($collection as $proxy) {
            $storage->save($proxy);
        }
        $collection = $storage->load();
        $this->assertCount(10, $collection);
        /** @var ProxyInterface $proxy */
        $proxy = $collection[0];
        $proxy->setAttempts(10);
        $storage->save($proxy);

        $collection = $storage->load();
        $this->assertCount(10, $collection);
        /** @var ProxyInterface $proxy */
        $proxy = $collection[0];
        $this->assertEquals(10, $proxy->getAttempts());
    }
}
