<?php

declare(strict_types=1);

namespace Fi1a\Unit\Crawler\Proxy\Selections;

use Fi1a\Crawler\Proxy\Selections\Limit;
use Fi1a\Crawler\Proxy\Selections\SortedByTime;
use Fi1a\Unit\Crawler\TestCases\TestCase;
use InvalidArgumentException;

/**
 * Ограничение на кол-во подобранных прокси
 */
class LimitTest extends TestCase
{
    /**
     * Ограничение на кол-во подобранных прокси
     */
    public function testSelection(): void
    {
        $collection = $this->getProxyCollection();
        $selection = new Limit(new SortedByTime(), 3);
        $collection = $selection->selection($collection, $this->getItem());
        $this->assertCount(3, $collection);
    }

    /**
     * Исключение при значении лимита меньше или равно 0
     */
    public function testSelectionException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new Limit(null, -1);
    }
}
