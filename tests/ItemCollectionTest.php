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
        $collection[] = new Item(new Uri('/index.html'), 0);
        $collection[] = new Item(new Uri('/link1.html'), 1);
        $this->assertCount(2, $collection);
    }
}
