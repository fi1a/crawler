<?php

declare(strict_types=1);

namespace Fi1a\Crawler;

use DateTime;
use Fi1a\Console\IO\ConsoleOutputInterface;
use Fi1a\Console\IO\OutputInterface;
use Fi1a\Crawler\ItemStorages\ItemStorageInterface;
use Fi1a\Crawler\Proxy\ProxyCollectionInterface;
use Fi1a\Crawler\Proxy\ProxyInterface;
use Fi1a\Crawler\Proxy\ProxyStorageInterface;
use Fi1a\Crawler\Proxy\Selections\ProxySelectionInterface;
use Fi1a\Crawler\Restrictions\RestrictionCollection;
use Fi1a\Crawler\Restrictions\RestrictionCollectionInterface;
use Fi1a\Crawler\Restrictions\RestrictionInterface;
use Fi1a\Crawler\Restrictions\UriRestriction;
use Fi1a\Crawler\UriParsers\HtmlUriParser;
use Fi1a\Crawler\UriParsers\UriParserInterface;
use Fi1a\Http\Http;
use Fi1a\Http\Uri;
use Fi1a\Http\UriInterface;
use Fi1a\HttpClient\Handlers\Exceptions\ConnectionErrorException;
use Fi1a\HttpClient\HttpClient;
use Fi1a\HttpClient\HttpClientInterface;
use Fi1a\HttpClient\Middlewares\RetryMiddleware;
use Fi1a\HttpClient\Request;
use Fi1a\HttpClient\Response;
use Fi1a\Log\LevelInterface;
use Fi1a\Log\LoggerInterface;
use InvalidArgumentException;

/**
 * Загрузка элементов
 */
class DownloadOperation extends AbstractOperation
{
    /**
     * @var RestrictionCollectionInterface
     */
    protected $restrictions;

    /**
     * @var array<string, UriParserInterface>
     */
    protected $uriParsers = [];

    /**
     * @var ProxyStorageInterface|null
     */
    protected $proxyStorage;

    /**
     * @var ProxyCollectionInterface|null
     */
    protected $proxyCollection;

    /**
     * @var ProxySelectionInterface|null
     */
    protected $proxySelection;

    /**
     * @var HttpClientInterface
     */
    protected $httpClient;

    public function __construct(
        ConfigInterface $config,
        ConsoleOutputInterface $output,
        LoggerInterface $logger,
        ItemStorageInterface $itemStorage,
        string $runId,
        ?ProxyStorageInterface $proxyStorage = null
    ) {
        parent::__construct($config, $output, $logger, $itemStorage, $runId);

        $this->httpClient = new HttpClient(
            $config->getHttpClientConfig(),
            $config->getHttpClientHandler()
        );
        $this->restrictions = new RestrictionCollection();
        $this->setProxyStorage($proxyStorage);
    }

    /**
     * @inheritDoc
     */
    public function validate(): void
    {
        if (!count($this->config->getStartUri())) {
            throw new InvalidArgumentException('Не задана точка входа ($config->addStartUrl())');
        }
    }

    /**
     * @inheritDoc
     */
    protected function beforeOperate(): void
    {
        if (!count($this->restrictions)) {
            $this->addDefaultRestrictions();
        }
        $this->addDefaultUriParser();
        $this->logger->info('Скачивание данных');
        $this->output->writeln('<bg=white;color=black>Шаг загрузки (download)</>');
        $this->output->writeln('');
        $this->output->writeln('runId: {{}}', [$this->runId]);
        $this->initStartUri();
    }

    /**
     * @inheritDoc
     */
    protected function afterOperate(): void
    {
        $this->logger->info('Скачивание данных завершено');
    }

