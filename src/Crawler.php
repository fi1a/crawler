<?php

declare(strict_types=1);

namespace Fi1a\Crawler;

use Fi1a\Console\IO\ConsoleOutput;
use Fi1a\Console\IO\ConsoleOutputInterface;
use Fi1a\Console\IO\Formatter;
use Fi1a\Crawler\ItemStorages\ItemStorageInterface;
use Fi1a\Crawler\PrepareItem\PrepareItemInterface;
use Fi1a\Crawler\Proxy\ProxyCollectionInterface;
use Fi1a\Crawler\Proxy\ProxyStorageInterface;
use Fi1a\Crawler\Proxy\Selections\ProxySelectionInterface;
use Fi1a\Crawler\Restrictions\RestrictionCollectionInterface;
use Fi1a\Crawler\Restrictions\RestrictionInterface;
use Fi1a\Crawler\UriParsers\UriParserInterface;
use Fi1a\Crawler\UriTransformers\UriTransformerInterface;
use Fi1a\Crawler\Writers\WriterInterface;
use Fi1a\Log\Logger;
use Fi1a\Log\LoggerInterface;

/**
 * Web Crawler
 */
class Crawler implements CrawlerInterface
{
    /**
     * @var ConsoleOutputInterface
     */
    protected $output;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var ItemCollectionInterface
     */
    protected $items;

    /**
     * @var ItemStorageInterface
     */
    protected $itemStorage;

    /**
     * @var bool
     */
    protected $needLoadFromStorage = true;

    /**
     * @var DownloadOperation
     */
    protected $downloadOperation;

    /**
     * @var ProcessOperation
     */
    protected $processOperation;

    /**
     * @var WriteOperation
     */
    protected $writeOperation;

    public function __construct(
        ConfigInterface $config,
        ItemStorageInterface $itemStorage,
        ?ProxyStorageInterface $proxyStorage = null,
        ?ConsoleOutputInterface $output = null,
        ?LoggerInterface $logger = null
    ) {
        if ($output === null) {
            $output = new ConsoleOutput(new Formatter());
        }
        $this->output = $output;
        $this->output->setVerbose($config->getVerbose());
        if ($logger === null) {
            /** @var LoggerInterface|false $logger */
            $logger = logger($config->getLogChannel());
            if ($logger === false) {
                $logger = new Logger($config->getLogChannel());
            }
        }
        $this->logger = $logger;
        $runId = uniqid();
        $this->logger->withContext(['runId' => $runId]);
        $this->itemStorage = $itemStorage;
        $this->items = new ItemCollection();
        $this->downloadOperation = new DownloadOperation(
            $config,
            $output,
            $logger,
            $itemStorage,
            $runId,
            $proxyStorage
        );
        $this->processOperation = new ProcessOperation(
            $config,
            $output,
            $logger,
            $itemStorage,
            $runId
        );
        $this->writeOperation = new WriteOperation(
            $config,
            $output,
            $logger,
            $itemStorage,
            $runId
        );
    }

    /**
     * @inheritDoc
     */
    public function run()
    {
        $this->downloadOperation->validate();
        $this->processOperation->validate();
        $this->writeOperation->validate();

        return $this->download()->process()->write();
    }

    /**
     * @inheritDoc
     */
    public function download()
    {
        return $this->operate($this->downloadOperation);
    }

    /**
     * @inheritDoc
     */
    public function process()
    {
        return $this->operate($this->processOperation);
    }

    /**
     * @inheritDoc
     */
    public function write()
    {
        return $this->operate($this->writeOperation);
    }

