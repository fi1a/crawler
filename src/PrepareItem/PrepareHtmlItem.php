<?php

declare(strict_types=1);

namespace Fi1a\Crawler\PrepareItem;

use Fi1a\Crawler\ItemCollectionInterface;
use Fi1a\Crawler\ItemInterface;
use Fi1a\Http\Uri;
use Fi1a\SimpleQuery\SimpleQuery;
use Fi1a\SimpleQuery\SimpleQueryInterface;
use InvalidArgumentException;

/**
 * Подготавливает HTML элемент
 */
class PrepareHtmlItem implements PrepareItemInterface
{
    /**
     * @inheritDoc
     */
    public function prepare(ItemInterface $item, ItemCollectionInterface $items)
    {
        $sq = new SimpleQuery((string) $item->getBody());
        $this->replace('a', 'href', $sq, $item, $items);
        $this->replace('img', 'src', $sq, $item, $items);
        $this->replace('link[rel="stylesheet"]', 'href', $sq, $item, $items);
        $this->replace('script', 'src', $sq, $item, $items);

        return html_entity_decode((string) $sq);
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

        if (!$items->has($absoluteUri->uri())) {
            return false;
        }

        /** @var ItemInterface $newItem */
        $newItem = $items->get($absoluteUri->uri());

        return $newItem->getNewItemUri();
    }
}
