<?php

declare(strict_types=1);

namespace Fi1a\Crawler\UriTransformers;

use Fi1a\Crawler\ItemInterface;
use Fi1a\Http\UriInterface;

/**
 * Преобразует uri из внешних адресов в локальные
 */
class SiteUriTransformer implements UriTransformerInterface
{
    /**
     * @var string|null
     */
    protected $urlPrefix;

    public function __construct(?string $urlPrefix = null)
    {
        $this->urlPrefix = $urlPrefix;
    }

    /**
     * @inheritDoc
     */
    public function transform(ItemInterface $item): UriInterface
    {
        if (!$item->isAllow()) {
            return $item->getItemUri();
        }

        $object = $item->getItemUri()
            ->withHost('')
            ->withPort(null);

        if ($this->urlPrefix) {
            $parsed = parse_url($this->urlPrefix);
            if (isset($parsed['scheme'])) {
                $object = $object->withScheme($parsed['scheme']);
            }
            if (isset($parsed['user']) && isset($parsed['pass'])) {
                $object = $object->withUserInfo($parsed['user'], $parsed['pass']);
            }
            if (isset($parsed['host'])) {
                $object = $object->withHost($parsed['host']);
            }
            if (isset($parsed['port'])) {
                $object = $object->withPort($parsed['port']);
            }
            if (isset($parsed['path'])) {
                $object = $object->withPath(rtrim($parsed['path'], '/') . '/' . ltrim($object->getPath(), '/'));
            }
        }

        return $object;
    }
}
