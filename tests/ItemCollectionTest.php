<?php

declare(strict_types=1);

namespace Fi1a\Unit\Crawler;

use Fi1a\Crawler\Item;
use Fi1a\Crawler\ItemCollection;
use Fi1a\Http\Mime;
use Fi1a\Http\Uri;
use Fi1a\Unit\Crawler\TestCases\TestCase;

/**
 * Коллекция элементов
 */
class ItemCollectionTest extends TestCase
{
    /**
     * Коллекция элементов
     */
    public function testItemCollection(): void
    {
        $collection = new ItemCollection();
        $collection[] = new Item(new Uri('/index.html'));
        $collection[] = new Item(new Uri('/link1.html'));
        $this->assertCount(2, $collection);
    }

    /**
     * Загруженные
     */
    public function testDownloaded(): void
    {
        $collection = new ItemCollection();
        $item = new Item(new Uri('/index.html'));
        $item->setDownloadStatus(true);
        $collection[] = $item;
        $item = new Item(new Uri('/link1.html'));
        $collection[] = $item;
        $this->assertCount(1, $collection->getDownloaded());
    }

    /**
     * обработанные
     */
    public function testProcessed(): void
    {
        $collection = new ItemCollection();
        $item = new Item(new Uri('/index.html'));
        $item->setProcessStatus(true);
        $collection[] = $item;
        $item = new Item(new Uri('/link1.html'));
        $collection[] = $item;
        $this->assertCount(1, $collection->getProcessed());
    }

    /**
     * Записанные
     */
    public function testWrited(): void
    {
        $collection = new ItemCollection();
        $item = new Item(new Uri('/index.html'));
        $item->setWriteStatus(true);
        $collection[] = $item;
        $item = new Item(new Uri('/link1.html'));
        $collection[] = $item;
        $this->assertCount(1, $collection->getWrited());
    }

    /**
     * Изображения
     */
    public function testImages(): void
    {
        $collection = new ItemCollection();
        $item = new Item(new Uri('/image.gif'));
        $item->setContentType('image/gif');
        $collection[] = $item;
        $collection[] = new Item(new Uri('/link1.html'));
        $this->assertCount(2, $collection);
        $this->assertCount(1, $collection->getImages());
    }

    /**
     * Страницы
     */
    public function testPages(): void
    {
        $collection = new ItemCollection();
        $item = new Item(new Uri('/index.html'));
        $item->setContentType(Mime::HTML);
        $collection[] = $item;
        $collection[] = new Item(new Uri('/link1.html'));
        $this->assertCount(2, $collection);
        $this->assertCount(1, $collection->getPages());
    }

    /**
     * CSS файлы
     */
    public function testCss(): void
    {
        $collection = new ItemCollection();
        $item = new Item(new Uri('/style.css'));
        $collection[] = $item;
        $collection[] = new Item(new Uri('/link1.html'));
        $this->assertCount(2, $collection);
        $this->assertCount(1, $collection->getCss());
    }

    /**
     * JS файлы
     */
    public function testJs(): void
    {
        $collection = new ItemCollection();
        $item = new Item(new Uri('/script.js'));
        $item->setContentType('application/javascript');
        $collection[] = $item;
        $collection[] = new Item(new Uri('/link1.html'));
        $this->assertCount(2, $collection);
        $this->assertCount(1, $collection->getJs());
    }

    /**
     * Файлы
     */
    public function testFiles(): void
    {
        $collection = new ItemCollection();
        $item = new Item(new Uri('/script.js'));
        $item->setContentType('application/javascript');
        $collection[] = $item;
        $item = new Item(new Uri('/style.css'));
        $collection[] = $item;
        $item = new Item(new Uri('/file.docx'));
        $item->setContentType('application/vnd.openxmlformats-officedocument.wordprocessingml.document');
        $collection[] = $item;
        $this->assertCount(3, $collection);
        $this->assertCount(1, $collection->getFiles());
    }
}
