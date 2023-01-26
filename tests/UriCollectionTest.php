<?php

declare(strict_types=1);

namespace Fi1a\Unit\Crawler;

use Fi1a\Crawler\UriCollection;
use Fi1a\Http\Uri;
use Fi1a\Unit\Crawler\TestCases\TestCase;

/**
 * Коллекция адресов
 */
class UriCollectionTest extends TestCase
{
    /**
     * Коллекция адресов
     */
    public function testCollection(): void
    {
        $collection = new UriCollection();
        $collection[] = '/index.html';
        $collection[] = $this->getUrl('/link1.html');
        $collection[] = new Uri('/index.html');

        $this->assertCount(3, $collection);
    }
}
