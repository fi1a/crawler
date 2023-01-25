<?php

declare(strict_types=1);

namespace Fi1a\Crawler;

use Fi1a\Collection\DataType\ValueObjectInterface;

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
    public function addStartUrl(?string $startUrl);

    /**
     * Возвращает точки входа, с которых начинается обход
     *
     * @return array<int, string>
     */
    public function getStartUrls(): array;
}
