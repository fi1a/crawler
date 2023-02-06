<?php

declare(strict_types=1);

namespace Fi1a\Crawler;

use Fi1a\Console\IO\OutputInterface;
use Fi1a\Crawler\UriTransformers\SiteUriTransformer;
use Fi1a\Crawler\UriTransformers\UriTransformerInterface;

/**
 * Операция преобразования
 */
class ProcessOperation extends AbstractOperation
{
    /**
     * @var UriTransformerInterface|null
     */
    protected $uriTransformer;

    /**
     * @inheritDoc
     */
    protected function operateOnItem(ItemInterface $item, int $index): void
    {
        if ($item->getProcessStatus() !== null) {
            $this->output->writeln(
                '{{index}}/{{count}} <color=blue>Uri {{uri|unescape}} уже преобразован</>',
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

        $newItemUri = $this->uriTransformer
            ? $this->uriTransformer->transform($item, $this->output, $this->logger)
            : $item->getItemUri();
        $item->setNewItemUri($newItemUri)
            ->setProcessStatus(true);

        if ($newItemUri->uri() === $item->getItemUri()->uri()) {
            $this->output->writeln(
                '    <color=blue>- Без преобразования</>',
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
            '    <color=yellow>+ Преобразован в {{|unescape}}</>',
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
     * @inheritDoc
     */
    protected function getResultCollection(): ItemCollectionInterface
    {
        return $this->items->getProcessed();
    }

    /**
     * @inheritDoc
     */
    protected function beforeOperate(): void
    {
        $this->output->setVerbose($this->config->getVerbose());
        $this->addDefaultUriTransformer();

        $this->output->writeln('<bg=white;color=black>Шаг преобразования (process)</>');
        $this->output->writeln('');
        $this->output->writeln('runId: {{}}', [$this->runId]);
        $this->logger->info('Преобразование');
    }

    /**
     * @inheritDoc
     */
    protected function afterOperate(): void
    {
        $this->logger->info('Преобразование завершено');
    }

    /**
     * Установить класс преобразователь адресов из внешних во внутренние
     *
     * @return $this
     */
    public function setUriTransformer(UriTransformerInterface $uriTransformer)
    {
        $this->uriTransformer = $uriTransformer;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function restart(): ItemCollectionInterface
    {
        foreach ($this->items as $item) {
            assert($item instanceof ItemInterface);
            $item->setProcessStatus(null);
        }

        return $this->items;
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
}
