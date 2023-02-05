<?php

declare(strict_types=1);

namespace Fi1a\Unit\Crawler\UriParsers;

use Fi1a\Crawler\Item;
use Fi1a\Crawler\UriParsers\HtmlUriParser;
use Fi1a\Http\Uri;
use Fi1a\Unit\Crawler\TestCases\TestCase;

/**
 * Парсит html и возвращает uri для обхода
 */
class HtmlUriParserTest extends TestCase
{
    /**
     * Парсит html и возвращает uri для обхода
     */
    public function testParse(): void
    {
        $item = new Item(new Uri('/index.html'), 0);

        $item->setBody(file_get_contents(__DIR__ . '/../Fixtures/Server/public/index.html'));

        $parser = new HtmlUriParser();
        $this->assertCount(
            5,
            $parser->parse($item, $this->getOutput(), $this->getLogger())
        );
    }
}
