<?php

declare(strict_types=1);

namespace Fi1a\Crawler;

use Fi1a\Collection\DataType\ValueObjectInterface;
use Fi1a\Http\UriInterface;

/**
 * Конфигурация
 */
interface ConfigInterface extends ValueObjectInterface
{
    /**
     * Добавить точку входа, с которой начинается обход
     *
     * @return $this
     */
    public function addStartUri(string $startUri);

    /**
     * Возвращает точки входа, с которых начинается обход
     *
     * @return array<int, UriInterface>
     */
    public function getStartUri(): array;
}
