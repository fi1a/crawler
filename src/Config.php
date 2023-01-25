<?php

declare(strict_types=1);

namespace Fi1a\Crawler;

use Fi1a\Collection\DataType\ValueObject;

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
    public function addStartUrl(?string $startUrl)
    {
        $startUrls = $this->getStartUrls();
        $startUrls[] = $startUrl;
        $this->modelSet('startUrls', $startUrls);

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getStartUrls(): array
    {
        /** @var array<int, string> $startUrls */
        $startUrls = $this->modelGet('startUrls');

        return $startUrls;
    }
}
