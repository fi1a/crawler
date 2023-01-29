<?php

declare(strict_types=1);

namespace Fi1a\Unit\Crawler\UriConverters;

use Fi1a\Crawler\Item;
use Fi1a\Crawler\UriConverters\LocalUriConverter;
use Fi1a\Http\Uri;
use Fi1a\Unit\Crawler\TestCases\TestCase;

/**
 * Преобразует uri из внешних адресов в локальные
 */
class LocalUriConverterTest extends TestCase
{
    /**
     * Преобразует uri из внешних адресов в локальные
     */
    public function testConverter(): void
    {
        $converter = new LocalUriConverter();
        $item = new Item(new Uri($this->getUrl('/index.html')), 0);
        $this->assertEquals('/index.html', $converter->convert($item)->getUri());

        $item = new Item(new Uri($this->getUrl('/index.html?q=1')), 0);
        $this->assertEquals('/index.html?q=1', $converter->convert($item)->getUri());

        $item = new Item(new Uri('/path/index.html'), 0);
        $this->assertEquals('/path/index.html', $converter->convert($item)->getUri());
    }
}
