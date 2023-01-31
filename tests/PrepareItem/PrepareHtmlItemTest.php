<?php

declare(strict_types=1);

namespace Fi1a\Unit\Crawler\PrepareItem;

use Fi1a\Crawler\Item;
use Fi1a\Crawler\ItemCollection;
use Fi1a\Crawler\PrepareItem\PrepareHtmlItem;
use Fi1a\Http\Uri;
use Fi1a\Unit\Crawler\TestCases\TestCase;

/**
 * Подготавливает HTML элемент
 */
class PrepareHtmlItemTest extends TestCase
{
    /**
     * Подготавливает HTML элемент
     */
    public function testPrepare(): void
    {
        $prepare = new PrepareHtmlItem();

        $preparePage = new Item(new Uri($this->getUrl('/path/to/index.html')));

        $preparePage->setNewItemUri(new Uri('/path/to/index.html'));
        $preparePage->setBody(file_get_contents(__DIR__ . '/../Fixtures/Server/public/path/to/index.html'));

        $itemCollection = new ItemCollection();

        $item = new Item(new Uri($this->getUrl('/path/to/link1.html')));
        $item->setNewItemUri(new Uri('/path/to/link1.html'));
        $itemCollection[$item->getItemUri()->getUri()] = $item;

        $item = new Item(new Uri($this->getUrl('/path/link2.html')));
        $item->setNewItemUri(new Uri('/path/link2.html'));
        $itemCollection[$item->getItemUri()->getUri()] = $item;

        $item = new Item(new Uri($this->getUrl('/path/some/link3.html')));
        $item->setNewItemUri(new Uri('/path/some/link3.html'));
        $itemCollection[$item->getItemUri()->getUri()] = $item;

        $item = new Item(new Uri($this->getUrl('/new/link4.html')));
        $item->setNewItemUri(new Uri('/new/link4.html'));
        $itemCollection[$item->getItemUri()->getUri()] = $item;

        $this->assertEquals(
            file_get_contents(__DIR__ . '/../Fixtures/Server/equals/path/to/index.html'),
            $prepare->prepare($preparePage, $itemCollection)
        );
    }
}
