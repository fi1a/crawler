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
        $this->replaceLinks($sq, $item, $items);
        $this->replaceImages($sq, $item, $items);

        return html_entity_decode((string) $sq);
    }

    /**
     * Замена ссылок
     */
    protected function replaceLinks(SimpleQueryInterface $sq, ItemInterface $item, ItemCollectionInterface $items): void
    {
        $links = $sq('a');
        /** @var \DOMElement $link */
        foreach ($links as $link) {
            $href = $sq($link)->attr('href');
            if (!is_string($href) || !$href) {
                continue;
            }
            $uri = $this->getNewUri($href, $item, $items);
            if ($uri) {
                $sq($link)->attr('href', $uri->uri());
            }
        }
    }

    /**
     * Замена изображений
     */
    protected function replaceImages(
        SimpleQueryInterface $sq,
        ItemInterface $item,
        ItemCollectionInterface $items
    ): void {
        $images = $sq('img');
        /** @var \DOMElement $image */
        foreach ($images as $image) {
            $src = $sq($image)->attr('src');
            if (!is_string($src) || !$src) {
                continue;
            }
            $uri = $this->getNewUri($src, $item, $items);
            if ($uri) {
                $sq($image)->attr('src', $uri->uri());
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
