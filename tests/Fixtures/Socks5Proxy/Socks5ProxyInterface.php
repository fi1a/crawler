<?php

declare(strict_types=1);

namespace Fi1a\Unit\Crawler\Fixtures\Socks5Proxy;

/**
 * Управление Socks5 proxy для тестирования
 */
interface Socks5ProxyInterface
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
