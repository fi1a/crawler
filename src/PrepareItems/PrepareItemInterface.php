<?php

declare(strict_types=1);

namespace Fi1a\Crawler\PrepareItems;

use Fi1a\Console\IO\ConsoleOutputInterface;
use Fi1a\Crawler\ItemCollectionInterface;
use Fi1a\Crawler\ItemInterface;
use Fi1a\Log\LoggerInterface;

/**
 * Подготавливает элемент
 */
interface PrepareItemInterface
{
    /**
     * Подготавливает элемент
     *
     * @return mixed
     */
    public function prepare(
        ItemInterface $item,
        ItemCollectionInterface $items,
        ConsoleOutputInterface $output,
        LoggerInterface $logger
    );
}
