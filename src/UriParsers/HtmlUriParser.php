<?php

declare(strict_types=1);

namespace Fi1a\Crawler\UriParsers;

use Fi1a\Console\IO\ConsoleOutputInterface;
use Fi1a\Crawler\ItemInterface;
use Fi1a\Crawler\UriCollection;
use Fi1a\Crawler\UriCollectionInterface;
use Fi1a\Http\Uri;
use Fi1a\Log\LoggerInterface;
use Fi1a\SimpleQuery\SimpleQuery;
use Fi1a\SimpleQuery\SimpleQueryInterface;
use InvalidArgumentException;

/**
 * Парсит html и возвращает uri для обхода
 */
class HtmlUriParser implements UriParserInterface
{
    /**
     * @var string
     */
    protected $encoding;

    public function __construct(string $encoding = 'UTF-8')
    {
        $this->encoding = $encoding;
    }

    /**
     * @inheritDoc
     */
    public function parse(
        ItemInterface $item,
        ConsoleOutputInterface $output,
        LoggerInterface $logger
    ): UriCollectionInterface {
        $sq = new SimpleQuery((string) $item->getBody(), $this->encoding);

        $collection = $this->parseNode('a', 'href', $sq);
        $collection->exchangeArray(
            array_merge(
                $collection->getArrayCopy(),
                $this->parseNode('img', 'src', $sq)->getArrayCopy()
            )
        );
        $collection->exchangeArray(
            array_merge(
                $collection->getArrayCopy(),
                $this->parseNode('link[rel="stylesheet"]', 'href', $sq)->getArrayCopy()
            )
        );
        $collection->exchangeArray(
            array_merge(
                $collection->getArrayCopy(),
                $this->parseNode('script', 'src', $sq)->getArrayCopy()
            )
        );
        $collection->exchangeArray(
            array_merge(
                $collection->getArrayCopy(),
                $this->parseNode('iframe', 'src', $sq)->getArrayCopy()
            )
        );

        return $collection;
    }

    /**
     * Парсинг
     */
    protected function parseNode(string $selector, string $attribute, SimpleQueryInterface $sq): UriCollectionInterface
    {
        $collection = new UriCollection();

        $nodes = $sq($selector);
        /** @var \DOMElement $node */
        foreach ($nodes as $node) {
            $value = $sq($node)->attr($attribute);
            if (!is_string($value) || !$value) {
                continue;
            }
            try {
                $uri = new Uri($value);
            } catch (InvalidArgumentException $exception) {
                continue;
            }

            $collection[] = $uri;
        }

        return $collection;
    }
}
