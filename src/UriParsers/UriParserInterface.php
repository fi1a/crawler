<?php

declare(strict_types=1);

namespace Fi1a\Crawler\UriParsers;

use Fi1a\Console\IO\ConsoleOutputInterface;
use Fi1a\Crawler\ItemInterface;
use Fi1a\Crawler\UriCollectionInterface;
use Fi1a\Log\LoggerInterface;

/**
 * Возвращает uri для обхода
 */
interface UriParserInterface
{
    /**
     * Возвращает uri для обхода
     */
    public function parse(
        ItemInterface $item,
        ConsoleOutputInterface $output,
        LoggerInterface $logger
    ): UriCollectionInterface;
}
