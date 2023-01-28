<?php

declare(strict_types=1);

namespace Fi1a\Crawler\UriConverters;

use Fi1a\Crawler\PageInterface;
use Fi1a\Http\UriInterface;

/**
 * Преобразует uri из внешних адресов в локальные
 */
class LocalUriConverter implements UriConverterInterface
{
    /**
     * @inheritDoc
     */
    public function convert(PageInterface $page): UriInterface
    {
        return $page->getUri()
            ->withHost('')
            ->withPort(null);
    }
}
