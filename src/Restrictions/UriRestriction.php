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
        if (!$allow->getHost()) {
            throw new InvalidArgumentException('Не задан разрешенный хост');
        }
        $this->allow = $allow;
    }

    /**
     * @inheritDoc
     */
    public function isAllow(UriInterface $uri): bool
    {
        if (!$uri->getHost() && $this->allow->getPath() === '/') {
            return true;
        }
        if ($uri->getHost() && $this->allow->getHost() !== $uri->getHost()) {
            return false;
        }

        return mb_stripos($uri->getPath(), $this->allow->getPath()) === 0;
    }
}
