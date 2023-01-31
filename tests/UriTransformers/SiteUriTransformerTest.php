<?php

declare(strict_types=1);

namespace Fi1a\Unit\Crawler\UriTransformers;

use Fi1a\Crawler\Item;
use Fi1a\Crawler\UriTransformers\SiteUriTransformer;
use Fi1a\Http\Uri;
use Fi1a\Unit\Crawler\TestCases\TestCase;

/**
 * Преобразует uri из внешних адресов в локальные
 */
class SiteUriTransformerTest extends TestCase
{
    /**
     * Преобразует uri из внешних адресов в локальные
     */
    public function testTransformer(): void
    {
        $converter = new SiteUriTransformer();
        $item = new Item(new Uri($this->getUrl('/index.html')));
        $item->setAllow(true);
        $this->assertEquals('/index.html', $converter->transform($item)->getUri());

        $item = new Item(new Uri($this->getUrl('/index.html?q=1')));
        $item->setAllow(true);
        $this->assertEquals('/index.html?q=1', $converter->transform($item)->getUri());

        $item = new Item(new Uri('/path/index.html'));
        $item->setAllow(true);
        $this->assertEquals('/path/index.html', $converter->transform($item)->getUri());
    }

    /**
     * Преобразует uri из внешних адресов в локальные (не разрешенные к обходу адреса)
     */
    public function testTransformerNotAllow(): void
    {
        $converter = new SiteUriTransformer();
        $item = new Item(new Uri($this->getUrl('/index.html')));
        $item->setAllow(false);
        $this->assertEquals($item->getItemUri()->getUri(), $converter->transform($item)->getUri());
    }

    /**
     * Преобразует uri из внешних адресов в локальные (префикс)
     */
    public function testTransformerPrefix(): void
    {
        $converter = new SiteUriTransformer('https://user:pass@' . self::HOST . '/prefix');
        $item = new Item(new Uri($this->getUrl('/index.html')));
        $item->setAllow(true);
        $this->assertEquals(
            'https://user:pass@' . self::HOST . '/prefix/index.html',
            $converter->transform($item)->getUri()
        );
    }
}
