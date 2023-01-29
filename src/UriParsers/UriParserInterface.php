<?php

declare(strict_types=1);

namespace Fi1a\Crawler\UriParsers;

use Fi1a\Crawler\ItemInterface;
use Fi1a\Crawler\UriCollectionInterface;

/**
 * Возвращает uri для обхода
 */
interface UriParserInterface
{
    /**
     * Возвращает uri для обхода
     */
    public function parse(ItemInterface $item): UriCollectionInterface;
}
