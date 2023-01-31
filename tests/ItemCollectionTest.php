<?php

declare(strict_types=1);

namespace Fi1a\Unit\Crawler;

use Fi1a\Crawler\Item;
use Fi1a\Crawler\ItemCollection;
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
     * Коллекция элементов из JSON строки
     */
    public function testFromJson(): void
    {
        $collection = new ItemCollection();
        $collection->fromJson('[{"itemUri":"/path/uri1/"}, {"itemUri":"/path/uri2/"}]');
        $this->assertCount(2, $collection);
        $this->assertTrue($collection->has('/path/uri1/'));
        $this->assertTrue($collection->has('/path/uri2/'));
    }

    /**
     * Загруженные
     */
    public function testDownloaded(): void
    {
        $collection = new ItemCollection();
        $item = new Item(new Uri('/index.html'));
        $item->setDownloadSuccess(true);
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
        $item->setProcessSuccess(true);
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
        $item->setWriteSuccess(true);
        $collection[] = $item;
        $item = new Item(new Uri('/link1.html'));
        $collection[] = $item;
        $this->assertCount(1, $collection->getWrited());
    }
}
