<?php

declare(strict_types=1);

namespace Fi1a\Unit\Crawler\Proxy\Selections;

use Fi1a\Crawler\Proxy\Selections\FilterByAttempts;
use Fi1a\Crawler\Proxy\Selections\SortedByTime;
use Fi1a\Unit\Crawler\TestCases\TestCase;
use InvalidArgumentException;

/**
 * Фильтрация прокси по числу ошибок соединения
 */
class FilterByAttemptsTest extends TestCase
{
    /**
     * Фильтрация прокси по числу ошибок соединения
     */
    public function testSelection(): void
    {
        $collection = $this->getProxyCollection();
        $selection = new FilterByAttempts(new SortedByTime(), 3);
        $collection = $selection->selection($collection, $this->getItem());
        $this->assertCount(8, $collection);
    }

    /**
     * Фильтрация прокси по числу ошибок соединения
     */
    public function testAttemptsException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new FilterByAttempts(new SortedByTime(), -1);
    }
}
