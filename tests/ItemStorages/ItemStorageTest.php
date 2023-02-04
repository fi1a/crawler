<?php

declare(strict_types=1);

namespace Fi1a\Unit\Crawler\ItemStorages;

use ErrorException;
use Fi1a\Crawler\Item;
use Fi1a\Crawler\ItemCollection;
use Fi1a\Crawler\ItemInterface;
use Fi1a\Crawler\ItemStorages\FilesystemAdapter;
use Fi1a\Crawler\ItemStorages\ItemStorage;
use Fi1a\Crawler\ItemStorages\ItemStorageInterface;
use Fi1a\Http\Uri;
use Fi1a\Unit\Crawler\TestCases\TestCase;

/**
 * Хранение элементов
 */
class ItemStorageTest extends TestCase
{
    /**
     * Возвращает экземпляр класса хранилища
     */
    protected function getStorage(): ItemStorageInterface
    {
        return new ItemStorage(new FilesystemAdapter($this->runtimeFolder . '/storage'));
    }

    /**
     * Сохранение и загрузка
     */
    public function testSaveAndLoad(): void
    {
        $storage = $this->getStorage();

        $this->deleteDir($this->runtimeFolder . '/storage');
        $this->assertTrue($storage->clear());
        mkdir($this->runtimeFolder . '/storage');
        mkdir($this->runtimeFolder . '/storage/body');

        $collectionFromStorage = $storage->load();
        $this->assertCount(0, $collectionFromStorage);

        $collection = new ItemCollection();
        $collection[] = new Item(new Uri('/index.html'));
        $collection[] = new Item(new Uri('/link1.html'));
        $this->assertCount(2, $collection);

        $storage->save($collection);

        $collectionFromStorage = $storage->load();
        $this->assertCount(2, $collectionFromStorage);

        $this->assertTrue($storage->clear());

        $collectionFromStorage = $storage->load();
        $this->assertCount(0, $collectionFromStorage);
    }

    /**
     * Сохранение тела ответа
     */
    public function testBody(): void
    {
        $storage = $this->getStorage();

        $item = new Item(new Uri('/index.html'));

        $this->assertFalse($storage->getBody($item));
        $this->assertTrue($storage->saveBody($item, 'body'));
        $this->assertEquals('body', $storage->getBody($item));
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
            new FilesystemAdapter($this->runtimeFolder . '/storage');
        } catch (ErrorException $exception) {
            chmod($this->runtimeFolder . '/storage/body', 0777);

            throw $exception;
        }
    }

    /**
     * Время жизни для загружаемых элементов
     */
    public function testLifetime(): void
    {
        $storage = $this->getStorage();

        $collection = new ItemCollection();
        $item = new Item(new Uri('/index.html'));
        $item->setDownloadStatus(true);
        $item->expiresAfter(1);
        $collection[] = $item;
        $item = new Item(new Uri('/link1.html'));
        $collection[] = $item;
        $item->setDownloadStatus(true);
        $this->assertCount(2, $collection);

        $storage->save($collection);

        sleep(2);

        $collection = $storage->load();
        $this->assertCount(2, $collection);
        /** @var ItemInterface $item */
        $item = $collection['/index.html'];
        $this->assertNull($item->getDownloadStatus());
        /** @var ItemInterface $item */
        $item = $collection['/link1.html'];
        $this->assertTrue($item->getDownloadStatus());
    }
}
