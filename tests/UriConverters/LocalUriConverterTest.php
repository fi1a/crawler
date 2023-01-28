<?php

declare(strict_types=1);

namespace Fi1a\Unit\Crawler\UriConverters;

use Fi1a\Crawler\Page;
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
        $page = new Page(new Uri($this->getUrl('/index.html')));
        $this->assertEquals('/index.html', $converter->convert($page)->getUri());

        $page = new Page(new Uri($this->getUrl('/index.html?q=1')));
        $this->assertEquals('/index.html?q=1', $converter->convert($page)->getUri());

        $page = new Page(new Uri('/path/index.html'));
        $this->assertEquals('/path/index.html', $converter->convert($page)->getUri());
    }
}
