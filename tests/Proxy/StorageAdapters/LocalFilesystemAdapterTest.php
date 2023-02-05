<?php

declare(strict_types=1);

namespace Fi1a\Unit\Crawler\Proxy\StorageAdapters;

use ErrorException;
use Fi1a\Crawler\Proxy\ProxyInterface;
use Fi1a\Crawler\Proxy\StorageAdapters\LocalFilesystemAdapter;
use Fi1a\Unit\Crawler\TestCases\TestCase;

/**
 * Адаптер хранилища в файловой системе
 */
class LocalFilesystemAdapterTest extends TestCase
{
    /**
     * Сохранение и загрузка прокси из хранилища
     */
    public function testSaveAndLoad(): void
    {
        $adapter = new LocalFilesystemAdapter($this->runtimeFolder);

        $collection = $this->getProxyCollection();
        $this->assertCount(10, $collection);
        foreach ($collection as $proxy) {
            $adapter->save($proxy);
        }
        $collection = $adapter->load();
        $this->assertCount(10, $collection);
        /** @var ProxyInterface $proxy */
        $proxy = $collection[0];
        $proxy->setAttempts(10);
        $adapter->save($proxy);

        $collection = $adapter->load();
        $this->assertCount(10, $collection);
        /** @var ProxyInterface $proxy */
        $proxy = $collection[0];
        $this->assertEquals(10, $proxy->getAttempts());
    }

    /**
     * Исключение при создании директории
     */
    public function testDirNotCreateException(): void
    {
        $this->expectException(ErrorException::class);
        $this->deleteDir($this->runtimeFolder);
        mkdir($this->runtimeFolder, 0000, true);
        try {
            new LocalFilesystemAdapter($this->runtimeFolder . '/storage');
        } catch (ErrorException $exception) {
            chmod($this->runtimeFolder, 0777);

            throw $exception;
        }
    }

    /**
     * Исключение при создании директории
     */
    public function testNotWriteException(): void
    {
        $this->expectException(ErrorException::class);
        $this->deleteDir($this->runtimeFolder);
        mkdir($this->runtimeFolder, 0777, true);
        mkdir($this->runtimeFolder . '/storage', 0000);
        try {
            new LocalFilesystemAdapter($this->runtimeFolder . '/storage');
        } catch (ErrorException $exception) {
            chmod($this->runtimeFolder . '/storage', 0777);

            throw $exception;
        }
    }
}
