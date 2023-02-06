<?php

declare(strict_types=1);

namespace Fi1a\Crawler;

use Fi1a\Collection\DataType\ValueObjectInterface;
use Fi1a\Console\IO\OutputInterface;
use Fi1a\Http\UriInterface;
use Fi1a\HttpClient\ConfigInterface as HttpClientConfigInterface;

/**
 * Конфигурация
 */
interface ConfigInterface extends ValueObjectInterface
{
    public const VERBOSE_NONE = OutputInterface::VERBOSE_NONE;

    public const VERBOSE_NORMAL = OutputInterface::VERBOSE_NORMAL;

    public const VERBOSE_HIGHT = OutputInterface::VERBOSE_HIGHT;

    public const VERBOSE_HIGHTEST = OutputInterface::VERBOSE_HIGHTEST;

    public const VERBOSE_DEBUG = OutputInterface::VERBOSE_DEBUG;

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

    /**
     * Конфигурация http-клиента
     *
     * @return $this
     */
    public function setHttpClientConfig(HttpClientConfigInterface $config);

    /**
     * Возвращает конфигурацию http-клиента
     */
    public function getHttpClientConfig(): HttpClientConfigInterface;

    /**
     * Установить обработчик запросов
     *
     * @return $this
     */
    public function setHttpClientHandler(string $handler);

    /**
     * Вернуть обработчик запросов
     */
    public function getHttpClientHandler(): string;

    /**
     * Установить уровень подробности вывода
     *
     * @return $this
     */
    public function setVerbose(int $verbose);

    /**
     * Вернуть уровень подробности вывода
     */
    public function getVerbose(): int;

    /**
     * Установить канал логирования
     *
     * @return $this
     */
    public function setLogChannel(string $logChannel);

    /**
     * Вернуть канал логирования
     */
    public function getLogChannel(): string;

    /**
     * Установить параметр определяющий через какое новое кол-во элементов сохранять элементы в хранилище
     *
     * @return $this
     */
    public function setSaveAfterQuantity(int $quantity);

    /**
     * Возвращает параметр определяющий через какое новое кол-во элементов сохранять элементы в хранилище
     */
    public function getSaveAfterQuantity(): int;

    /**
     * Установить время жизни элементов в хранилище
     *
     * @return $this
     */
    public function setLifetime(int $lifeTime);

    /**
     * Вернуть время жизни элементов в хранилище
     */
    public function getLifetime(): int;

    /**
     * Установить паузу между запросами
     *
     * @param int|array<array-key, int>|mixed $delay
     *
     * @return mixed
     */
    public function setDelay($delay);

    /**
     * Вернуть паузу между запросами
     *
     * @return array<array-key, int>
     */
    public function getDelay(): array;

    /**
     * Установить ограничение на загружаемый файл по типу контента
     *
     * @return $this
     */
    public function setSizeLimit(int $sizeLimit, ?string $mime = null);

    /**
     * Возвращает ограничения на загружаемые файлы по типу контента
     *
     * @return array<string, int>
     */
    public function getSizeLimits(): array;

    /**
     * Установить кол-во попыток запросов к адресу при http ошибки
     *
     * @return $this
     */
    public function setRetry(int $retry);

    /**
     * Вернуть кол-во попыток запросов к адресу при http ошибки
     */
    public function getRetry(): int;
}
