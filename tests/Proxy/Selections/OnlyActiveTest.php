<?php

declare(strict_types=1);

namespace Fi1a\Unit\Crawler\Proxy\Selections;

use Fi1a\Crawler\Proxy\Selections\OnlyActive;
use Fi1a\Crawler\Proxy\Selections\SortedByTime;
use Fi1a\Unit\Crawler\TestCases\TestCase;

/**
 * Фильтрация прокси по активности
 */
class OnlyActiveTest extends TestCase
{
    /**
     * Фильтрация прокси по активности
     */
    public function testSelection(): void
    {
        $collection = $this->getProxyCollection();
        $selection = new OnlyActive(new SortedByTime());
        $collection = $selection->selection($collection, $this->getItem());
        $this->assertCount(8, $collection);
    }
}
