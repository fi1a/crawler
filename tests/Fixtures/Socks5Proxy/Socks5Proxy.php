<?php

declare(strict_types=1);

namespace Fi1a\Unit\Crawler\Fixtures\Socks5Proxy;

use function exec;
use function posix_kill;

use const SIGKILL;

/**
 * Управление Socks5 proxy для тестирования
 */
class Socks5Proxy implements Socks5ProxyInterface
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
    public function start(int $port, string $username, string $password): bool
    {
        if ($this->started) {
            return true;
        }

        $logFilePath = '/tmp/socks5-proxy.log';
        if (is_file($logFilePath)) {
            unlink($logFilePath);
        }

        $output = [];
        exec(
            'node ' . str_replace(' ', '\ ', __DIR__)
            . '/socks5-proxy.js ' . $port . ' ' . $username . ' '
            . $password . ' >> ' . $logFilePath . ' 2>&1 & echo $!',
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

        echo file_get_contents('/tmp/socks5-proxy.log');

        return true;
    }
}
