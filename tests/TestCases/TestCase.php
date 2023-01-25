<?php

declare(strict_types=1);

namespace Fi1a\Unit\Crawler\TestCases;

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
}
