<?php

declare(strict_types=1);

namespace Fi1a\Crawler;

/**
 * Интерфейс действия
 */
interface OperationInterface
{
    /**
     * Выполнить действие
     */
    public function operate(): ItemCollectionInterface;

    /**
     * Установить коллекцию элементов
     *
     * @return $this
     */
    public function setItems(ItemCollectionInterface $items);

    /**
     * Валидация состояния операции
     */
    public function validate(): void;
}
