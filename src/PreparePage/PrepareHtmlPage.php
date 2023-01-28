<?php

declare(strict_types=1);

namespace Fi1a\Crawler\PreparePage;

use Fi1a\Crawler\PageCollectionInterface;
use Fi1a\Crawler\PageInterface;
use Fi1a\Http\Uri;
use Fi1a\SimpleQuery\SimpleQuery;
use InvalidArgumentException;

/**
 * Подготавливает HTML страницу
 */
class PrepareHtmlPage implements PreparePageInterface
{
    /**
     * @inheritDoc
     */
    public function prepare(PageInterface $page, PageCollectionInterface $pages)
    {
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

            $absoluteUri = $page->getAbsoluteUri($uri);

            if ($absoluteUri->getHost() !== $page->getUri()->getHost()) {
                continue;
            }

            $relativeUri = $page->getRelativeUri($uri);

            $sq($link)->attr('href', $relativeUri->getUri());
        }

        return (string) $sq;
    }
}
