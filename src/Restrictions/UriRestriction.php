<?php

declare(strict_types=1);

namespace Fi1a\Crawler\Restrictions;

use Fi1a\Http\Uri;
use Fi1a\Http\UriInterface;
use InvalidArgumentException;

/**
 * Ограничение по домену
 */
class UriRestriction implements RestrictionInterface
{
    /**
     * @var UriInterface
     */
    protected $allow;

    /**
     * @param string|UriInterface $allow
     */
    public function __construct($allow)
    {
        if (!($allow instanceof UriInterface)) {
            $allow = new Uri($allow);
        }
        if (!$allow->host()) {
            throw new InvalidArgumentException('Не задан разрешенный хост');
        }
        $this->allow = $allow;
    }

    /**
     * @inheritDoc
     */
    public function isAllow(UriInterface $uri): bool
    {
        if (!$uri->host() && $this->allow->path() === '/') {
            return true;
        }
        if ($uri->host() && $this->allow->host() !== $uri->host()) {
            return false;
        }

        return mb_stripos($uri->path(), $this->allow->path()) === 0;
    }
}
