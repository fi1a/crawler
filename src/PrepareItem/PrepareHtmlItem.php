<?php

declare(strict_types=1);

namespace Fi1a\Crawler\PrepareItem;

use Fi1a\Crawler\ItemCollectionInterface;
use Fi1a\Crawler\ItemInterface;
use Fi1a\Http\Uri;
use Fi1a\SimpleQuery\SimpleQuery;
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

            $absoluteUri = $item->getAbsoluteUri($uri);

            if ($absoluteUri->getHost() !== $item->getUri()->getHost()) {
                continue;
            }

            $relativeUri = $item->getRelativeUri($uri);

            $sq($link)->attr('href', $relativeUri->getUri());
        }

        return (string) $sq;
    }
}