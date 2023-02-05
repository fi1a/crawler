<?php

declare(strict_types=1);

use Fi1a\Unit\Crawler\Fixtures\HttpProxy\HttpProxy;
use Fi1a\Unit\Crawler\Fixtures\Server\Server;
use Fi1a\Unit\Crawler\Fixtures\Socks5Proxy\Socks5Proxy;

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/define.php';

$server = new Server();
$server->start((int) WEB_SERVER_HTTPS_PORT);

$httpProxy = new HttpProxy();
$httpProxy->start((int) HTTP_PROXY_PORT, HTTP_PROXY_USERNAME, HTTP_PROXY_PASSWORD);

$socks5Proxy = new Socks5Proxy();
$socks5Proxy->start((int) SOCKS5_PROXY_PORT, SOCKS5_PROXY_USERNAME, SOCKS5_PROXY_PASSWORD);

register_shutdown_function(static function () use ($server, $httpProxy, $socks5Proxy) {
    $server->stop();
    $httpProxy->stop();
    $socks5Proxy->stop();
});
