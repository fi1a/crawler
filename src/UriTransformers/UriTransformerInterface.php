<?php

declare(strict_types=1);

namespace Fi1a\Crawler\UriTransformers;

use Fi1a\Console\IO\ConsoleOutputInterface;
use Fi1a\Crawler\ItemInterface;
use Fi1a\Http\UriInterface;
use Fi1a\Log\LoggerInterface;

/**
 * Преобразует uri из внешних адресов во внутренние
 */
interface UriTransformerInterface
{
    /**
     * Преобразует uri из внешних адресов во внутреннии
     */
    public function transform(
        ItemInterface $item,
        ConsoleOutputInterface $output,
        LoggerInterface $logger
    ): UriInterface;
}
