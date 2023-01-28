<?php

declare(strict_types=1);

namespace Fi1a\Unit\Crawler;

use Fi1a\Crawler\Page;
use Fi1a\Http\Mime;
use Fi1a\Http\Uri;
use Fi1a\Http\UriInterface;
use Fi1a\Unit\Crawler\TestCases\TestCase;

/**
 * Страница
 */
class PageTest extends TestCase
{
    /**
     * Uri
     */
    public function testUri(): void
    {
        $page = new Page(new Uri($this->getUrl('/index.html')));

        $this->assertInstanceOf(UriInterface::class, $page->getUri());
    }

    /**
     * Код ответа
     */
    public function testStatusCode(): void
    {
        $page = new Page(new Uri($this->getUrl('/index.html')));

        $this->assertNull($page->getStatusCode());
        $page->setStatusCode(200);
        $this->assertEquals(200, $page->getStatusCode());
    }

    /**
     * Тело ответа
     */
    public function testBody(): void
    {
        $page = new Page(new Uri($this->getUrl('/index.html')));

        $this->assertNull($page->getBody());
        $page->setBody('body');
        $this->assertEquals('body', $page->getBody());
    }

    /**
     * Тип контента
     */
    public function testContentType(): void
    {
        $page = new Page(new Uri($this->getUrl('/index.html')));

        $this->assertNull($page->getContentType());
        $page->setContentType(Mime::HTML);
        $this->assertEquals(Mime::HTML, $page->getContentType());
    }

    /**
     * Преобразованное Uri
     */
    public function testConvertedUri(): void
    {
        $page = new Page(new Uri($this->getUrl('/index.html')));

        $this->assertNull($page->getConvertedUri());
        $page->setConvertedUri(new Uri('/index.html'));
        $this->assertInstanceOf(UriInterface::class, $page->getConvertedUri());
    }

    /**
     * Возвращает абсолютный uri
     */
    public function testAbsoluteUri(): void
    {
        $page = new Page(new Uri($this->getUrl('/index.html')));

        $this->assertEquals(
            'https://127.0.0.1:3000/some/path.html',
            $page->getAbsoluteUri(new Uri('/some/path.html'))->getUri()
        );
    }

    /**
     * Подготовленное тело ответа
     */
    public function testPrepareBody(): void
    {
        $page = new Page(new Uri($this->getUrl('/index.html')));

        $this->assertNull($page->getPrepareBody());
        $page->setPrepareBody('body');
        $this->assertEquals('body', $page->getPrepareBody());
    }
}
