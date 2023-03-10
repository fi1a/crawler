<?php

declare(strict_types=1);

namespace Fi1a\Crawler\PrepareItems;

use Fi1a\Console\IO\ConsoleOutputInterface;
use Fi1a\Crawler\ItemCollectionInterface;
use Fi1a\Crawler\ItemInterface;
use Fi1a\Http\Uri;
use Fi1a\Log\LoggerInterface;
use Fi1a\SimpleQuery\SimpleQuery;
use Fi1a\SimpleQuery\SimpleQueryInterface;
use InvalidArgumentException;

/**
 * Подготавливает HTML элемент
 */
class PrepareHtmlItem implements PrepareItemInterface
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
    public function prepare(
        ItemInterface $item,
        ItemCollectionInterface $items,
        ConsoleOutputInterface $output,
        LoggerInterface $logger
    ) {
        $sq = new SimpleQuery((string) $item->getBody(), $this->encoding);
        $this->replace('a', 'href', $sq, $item, $items);
        $this->replace('img', 'src', $sq, $item, $items);
        $this->replace('link[rel="stylesheet"]', 'href', $sq, $item, $items);
        $this->replace('script', 'src', $sq, $item, $items);
        $this->replace('iframe', 'src', $sq, $item, $items);

        return (string) $sq;
    }

    /**
     * Замена
     */
    protected function replace(
        string $selector,
        string $attribute,
        SimpleQueryInterface $sq,
        ItemInterface $item,
        ItemCollectionInterface $items
    ): void {
        $nodes = $sq($selector);
        /** @var \DOMElement $node */
        foreach ($nodes as $node) {
            $value = $sq($node)->attr($attribute);
            if (!is_string($value) || !$value) {
                continue;
            }
            $uri = $this->getNewUri($value, $item, $items);
            if ($uri) {
                $sq($node)->attr($attribute, $uri->uri());
            }
        }
    }

    /**
     *  Возвращает новый uri
     *
     * @return \Fi1a\Http\UriInterface|false|null
     */
    protected function getNewUri(string $htmlUri, ItemInterface $item, ItemCollectionInterface $items)
    {
        try {
            $uri = new Uri($htmlUri);
        } catch (InvalidArgumentException $exception) {
            return false;
        }

        $absoluteUri = $item->getAbsoluteUri($uri);
        $fragment = $absoluteUri->fragment();
        $absoluteUri = $absoluteUri->withFragment('');

        if (!$items->has($absoluteUri->uri())) {
            return false;
        }

        /** @var ItemInterface $newItem */
        $newItem = $items->get($absoluteUri->uri());
        $newItemUri = $newItem->getNewItemUri();

        return $newItemUri ? $newItemUri->withFragment($fragment) : null;
    }
}
