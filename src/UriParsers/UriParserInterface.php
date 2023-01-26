<?php

declare(strict_types=1);

namespace Fi1a\Crawler\UriParsers;

use Fi1a\Crawler\UriCollectionInterface;

/**
 * Возвращает uri для обхода
 */
interface UriParserInterface
{
    /**
     * Возвращает uri для обхода
     *
     * @param mixed $body
     */
    public function parse($body): UriCollectionInterface;
}
