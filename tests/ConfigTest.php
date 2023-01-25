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
    public function testStartUrls(): void
    {
        $config = new Config();
        $this->assertCount(0, $config->getStartUrls());
        $config->addStartUrl($this->getUrl('/start1.html'));
        $config->addStartUrl($this->getUrl('/start2.html'));
        $this->assertCount(2, $config->getStartUrls());
    }
}
