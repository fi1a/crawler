<?php

declare(strict_types=1);

namespace Fi1a\Unit\Crawler\TestCases;

use Fi1a\Crawler\Page;
use Fi1a\Crawler\PageInterface;
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

        if (!is_dir($this->runtimeFolder)) {
            return;
        }

        $directoryIterator = new RecursiveDirectoryIterator(
            $this->runtimeFolder,
            RecursiveDirectoryIterator::SKIP_DOTS
        );
        $filesIterator = new RecursiveIteratorIterator($directoryIterator, RecursiveIteratorIterator::CHILD_FIRST);
        foreach ($filesIterator as $file) {
            if ($file->isDir()) {
                rmdir($file->getRealPath());
            } else {
                unlink($file->getRealPath());
            }
        }
        rmdir($this->runtimeFolder);
    }

    /**
     * Возвращает url адрес
     */
    protected function getUrl(string $url): string
    {
        return 'https://' . self::HOST . $url;
    }

    /**
     * Возвращает страницу
     */
    protected function getPage(): PageInterface
    {
        $page = new Page(new Uri($this->getUrl('/index.html')), 0);

        $page->setConvertedUri(new Uri('/index.html'));
        $page->setBody(file_get_contents(__DIR__ . '/../Fixtures/Server/public/index.html'));
        $page->setPrepareBody(file_get_contents(__DIR__ . '/../Fixtures/Server/equals/index.html'));

        return $page;
    }
}
