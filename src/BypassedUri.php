<?php

declare(strict_types=1);

namespace Fi1a\Crawler;

use Fi1a\Http\UriInterface;

/**
 * Обработанный Uri
 *
 * @psalm-suppress MissingConstructor
 */
class BypassedUri
{
    /**
     * @var UriInterface
     */
    public $uri;

    /**
     * @var int
     */
    public $statusCode;
}
