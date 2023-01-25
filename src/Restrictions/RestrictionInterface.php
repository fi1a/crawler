<?php

declare(strict_types=1);

namespace Fi1a\Crawler\Restrictions;

use Fi1a\Http\UriInterface;

/**
 * Ограничение по адресу
 */
interface RestrictionInterface
{
    /**
     * Разрешен обход для этого адреса или нет
     */
    public function isAllow(UriInterface $uri): bool;
}
