<?php

declare(strict_types=1);

namespace Fi1a\Crawler;

use Fi1a\Collection\Collection;

/**
 * Коллекция элементов обхода
 */
class ItemCollection extends Collection implements ItemCollectionInterface
{
    /**
     * @inheritDoc
     */
    public function __construct(?array $data = null)
    {
        parent::__construct(ItemInterface::class, $data);
    }

    /**
     * @inheritDoc
     */
    public function getDownloaded()
    {
        return $this->filter(function (ItemInterface $item) {
            return $item->getDownloadStatus() === true;
        });
    }

    /**
     * @inheritDoc
     */
    public function getProcessed()
    {
        return $this->filter(function (ItemInterface $item) {
            return $item->getProcessStatus() === true;
        });
    }

    /**
     * @inheritDoc
     */
    public function getWrited()
    {
        return $this->filter(function (ItemInterface $item) {
            return $item->getWriteStatus() === true;
        });
    }

    /**
     * @inheritDoc
     */
    public function getImages()
    {
        return $this->filter(function (ItemInterface $item) {
            return $item->isImage();
        });
    }

    /**
     * @inheritDoc
     */
    public function getFiles()
    {
        return $this->filter(function (ItemInterface $item) {
            return $item->isFile();
        });
    }

    /**
     * @inheritDoc
     */
    public function getPages()
    {
        return $this->filter(function (ItemInterface $item) {
            return $item->isPage();
        });
    }

    /**
     * @inheritDoc
     */
    public function getCss()
    {
        return $this->filter(function (ItemInterface $item) {
            return $item->isCss();
        });
    }

    /**
     * @inheritDoc
     */
    public function getJs()
    {
        return $this->filter(function (ItemInterface $item) {
            return $item->isJs();
        });
    }
}
