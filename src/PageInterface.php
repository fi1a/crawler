<?php

declare(strict_types=1);

namespace Fi1a\Crawler;

use Fi1a\Http\UriInterface;

/**
 * Interface ResultUriInterface
 */
interface PageInterface
{
    /**
     * Возвращает uri
     */
    public function getUri(): UriInterface;

    /**
     * Устанавливает код статуса ответа
     *
     * @return $this
     */
    public function setStatusCode(int $statusCode);

    /**
     * Возвращает код статуса ответа
     */
    public function getStatusCode(): ?int;

    /**
     * Установить тело ответа
     *
     * @param mixed $body
     *
     * @return $this
     */
    public function setBody($body);

    /**
     * Вернуть тело ответа
     *
     * @return mixed
     */
    public function getBody();

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
     * Установить преобразованное Uri
     *
     * @return $this
     */
    public function setConvertedUri(UriInterface $uri);

    /**
     * Вернуть преобразованное Uri
     */
    public function getConvertedUri(): ?UriInterface;

    /**
     * Возвращает абсолютный путь относительно страницы
     */
    public function getAbsoluteUri(UriInterface $uri): UriInterface;

    /**
     * Возвращает относительный путь относительно страницы
     */
    public function getRelativeUri(UriInterface $uri): UriInterface;

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
     * Номер страницы в очереди на обработку
     */
    public function getIndex(): int;
}
