<?php

declare(strict_types=1);

namespace Fi1a\Crawler;

use Fi1a\Collection\DataType\ValueObject;
use Fi1a\Http\Uri;
use Fi1a\Http\UriInterface;
use Fi1a\HttpClient\Config as HttpClientConfig;
use Fi1a\HttpClient\ConfigInterface as HttpClientConfigInterface;
use Fi1a\HttpClient\Handlers\StreamHandler;
use InvalidArgumentException;

/**
 * Конфигурация
 */
class Config extends ValueObject implements ConfigInterface
{
    protected $modelKeys = [
        'startUri', 'httpClientConfig', 'httpClientHandler', 'verbose', 'logChannel', 'saveAfterQuantity',
        'lifeTime', 'delay', 'sizeLimits',
    ];

    /**
     * @inheritDoc
     */
    protected function getDefaultModelValues()
    {
        return [
            'startUri' => [],
            'httpClientConfig' => new HttpClientConfig(),
            'httpClientHandler' => StreamHandler::class,
            'verbose' => self::VERBOSE_NORMAL,
            'logChannel' => 'crawler',
            'saveAfterQuantity' => 10,
            'lifeTime' => 24 * 60 * 60,
            'delay' => [0, 0],
            'sizeLimits' => [],
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
        if (!$startUri->host()) {
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

    /**
     * @inheritDoc
     */
    public function setHttpClientConfig(HttpClientConfigInterface $config)
    {
        $this->modelSet('httpClientConfig', $config);

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getHttpClientConfig(): HttpClientConfigInterface
    {
        /** @var HttpClientConfigInterface $config */
        $config = $this->modelGet('httpClientConfig');

        return $config;
    }

    /**
     * @inheritDoc
     */
    public function setHttpClientHandler(string $handler)
    {
        $this->modelSet('httpClientHandler', $handler);

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getHttpClientHandler(): string
    {
        return (string) $this->modelGet('httpClientHandler');
    }

    /**
     * @inheritDoc
     */
    public function setVerbose(int $verbose)
    {
        if ($verbose < self::VERBOSE_NONE || $verbose > self::VERBOSE_DEBUG) {
            throw new InvalidArgumentException(
                sprintf('Передано ошибочное значение "%d" в качестве аргумента', $verbose)
            );
        }

        $this->modelSet('verbose', $verbose);

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getVerbose(): int
    {
        return (int) $this->modelGet('verbose');
    }

    /**
     * @inheritDoc
     */
    public function setLogChannel(string $logChannel)
    {
        $this->modelSet('logChannel', $logChannel);

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getLogChannel(): string
    {
        return (string) $this->modelGet('logChannel');
    }

    /**
     * @inheritDoc
     */
    public function setSaveAfterQuantity(int $quantity)
    {
        if ($quantity < 0) {
            throw new InvalidArgumentException('Кол-во должно быть больше или равно 0');
        }
        $this->modelSet('saveAfterQuantity', $quantity);

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getSaveAfterQuantity(): int
    {
        return (int) $this->modelGet('saveAfterQuantity');
    }

    /**
     * @inheritDoc
     */
    public function setLifetime(int $lifeTime)
    {
        if ($lifeTime < 0) {
            throw new InvalidArgumentException('Время жизни должно быть больше или равно 0');
        }
        $this->modelSet('lifeTime', $lifeTime);

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getLifetime(): int
    {
        return (int) $this->modelGet('lifeTime');
    }

    /**
     * @inheritDoc
     */
    public function setDelay($delay)
    {
        if (is_numeric($delay)) {
            $delay = [(int) $delay, (int) $delay];
        }
        if (!is_array($delay) || count($delay) !== 2 || $delay[0] > $delay[1]) {
            throw new InvalidArgumentException(
                'Ошибка в формате паузы между запросами. '
                . 'Должно быть целое число или массив с двумя целыми числами '
                . 'представляющими собой минимальное и максимальное значение'
            );
        }

        $this->modelSet('delay', $delay);

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getDelay(): array
    {
        /** @var array<array-key, int> $delay */
        $delay = (array) $this->modelGet('delay');

        return $delay;
    }

    /**
     * @inheritDoc
     */
    public function setSizeLimit(int $sizeLimit, ?string $mime = null)
    {
        $sizeLimits = $this->getSizeLimits();
        if (!$mime) {
            $mime = '*';
        }
        $sizeLimits[$mime] = $sizeLimit;
        $this->modelSet('sizeLimits', $sizeLimits);

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getSizeLimits(): array
    {
        /** @var array<string, int> $sizeLimits */
        $sizeLimits = (array) $this->modelGet('sizeLimits');

        return $sizeLimits;
    }
}
