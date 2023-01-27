<?php

declare(strict_types=1);

namespace Fi1a\Unit\Crawler;

use Fi1a\Crawler\Page;
use Fi1a\Crawler\PageInterface;
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
     * Возвращает страницу
     */
    protected function getPage(): PageInterface
    {
        return new Page(new Uri('/index.html'));
    }

    /**
     * Uri
     */
    public function testUri(): void
    {
        $page = $this->getPage();
        $this->assertInstanceOf(UriInterface::class, $page->getUri());
    }

    /**
     * Код ответа
     */
    public function testStatusCode(): void
    {
        $page = $this->getPage();
        $this->assertNull($page->getStatusCode());
        $page->setStatusCode(200);
        $this->assertEquals(200, $page->getStatusCode());
    }

    /**
     * Тело ответа
     */
    public function testBody(): void
    {
        $page = $this->getPage();
        $this->assertNull($page->getBody());
        $page->setBody('body');
        $this->assertEquals('body', $page->getBody());
    }

    /**
     * Тип контента
     */
    public function testContentType(): void
    {
        $page = $this->getPage();
        $this->assertNull($page->getContentType());
        $page->setContentType(Mime::HTML);
        $this->assertEquals(Mime::HTML, $page->getContentType());
    }
}
