<?php

declare(strict_types=1);

namespace Fi1a\Crawler\Writers;

use Fi1a\Console\IO\ConsoleOutputInterface;
use Fi1a\Crawler\ItemInterface;
use Fi1a\Log\LoggerInterface;

/**
 * Записывает результат обхода
 */
interface WriterInterface
{
    /**
     * Записывает результат обхода
     */
    public function write(
        ItemInterface $item,
        ConsoleOutputInterface $output,
        LoggerInterface $logger
    ): bool;
}
