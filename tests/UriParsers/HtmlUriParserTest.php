<?php

declare(strict_types=1);

namespace Fi1a\Unit\Crawler\UriParsers;

use Fi1a\Crawler\UriParsers\HtmlUriParser;
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
        $parser = new HtmlUriParser();
        $this->assertCount(
            4,
            $parser->parse(file_get_contents(__DIR__ . '/../Fixtures/Server/public/index.html'))
        );
    }
}
