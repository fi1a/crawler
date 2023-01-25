<?php

declare(strict_types=1);

namespace Fi1a\Crawler;

use Fi1a\Collection\DataType\ValueObject;
use Fi1a\Http\Uri;
use Fi1a\Http\UriInterface;
use InvalidArgumentException;

/**
 * Конфигурация
 */
class Config extends ValueObject implements ConfigInterface
{
    protected $modelKeys = ['startUri',];

    /**
     * @inheritDoc
     */
    protected function getDefaultModelValues()
    {
        return [
            'startUri' => [],
        ];
    }

    /**
     * @inheritDoc
     */
    public function addStartUri(string $startUri)
    {
        $uri = $this->getStartUri();
        if (!($startUri instanceof UriInterface)) {
            $startUri = new Uri($startUri);
        }
        if (!$startUri->getHost()) {
            throw new InvalidArgumentException('Не задан хост');
        }
        $uri[] = $startUri;
        $this->modelSet('startUri', $uri);

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getStartUri(): array
    {
        /** @var array<int, UriInterface> $startUri */
        $startUri = $this->modelGet('startUri');

        return $startUri;
    }
}
