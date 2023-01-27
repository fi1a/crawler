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
}