    /**
     * @inheritDoc
     */
    protected function operateOnItem(ItemInterface $item, int $index): void
    {
        if ($this->config->getLifetime() && $item->getExpire() === null) {
            $item->expiresAfter($this->config->getLifetime());
        } elseif (!$this->config->getLifetime() && $item->getExpire()) {
            $item->expiresAfter(null);
        }

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

        $proxyIterator = null;
        if ($this->proxyCollection) {
            /** @var \ArrayIterator $proxyIterator */
            $proxyIterator = $this->proxyCollection->getIterator();
            if ($this->proxySelection) {
                /** @var \ArrayIterator $proxyIterator */
                $proxyIterator = $this->proxySelection
                    ->selection($this->proxyCollection, $item)
                    ->getIterator();
            }

            $proxyIterator->rewind();
        }

        do {
            $proxy = null;
            if ($proxyIterator && $proxyIterator->valid()) {
                /** @var ProxyInterface $proxy */
                $proxy = $proxyIterator->current();
                $proxyIterator->next();
            }

            if ($proxyIterator && !$proxy) {
                $response = new Response();

                break;
            }

            $request = Request::create();
            $request->withMethod(Http::GET)
                ->withUri($item->getItemUri());

            if ($proxy) {
                $request->withProxy($proxy);
            }

            $retry = $this->config->getRetry();
            if ($retry) {
                $request->withMiddleware(new RetryMiddleware($retry));
            }

            try {
                $response = $this->httpClient->send($request);
            } catch (ConnectionErrorException $exception) {
                $response = new Response();
            }

            $delay = $this->config->getDelay();
            if ($delay[0]) {
                /** @var positive-int $delayInSeconds */
                $delayInSeconds = rand($delay[0], $delay[1]);
                sleep($delayInSeconds);
            }

            if ($proxy) {
                $proxy->setLastUse(new DateTime());
                if ($response->getStatusCode() === 0) {
                    $proxy->incrementAttempts();
                }
                if ($this->proxyStorage) {
                    $this->proxyStorage->save($proxy);
                }
            }

            if ($response->getStatusCode() !== 0) {
                break;
            }
        } while (true);

        $sizeAllow = true;
        $sizeLimit = false;
        $sizeLimits = $this->config->getSizeLimits();
        if (isset($sizeLimits[$this->getMime()])) {
            $sizeLimit = $sizeLimits[$this->getMime()];
        }
        if (isset($sizeLimits[$this->getMime($response->getBody()->getContentType())])) {
            $sizeLimit = $sizeLimits[$this->getMime($response->getBody()->getContentType())];
        }
        if ($sizeLimit !== false) {
            $sizeAllow = $sizeLimit >= $response->getBody()->getSize();
        }

        $item->setStatusCode($response->getStatusCode())
            ->setReasonPhrase($response->getReasonPhrase())
            ->setDownloadStatus($response->isSuccess() && $sizeAllow)
            ->setContentType($response->getBody()->getContentType());

        if ($sizeAllow) {
            $body = $response->getBody()->getRaw();
            $this->itemStorage->saveBody($item, $response->getBody()->getRaw());
            $item->setBody($body);
        }

        $this->logger->log(
            $item->getDownloadStatus() ? LevelInterface::INFO : LevelInterface::WARNING,
            'Item {{uri}}: statusCode={{statusCode}} contentType={{contentType}} size={{size}}',
            [
                'uri' => $item->getItemUri()->maskedUri(),
                'statusCode' => $item->getStatusCode(),
                'contentType' => $item->getContentType(),
                'size' => $response->getBody()->getSize(),
            ]
        );

        if ($item->getDownloadStatus()) {
            $this->output->writeln(
                '    StatusCode=<color=green>{{statusCode}}</>, '
                . 'ContentType=<color=green>{{contentType}}</>, Size=<color=green>{{size|memory}}</>',
                [
                    'uri' => $item->getItemUri()->maskedUri(),
                    'statusCode' => $item->getStatusCode(),
                    'contentType' => $item->getContentType(),
                    'size' => $response->getBody()->getSize(),
                ],
                null,
                OutputInterface::VERBOSE_HIGHTEST
            );

            $this->uriParse($item);
        } elseif ($sizeAllow) {
            $this->output->writeln(
                '    <color=red>- Status={{statusCode}} ({{reasonPhrase}})</>',
                [
                    'statusCode' => $item->getStatusCode(),
                    'reasonPhrase' => $item->getReasonPhrase(),
                ],
                null,
                OutputInterface::VERBOSE_HIGHT
            );
        } else {
            $this->output->writeln(
                '    <color=red>- Размер {{size|memory}} превышает разрешенный в {{sizeLimit|memory}}</>',
                [
                    'size' => $response->getBody()->getSize(),
                    'sizeLimit' => $sizeLimit,
                ],
                null,
                OutputInterface::VERBOSE_HIGHT
            );
        }

        $item->free();
    }

    /**
     * Добавить ограничение
     *
     * @return $this
     */
    public function addRestriction(RestrictionInterface $restriction)
    {
        $this->restrictions[] = $restriction;

        return $this;
    }

    /**
     * Возвращает ограничения
     */
    public function getRestrictions(): RestrictionCollectionInterface
    {
        return $this->restrictions;
    }

    /**
     * Устанавливает парсер uri для обхода (в зависимости от типа контента)
     *
     * @return $this
     */
    public function setUriParser(UriParserInterface $parser, ?string $mime = null)
    {
        $this->uriParsers[$this->getMime($mime)] = $parser;

        return $this;
    }

    /**
     * Проверяет наличие парсера uri (в зависимости от типа контента)
     */
    public function hasUriParser(?string $mime = null): bool
    {
        return array_key_exists($this->getMime($mime), $this->uriParsers);
    }

    /**
     * Удаляет парсер uri (в зависимости от типа контента)
     *
     * @return $this
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
     * Установить коллекцию прокси
     *
     * @return $this
     */
    public function setProxyCollection(?ProxyCollectionInterface $collection)
    {
        $this->proxyCollection = $collection;

        return $this;
    }

    /**
     * Установить хранилище прокси
     *
     * @return $this
     */
    public function setProxyStorage(?ProxyStorageInterface $proxyStorage)
    {
        $this->proxyStorage = $proxyStorage;
        if (!$proxyStorage) {
            $this->setProxyCollection(null);

            return $this;
        }

        $this->setProxyCollection($proxyStorage->load());

        return $this;
    }

    /**
     * Объект подбора подходящих прокси
     *
     * @return $this
     */
    public function setProxySelection(?ProxySelectionInterface $proxySelection)
    {
        $this->proxySelection = $proxySelection;

        return $this;
    }

    /**
     * Добавить uri в обработку
     *
     * @param string|UriInterface $uri
     */
    public function addUri($uri): ItemCollectionInterface
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
            $this->itemStorage->save($this->items);
        }
        $this->output->writeln('', [], null, OutputInterface::VERBOSE_HIGHT);

        return $this->items;
    }

    /**
     * @inheritDoc
     */
    public function restart(): ItemCollectionInterface
    {
        foreach ($this->items as $item) {
            assert($item instanceof ItemInterface);
            $item->setDownloadStatus(null);
        }

        return $this->items;
    }

    /**
     * @inheritDoc
     */
    protected function getResultCollection(): ItemCollectionInterface
    {
        return $this->items->getDownloaded();
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
        $uri = $uri->withFragment('');
        if ($this->items->has($uri->uri())) {
            $this->output->writeln(
                '        <color=blue>= Уже в очереди</>',
                [],
                null,
                OutputInterface::VERBOSE_HIGHTEST
            );
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

        $collection = $parser->parse($item, $this->output, $this->logger);
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
}
