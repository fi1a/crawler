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
    protected $modelKeys = ['startUrls',];

    /**
     * @inheritDoc
     */
    protected function getDefaultModelValues()
    {
        return [
            'startUrls' => [],
        ];
    }

    /**
     * @inheritDoc
     */
    public function addStartUrl(string $startUrl)
    {
        $startUrls = $this->getStartUrls();
        if (!($startUrl instanceof UriInterface)) {
            $startUrl = new Uri($startUrl);
        }
        if (!$startUrl->getHost()) {
            throw new InvalidArgumentException('Не задан хост');
        }
        $startUrls[] = $startUrl;
        $this->modelSet('startUrls', $startUrls);

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getStartUrls(): array
    {
        /** @var array<int, UriInterface> $startUrls */
        $startUrls = $this->modelGet('startUrls');

        return $startUrls;
    }
}
