<?php

declare(strict_types=1);

namespace Fi1a\Crawler\Proxy;

use Fi1a\Collection\AbstractInstanceCollection;
use InvalidArgumentException;

/**
 * Коллекция прокси
 */
class ProxyCollection extends AbstractInstanceCollection implements ProxyCollectionInterface
{
    /**
     * @inheritDoc
     */
    protected function factory($key, $value)
    {
        if (!is_array($value)) {
            throw new InvalidArgumentException('Для создания прокси нужен массив полей');
        }

        return Proxy::factory($value);
    }

    /**
     * @inheritDoc
     */
    protected function isInstance($value): bool
    {
        return $value instanceof ProxyInterface;
    }
}
