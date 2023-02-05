<?php

declare(strict_types=1);

namespace Fi1a\Crawler;

use Fi1a\Collection\Collection;
use Fi1a\Http\Mime;

use const PATHINFO_EXTENSION;

/**
 * Коллекция элементов
 */
class ItemCollection extends Collection implements ItemCollectionInterface
{
    /**
     * @var array<array-key, string>
     */
    protected static $imageMimes = [
        'image/gif', 'image/jpeg', 'image/png', 'image/bmp', 'image/tiff',
    ];

    /**
     * @var array<array-key, string>
     */
    protected static $pageMimes = [Mime::HTML, Mime::XHTML];

    /**
     * @var array<array-key, string>
     */
    protected static $jsMimes = ['application/javascript', 'text/javascript'];

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
        return $this->filter(function ($item) {
            assert($item instanceof ItemInterface);

            return $item->getDownloadStatus() === true;
        });
    }

    /**
     * @inheritDoc
     */
    public function getProcessed()
    {
        return $this->filter(function ($item) {
            assert($item instanceof ItemInterface);

            return $item->getProcessStatus() === true;
        });
    }

    /**
     * @inheritDoc
     */
    public function getWrited()
    {
        return $this->filter(function ($item) {
            assert($item instanceof ItemInterface);

            return $item->getWriteStatus() === true;
        });
    }

    /**
     * @inheritDoc
     */
    public function getImages()
    {
        $mimes = static::$imageMimes;

        return $this->filter(function ($item) use ($mimes) {
            assert($item instanceof ItemInterface);

            return in_array($item->getContentType(), $mimes);
        });
    }

    /**
     * @inheritDoc
     */
    public function getFiles()
    {
        $mimes = array_merge(static::$imageMimes, static::$pageMimes, static::$jsMimes);

        return $this->filter(function ($item) use ($mimes) {
            assert($item instanceof ItemInterface);
            $extension = pathinfo($item->getItemUri()->uri(), PATHINFO_EXTENSION);

            return !in_array($item->getContentType(), $mimes) && mb_strtolower($extension) !== 'css';
        });
    }

    /**
     * @inheritDoc
     */
    public function getPages()
    {
        $mimes = static::$pageMimes;

        return $this->filter(function ($item) use ($mimes) {
            assert($item instanceof ItemInterface);

            return in_array($item->getContentType(), $mimes);
        });
    }

    /**
     * @inheritDoc
     */
    public function getCss()
    {
        return $this->filter(function ($item) {
            assert($item instanceof ItemInterface);

            $extension = pathinfo($item->getItemUri()->uri(), PATHINFO_EXTENSION);

            return mb_strtolower($extension) === 'css';
        });
    }

    /**
     * @inheritDoc
     */
    public function getJs()
    {
        $mimes = static::$jsMimes;

        return $this->filter(function ($item) use ($mimes) {
            assert($item instanceof ItemInterface);

            return in_array($item->getContentType(), $mimes);
        });
    }
}
