<?php

declare(strict_types=1);

namespace Fi1a\Crawler;

use Fi1a\Collection\Queue;
use Fi1a\Collection\QueueInterface;
use Fi1a\Crawler\Restrictions\RestrictionCollection;
use Fi1a\Crawler\Restrictions\RestrictionCollectionInterface;
use Fi1a\Crawler\Restrictions\RestrictionInterface;
use Fi1a\Crawler\Restrictions\UriRestriction;
use Fi1a\Crawler\UriParsers\HtmlUriParser;
use Fi1a\Crawler\UriParsers\UriParserInterface;
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
     * @var PageCollectionInterface
     */
    protected $bypassedPages;

    /**
     * @var HttpClientInterface
     */
    protected $httpClient;

    /**
     * @var array<string, UriParserInterface>
     */
    protected $uriParsers = [];

    /**
     * @var PageCollectionInterface
     */
    protected $pages;

    public function __construct(ConfigInterface $config)
    {
        $this->config = $config;
        $this->restrictions = new RestrictionCollection();
        $this->queue = new Queue();
        $this->bypassedPages = new PageCollection();
        $this->pages = new PageCollection();
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
        $this->addDefaultUriParser();
        $this->initStartUri();

        /** @psalm-suppress MixedAssignment */
        while ($page = $this->queue->pollBegin()) {
            assert($page instanceof PageInterface);

            $response = $this->httpClient->get($page->getUri());

            $page->setStatusCode($response->getStatusCode())
                ->setContentType($response->getBody()->getContentType())
                ->setBody($response->getBody()->get());

            if ($response->isSuccess()) {
                $this->uriParse($page);
            }

            $this->bypassedPages[] = $page;
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
    public function getBypassedPages(): PageCollectionInterface
    {
        return $this->bypassedPages;
    }

    /**
     * @inheritDoc
     */
    public function setUriParser(UriParserInterface $parser, ?string $mime = null)
    {
        $this->uriParsers[$this->getUriParserMime($mime)] = $parser;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function hasUriParser(?string $mime = null): bool
    {
        return array_key_exists($this->getUriParserMime($mime), $this->uriParsers);
    }

    /**
     * @inheritDoc
     */
    public function removeUriParser(?string $mime = null)
    {
        if (!$this->hasUriParser($mime)) {
            return $this;
        }

        unset($this->uriParsers[$this->getUriParserMime($mime)]);

        return $this;
    }

    /**
     * Возвращатет mime тип для парсера uri
     */
    protected function getUriParserMime(?string $mime = null): string
    {
        if (!$mime) {
            return '*';
        }

        return $mime;
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
     * Добавить парсер uri по умолчанию
     */
    protected function addDefaultUriParser(): void
    {
        if (!$this->hasUriParser()) {
            $this->setUriParser(new HtmlUriParser());
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
    protected function initStartUri(): void
    {
        foreach ($this->config->getStartUri() as $startUrl) {
            $this->addPage($startUrl);
        }
    }

    /**
     * Добавляет страницу, если ее нет
     */
    protected function addPage(UriInterface $uri): void
    {
        if ($this->pages->has($uri->getUri())) {
            return;
        }

        $page = new Page($uri);
        $this->queue->addEnd($page);
        $this->pages[$uri->getUri()] = $page;
    }

    /**
     * Парсинг uri из ответа
     *
     * @param mixed $body
     */
    protected function uriParse(PageInterface $page): void
    {
        $parser = $this->uriParsers[$this->getUriParserMime()];
        $mime = $page->getContentType();
        if ($mime && $this->hasUriParser($mime)) {
            $parser = $this->uriParsers[$this->getUriParserMime($mime)];
        }

        $collection = $parser->parse($page);
        /** @var UriInterface $uri */
        foreach ($collection as $uri) {
            if (!$uri->getHost()) {
                $uri = $uri->withScheme($page->getUri()->getScheme())
                    ->withHost($page->getUri()->getHost())
                    ->withPort($page->getUri()->getPort());
            }
            if (mb_substr($uri->getPath(), 0, 1) !== '/') {
                $uri = $uri->withPath($page->getUri()->getNormalizedBasePath() . $uri->getPath());
            }

            /** @var RestrictionInterface $restriction */
            foreach ($this->restrictions as $restriction) {
                if (!$restriction->isAllow($uri)) {
                    break 2;
                }
            }

            $this->addPage($uri);
        }
    }
}
