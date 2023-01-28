<?php

declare(strict_types=1);

namespace Fi1a\Crawler\UriConverters;

use Fi1a\Crawler\PageInterface;
use Fi1a\Http\UriInterface;

/**
 * Преобразует uri из внешних адресов во внутренние
 */
interface UriConverterInterface
{
    /**
     * Преобразует uri из внешних адресов во внутреннии
     */
    public function convert(PageInterface $page): UriInterface;
}
