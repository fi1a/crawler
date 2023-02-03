<?php

declare(strict_types=1);

namespace Fi1a\Crawler;

use Fi1a\Collection\Queue;
use Fi1a\Collection\QueueInterface;
use Fi1a\Console\Component\PanelComponent\PanelComponent;
use Fi1a\Console\Component\PanelComponent\PanelStyle;
use Fi1a\Console\Component\PanelComponent\PanelStyleInterface;
use Fi1a\Console\Component\ProgressbarComponent\ProgressbarComponent;
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
use Fi1a\Http\Mime;
use Fi1a\Http\Uri;
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
     * @var array<string, PrepareItemInterface>
     */
    protected $prepareItems = [];

    /**
     * @var array<string, WriterInterface>
     */
    protected $writers = [];

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
        $this->httpClient = new HttpClient(
            $this->config->getHttpClientConfig(),
            $this->config->getHttpClientHandler()
        );
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
        if (!count($this->writers)) {
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
        if (!count($this->writers)) {
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
        $this->uriParsers[$this->getMime($mime)] = $parser;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function hasUriParser(?string $mime = null): bool
    {
        return array_key_exists($this->getMime($mime), $this->uriParsers);
    }

    /**
     * @inheritDoc
     */
    public function removeUriParser(?string $mime = null)
    {
        if (!$this->hasUriParser($mime)) {
            return $this;
        }

        unset($this->uriParsers[$this->getMime($mime)]);

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
    public function setPrepareItem(PrepareItemInterface $prepareItem, ?string $mime = null)
    {
        $this->prepareItems[$this->getMime($mime)] = $prepareItem;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function hasPrepareItem(?string $mime = null): bool
    {
        return array_key_exists($this->getMime($mime), $this->prepareItems);
    }

    /**
     * @inheritDoc
     */
    public function removePrepareItem(?string $mime = null)
    {
        if (!$this->hasPrepareItem($mime)) {
            return $this;
        }

        unset($this->prepareItems[$this->getMime($mime)]);

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function setWriter(WriterInterface $writer, ?string $mime = null)
    {
        $this->writers[$this->getMime($mime)] = $writer;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function hasWriter(?string $mime = null): bool
    {
        return array_key_exists($this->getMime($mime), $this->writers);
    }

    /**
     * @inheritDoc
     */
    public function removeWriter(?string $mime = null)
    {
        if (!$this->hasWriter($mime)) {
            return $this;
        }

        unset($this->writers[$this->getMime($mime)]);

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
     * @inheritDoc
     */
    public function addUri($uri)
    {
        if (!($uri instanceof UriInterface)) {
            $uri = new Uri($uri);
        }
        $this->output->writeln(
            'Вызван метод добавления uri',
            [],
            null,
            OutputInterface::VERBOSE_HIGHT
        );
        $this->output->writeln(
            '    Получен uri {{|unescape}}',
            [$uri->maskedUri()],
            null,
            OutputInterface::VERBOSE_HIGHT
        );
        $this->logger->debug('Вызван метод добавления uri');
        if ($this->addItemByUri($uri)) {
            $this->storage->save($this->items);
        }
        $this->output->writeln('', [], null, OutputInterface::VERBOSE_HIGHT);

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
    protected function getMime(?string $mime = null): string
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
            $uri = $startUrl->replace($startUrl->normalizedBasePath());
            if (in_array($uri->url(), $existing)) {
                continue;
            }
            $existing[] = $uri->url();

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
        if (!count($this->prepareItems)) {
            $this->setPrepareItem(new PrepareHtmlItem(), Mime::HTML);
        }
    }

    /**
     * Добавляем точки входа в очередь
     */
    protected function initStartUri(): void
    {
        $logUri = [];
        foreach ($this->config->getStartUri() as $startUri) {
            $logUri[] = $startUri->uri();
        }
        $this->logger->debug('Начальные uri', [], $logUri);

        $this->output->writeln(
            'Вызван метод добавления начальных uri',
            [],
            null,
            OutputInterface::VERBOSE_HIGHT
        );

        foreach ($this->config->getStartUri() as $startUri) {
            $this->output->writeln(
                '    Получен uri {{|unescape}}',
                [$startUri->maskedUri()],
                null,
                OutputInterface::VERBOSE_HIGHT
            );

            $this->addItemByUri($startUri);
        }
    }

    /**
     * Добавляет элемент, если его нет
     */
    protected function addItemByUri(UriInterface $uri): bool
    {
        if ($this->items->has($uri->uri())) {
            $this->logger->debug(
                'Uri {{uri}} уже добавлен',
                [
                    'uri' => $uri->maskedUri(),
                ]
            );

            return false;
        }

        $item = new Item($uri);

        $allow = false;
        /** @var RestrictionInterface $restriction */
        foreach ($this->restrictions as $restriction) {
            if ($restriction->isAllow($uri)) {
                $allow = true;
            }
        }

        if (!$allow) {
            $this->output->writeln(
                '        <color=blue>- Запрещен обход для этого адреса</>',
                [],
                null,
                OutputInterface::VERBOSE_HIGHTEST
            );
            $this->logger->debug(
                'Запрещен обход для этого адреса: {{uri}}',
                [
                    'uri' => $uri->maskedUri(),
                ]
            );
        }

        $item->setAllow($allow);

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
                    'uri' => $uri->maskedUri(),
                ]
            );
        }

        $this->queue->addEnd($item);
        $this->items[$uri->uri()] = $item;

        return true;
    }

    /**
     * Парсинг uri из ответа
     *
     * @param mixed $body
     */
    protected function uriParse(ItemInterface $item): void
    {
        $parser = $this->uriParsers[$this->getMime()];
        $mime = $item->getContentType();
        if ($mime && $this->hasUriParser($mime)) {
            $parser = $this->uriParsers[$this->getMime($mime)];
        }

        $collection = $parser->parse($item);
        /** @var UriInterface $uri */
        foreach ($collection as $uri) {
            $uri = $item->getAbsoluteUri($uri);

            $this->output->writeln(
                '    Получен uri {{|unescape}}',
                [$uri->maskedUri()],
                null,
                OutputInterface::VERBOSE_HIGHTEST
            );

            $this->logger->debug(
                'Получен uri {{uri}} из {{itemUri}}',
                [
                    'uri' => $uri->maskedUri(),
                    'itemUri' => $item->getItemUri()->maskedUri(),
                ]
            );

            $this->addItemByUri($uri);
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

            if ($this->output->getVerbose() >= OutputInterface::VERBOSE_HIGHT) {
                $progressbar->clear();
            }

            $this->$function($item, $index);

            $progressbar->setMaxSteps($this->items->count());
            $progressbar->increment();
            $progressbar->display();

            if ($this->config->getSaveAfterQuantity() > 0 && $index % $this->config->getSaveAfterQuantity() === 0) {
                $this->saveStorage($this->items);
            }
        }

        $this->saveStorage($this->items);

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
     * Сохранение элементов в хранилище
     */
    protected function saveStorage(ItemCollectionInterface $items): void
    {
        $this->logger->debug(
            '({{count}}) Старт сохранения элементов',
            [
                'count' => $items->count(),
            ],
        );
        $startTime = microtime(true);
        $this->storage->save($items);
        $time = microtime(true) - $startTime;
        $this->output->writeln();
        $this->output->writeln(
            '({{count}}) Сохранения элементов / {{time|time|escape}}',
            [
                'count' => $items->count(),
                'time' => $time,
            ],
            null,
            OutputInterface::VERBOSE_DEBUG
        );
        $this->logger->debug(
            '({{count}}) Сохранения элементов завершено / {{time|time}}',
            [
                'count' => $items->count(),
                'time' => $time,
            ],
        );
    }

    /**
     * Загрузка элемента
     */
    protected function downloadItem(ItemInterface $item, int $index): void
    {
        if (!$item->isAllow()) {
            $this->output->writeln(
                '{{index}}/{{count}} <color=yellow>Пропуск загрузки uri {{uri|unescape}}</>',
                [
                    'index' => $index,
                    'count' => $this->items->count(),
                    'uri' => $item->getItemUri()->maskedUri(),
                ],
                null,
                OutputInterface::VERBOSE_HIGHT
            );

            return;
        }

        if ($item->getDownloadStatus() !== null) {
            $this->output->writeln(
                '{{index}}/{{count}} <color=green>Uri {{uri|unescape}} уже загружен</>',
                [
                    'index' => $index,
                    'count' => $this->items->count(),
                    'uri' => $item->getItemUri()->maskedUri(),
                ],
                null,
                OutputInterface::VERBOSE_HIGHT
            );

            if ($item->getDownloadStatus() === false) {
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

            return;
        }

        $this->output->writeln(
            '{{index}}/{{count}} <color=green>Загрузка uri {{uri|unescape}}</>',
            [
                'index' => $index,
                'count' => $this->items->count(),
                'uri' => $item->getItemUri()->maskedUri(),
            ],
            null,
            OutputInterface::VERBOSE_HIGHT
        );

        $this->output->writeln(
            '    GET {{uri}}',
            ['uri' => $item->getItemUri()->maskedUri()],
            null,
            OutputInterface::VERBOSE_HIGHT
        );

        $this->logger->info('GET {{uri}}', ['uri' => $item->getItemUri()->maskedUri()]);

        $response = $this->httpClient->get($item->getItemUri()->uri());

        $item->setStatusCode($response->getStatusCode())
            ->setReasonPhrase($response->getReasonPhrase())
            ->setDownloadStatus($response->isSuccess())
            ->setContentType($response->getBody()->getContentType());

        $body = $response->getBody()->getRaw();
        $this->storage->saveBody($item, $response->getBody()->getRaw());

        $this->logger->log(
            $item->getDownloadStatus() ? LevelInterface::INFO : LevelInterface::WARNING,
            'Item {{uri}}: statusCode={{statusCode}} contentType={{contentType}}',
            [
                'uri' => $item->getItemUri()->maskedUri(),
                'statusCode' => $item->getStatusCode(),
                'contentType' => $item->getContentType(),
            ]
        );

        $item->setBody($body);

        if ($item->getDownloadStatus()) {
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
    protected function processItem(ItemInterface $item, int $index): void
    {
        if ($item->getProcessStatus() !== null) {
            $this->output->writeln(
                '{{index}}/{{count}} <color=green>Uri {{uri|unescape}} уже преобразован</>',
                [
                    'index' => $index,
                    'count' => $this->items->count(),
                    'uri' => $item->getItemUri()->maskedUri(),
                ],
                null,
                OutputInterface::VERBOSE_HIGHT
            );

            return;
        }

        $this->output->writeln(
            '{{index}}/{{count}} <color=green>Преобразование uri {{uri|unescape}}</>',
            [
                'index' => $index,
                'count' => $this->items->count(),
                'uri' => $item->getItemUri()->maskedUri(),
            ],
            null,
            OutputInterface::VERBOSE_HIGHT
        );

        $newItemUri = $this->uriTransformer ? $this->uriTransformer->transform($item) : $item->getItemUri();
        $item->setNewItemUri($newItemUri)
            ->setProcessStatus(true);

        if ($newItemUri->uri() === $item->getItemUri()->uri()) {
            $this->output->writeln(
                '    Без преобразования',
                [],
                null,
                OutputInterface::VERBOSE_HIGHTEST
            );
            $this->logger->info(
                '{{uri}} без преобразования',
                [
                    'uri' => $item->getItemUri()->maskedUri(),
                ]
            );

            return;
        }

        $this->output->writeln(
            '    Преобразован в {{|unescape}}',
            [$newItemUri->maskedUri()],
            null,
            OutputInterface::VERBOSE_HIGHTEST
        );
        $this->logger->info(
            '{{uri}} преобразован в {{newUri}}',
            [
                'uri' => $item->getItemUri()->maskedUri(),
                'newUri' => $newItemUri->maskedUri(),
            ]
        );
    }

    /**
     * Записывает результат обхода
     */
    protected function writeItem(ItemInterface $item, int $index): void
    {
        if (!$item->isAllow()) {
            $this->output->writeln(
                '{{index}}/{{count}} <color=yellow>Пропуск записи uri {{uri|unescape}}</>',
                [
                    'index' => $index,
                    'count' => $this->items->count(),
                    'uri' => $item->getItemUri()->maskedUri(),
                ],
                null,
                OutputInterface::VERBOSE_HIGHT
            );

            return;
        }

        if ($item->getWriteStatus() !== null) {
            $this->output->writeln(
                '{{index}}/{{count}} <color=green>Uri {{uri|unescape}} уже записан</>',
                [
                    'index' => $index,
                    'count' => $this->items->count(),
                    'uri' => $item->getItemUri()->maskedUri(),
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
                'uri' => $item->getItemUri()->maskedUri(),
            ],
            null,
            OutputInterface::VERBOSE_HIGHT
        );

        $item->setWriteStatus(false);

        if ($item->getDownloadStatus()) {
            $body = $this->storage->getBody($item);
            if ($body !== false) {
                $item->setBody($body);
                $item->setPrepareBody($body);
                if (count($this->prepareItems)) {
                    $prepareItem = null;
                    if ($this->hasPrepareItem($this->getMime())) {
                        $prepareItem = $this->prepareItems[$this->getMime()];
                    }
                    $mime = $item->getContentType();
                    if ($mime && $this->hasPrepareItem($mime)) {
                        $prepareItem = $this->prepareItems[$this->getMime($mime)];
                    }
                    if ($prepareItem) {
                        $item->setPrepareBody($prepareItem->prepare($item, $this->items));
                    }
                }
                $writer = $this->writers[$this->getMime()];
                $mime = $item->getContentType();
                if ($mime && $this->hasWriter($mime)) {
                    $writer = $this->writers[$this->getMime($mime)];
                }
                if ($writer->write($item)) {
                    $item->setWriteStatus(true);

                    $this->output->writeln(
                        '    Записан',
                        [],
                        null,
                        OutputInterface::VERBOSE_HIGHTEST
                    );
                    $this->logger->info(
                        '{{uri}} записан',
                        [
                            'uri' => $item->getItemUri()->maskedUri(),
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
                        'uri' => $item->getItemUri()->maskedUri(),
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
                    'uri' => $item->getItemUri()->maskedUri(),
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
                'uri' => $item->getItemUri()->maskedUri(),
                'statusCode' => $item->getStatusCode(),
                'reasonPhrase' => $item->getReasonPhrase(),
            ]
        );
    }
}
