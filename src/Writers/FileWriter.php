<?php

declare(strict_types=1);

namespace Fi1a\Crawler\Writers;

use ErrorException;
use Fi1a\Console\IO\ConsoleOutputInterface;
use Fi1a\Crawler\ItemInterface;
use Fi1a\Filesystem\Adapters\LocalAdapter;
use Fi1a\Filesystem\FileInterface;
use Fi1a\Filesystem\Filesystem;
use Fi1a\Filesystem\FilesystemInterface;
use Fi1a\Filesystem\FolderInterface;
use Fi1a\Log\LoggerInterface;
use InvalidArgumentException;

/**
 * Записывает результат обхода в файл
 */
class FileWriter implements WriterInterface
{
    /**
     * @var FolderInterface
     */
    protected $pathDir;

    /**
     * @var string|null
     */
    protected $urlPrefix;

    /**
     * @var FilesystemInterface
     */
    protected $filesystem;

    /**
     * @var bool
     */
    protected $clear;

    public function __construct(string $path, ?string $urlPrefix = null, bool $clearOnStart = true)
    {
        $this->urlPrefix = $urlPrefix;
        if (!$path) {
            throw new InvalidArgumentException('Не передан путь до директории');
        }
        $adapter = new LocalAdapter($path);
        $this->filesystem = new Filesystem($adapter);
        $this->pathDir = $this->filesystem->factoryFolder('./');
        $this->createDir();
        $this->clear = $clearOnStart;
    }

    /**
     * @inheritDoc
     */
    public function write(
        ItemInterface $item,
        ConsoleOutputInterface $output,
        LoggerInterface $logger
    ): bool {
        if ($this->clear) {
            $this->clearDir();
            $this->clear = false;
        }
        $file = $this->filesystem->factoryFile($this->getFileName($item));
        $folder = $file->getParent();
        if ($folder && !$folder->isExist() && !$folder->make()) {
            throw new ErrorException(
                sprintf('Не удалось создать директорию "%s"', $folder->getPath())
            );
        }

        return $this->doWrite($file, (string) $item->getPrepareBody()) !== false;
    }

    /**
     * Осуществляет запись в файл
     *
     * @return int|false
     *
     * @codeCoverageIgnore
     */
    protected function doWrite(FileInterface $file, string $content)
    {
        return $file->write($content);
    }

    /**
     * Возвращает название файла
     */
    protected function getFileName(ItemInterface $item): string
    {
        $newItemUri = $item->getNewItemUri();
        if (!$newItemUri) {
            throw new ErrorException(
                sprintf(
                    'Пустой преобразованный uri для ссылки (%s)',
                    htmlspecialchars($item->getItemUri()->uri())
                )
            );
        }

        $uri = $newItemUri->uri();

        if ($this->urlPrefix && mb_stripos($uri, $this->urlPrefix) === 0) {
            $uri = mb_substr($uri, mb_strlen($this->urlPrefix));
        }

        return rtrim($this->pathDir->getPath(), '/') . '/' . ltrim($uri, '/');
    }

    /**
     * Создать директории
     */
    protected function createDir(): void
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

    /**
     * Очищает директорию
     *
     * @throws ErrorException
     */
    protected function clearDir(): void
    {
        $this->pathDir->delete();
        $this->createDir();
    }
}
