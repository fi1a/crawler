<?php

declare(strict_types=1);

namespace Fi1a\Unit\Crawler\Fixtures\Server;

/**
 * Управление сервером для тестирования
 */
interface ServerInterface
{
    /**
     * Запускает сервер
     */
    public function start(int $httpsPort): bool;

    /**
     * Останавливает сервер
     */
    public function stop(): bool;
}
