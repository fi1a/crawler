<?php

declare(strict_types=1);

namespace Fi1a\Crawler;

use Fi1a\Collection\AbstractInstanceCollection;
use Fi1a\Http\Uri;
use Fi1a\Http\UriInterface;

/**
 * Коллекция адресов
 */
class UriCollection extends AbstractInstanceCollection implements UriCollectionInterface
{
    /**
     * @inheritDoc
     */
    protected function factory($key, $value)
    {
        return new Uri((string) $value);
    }

    /**
     * @inheritDoc
     */
    protected function isInstance($value): bool
    {
        return $value instanceof UriInterface;
    }
}
