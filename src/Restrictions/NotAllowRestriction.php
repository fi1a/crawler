<?php

declare(strict_types=1);

namespace Fi1a\Crawler\Restrictions;

use Fi1a\Http\UriInterface;

/**
 * Запрет на обход
 */
class NotAllowRestriction implements RestrictionInterface
{
    /**
     * @inheritDoc
     */
    public function isAllow(UriInterface $uri): bool
    {
        return false;
    }
}
