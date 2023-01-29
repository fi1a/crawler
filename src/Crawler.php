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
use Fi1a\Crawler\PrepareItem\PrepareHtmlItem;
use Fi1a\Crawler\PrepareItem\PrepareItemInterface;
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
     * @var ItemCollectionInterface
     */
    protected $bypassedItems;

    /**
     * @var HttpClientInterface
     */
    protected $httpClient;

    /**
     * @var array<string, UriParserInterface>
     */
    protected $uriParsers = [];

    /**
     * @var ItemCollectionInterface
     */
    protected $items;

    /**
     * @var UriConverterInterface|null
     */
    protected $uriConverter;

    /**
     * @var PrepareItemInterface|null
     */
    protected $prepareItem;

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
        $this->bypassedItems = new ItemCollection();
        $this->items = new ItemCollection();
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
        $this->addDefaultPrepareItem();
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
        while ($item = $this->queue->pollBegin()) {
            assert($item instanceof ItemInterface);
            $this->processItem($item);
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
    public function getBypassedItems(): ItemCollectionInterface
    {
        return $this->bypassedItems;
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
    public function setPrepareItem(PrepareItemInterface $prepareItem)
    {
        $this->prepareItem = $prepareItem;

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
     * Добавить класс подготавливающий элемент
     */
    protected function addDefaultPrepareItem(): void
    {
        if (!$this->prepareItem) {
            $this->setPrepareItem(new PrepareHtmlItem());
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

            $this->addItem($startUri);
        }
    }

    /**
     * Добавляет элемент, если его нет
     */
    protected function addItem(UriInterface $uri): void
    {
        if ($this->items->has($uri->getUri())) {
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
        $item = new Item($uri, $this->count);

        $item->setConvertedUri($uri);
        if ($this->uriConverter) {
            $item->setConvertedUri($this->uriConverter->convert($item));
        }

        $this->queue->addEnd($item);
        $this->items[$uri->getUri()] = $item;
    }

    /**
     * Парсинг uri из ответа
     *
     * @param mixed $body
     */
    protected function uriParse(ItemInterface $item): void
    {
        $parser = $this->uriParsers[$this->getUriParserMime()];
        $mime = $item->getContentType();
        if ($mime && $this->hasUriParser($mime)) {
            $parser = $this->uriParsers[$this->getUriParserMime($mime)];
        }

        $collection = $parser->parse($item);
        /** @var UriInterface $uri */
        foreach ($collection as $uri) {
            $uri = $item->getAbsoluteUri($uri);

            $this->output->writeln(
                '    Получен uri {{|unescape}}',
                [$uri->getUri()],
                null,
                OutputInterface::VERBOSE_HIGHTEST
            );

            $this->logger->debug(
                'Получен uri {{uri}} из {{itemUri}}',
                [
                    'uri' => $uri->getUri(),
                    'itemUri' => $item->getUri()->getUri(),
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

            $this->addItem($uri);
        }
    }

    /**
     * Подготавливает элемент
     */
    protected function prepareItem(ItemInterface $item): void
    {
        if ($this->prepareItem) {
            $item->setPrepareBody($this->prepareItem->prepare($item, $this->items));
        }
    }

    /**
     * Записывает результат обхода
     */
    protected function write(ItemInterface $item): void
    {
        if ($this->writer) {
            $this->writer->write($item);
        }
    }

    /**
     * Обрабатывает элемент
     */
    protected function processItem(ItemInterface $item): void
    {
        $this->logger->info('GET {{uri}}', ['uri' => $item->getUri()->getUri()]);
        $response = $this->httpClient->get($item->getUri()->getUri());
        $this->logger->info(
            'Response {{uri}}: statusCode={{statusCode}} contentType={{contentType}}',
            [
                'uri' => $item->getUri()->getUri(),
                'statusCode' => $response->getStatusCode(),
                'contentType' => $response->getBody()->getContentType(),
            ]
        );

        $item->setStatusCode($response->getStatusCode())
            ->setContentType($response->getBody()->getContentType())
            ->setBody($response->getBody()->get());

        if ($this->output->getVerbose() >= OutputInterface::VERBOSE_HIGHT) {
            $this->progressbar->clear();
        }

        $this->output->writeln(
            '{{}}/{{}} <color=green>Обработка uri {{|unescape}}</>',
            [
                $item->getIndex(),
                $this->count,
                $item->getUri()->getUri(),
            ],
            null,
            OutputInterface::VERBOSE_HIGHT
        );

        if ($response->isSuccess()) {
            $this->uriParse($item);
            $this->prepareItem($item);
            $this->write($item);
        }

        $this->progressbar->setMaxSteps($this->count);
        $this->progressbar->increment();
        $this->progressbar->display();

        $this->bypassedItems[] = $item;
    }
}
