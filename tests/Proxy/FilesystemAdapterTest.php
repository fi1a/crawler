<?php

declare(strict_types=1);

namespace Fi1a\Unit\Crawler\Proxy;

use ErrorException;
use Fi1a\Crawler\Proxy\FilesystemAdapter;
use Fi1a\Unit\Crawler\TestCases\TestCase;

/**
 * Адаптер хранилища в файловой системе
 */
class FilesystemAdapterTest extends TestCase
{
    /**
     * Сохранение и загрузка прокси из хранилища
     */
    public function testSaveAndLoad(): void
    {
        $adapter = new FilesystemAdapter($this->runtimeFolder);

        $collection = $adapter->load();
        $this->assertCount(0, $collection);
        $collection = $this->getProxyCollection();
        $this->assertCount(10, $collection);
        $adapter->save($collection);
        $collection = $adapter->load();
        $this->assertCount(10, $collection);
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
            new FilesystemAdapter($this->runtimeFolder . '/storage');
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
            new FilesystemAdapter($this->runtimeFolder . '/storage');
        } catch (ErrorException $exception) {
            chmod($this->runtimeFolder . '/storage', 0777);

            throw $exception;
        }
    }
}
