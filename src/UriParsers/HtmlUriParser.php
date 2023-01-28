<?php

declare(strict_types=1);

namespace Fi1a\Crawler\UriParsers;

use Fi1a\Crawler\PageInterface;
use Fi1a\Crawler\UriCollection;
use Fi1a\Crawler\UriCollectionInterface;
use Fi1a\Http\Uri;
use Fi1a\SimpleQuery\SimpleQuery;
use InvalidArgumentException;

/**
 * Парсит html и возвращает uri для обхода
 */
class HtmlUriParser implements UriParserInterface
{
    /**
     * @inheritDoc
     */
    public function parse(PageInterface $page): UriCollectionInterface
    {
        $collection = new UriCollection();

        $sq = new SimpleQuery((string) $page->getBody());
        $links = $sq('a');
        /** @var \DOMElement $link */
        foreach ($links as $link) {
            $href = (string) $sq($link)->attr('href');
            if (!$href) {
                continue;
            }
            try {
                $uri = new Uri($href);
            } catch (InvalidArgumentException $exception) {
                continue;
            }

            $collection[] = $uri;
        }

        return $collection;
    }
}
