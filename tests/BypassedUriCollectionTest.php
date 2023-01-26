<?php

declare(strict_types=1);

namespace Fi1a\Unit\Crawler;

use Fi1a\Crawler\BypassedUri;
use Fi1a\Crawler\BypassedUriCollection;
use Fi1a\Http\Uri;
use Fi1a\Unit\Crawler\TestCases\TestCase;

/**
 * Коллекция обработанных адресов
 */
class BypassedUriCollectionTest extends TestCase
{
    /**
     * Коллекция обработанных адресов
     */
    public function testCollection(): void
    {
        $collection = new BypassedUriCollection();
        $bypassed = new BypassedUri();
        $bypassed->uri = new Uri('/index.html');
        $bypassed->statusCode = 200;
        $collection[] = $bypassed;
        $bypassed = new BypassedUri();
        $bypassed->uri = new Uri($this->getUrl('/link1.html'));
        $bypassed->statusCode = 200;
        $collection[] = $bypassed;
        $bypassed = new BypassedUri();
        $bypassed->uri = new Uri('/index.html');
        $bypassed->statusCode = 200;
        $collection[] = $bypassed;

        $this->assertCount(3, $collection);
    }
}
