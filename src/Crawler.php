<?php

declare(strict_types=1);

namespace Fi1a\Crawler;

use Fi1a\Collection\Queue;
use Fi1a\Collection\QueueInterface;
use Fi1a\Console\Component\PanelComponent\PanelComponent;
use Fi1a\Console\Component\PanelComponent\PanelStyle;
use Fi1a\Console\Component\PanelComponent\PanelStyleInterface;
use Fi1a\Console\Component\ProgressbarComponent\ProgressbarComponent;
use Fi1a\Console\Component\ProgressbarComponent\ProgressbarComponentInterface;
use Fi1a\Console\Component\ProgressbarComponent\ProgressbarStyle;
use Fi1a\Console\IO\ConsoleOutput;
use Fi1a\Console\IO\ConsoleOutputInterface;
use Fi1a\Console\IO\Formatter;
use Fi1a\Console\IO\OutputInterface;
use Fi1a\Console\IO\Style\ColorInterface;
use Fi1a\Crawler\ItemStorages\ItemStorageInterface;
use Fi1a\Crawler\PrepareItem\PrepareHtmlItem;
use Fi1a\Crawler\PrepareItem\PrepareItemInterface;
use Fi1a\Crawler\Restrictions\RestrictionCollection;
use Fi1a\Crawler\Restrictions\RestrictionCollectionInterface;
use Fi1a\Crawler\Restrictions\RestrictionInterface;
use Fi1a\Crawler\Restrictions\UriRestriction;
use Fi1a\Crawler\UriParsers\HtmlUriParser;
use Fi1a\Crawler\UriParsers\UriParserInterface;
use Fi1a\Crawler\UriTransformers\SiteUriTransformer;
use Fi1a\Crawler\UriTransformers\UriTransformerInterface;
use Fi1a\Crawler\Writers\WriterInterface;
use Fi1a\Http\UriInterface;
use Fi1a\HttpClient\HttpClient;
use Fi1a\HttpClient\HttpClientInterface;
use Fi1a\Log\LevelInterface;
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
     * @var UriTransformerInterface|null
     */
    protected $uriTransformer;

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
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var string
     */
    protected $runId;

    /**
     * @var ItemStorageInterface
     */
    protected $storage;

    public function __construct(
        ConfigInterface $config,
        ItemStorageInterface $storage,
        ?ConsoleOutputInterface $output = null,
        ?LoggerInterface $logger = null
    ) {
        $this->config = $config;
        $this->restrictions = new RestrictionCollection();
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
        $this->runId = uniqid();
        $this->logger->withContext(['runId' => $this->runId]);
        $this->storage = $storage;
        $this->items = new ItemCollection();
        $this->queue = new Queue();
    }

    /**
     * @inheritDoc
     */
    public function run()
    {
        if (!count($this->config->getStartUri())) {
            throw new InvalidArgumentException('Не задана точка входа ($config->addStartUrl())');
        }
        if (!$this->writer) {
            throw new InvalidArgumentException('Не задан класс записывающий результат обхода');
        }

        return $this->download()->process()->write();
    }

    /**
     * @inheritDoc
     */
    public function download()
    {
        if (!count($this->config->getStartUri())) {
            throw new InvalidArgumentException('Не задана точка входа ($config->addStartUrl())');
        }
        $this->output->setVerbose($this->config->getVerbose());
        if (!count($this->restrictions)) {
            $this->addDefaultRestrictions();
        }
        $this->addDefaultUriParser();
        $this->logger->info('Скачивание данных');
        $this->output->writeln('<bg=white;color=black>Шаг загрузки (download)</>');
        $this->output->writeln('');
        $this->output->writeln('runId: {{}}', [$this->runId]);
        $this->initFromStorage();
        $this->initStartUri();
        $this->loop('downloadItem', 'getDownloaded');
        $this->logger->info('Скачивание данных завершено');

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function process()
    {
        $this->output->setVerbose($this->config->getVerbose());
        $this->addDefaultUriTransformer();

        $this->output->writeln('<bg=white;color=black>Шаг преобразования (process)</>');
        $this->output->writeln('');
        $this->output->writeln('runId: {{}}', [$this->runId]);
        $this->logger->info('Преобразование');

        $this->initFromStorage();
        $this->loop('processItem', 'getProcessed');
        $this->logger->info('Преобразование завершено');

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function write()
    {
        if (!$this->writer) {
            throw new InvalidArgumentException('Не задан обработчик записывающий результат');
        }
        $this->output->setVerbose($this->config->getVerbose());
        $this->addDefaultPrepareItem();

        $this->output->writeln('<bg=white;color=black>Шаг записи (write)</>');
        $this->output->writeln('');
        $this->output->writeln('runId: {{}}', [$this->runId]);
        $this->logger->info('Запись');

        $this->initFromStorage();
        $this->loop('writeItem', 'getWrited');
        $this->logger->info('Запись завершено');

        return $this;
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
    public function getItems(): ItemCollectionInterface
    {
        return $this->items;
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
    public function setUriTransformer(UriTransformerInterface $uriTransformer)
    {
        $this->uriTransformer = $uriTransformer;

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
     * @inheritDoc
     */
    public function clearStorageData()
    {
        $this->storage->clear();

        return $this;
    }

    /**
     * Инициализация из хранилища
     */
    protected function initFromStorage(): void
    {
        $this->logger->info('Извлечение данных из хранилища');
        $this->items = $this->storage->load();
        $this->queue = new Queue();
        foreach ($this->items as $item) {
            assert($item instanceof ItemInterface);
            $this->queue->addEnd($item);
        }
        $this->logger->info(
            'Извлечено {{count}} {{count|declension("элемент", "элемента", "элементов")}}',
            ['count' => $this->items->count()]
        );
        if ($this->items->count()) {
            $this->output->writeln(
                'Извлечено {{count}} {{count|declension("элемент", "элемента", "элементов")}}',
                ['count' => $this->items->count()]
            );
        }
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
    protected function addDefaultUriTransformer(): void
    {
        if (!$this->uriTransformer) {
            $this->setUriTransformer(new SiteUriTransformer());
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

        $item = new Item($uri);

        $item->setAllow(true);

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

                $item->setAllow(false);
            }
        }

        if ($item->isAllow()) {
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
                    'itemUri' => $item->getItemUri()->getUri(),
                ]
            );

            $this->addItem($uri);
        }
    }

    /**
     * Цикл процесса
     */
    protected function loop(string $function, string $resultFunction): void
    {
        $progressbarStyle = new ProgressbarStyle();
        $progressbarStyle->setTemplateByName('full');
        $progressbar = new ProgressbarComponent($this->output, $progressbarStyle);

        $progressbar->start($this->items->count());
        $progressbar->display();
        $index = 0;

        while ($item = $this->queue->pollBegin()) {
            assert($item instanceof ItemInterface);
            $index++;

            $this->$function($item, $progressbar, $index);

            $progressbar->setMaxSteps($this->items->count());
            $progressbar->increment();
            $progressbar->display();
        }

        $this->storage->save($this->items);

        $progressbar->finish();

        $this->output->writeln();
        $this->output->writeln();

        /** @var ItemCollectionInterface $itemCollection */
        $itemCollection = $this->items->$resultFunction();

        $panelStyle = new PanelStyle();
        $panelStyle->setWidth(40)
            ->setPadding(1)
            ->setBorder('ascii')
            ->setBackgroundColor(ColorInterface::GREEN)
            ->setBorderColor(ColorInterface::WHITE)
            ->setColor(ColorInterface::WHITE)
            ->setAlign(PanelStyleInterface::ALIGN_CENTER);
        $panel = new PanelComponent(
            $this->output,
            'Шаг завершен (' . $itemCollection->count() . '/' . $this->items->count() . ')',
            $panelStyle
        );
        $panel->display();

        $this->output->writeln();
    }

    /**
     * Загрузка элемента
     */
    protected function downloadItem(
        ItemInterface $item,
        ProgressbarComponentInterface $progressbar,
        int $index
    ): void {
        if ($this->output->getVerbose() >= OutputInterface::VERBOSE_HIGHT) {
            $progressbar->clear();
        }

        if (!$item->isAllow()) {
            $this->output->writeln(
                '{{index}}/{{count}} <color=yellow>Пропуск загрузки uri {{uri|unescape}}</>',
                [
                    'index' => $index,
                    'count' => $this->items->count(),
                    'uri' => $item->getItemUri()->getUri(),
                ],
                null,
                OutputInterface::VERBOSE_HIGHT
            );

            return;
        }

        $this->output->writeln(
            '{{index}}/{{count}} <color=green>Загрузка uri {{uri|unescape}}</>',
            [
                'index' => $index,
                'count' => $this->items->count(),
                'uri' => $item->getItemUri()->getUri(),
            ],
            null,
            OutputInterface::VERBOSE_HIGHT
        );

        $body = $this->storage->getBody($item);
        if ($body === false) {
            $this->output->writeln(
                '    GET {{uri}}',
                ['uri' => $item->getItemUri()->getUri()],
                null,
                OutputInterface::VERBOSE_HIGHT
            );

            $this->logger->info('GET {{uri}}', ['uri' => $item->getItemUri()->getUri()]);
            $response = $this->httpClient->get($item->getItemUri()->getUri());

            $item->setStatusCode($response->getStatusCode())
                ->setReasonPhrase($response->getReasonPhrase())
                ->setDownloadSuccess($response->isSuccess())
                ->setContentType($response->getBody()->getContentType());

            $body = $response->getBody()->getRaw();
            $this->storage->saveBody($item, $response->getBody()->getRaw());
        } else {
            $this->logger->info('{{uri}} извлечен из хранилища', ['uri' => $item->getItemUri()->getUri()]);
            $this->output->writeln(
                '    Извлечен из хранилища',
                [],
                null,
                OutputInterface::VERBOSE_HIGHT
            );
        }

        $this->logger->log(
            $item->isDownloadSuccess() ? LevelInterface::INFO : LevelInterface::WARNING,
            'Item {{uri}}: statusCode={{statusCode}} contentType={{contentType}}',
            [
                'uri' => $item->getItemUri()->getUri(),
                'statusCode' => $item->getStatusCode(),
                'contentType' => $item->getContentType(),
            ]
        );

        $item->setBody($body);

        if ($item->isDownloadSuccess()) {
            $this->uriParse($item);
        } else {
            $this->output->writeln(
                '    <color=red>- Status={{statusCode}} ({{reasonPhrase}})</>',
                [
                    'statusCode' => $item->getStatusCode(),
                    'reasonPhrase' => $item->getReasonPhrase(),
                ],
                null,
                OutputInterface::VERBOSE_HIGHT
            );
        }

        $item->free();
    }

    /**
     * Преобразование
     */
    protected function processItem(
        ItemInterface $item,
        ProgressbarComponentInterface $progressbar,
        int $index
    ): void {
        if ($this->output->getVerbose() >= OutputInterface::VERBOSE_HIGHT) {
            $progressbar->clear();
        }

        $this->output->writeln(
            '{{index}}/{{count}} <color=green>Преобразование uri {{uri|unescape}}</>',
            [
                'index' => $index,
                'count' => $this->items->count(),
                'uri' => $item->getItemUri()->getUri(),
            ],
            null,
            OutputInterface::VERBOSE_HIGHT
        );

        $newItemUri = $this->uriTransformer ? $this->uriTransformer->transform($item) : $item->getItemUri();
        $item->setNewItemUri($newItemUri)
            ->setProcessSuccess(true);

        if ($newItemUri->getUri() === $item->getItemUri()->getUri()) {
            $this->output->writeln(
                '    Без преобразования',
                [],
                null,
                OutputInterface::VERBOSE_HIGHTEST
            );
            $this->logger->info(
                '{{uri}} без преобразования',
                [
                    'uri' => $item->getItemUri()->getUri(),
                ]
            );

            return;
        }

        $this->output->writeln(
            '    Преобразован в {{|unescape}}',
            [$newItemUri->getUri()],
            null,
            OutputInterface::VERBOSE_HIGHTEST
        );
        $this->logger->info(
            '{{uri}} преобразован в {{newUri}}',
            [
                'uri' => $item->getItemUri()->getUri(),
                'newUri' => $newItemUri->getUri(),
            ]
        );
    }

    /**
     * Записывает результат обхода
     */
    protected function writeItem(
        ItemInterface $item,
        ProgressbarComponentInterface $progressbar,
        int $index
    ): void {
        if ($this->output->getVerbose() >= OutputInterface::VERBOSE_HIGHT) {
            $progressbar->clear();
        }

        if (!$item->isAllow()) {
            $this->output->writeln(
                '{{index}}/{{count}} <color=yellow>Пропуск записи uri {{uri|unescape}}</>',
                [
                    'index' => $index,
                    'count' => $this->items->count(),
                    'uri' => $item->getItemUri()->getUri(),
                ],
                null,
                OutputInterface::VERBOSE_HIGHT
            );

            return;
        }

        $this->output->writeln(
            '{{index}}/{{count}} <color=green>Запись uri {{uri|unescape}}</>',
            [
                'index' => $index,
                'count' => $this->items->count(),
                'uri' => $item->getItemUri()->getUri(),
            ],
            null,
            OutputInterface::VERBOSE_HIGHT
        );

        $item->setWriteSuccess(false);

        if ($item->isDownloadSuccess()) {
            $body = $this->storage->getBody($item);
            if ($body !== false) {
                $item->setBody($body);
                if ($this->prepareItem) {
                    $item->setPrepareBody($this->prepareItem->prepare($item, $this->items));
                }
                if ($this->writer && $this->writer->write($item)) {
                    $item->setWriteSuccess(true);

                    $this->output->writeln(
                        '    Записан',
                        [],
                        null,
                        OutputInterface::VERBOSE_HIGHTEST
                    );
                    $this->logger->info(
                        '{{uri}} записан',
                        [
                            'uri' => $item->getItemUri()->getUri(),
                        ]
                    );

                    return;
                }

                $this->output->writeln(
                    '    <color=red>- Не удалось записать</>',
                    [
                    ],
                    null,
                    OutputInterface::VERBOSE_HIGHT
                );
                $this->logger->warning(
                    '{{uri}} не записан. Не удалось записать',
                    [
                        'uri' => $item->getItemUri()->getUri(),
                    ]
                );

                return;
            }

            $this->output->writeln(
                '    <color=red>- Не получено тело ответа</>',
                [
                ],
                null,
                OutputInterface::VERBOSE_HIGHT
            );
            $this->logger->warning(
                '{{uri}} не записан. Не получено тело ответа',
                [
                    'uri' => $item->getItemUri()->getUri(),
                ]
            );

            return;
        }

        $this->output->writeln(
            '    <color=red>- Status={{statusCode}} ({{reasonPhrase}})</>',
            [
                'statusCode' => $item->getStatusCode(),
                'reasonPhrase' => $item->getReasonPhrase(),
            ],
            null,
            OutputInterface::VERBOSE_HIGHT
        );
        $this->logger->warning(
            '{{uri}} не записан Status={{statusCode}} ({{reasonPhrase}})',
            [
                'uri' => $item->getItemUri()->getUri(),
                'statusCode' => $item->getStatusCode(),
                'reasonPhrase' => $item->getReasonPhrase(),
            ]
        );
    }
}
