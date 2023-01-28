<?php

declare(strict_types=1);

namespace Fi1a\Crawler;

use Fi1a\Collection\Queue;
use Fi1a\Collection\QueueInterface;
use Fi1a\Crawler\PreparePage\PrepareHtmlPage;
use Fi1a\Crawler\PreparePage\PreparePageInterface;
use Fi1a\Crawler\Restrictions\RestrictionCollection;
use Fi1a\Crawler\Restrictions\RestrictionCollectionInterface;
use Fi1a\Crawler\Restrictions\RestrictionInterface;
use Fi1a\Crawler\Restrictions\UriRestriction;
use Fi1a\Crawler\UriConverters\LocalUriConverter;
use Fi1a\Crawler\UriConverters\UriConverterInterface;
use Fi1a\Crawler\UriParsers\HtmlUriParser;
use Fi1a\Crawler\UriParsers\UriParserInterface;
use Fi1a\Crawler\Writers\WriterInterface;
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

    /**
     * @var UriConverterInterface|null
     */
    protected $uriConverter;

    /**
     * @var PreparePageInterface|null
     */
    protected $preparePage;

    /**
     * @var WriterInterface|null
     */
    protected $writer;

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
        $this->validate();
        if (!count($this->restrictions)) {
            $this->addDefaultRestrictions();
        }
        $this->addDefaultUriParser();
        $this->addDefaultUriConverter();
        $this->addDefaultPreparePage();
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
                $this->preparePage($page);
                $this->write($page);
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
     * @inheritDoc
     */
    public function setUriConverter(UriConverterInterface $uriConverter)
    {
        $this->uriConverter = $uriConverter;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function setPreparePage(PreparePageInterface $preparePage)
    {
        $this->preparePage = $preparePage;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function setWriter(WriterInterface $writer)
    {
        $this->writer = $writer;

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
            $uri = $startUrl->replace($startUrl->getNormalizedBasePath());
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
     * Добавить преобразователь адресов из внешних во внутренние используемый по умолчанию
     */
    protected function addDefaultUriConverter(): void
    {
        if (!$this->uriConverter) {
            $this->setUriConverter(new LocalUriConverter());
        }
    }

    /**
     * Добавить класс подготавливающий страницу
     */
    protected function addDefaultPreparePage(): void
    {
        if (!$this->preparePage) {
            $this->setPreparePage(new PrepareHtmlPage());
        }
    }

    /**
     * Валидация конфига
     */
    protected function validate(): void
    {
        if (!count($this->config->getStartUri())) {
            throw new InvalidArgumentException('Не задана точка входа ($config->addStartUrl())');
        }
        if (!$this->writer) {
            throw new InvalidArgumentException('Не задан класс записывающий результат обхода');
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

        $page->setConvertedUri($uri);
        if ($this->uriConverter) {
            $page->setConvertedUri($this->uriConverter->convert($page));
        }

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
            $uri = $page->getAbsoluteUri($uri);

            /** @var RestrictionInterface $restriction */
            foreach ($this->restrictions as $restriction) {
                if (!$restriction->isAllow($uri)) {
                    break 2;
                }
            }

            $this->addPage($uri);
        }
    }

    /**
     * Подготавливает страницу
     */
    protected function preparePage(PageInterface $page): void
    {
        if ($this->preparePage) {
            $page->setPrepareBody($this->preparePage->prepare($page, $this->pages));
        }
    }

    /**
     * Записывает результат обхода
     */
    protected function write(PageInterface $page): void
    {
        if ($this->writer) {
            $this->writer->write($page);
        }
    }
}
