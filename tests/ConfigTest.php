<?php

declare(strict_types=1);

namespace Fi1a\Unit\Crawler;

use Fi1a\Crawler\Config;
use Fi1a\Unit\Crawler\TestCases\TestCase;

/**
 * Конфигурация
 */
class ConfigTest extends TestCase
{
    /**
     * Добавить точку входа, с которой начинается обход
     */
    public function testStartUri(): void
    {
        $config = new Config();
        $this->assertCount(0, $config->getStartUri());
        $config->addStartUri($this->getUrl('/start1.html'));
        $config->addStartUri($this->getUrl('/start2.html'));
        $this->assertCount(2, $config->getStartUri());
    }

    /**
     * Добавить точку входа (исключение при пустом хосте)
     */
    public function testStartUriHostException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $config = new Config();
        $config->addStartUri('/start1.html');
    }
}
