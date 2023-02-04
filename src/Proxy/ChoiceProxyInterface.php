<?php

declare(strict_types=1);

namespace Fi1a\Crawler\Proxy;

use Fi1a\Crawler\ItemInterface;

/**
 * Выбор прокси для запроса
 */
interface ChoiceProxyInterface
{
    /**
     * Выбор прокси
     *
     * @return ProxyInterface|false
     */
    public function choice(ItemInterface $item);
}
