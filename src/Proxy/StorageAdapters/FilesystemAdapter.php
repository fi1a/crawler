<?php

declare(strict_types=1);

namespace Fi1a\Crawler\Proxy\StorageAdapters;

use ErrorException;
use Fi1a\Crawler\Proxy\Proxy;
use Fi1a\Crawler\Proxy\ProxyCollection;
use Fi1a\Crawler\Proxy\ProxyCollectionInterface;
use Fi1a\Crawler\Proxy\ProxyInterface;
use Fi1a\Filesystem\FileInterface;
use Fi1a\Filesystem\FilesystemInterface;
use Fi1a\Filesystem\FolderInterface;

use const JSON_UNESCAPED_UNICODE;

class FilesystemAdapter implements StorageAdapterInterface
{
    /**
     * @var FilesystemInterface
     */
    protected $filesystem;

    /**
     * @var FolderInterface
     */
    protected $pathDir;

    /**
     * @var FileInterface
     */
    protected $jsonFile;

    public function __construct(FilesystemInterface $filesystem)
    {
        $this->filesystem = $filesystem;

        $this->pathDir = $this->filesystem->factoryFolder('.');
        $this->jsonFile = $this->filesystem->factoryFile('./proxy.json');
        $this->createDirs();
    }

    /**
     * @inheritDoc
     */
    public function load(): ProxyCollectionInterface
    {
        $collection = new ProxyCollection();

        if (!$this->jsonFile->isExist() || ($content = $this->jsonFile->read()) === false) {
            return $collection;
        }

        /** @var array<array-key, array<array-key, mixed>>|false $json */
        $json = json_decode($content, true);

        if (is_array($json)) {
            foreach ($json as $jsonItem) {
                $collection[] = Proxy::factory($jsonItem);
            }
        }

        return $collection;
    }

    /**
     * @inheritDoc
     */
    public function save(ProxyInterface $proxy): bool
    {
        $collection = $this->load();

        if ($proxy->getId() === null) {
            $proxy->generateId();
            $collection[] = $proxy;

            return $this->doSave($collection);
        }

        /**
         * @var array-key $index
         * @var ProxyInterface $collectionProxy
         */
        foreach ($collection as $index => $collectionProxy) {
            if ($collectionProxy->getId() === $proxy->getId()) {
                $collection[$index] = $proxy;
            }
        }

        return $this->doSave($collection);
    }

    /**
     * Сохранение коллекции прокси
     */
    protected function doSave(ProxyCollectionInterface $collection): bool
    {
        $json = [];
        foreach ($collection as $proxy) {
            assert($proxy instanceof ProxyInterface);
            $json[] = $proxy->toArray();
        }

        return $this->jsonFile->write(json_encode($json, JSON_UNESCAPED_UNICODE)) !== false;
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
    }
}
