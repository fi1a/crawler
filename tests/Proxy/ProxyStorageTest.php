<?php

declare(strict_types=1);

namespace Fi1a\Unit\Crawler\Proxy;

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
        $collection = $storage->load();
        $this->assertCount(0, $collection);
        $collection = $this->getProxyCollection();
        $this->assertCount(10, $collection);
        $storage->save($collection);
        $collection = $storage->load();
        $this->assertCount(10, $collection);
    }
}
