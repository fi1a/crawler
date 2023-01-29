<?php

declare(strict_types=1);

namespace Fi1a\Crawler\Writers;

use Fi1a\Crawler\ItemInterface;

/**
 * Записывает результат обхода
 */
interface WriterInterface
{
    /**
     * Записывает результат обхода
     */
    public function write(ItemInterface $item): bool;
}
