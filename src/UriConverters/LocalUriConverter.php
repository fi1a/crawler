<?php

declare(strict_types=1);

namespace Fi1a\Crawler\UriConverters;

use Fi1a\Crawler\ItemInterface;
use Fi1a\Http\UriInterface;

/**
 * Преобразует uri из внешних адресов в локальные
 */
class LocalUriConverter implements UriConverterInterface
{
    /**
     * @inheritDoc
     */
    public function convert(ItemInterface $item): UriInterface
    {
        return $item->getUri()
            ->withHost('')
            ->withPort(null);
    }
}