    /**
     * @inheritDoc
     */
    public function addRestriction(RestrictionInterface $restriction)
    {
        $this->downloadOperation->addRestriction($restriction);

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getRestrictions(): RestrictionCollectionInterface
    {
        return $this->downloadOperation->getRestrictions();
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
        $this->downloadOperation->setUriParser($parser, $mime);

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function hasUriParser(?string $mime = null): bool
    {
        return $this->downloadOperation->hasUriParser($mime);
    }

    /**
     * @inheritDoc
     */
    public function removeUriParser(?string $mime = null)
    {
        $this->downloadOperation->removeUriParser($mime);

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function setUriTransformer(UriTransformerInterface $uriTransformer)
    {
        $this->processOperation->setUriTransformer($uriTransformer);

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function setPrepareItem(PrepareItemInterface $prepareItem, ?string $mime = null)
    {
        $this->writeOperation->setPrepareItem($prepareItem, $mime);

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function hasPrepareItem(?string $mime = null): bool
    {
        return $this->writeOperation->hasPrepareItem($mime);
    }

    /**
     * @inheritDoc
     */
    public function removePrepareItem(?string $mime = null)
    {
        $this->writeOperation->removePrepareItem($mime);

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function setWriter(WriterInterface $writer, ?string $mime = null)
    {
        $this->writeOperation->setWriter($writer, $mime);

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function hasWriter(?string $mime = null): bool
    {
        return $this->writeOperation->hasWriter($mime);
    }

    /**
     * @inheritDoc
     */
    public function removeWriter(?string $mime = null)
    {
        $this->writeOperation->removeWriter($mime);

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function clearStorageData()
    {
        $this->itemStorage->clear();

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function setProxyStorage(?ProxyStorageInterface $proxyStorage)
    {
        $this->downloadOperation->setProxyStorage($proxyStorage);

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function setProxySelection(?ProxySelectionInterface $proxySelection)
    {
        $this->downloadOperation->setProxySelection($proxySelection);

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function setProxyCollection(?ProxyCollectionInterface $collection)
    {
        $this->downloadOperation->setProxyCollection($collection);

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function addUri($uri)
    {
        $this->items = $this->downloadOperation->addUri($uri);

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function restartDownload()
    {
        $this->restartWrite();
        $this->restartProcess();

        return $this->restart($this->downloadOperation);
    }

    /**
     * @inheritDoc
     */
    public function restartProcess()
    {
        $this->restartWrite();

        return $this->restart($this->processOperation);
    }

    /**
     * @inheritDoc
     */
    public function restartWrite()
    {
        return $this->restart($this->writeOperation);
    }

    /**
     * @inheritDoc
     */
    public function restartErrors()
    {
        $this->initFromStorage();
        foreach ($this->items as $item) {
            assert($item instanceof ItemInterface);
            if ($item->getDownloadStatus() !== false || !$item->isAllow()) {
                continue;
            }

            $item->setDownloadStatus(null);
            $item->setProcessStatus(null);
            $item->setWriteStatus(null);
        }
        $this->itemStorage->save($this->items);

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function setRequestFactory(callable $factory)
    {
        $this->downloadOperation->setRequestFactory($factory);

        return $this;
    }

    /**
     * Перезапуск операции
     *
     * @return $this
     */
    protected function restart(OperationInterface $operation)
    {
        $this->initFromStorage();
        $operation->setItems($this->items);
        $this->items = $operation->restart();
        $this->itemStorage->save($this->items);

        return $this;
    }

    /**
     * Выполняет действие
     *
     * @return $this
     */
    protected function operate(OperationInterface $operation)
    {
        $this->initFromStorage();
        $operation->setItems($this->items);
        $this->items = $operation->operate();

        return $this;
    }

    /**
     * Инициализация из хранилища
     */
    protected function initFromStorage(): void
    {
        if (!$this->needLoadFromStorage) {
            return;
        }

        $this->output->writeln('<bg=white;color=black>Извлечение данных из хранилища</>');
        $this->logger->info('Извлечение данных из хранилища');
        $this->items = $this->itemStorage->load();
        $this->logger->info(
            '{{count|declension("Извлечен", "Извлечено", "Извлечено")}} {{count}} '
            . '{{count|declension("элемент", "элемента", "элементов")}}',
            ['count' => $this->items->count()]
        );
        $this->output->writeln(
            '{{count|declension("Извлечен", "Извлечено", "Извлечено")}} {{count}} '
            . '{{count|declension("элемент", "элемента", "элементов")}}',
            ['count' => $this->items->count()]
        );
        $this->output->writeln();
        $this->needLoadFromStorage = false;
    }
}
