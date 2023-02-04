<?php

declare(strict_types=1);

namespace Fi1a\Crawler\Proxy;

use ErrorException;
use Fi1a\Filesystem\Adapters\LocalAdapter as FilesystemLocalAdapter;
use Fi1a\Filesystem\FileInterface;
use Fi1a\Filesystem\Filesystem;
use Fi1a\Filesystem\FilesystemInterface;
use Fi1a\Filesystem\FolderInterface;

use const JSON_UNESCAPED_UNICODE;

/**
 * Адаптер хранилища в файловой системе
 */
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

    public function __construct(string $path)
    {
        $adapter = new FilesystemLocalAdapter($path);
        $this->filesystem = new Filesystem($adapter);

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
    public function save(ProxyCollectionInterface $collection): bool
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
