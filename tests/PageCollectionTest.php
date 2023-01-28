<?php

declare(strict_types=1);

namespace Fi1a\Unit\Crawler;

use Fi1a\Crawler\Page;
use Fi1a\Crawler\PageCollection;
use Fi1a\Http\Uri;
use Fi1a\Unit\Crawler\TestCases\TestCase;

/**
 * Коллекция страниц
 */
class PageCollectionTest extends TestCase
{
    /**
     * Коллекция страниц
     */
    public function testPageCollection(): void
    {
        $collection = new PageCollection();
        $collection[] = new Page(new Uri('/index.html'));
        $collection[] = new Page(new Uri('/link1.html'));
        $this->assertCount(2, $collection);
    }
}
