<?php

declare(strict_types=1);

namespace Fi1a\Crawler\UriParsers;

use Fi1a\Crawler\ItemInterface;
use Fi1a\Crawler\UriCollection;
use Fi1a\Crawler\UriCollectionInterface;
use Fi1a\Http\Uri;
use Fi1a\SimpleQuery\SimpleQuery;
use Fi1a\SimpleQuery\SimpleQueryInterface;
use InvalidArgumentException;

/**
 * Парсит html и возвращает uri для обхода
 */
class HtmlUriParser implements UriParserInterface
{
    /**
     * @inheritDoc
     */
    public function parse(ItemInterface $item): UriCollectionInterface
    {
        $sq = new SimpleQuery((string) $item->getBody());
        $collection = $this->parseLinks($sq);
        $collection->exchangeArray(
            array_merge($collection->getArrayCopy(), $this->parseImages($sq)->getArrayCopy())
        );
        $collection->exchangeArray(
            array_merge($collection->getArrayCopy(), $this->parseCss($sq)->getArrayCopy())
        );

        return $collection;
    }

    /**
     * Парсит ссылки
     */
    protected function parseLinks(SimpleQueryInterface $sq): UriCollectionInterface
    {
        $collection = new UriCollection();

        $links = $sq('a');
        /** @var \DOMElement $link */
        foreach ($links as $link) {
            $href = $sq($link)->attr('href');
            if (!is_string($href) || !$href) {
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

    /**
     * Парсит изображения
     */
    protected function parseImages(SimpleQueryInterface $sq): UriCollectionInterface
    {
        $collection = new UriCollection();

        $images = $sq('img');
        /** @var \DOMElement $image */
        foreach ($images as $image) {
            $src = $sq($image)->attr('src');
            if (!is_string($src) || !$src) {
                continue;
            }
            try {
                $uri = new Uri($src);
            } catch (InvalidArgumentException $exception) {
                continue;
            }

            $collection[] = $uri;
        }

        return $collection;
    }

    /**
     * Парсит css
     */
    protected function parseCss(SimpleQueryInterface $sq): UriCollectionInterface
    {
        $collection = new UriCollection();

        $css = $sq('link[rel="stylesheet"]');
        /** @var \DOMElement $cssLink */
        foreach ($css as $cssLink) {
            $href = $sq($cssLink)->attr('href');
            if (!is_string($href) || !$href) {
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
