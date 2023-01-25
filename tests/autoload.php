<?php

declare(strict_types=1);

use Fi1a\Unit\Crawler\Fixtures\Server\Server;

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/define.php';

$server = new Server();
$server->start((int) WEB_SERVER_HTTPS_PORT);

register_shutdown_function(static function () use ($server) {
    $server->stop();
});
