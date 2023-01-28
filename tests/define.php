<?php

declare(strict_types=1);

if (!function_exists('pcntl_signal')) {
    define('SIGKILL', 9);
}
