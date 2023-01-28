<?php

declare(strict_types=1);

namespace Fi1a\Crawler\Writers;

use Fi1a\Crawler\PageInterface;

/**
 * Записывает результат обхода
 */
interface WriterInterface
{
    /**
     * Записывает результат обхода
     */
    public function write(PageInterface $page): bool;
}
