<?php

declare(strict_types=1);

namespace Fi1a\Unit\Crawler;

use Fi1a\Crawler\Item;
use Fi1a\Http\Mime;
use Fi1a\Http\Uri;
use Fi1a\Http\UriInterface;
use Fi1a\Unit\Crawler\TestCases\TestCase;

/**
 * Элементы
 */
class ItemTest extends TestCase
{
    /**
     * Uri
     */
    public function testUri(): void
    {
        $item = new Item(new Uri($this->getUrl('/index.html')), 0);

        $this->assertInstanceOf(UriInterface::class, $item->getUri());
    }

    /**
     * Код ответа
     */
    public function testStatusCode(): void
    {
        $item = new Item(new Uri($this->getUrl('/index.html')), 0);

        $this->assertNull($item->getStatusCode());
        $item->setStatusCode(200);
        $this->assertEquals(200, $item->getStatusCode());
    }

    /**
     * Тело ответа
     */
    public function testBody(): void
    {
        $item = new Item(new Uri($this->getUrl('/index.html')), 0);

        $this->assertNull($item->getBody());
        $item->setBody('body');
        $this->assertEquals('body', $item->getBody());
    }

    /**
     * Тип контента
     */
    public function testContentType(): void
    {
        $item = new Item(new Uri($this->getUrl('/index.html')), 0);

        $this->assertNull($item->getContentType());
        $item->setContentType(Mime::HTML);
        $this->assertEquals(Mime::HTML, $item->getContentType());
    }

    /**
     * Преобразованное Uri
     */
    public function testConvertedUri(): void
    {
        $item = new Item(new Uri($this->getUrl('/index.html')), 0);

        $this->assertNull($item->getConvertedUri());
        $item->setConvertedUri(new Uri('/index.html'));
        $this->assertInstanceOf(UriInterface::class, $item->getConvertedUri());
    }

    /**
     * Возвращает абсолютный uri
     */
    public function testAbsoluteUri(): void
    {
        $item = new Item(new Uri($this->getUrl('/index.html')), 0);

        $this->assertEquals(
            'https://127.0.0.1:3000/some/path.html',
            $item->getAbsoluteUri(new Uri('/some/path.html'))->getUri()
        );
    }

    /**
     * Возвращает абсолютный uri
     */
    public function testAbsoluteUriFromRelative(): void
    {
        $item = new Item(new Uri($this->getUrl('/some/path/index.php')), 0);

        $this->assertEquals(
            'https://127.0.0.1:3000/some/path.html',
            $item->getAbsoluteUri(new Uri('../path.html'))->getUri()
        );

        $this->assertEquals(
            'https://127.0.0.1:3000/path.html',
            $item->getAbsoluteUri(new Uri('../../path.html'))->getUri()
        );
    }

    /**
     * Создание относительного uri
     */
    public function testRelativeUri(): void
    {
        $item = new Item(new Uri($this->getUrl('/path/to/index.html')), 0);

        $this->assertEquals(
            'index.html',
            $item->getRelativeUri(new Uri('/path/to/index.html'))->getUri()
        );

        $this->assertEquals(
            '../path.html',
            $item->getRelativeUri(new Uri('/path/path.html'))->getUri()
        );

        $this->assertEquals(
            '../some/path.html',
            $item->getRelativeUri(new Uri('/path/some/path.html'))->getUri()
        );

        $this->assertEquals(
            '../../new/path.html',
            $item->getRelativeUri(new Uri('/new/path.html'))->getUri()
        );

        $this->assertEquals(
            '../../new/path.html',
            $item->getRelativeUri(new Uri('../../new/path.html'))->getUri()
        );
    }

    /**
     * Подготовленное тело ответа
     */
    public function testPrepareBody(): void
    {
        $item = new Item(new Uri($this->getUrl('/index.html')), 0);

        $this->assertNull($item->getPrepareBody());
        $item->setPrepareBody('body');
        $this->assertEquals('body', $item->getPrepareBody());
    }

    /**
     * Место в очереди
     */
    public function testIndex(): void
    {
        $item = new Item(new Uri($this->getUrl('/index.html')), 1);

        $this->assertEquals(1, $item->getIndex());
    }
}
