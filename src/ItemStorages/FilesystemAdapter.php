<?php

declare(strict_types=1);

namespace Fi1a\Crawler\ItemStorages;

use ErrorException;
use Fi1a\Crawler\Item;
use Fi1a\Crawler\ItemCollection;
use Fi1a\Crawler\ItemCollectionInterface;
use Fi1a\Crawler\ItemInterface;
use Fi1a\Filesystem\Adapters\LocalAdapter as FilesystemLocalAdapter;
use Fi1a\Filesystem\FileInterface;
use Fi1a\Filesystem\Filesystem;
use Fi1a\Filesystem\FilesystemInterface;
use Fi1a\Filesystem\FolderInterface;

use const JSON_UNESCAPED_UNICODE;

/**
 * Хранение элементов в файловой системе
 */
class FilesystemAdapter implements StorageAdapterInterface
{
    /**
     * @var FolderInterface
     */
    protected $pathDir;

    /**
     * @var FileInterface
     */
    protected $jsonFile;

    /**
     * @var FolderInterface
     */
    protected $bodyDir;

    /**
     * @var FilesystemInterface
     */
    protected $filesystem;

    public function __construct(string $path)
    {
        $adapter = new FilesystemLocalAdapter($path);
        $this->filesystem = new Filesystem($adapter);

        $this->pathDir = $this->filesystem->factoryFolder('.');
        $this->jsonFile = $this->filesystem->factoryFile('./items.json');
        $this->bodyDir = $this->filesystem->factoryFolder('./body');
        $this->createDirs();
    }

    /**
     * @inheritDoc
     */
    public function load(): ItemCollectionInterface
    {
        $collection = new ItemCollection();

        if (!$this->jsonFile->isExist() || ($content = $this->jsonFile->read()) === false) {
            return $collection;
        }

        /** @var array<array-key, array<array-key, mixed>>|false $json */
        $json = json_decode($content, true);

        if (is_array($json)) {
            foreach ($json as $jsonItem) {
                $item = Item::fromArray($jsonItem);
                $collection->set($item->getItemUri()->uri(), $item);
            }
        }

        return $collection;
    }

    /**
     * @inheritDoc
     */
    public function getBody(ItemInterface $item)
    {
        $file = $this->filesystem->factoryFile($this->bodyDir->getPath() . '/' . $this->getBodyFileName($item));

        if (!$file->isExist() || !$file->canRead()) {
            return false;
        }

        return $file->read();
    }

    /**
     * @inheritDoc
     */
    public function saveBody(ItemInterface $item, string $body): bool
    {
        $file = $this->filesystem->factoryFile($this->bodyDir->getPath() . '/' . $this->getBodyFileName($item));

        return $file->write($body) !== false;
    }

    /**
     * @inheritDoc
     */
    public function save(ItemCollectionInterface $collection): bool
    {
        $json = [];
        foreach ($collection as $item) {
            assert($item instanceof ItemInterface);
            $json[] = $item->toArray();
        }

        return $this->jsonFile->write(json_encode($json, JSON_UNESCAPED_UNICODE)) !== false;
    }

    /**
     * @inheritDoc
     */
    public function clear(): bool
    {
        if (!$this->pathDir->isExist()) {
            return true;
        }

        $this->pathDir->delete();
        $this->createDirs();

        return true;
    }

    /**
     * Название файла для тела ответа
     */
    protected function getBodyFileName(ItemInterface $item): string
    {
        return md5($item->getItemUri()->uri());
    }

    /**
     * Создание директорий
     */
    protected function createDirs(): void
    {
        if (!$this->pathDir->isExist()) {
            if ($this->pathDir->make() === false) {
                throw new ErrorException(
                    sprintf('Не удалось создать директорию "%s"', $this->pathDir->getPath())
                );
            }
        }
        if (!$this->pathDir->canWrite()) {
            throw new ErrorException(
                sprintf('Нет прав на запись в директорию "%s"', $this->pathDir->getPath())
            );
        }
        if (!$this->bodyDir->isExist()) {
            $this->bodyDir->make();
        }
        if (!$this->bodyDir->canWrite()) {
            throw new ErrorException(
                sprintf('Нет прав на запись в директорию "%s"', $this->pathDir->getPath())
            );
        }
    }
}
