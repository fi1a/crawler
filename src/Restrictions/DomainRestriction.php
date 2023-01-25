<?php

declare(strict_types=1);

namespace Fi1a\Crawler\Restrictions;

use Fi1a\Http\Uri;
use Fi1a\Http\UriInterface;

/**
 * Ограничение по доменам
 */
class DomainRestriction implements RestrictionInterface
{
    /**
     * @var array<int, UriInterface>
     */
    protected $uri;

    /**
     * @param array<int, string>|array<int, UriInterface> $uri
     */
    public function __construct(array $uri)
    {
        $this->uri = [];
        foreach ($uri as $item) {
            if (!($item instanceof UriInterface)) {
                $item = new Uri($item);
            }
            $this->uri[] = $item;
        }
    }

    /**
     * @inheritDoc
     */
    public function isAllow(UriInterface $uri): bool
    {
        if (!$uri->getHost()) {
            return true;
        }

        foreach ($this->uri as $item) {
            if ($item->getHost() === $uri->getHost()) {
                return true;
            }
        }

        return false;
    }
}
