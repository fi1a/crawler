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
use Fi1a\Console\IO\ConsoleOutputInterface;
use Fi1a\Console\IO\OutputInterface;
use Fi1a\Console\IO\Style\ColorInterface;
use Fi1a\Crawler\ItemStorages\ItemStorageInterface;
use Fi1a\Log\LoggerInterface;

/**
 * Абстрактный класс действия
 */
abstract class AbstractOperation implements OperationInterface
{
    /**
     * @var ConfigInterface
     */
    protected $config;

    /**
     * @var ConsoleOutputInterface
     */
    protected $output;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var ItemStorageInterface
     */
    protected $itemStorage;

    /**
     * @var ItemCollectionInterface
     */
    protected $items;

    /**
     * @var QueueInterface
     */
    protected $queue;

    /**
     * @var string
     */
    protected $runId;

    /**
     * Выполнение операции на элементе
     */
    abstract protected function operateOnItem(ItemInterface $item, int $index): void;

    /**
     * Возвращает коллекцию обработанных элементов на шаге
     */
    abstract protected function getResultCollection(): ItemCollectionInterface;

    /**
     * До выполнения операции
     */
    abstract protected function beforeOperate(): void;

    /**
     * После выполнения операции
     */
    abstract protected function afterOperate(): void;

    public function __construct(
        ConfigInterface $config,
        ConsoleOutputInterface $output,
        LoggerInterface $logger,
        ItemStorageInterface $itemStorage,
        string $runId
    ) {
        $this->config = $config;
        $this->output = $output;
        $this->logger = $logger;
        $this->itemStorage = $itemStorage;
        $this->runId = $runId;
        $this->setItems(new ItemCollection());
    }

    /**
     * @inheritDoc
     */
    public function validate(): void
    {
    }

    /**
     * @inheritDoc
     */
    public function operate(): ItemCollectionInterface
    {
        $this->validate();
        $this->output->setVerbose($this->config->getVerbose());
        $this->beforeOperate();
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

            $this->operateOnItem($item, $index);

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

        $itemCollection = $this->getResultCollection();

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
        $this->afterOperate();

        return $this->items;
    }

    /**
     * @inheritDoc
     */
    public function setItems(ItemCollectionInterface $items)
    {
        $this->items = $items;
        $this->queue = new Queue();
        foreach ($items as $item) {
            assert($item instanceof ItemInterface);
            $this->queue->addEnd($item);
        }

        return $this;
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
        $this->itemStorage->save($items);
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
     * Возвращатет mime тип для парсера uri
     */
    protected function getMime(?string $mime = null): string
    {
        if (!$mime) {
            return '*';
        }

        return $mime;
    }
}
