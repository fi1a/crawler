<?php

declare(strict_types=1);

namespace Fi1a\Unit\Crawler\Fixtures\Server;

use function exec;
use function posix_kill;

use const SIGKILL;

/**
 * Управление сервером для тестирования
 */
class Server implements ServerInterface
{
    /**
     * @var bool
     */
    private $started = false;

    /**
     * @var int
     */
    private $pid;

    /**
     * @inheritDoc
     */
    public function start(int $httpsPort): bool
    {
        if ($this->started) {
            return true;
        }

        $logFilePath = '/tmp/server.log';
        if (is_file($logFilePath)) {
            unlink($logFilePath);
        }

        $output = [];
        exec(
            'node ' . str_replace(' ', '\ ', __DIR__)
            . '/server.js ' . $httpsPort . ' >> ' . $logFilePath . ' 2>&1 & echo $!',
            $output
        );
        sleep(1);
        $this->pid = (int) $output[0];

        $this->started = true;

        return true;
    }

    /**
     * @inheritDoc
     */
    public function stop(): bool
    {
        if (!$this->started) {
            return true;
        }

        posix_kill($this->pid, SIGKILL);
        $this->started = false;

        echo file_get_contents('/tmp/server.log');

        return true;
    }
}
