<?php

declare(strict_types=1);

namespace Fi1a\Crawler\Writers;

use ErrorException;
use Fi1a\Crawler\ItemInterface;
use InvalidArgumentException;

/**
 * Записывает результат обхода в файл
 */
class FileWriter implements WriterInterface
{
    /**
     * @var string
     */
    protected $path;

    /**
     * @var string|null
     */
    protected $urlPrefix;

    public function __construct(string $path, ?string $urlPrefix = null)
    {
        $this->urlPrefix = $urlPrefix;
        if (!$path) {
            throw new InvalidArgumentException('Не передан путь до директории');
        }
        $this->path = $path;
        if (!is_dir($this->path)) {
            @mkdir($this->path, 0777, true);
        }
        if (!is_dir($this->path)) {
            throw new ErrorException(sprintf('Не удалось создать папку "%s"', $this->path));
        }
        if (!is_writable($this->path)) {
            throw new ErrorException(sprintf('Нет прав на запись в папку "%s"', $this->path));
        }
    }

    /**
     * @inheritDoc
     */
    public function write(ItemInterface $item): bool
    {
        $fileName = $this->getFileName($item);

        $pathInfo = pathinfo($fileName);
        if ($pathInfo['dirname'] && !is_dir($pathInfo['dirname'])) {
            @mkdir($pathInfo['dirname'], 0777, true);
            if (!is_dir($pathInfo['dirname'])) {
                throw new ErrorException(
                    sprintf('Не удалось создать папку "%s"', $pathInfo['dirname'])
                );
            }
        }

        return $this->doWrite(
            $fileName,
            (string) $item->getPrepareBody()
        ) !== false;
    }

    /**
     * Осуществляет запись в файл
     *
     * @return int|false
     *
     * @codeCoverageIgnore
     */
    protected function doWrite(string $fileName, string $content)
    {
        return file_put_contents($fileName, $content);
    }

    protected function getFileName(ItemInterface $item): string
    {
        $newItemUri = $item->getNewItemUri();
        if (!$newItemUri) {
            throw new ErrorException(
                sprintf(
                    'Пустой преобразованный uri для ссылки (%s)',
                    htmlspecialchars($item->getItemUri()->getUri())
                )
            );
        }
        $uri = $newItemUri->getUri();

        if ($this->urlPrefix && mb_stripos($uri, $this->urlPrefix) === 0) {
            $uri = mb_substr($uri, mb_strlen($this->urlPrefix));
        }

        return rtrim($this->path, '/') . '/' . ltrim($uri, '/');
    }
}
