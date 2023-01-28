<?php

declare(strict_types=1);

namespace Fi1a\Crawler\Writers;

use ErrorException;
use Fi1a\Crawler\PageInterface;
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

    public function __construct(string $path)
    {
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
    public function write(PageInterface $page): bool
    {
        $convertedUri = $page->getConvertedUri();
        if (!$convertedUri) {
            throw new ErrorException(
                sprintf(
                    'Пустой преобразованный uri для ссылки (%s)',
                    htmlspecialchars($page->getUri()->getUri())
                )
            );
        }
        $fileName = $this->path . $convertedUri->getUri();

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
            (string) $page->getPrepareBody()
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
}
