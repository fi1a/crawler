<?php

declare(strict_types=1);

namespace Fi1a\Crawler;

use Fi1a\Collection\Queue;
use Fi1a\Collection\QueueInterface;
use Fi1a\Crawler\Restrictions\RestrictionCollection;
use Fi1a\Crawler\Restrictions\RestrictionCollectionInterface;
use Fi1a\Crawler\Restrictions\RestrictionInterface;
use Fi1a\Crawler\Restrictions\UriRestriction;
use Fi1a\Http\UriInterface;
use Fi1a\HttpClient\HttpClient;
use Fi1a\HttpClient\HttpClientInterface;
use InvalidArgumentException;

/**
 * Web Crawler
 */
class Crawler implements CrawlerInterface
{
    /**
     * @var RestrictionCollectionInterface
     */
    protected $restrictions;

    /**
     * @var ConfigInterface
     */
    protected $config;

    /**
     * @var QueueInterface
     */
    protected $queue;

    /**
     * @var BypassedUriCollectionInterface
     */
    protected $bypassedUri;

    /**
     * @var HttpClientInterface
     */
    protected $httpClient;

    public function __construct(ConfigInterface $config)
    {
        $this->config = $config;
        $this->restrictions = new RestrictionCollection();
        $this->queue = new Queue();
        $this->bypassedUri = new BypassedUriCollection();
        $this->httpClient = new HttpClient($this->config->getHttpClientConfig());
    }

    /**
     * @inheritDoc
     */
    public function run(): void
    {
        $this->validateConfig();
        if (!count($this->restrictions)) {
            $this->addDefaultRestrictions();
        }
        $this->initStartUrls();

        /** @psalm-suppress MixedAssignment */
        while ($uri = $this->queue->pollBegin()) {
            /** @var UriInterface $uri */
            $response = $this->httpClient->get($uri);

            $bypassed = new BypassedUri();
            $bypassed->uri = $uri;
            $bypassed->statusCode = $response->getStatusCode();

            $this->bypassedUri[] = $bypassed;
        }
    }

    /**
     * @inheritDoc
     */
    public function addRestriction(RestrictionInterface $restriction)
    {
        $this->restrictions[] = $restriction;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getRestrictions(): RestrictionCollectionInterface
    {
        return $this->restrictions;
    }

    /**
     * @inheritDoc
     */
    public function getBypassedUri(): BypassedUriCollectionInterface
    {
        return $this->bypassedUri;
    }

    /**
     * Добавить ограничения по домену используя точки входа
     */
    protected function addDefaultRestrictions(): void
    {
        $existing = [];
        foreach ($this->config->getStartUri() as $startUrl) {
            $uri = clone $startUrl;
            $uri->replace($startUrl->getNormalizedBasePath());
            if (in_array($uri->getUrl(), $existing)) {
                continue;
            }
            $existing[] = $uri->getUrl();

            $this->addRestriction(new UriRestriction($uri));
        }
    }

    /**
     * Валидация конфига
     */
    protected function validateConfig(): void
    {
        if (!count($this->config->getStartUri())) {
            throw new InvalidArgumentException('Не задана точка входа ($config->addStartUrl())');
        }
    }

    /**
     * Добавляем точки входа в очередь
     */
    protected function initStartUrls(): void
    {
        foreach ($this->config->getStartUri() as $startUrl) {
            $this->queue->addEnd($startUrl);
        }
    }
}
