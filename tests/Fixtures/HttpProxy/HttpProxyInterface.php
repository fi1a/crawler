<?php

declare(strict_types=1);

namespace Fi1a\Unit\Crawler\Fixtures\HttpProxy;

/**
 * Управление Http proxy для тестирования
 */
interface HttpProxyInterface
{
    /**
     * Запускает сервер
     */
    public function start(int $port, string $username, string $password): bool;

    /**
     * Останавливает сервер
     */
    public function stop(): bool;
}
