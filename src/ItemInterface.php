<?php

declare(strict_types=1);

namespace Fi1a\Crawler;

use Fi1a\Http\UriInterface;

/**
 * Элемент
 */
interface ItemInterface
{
    /**
     * Возвращает uri
     */
    public function getItemUri(): UriInterface;

    /**
     * Устанавливает код статуса ответа
     *
     * @return $this
     */
    public function setStatusCode(?int $statusCode);

    /**
     * Возвращает код статуса ответа
     */
    public function getStatusCode(): ?int;

    /**
     * Текст причины ассоциированный с кодом статуса
     *
     * @return $this
     */
    public function setReasonPhrase(?string $reasonPhrase);

    /**
     * Текст причины ассоциированный с кодом статуса
     */
    public function getReasonPhrase(): ?string;

    /**
     * Запрос выполнен успешно или нет
     *
     * @return $this
     */
    public function setDownloadSuccess(bool $success);

    /**
     * Запрос выполнен успешно или нет
     */
    public function isDownloadSuccess(): bool;

    /**
     * Обработка выполнена успешно или нет
     *
     * @return $this
     */
    public function setProcessSuccess(bool $success);

    /**
     * Обработка выполнена успешно или нет
     */
    public function isProcessSuccess(): bool;

    /**
     * Запись выполнена успешно или нет
     *
     * @return $this
     */
    public function setWriteSuccess(bool $success);

    /**
     * Запись выполнена успешно или нет
     */
    public function isWriteSuccess(): bool;

    /**
     * Разрешено к обработке или нет
     *
     * @return $this
     */
    public function setAllow(bool $allow);

    /**
     * Разрешено к обработке или нет
     */
    public function isAllow(): bool;

    /**
     * Установить тело ответа
     *
     * @return $this
     */
    public function setBody(string $body);

    /**
     * Вернуть тело ответа
     */
    public function getBody(): ?string;

    /**
     * Установить подготовленное тело ответа
     *
     * @param mixed $body
     *
     * @return $this
     */
    public function setPrepareBody($body);

    /**
     * Вернуть подготовленное тело ответа
     *
     * @return mixed
     */
    public function getPrepareBody();

    /**
     * Очищает тело запроса
     *
     * @return $this
     */
    public function free();

    /**
     * Установить тип контента
     *
     * @return $this
     */
    public function setContentType(?string $contentType);

    /**
     * Вернуть тип контента
     */
    public function getContentType(): ?string;

    /**
     * Установить новый uri
     *
     * @return $this
     */
    public function setNewItemUri(UriInterface $newItemUri);

    /**
     * Вернуть новый uri
     */
    public function getNewItemUri(): ?UriInterface;

    /**
     * Возвращает абсолютный путь относительно элемента
     */
    public function getAbsoluteUri(UriInterface $uri): UriInterface;

    /**
     * @return array{allow: bool, contentType: (string|null), downloadSuccess: bool, itemUri: string,
     * newItemUri: (string|null), processSuccess: bool, reasonPhrase: (string|null), statusCode: (int|null),
     * writeSuccess: bool}
     */
    public function toArray(): array;

    /**
     * Из массива
     *
     * @param array<array-key, mixed> $fields
     *
     * @return ItemInterface
     */
    public static function fromArray(array $fields);
}
