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
}
