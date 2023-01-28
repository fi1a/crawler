<?php

declare(strict_types=1);

namespace Fi1a\Crawler;

use Fi1a\Collection\Queue;
use Fi1a\Collection\QueueInterface;
use Fi1a\Console\Component\ProgressbarComponent\ProgressbarComponent;
use Fi1a\Console\Component\ProgressbarComponent\ProgressbarComponentInterface;
use Fi1a\Console\Component\ProgressbarComponent\ProgressbarStyle;
use Fi1a\Console\IO\ConsoleOutput;
use Fi1a\Console\IO\ConsoleOutputInterface;
use Fi1a\Console\IO\Formatter;
use Fi1a\Console\IO\OutputInterface;
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
use Fi1a\Log\Logger;
use Fi1a\Log\LoggerInterface;
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

    /**
     * @var ConsoleOutputInterface
     */
    protected $output;

    /**
     * @var int
     */
    protected $count = 0;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var ProgressbarComponentInterface
     * @psalm-suppress PropertyNotSetInConstructor
     */
    protected $progressbar;

    public function __construct(
        ConfigInterface $config,
        ?ConsoleOutputInterface $output = null,
        ?LoggerInterface $logger = null
    ) {
        $this->config = $config;
        $this->restrictions = new RestrictionCollection();
        $this->queue = new Queue();
        $this->bypassedPages = new PageCollection();
        $this->pages = new PageCollection();
        $this->httpClient = new HttpClient($this->config->getHttpClientConfig());
        if ($output === null) {
            $output = new ConsoleOutput(new Formatter());
        }
        $this->output = $output;
        if ($logger === null) {
            /** @var LoggerInterface|false $logger */
            $logger = logger($this->config->getLogChannel());
            if ($logger === false) {
                $logger = new Logger($this->config->getLogChannel());
            }
        }
        $this->logger = $logger;
    }

    /**
     * @inheritDoc
     */
    public function run(): void
    {
        $this->validate();
        $this->output->setVerbose($this->config->getVerbose());
        if (!count($this->restrictions)) {
            $this->addDefaultRestrictions();
        }
        $this->addDefaultUriParser();
        $this->addDefaultUriConverter();
        $this->addDefaultPreparePage();
        $runId = uniqid();
        $this->logger->withContext(['runId' => $runId]);
        $this->logger->info('Запуск обхода');
        $this->output->writeln('runId: {{}}', [$runId]);
        $this->initStartUri();

        $progressbarStyle = new ProgressbarStyle();
        $progressbarStyle->setTemplateByName('full');
        $this->progressbar = new ProgressbarComponent($this->output, $progressbarStyle);

        $this->progressbar->start($this->count);
        $this->progressbar->display();

        /** @psalm-suppress MixedAssignment */
        while ($page = $this->queue->pollBegin()) {
            assert($page instanceof PageInterface);
            $this->processPage($page);
        }

        $this->progressbar->finish();
        $this->output->writeln();
        $this->output->writeln();

        $this->logger->info('Обход завершен');
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
        $logUri = [];
        foreach ($this->config->getStartUri() as $startUri) {
            $logUri[] = $startUri->getUri();
        }
        $this->logger->debug('Начальные uri', [], $logUri);

        foreach ($this->config->getStartUri() as $startUri) {
            $this->output->writeln(
                '    Получен uri {{|unescape}}',
                [$startUri->getUri()],
                null,
                OutputInterface::VERBOSE_HIGHTEST
            );

            $this->addPage($startUri);
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

        $this->output->writeln(
            '        <color=yellow>+ Добавлен в очередь</>',
            [],
            null,
            OutputInterface::VERBOSE_HIGHTEST
        );
        $this->logger->debug(
            'Добавлен в очередь: {{uri}}',
            [
                'uri' => $uri->getUri(),
            ]
        );

        $this->count++;
        $page = new Page($uri, $this->count);

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

            $this->output->writeln(
                '    Получен uri {{|unescape}}',
                [$uri->getUri()],
                null,
                OutputInterface::VERBOSE_HIGHTEST
            );

            $this->logger->debug(
                'Получен uri {{uri}} со страницы {{pageUri}}',
                [
                    'uri' => $uri->getUri(),
                    'pageUri' => $page->getUri()->getUri(),
                ]
            );

            /** @var RestrictionInterface $restriction */
            foreach ($this->restrictions as $restriction) {
                if (!$restriction->isAllow($uri)) {
                    $this->output->writeln(
                        '        <color=blue>- Запрещен обход для этого адреса</>',
                        [],
                        null,
                        OutputInterface::VERBOSE_HIGHTEST
                    );
                    $this->logger->debug(
                        'Запрещен обход для этого адреса: {{uri}}',
                        [
                            'uri' => $uri->getUri(),
                        ]
                    );

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

    /**
     * Обрабатывает страницу
     */
    protected function processPage(PageInterface $page): void
    {
        $this->logger->info('GET {{uri}}', ['uri' => $page->getUri()->getUri()]);
        $response = $this->httpClient->get($page->getUri()->getUri());
        $this->logger->info(
            'Response {{uri}}: statusCode={{statusCode}} contentType={{contentType}}',
            [
                'uri' => $page->getUri()->getUri(),
                'statusCode' => $response->getStatusCode(),
                'contentType' => $response->getBody()->getContentType(),
            ]
        );

        $page->setStatusCode($response->getStatusCode())
            ->setContentType($response->getBody()->getContentType())
            ->setBody($response->getBody()->get());

        if ($this->output->getVerbose() >= OutputInterface::VERBOSE_HIGHT) {
            $this->progressbar->clear();
        }

        $this->output->writeln(
            '{{}}/{{}} <color=green>Обработка uri {{|unescape}}</>',
            [
                $page->getIndex(),
                $this->count,
                $page->getUri()->getUri(),
            ],
            null,
            OutputInterface::VERBOSE_HIGHT
        );

        if ($response->isSuccess()) {
            $this->uriParse($page);
            $this->preparePage($page);
            $this->write($page);
        }

        $this->progressbar->setMaxSteps($this->count);
        $this->progressbar->increment();
        $this->progressbar->display();

        $this->bypassedPages[] = $page;
    }
}
