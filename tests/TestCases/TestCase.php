<?php

declare(strict_types=1);

namespace Fi1a\Unit\Crawler\TestCases;

use Fi1a\Crawler\Page;
use Fi1a\Crawler\PageInterface;
use Fi1a\Http\Uri;
use PHPUnit\Framework\TestCase as PHPUnitTestCase;

/**
 * TestCase
 */
class TestCase extends PHPUnitTestCase
{
    protected const HOST = WEB_SERVER_HOST . ':' . WEB_SERVER_HTTPS_PORT;

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
        $page = new Page(new Uri($this->getUrl('/index.html')));

        $page->setConvertedUri(new Uri('/index.html'));
        $page->setBody(file_get_contents(__DIR__ . '/../Fixtures/Server/public/index.html'));

        return $page;
    }
}
