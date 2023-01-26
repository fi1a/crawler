<?php

declare(strict_types=1);

namespace Fi1a\Crawler;

use Fi1a\Collection\Collection;

/**
 * Коллекция обработанных адресов
 */
class BypassedUriCollection extends Collection implements BypassedUriCollectionInterface
{
    /**
     * @inheritDoc
     */
    public function __construct(?array $data = null)
    {
        parent::__construct(BypassedUri::class, $data);
    }
}
