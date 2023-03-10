<?php

declare(strict_types=1);

namespace Fi1a\Crawler\Proxy;

use DateTime;
use Fi1a\HttpClient\Proxy\ProxyInterface as HttpClientProxyInterface;

/**
 * Прокси
 */
interface ProxyInterface extends HttpClientProxyInterface
{
    /**
     * Установить идентификатор
     *
     * @return $this
     */
    public function setId(?string $id);

    /**
     * Вернуть идентификатор
     *
     * @return mixed
     */
    public function getId(): ?string;

    /**
     * Генерирует идентификтор
     */
    public function generateId(): string;

    /**
     * Установить число попыток с ошибкой
     *
     * @return $this
     */
    public function setAttempts(int $attempts);

    /**
     * Вернуть число попыток с ошибкой
     */
    public function getAttempts(): int;

    /**
     * Увеличить число попыток с ошибкой на 1
     *
     * @return $this
     */
    public function incrementAttempts();

    /**
     * Сбросить число попыток с ошибкой
     *
     * @return $this
     */
    public function resetAttempts();

    /**
     * Установить активность
     *
     * @return $this
     */
    public function setActive(bool $active);

    /**
     * Активна прокси или нет
     */
    public function isActive(): bool;

    /**
     * Преобразование в массив
     *
     * @return array<array-key, mixed>
     */
    public function toArray(): array;

    /**
     * Установить дату и время последнего использования
     *
     * @return $this
     */
    public function setLastUse(?DateTime $lastUse);

    /**
     * Вернуть дату и время последнего использования
     */
    public function getLastUse(): ?DateTime;

    /**
     * Фабричный метод
     *
     * @param array<array-key, mixed> $item
     */
    public static function factory(array $item): ProxyInterface;
}
