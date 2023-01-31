<?php

declare(strict_types=1);

namespace Fi1a\Unit\Crawler\TestCases;

use Fi1a\Crawler\Item;
use Fi1a\Crawler\ItemInterface;
use Fi1a\Http\Uri;
use PHPUnit\Framework\TestCase as PHPUnitTestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

/**
 * TestCase
 */
class TestCase extends PHPUnitTestCase
{
    protected const HOST = WEB_SERVER_HOST . ':' . WEB_SERVER_HTTPS_PORT;

    protected $runtimeFolder = __DIR__ . '/../runtime';

    /**
     * @inheritDoc
     */
    protected function tearDown(): void
    {
        parent::tearDown();
        $this->deleteDir($this->runtimeFolder);
    }

    /**
     * Удаляет директорию и вложенные элементы
     */
    protected function deleteDir(string $path)
    {
        if (!is_dir($path)) {
            return;
        }

        $directoryIterator = new RecursiveDirectoryIterator(
            $path,
            RecursiveDirectoryIterator::SKIP_DOTS
        );
        $filesIterator = new RecursiveIteratorIterator($directoryIterator, RecursiveIteratorIterator::CHILD_FIRST);
        foreach ($filesIterator as $file) {
            if ($file->isDir()) {
                rmdir($file->getRealPath());

                continue;
            }

            unlink($file->getRealPath());
        }
        rmdir($path);
    }

    /**
     * Возвращает url адрес
     */
    protected function getUrl(string $url): string
    {
        return 'https://' . self::HOST . $url;
    }

    /**
     * Возвращает элемент
     */
    protected function getItem(): ItemInterface
    {
        $item = new Item(new Uri($this->getUrl('/index.html')), 0);

        $item->setNewItemUri(new Uri('/index.html'));
        $item->setBody(file_get_contents(__DIR__ . '/../Fixtures/Server/public/index.html'));
        $item->setPrepareBody(file_get_contents(__DIR__ . '/../Fixtures/Server/equals/index.html'));

        return $item;
    }
}
