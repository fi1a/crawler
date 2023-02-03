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
        $itemCollection[$item->getItemUri()->uri()] = $item;

        $item = new Item(new Uri($this->getUrl('/path/link2.html')));
        $item->setNewItemUri(new Uri('/path/link2.html'));
        $itemCollection[$item->getItemUri()->uri()] = $item;

        $item = new Item(new Uri($this->getUrl('/path/some/link3.html')));
        $item->setNewItemUri(new Uri('/path/some/link3.html'));
        $itemCollection[$item->getItemUri()->uri()] = $item;

        $item = new Item(new Uri($this->getUrl('/new/link4.html')));
        $item->setNewItemUri(new Uri('/new/link4.html'));
        $itemCollection[$item->getItemUri()->uri()] = $item;

        $item = new Item(new Uri($this->getUrl('/path/to/Для теста.pdf')));
        $item->setNewItemUri(new Uri('/path/to/%D0%94%D0%BB%D1%8F%20%D1%82%D0%B5%D1%81%D1%82%D0%B0.pdf'));
        $itemCollection[$item->getItemUri()->uri()] = $item;

        $item = new Item(new Uri($this->getUrl('/path/to/files/Для теста.docx')));
        $item->setNewItemUri(
            new Uri('/path/to/files/%D0%94%D0%BB%D1%8F%20%D1%82%D0%B5%D1%81%D1%82%D0%B0.docx')
        );
        $itemCollection[$item->getItemUri()->uri()] = $item;

        $item = new Item(new Uri($this->getUrl('/path/to/for-test.pdf')));
        $item->setNewItemUri(new Uri('/path/to/for-test.pdf'));
        $itemCollection[$item->getItemUri()->uri()] = $item;

        $item = new Item(new Uri($this->getUrl('/path/to/files/for-test.docx')));
        $item->setNewItemUri(new Uri('/path/to/files/for-test.docx'));
        $itemCollection[$item->getItemUri()->uri()] = $item;

        $item = new Item(new Uri($this->getUrl('/path/to/images/for-test.jpeg')));
        $item->setNewItemUri(new Uri('/path/to/images/for-test.jpeg'));
        $itemCollection[$item->getItemUri()->uri()] = $item;

        $this->assertEquals(
            file_get_contents(__DIR__ . '/../Fixtures/Server/equals/path/to/index.html'),
            $prepare->prepare($preparePage, $itemCollection)
        );
    }
}
