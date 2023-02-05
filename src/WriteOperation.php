<?php

declare(strict_types=1);

namespace Fi1a\Crawler;

use Fi1a\Console\IO\ConsoleOutputInterface;
use Fi1a\Console\IO\OutputInterface;
use Fi1a\Crawler\ItemStorages\ItemStorageInterface;
use Fi1a\Crawler\PrepareItem\PrepareHtmlItem;
use Fi1a\Crawler\PrepareItem\PrepareItemInterface;
use Fi1a\Crawler\Writers\WriterInterface;
use Fi1a\Http\Mime;
use Fi1a\Log\LoggerInterface;
use InvalidArgumentException;

/**
 * Операция записи
 */
class WriteOperation extends AbstractOperation
{
    /**
     * @var array<string, PrepareItemInterface>
     */
    protected $prepareItems = [];

    /**
     * @var array<string, WriterInterface>
     */
    protected $writers = [];

    public function __construct(
        ConfigInterface $config,
        ConsoleOutputInterface $output,
        LoggerInterface $logger,
        ItemStorageInterface $itemStorage,
        string $runId
    ) {
        parent::__construct($config, $output, $logger, $itemStorage, $runId);
    }

    /**
     * @inheritDoc
     */
    public function validate(): void
    {
        if (!count($this->writers)) {
            throw new InvalidArgumentException('Не задан обработчик записывающий результат');
        }
    }

    /**
     * @inheritDoc
     */
    protected function operateOnItem(ItemInterface $item, int $index): void
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
            $body = $this->itemStorage->getBody($item);
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
                        $item->setPrepareBody($prepareItem->prepare(
                            $item,
                            $this->items,
                            $this->output,
                            $this->logger
                        ));
                    }
                }
                $writer = $this->writers[$this->getMime()];
                $mime = $item->getContentType();
                if ($mime && $this->hasWriter($mime)) {
                    $writer = $this->writers[$this->getMime($mime)];
                }
                if ($writer->write($item, $this->output, $this->logger)) {
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

    /**
     * @inheritDoc
     */
    protected function getResultCollection(): ItemCollectionInterface
    {
        return $this->items->getWrited();
    }

    /**
     * @inheritDoc
     */
    protected function beforeOperate(): void
    {
        $this->addDefaultPrepareItem();
        $this->output->writeln('<bg=white;color=black>Шаг записи (write)</>');
        $this->output->writeln('');
        $this->output->writeln('runId: {{}}', [$this->runId]);
        $this->logger->info('Запись');
    }

    /**
     * @inheritDoc
     */
    protected function afterOperate(): void
    {
        $this->logger->info('Запись завершено');
    }

    /**
     * Установить класс подготавливающий элемент
     *
     * @return $this
     */
    public function setPrepareItem(PrepareItemInterface $prepareItem, ?string $mime = null)
    {
        $this->prepareItems[$this->getMime($mime)] = $prepareItem;

        return $this;
    }

    /**
     * Проверяет наличие класса подготавливающего элемент (в зависимости от типа контента)
     */
    public function hasPrepareItem(?string $mime = null): bool
    {
        return array_key_exists($this->getMime($mime), $this->prepareItems);
    }

    /**
     * Удаляет класс подготавливающий элемент (в зависимости от типа контента)
     *
     * @return $this
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
     * Установить класс записывающий результат обхода
     *
     * @return $this
     */
    public function setWriter(WriterInterface $writer, ?string $mime = null)
    {
        $this->writers[$this->getMime($mime)] = $writer;

        return $this;
    }

    /**
     * Проверяет наличие класса записывающего результат обхода (в зависимости от типа контента)
     */
    public function hasWriter(?string $mime = null): bool
    {
        return array_key_exists($this->getMime($mime), $this->writers);
    }

    /**
     * Удаляет класс записывающий результат обхода (в зависимости от типа контента)
     *
     * @return $this
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
     * Добавить класс подготавливающий элемент
     */
    protected function addDefaultPrepareItem(): void
    {
        if (!count($this->prepareItems)) {
            $this->setPrepareItem(new PrepareHtmlItem(), Mime::HTML);
        }
    }
}
