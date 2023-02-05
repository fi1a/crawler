<?php

declare(strict_types=1);

namespace Fi1a\Unit\Crawler\ItemStorages\StorageAdapters;

use ErrorException;
use Fi1a\Crawler\Item;
use Fi1a\Crawler\ItemCollection;
use Fi1a\Crawler\ItemStorages\StorageAdapters\LocalFilesystemAdapter;
use Fi1a\Http\Uri;
use Fi1a\Unit\Crawler\TestCases\TestCase;

/**
 * Хранение элементов в файловой системе
 */
class LocalFilesystemAdapterTest extends TestCase
{
    /**
     * Сохранение и загрузка
     */
    public function testSaveAndLoad(): void
    {
        $adapter = new LocalFilesystemAdapter($this->runtimeFolder . '/storage');

        $this->deleteDir($this->runtimeFolder . '/storage');
        $this->assertTrue($adapter->clear());
        mkdir($this->runtimeFolder . '/storage');
        mkdir($this->runtimeFolder . '/storage/body');

        $collectionFromStorage = $adapter->load();
        $this->assertCount(0, $collectionFromStorage);

        $collection = new ItemCollection();
        $collection[] = new Item(new Uri('/index.html'));
        $collection[] = new Item(new Uri('/link1.html'));
        $this->assertCount(2, $collection);

        $adapter->save($collection);

        $collectionFromStorage = $adapter->load();
        $this->assertCount(2, $collectionFromStorage);

        $this->assertTrue($adapter->clear());

        $collectionFromStorage = $adapter->load();
        $this->assertCount(0, $collectionFromStorage);
    }

    /**
     * Сохранение тела ответа
     */
    public function testBody(): void
    {
        $adapter = new LocalFilesystemAdapter($this->runtimeFolder . '/storage');

        $item = new Item(new Uri('/index.html'));

        $this->assertFalse($adapter->getBody($item));
        $this->assertTrue($adapter->saveBody($item, 'body'));
        $this->assertEquals('body', $adapter->getBody($item));
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

    /**
     * Исключение при создании директории
     */
    public function testBodyDirNotWriteException(): void
    {
        $this->expectException(ErrorException::class);
        $this->deleteDir($this->runtimeFolder);
        mkdir($this->runtimeFolder . '/storage', 0777, true);
        mkdir($this->runtimeFolder . '/storage/body', 0000);
        try {
            new LocalFilesystemAdapter($this->runtimeFolder . '/storage');
        } catch (ErrorException $exception) {
            chmod($this->runtimeFolder . '/storage/body', 0777);

            throw $exception;
        }
    }
}
