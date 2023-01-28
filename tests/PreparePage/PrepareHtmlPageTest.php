<?php

declare(strict_types=1);

namespace Fi1a\Unit\Crawler\PreparePage;

use Fi1a\Crawler\Page;
use Fi1a\Crawler\PageCollection;
use Fi1a\Crawler\PreparePage\PrepareHtmlPage;
use Fi1a\Http\Uri;
use Fi1a\Unit\Crawler\TestCases\TestCase;

/**
 * Подготавливает HTML страницу
 */
class PrepareHtmlPageTest extends TestCase
{
    /**
     * Подготавливает HTML страницу
     */
    public function testPrepare(): void
    {
        $prepare = new PrepareHtmlPage();

        $pageCollection = new PageCollection();

        $page = new Page(new Uri($this->getUrl('/link1.html')));
        $pageCollection[] = $page;

        $page = new Page(new Uri($this->getUrl('/link2.html')));
        $pageCollection[] = $page;

        $page = new Page(new Uri($this->getUrl('/link3.html')));
        $pageCollection[] = $page;

        $this->assertEquals(
            file_get_contents(__DIR__ . '/../Fixtures/Server/equals/index.html'),
            $prepare->prepare($this->getPage(), $pageCollection)
        );
    }
}
