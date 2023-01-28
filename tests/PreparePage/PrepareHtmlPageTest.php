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

        $preparePage = new Page(new Uri($this->getUrl('/path/to/index.html')), 0);

        $preparePage->setConvertedUri(new Uri('/path/to/index.html'));
        $preparePage->setBody(file_get_contents(__DIR__ . '/../Fixtures/Server/public/path/to/index.html'));

        $pageCollection = new PageCollection();

        $page = new Page(new Uri($this->getUrl('/path/to/link1.html')), 0);
        $pageCollection[] = $page;

        $page = new Page(new Uri($this->getUrl('/path/link2.html')), 0);
        $pageCollection[] = $page;

        $page = new Page(new Uri($this->getUrl('/path/some/link3.html')), 0);
        $pageCollection[] = $page;

        $page = new Page(new Uri($this->getUrl('/new/link4.html')), 0);
        $pageCollection[] = $page;

        $this->assertEquals(
            file_get_contents(__DIR__ . '/../Fixtures/Server/equals/path/to/index.html'),
            $prepare->prepare($preparePage, $pageCollection)
        );
    }
}
