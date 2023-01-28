<?php

declare(strict_types=1);

namespace Fi1a\Crawler;

use Fi1a\Http\UriInterface;

/**
 * Страница
 */
class Page implements PageInterface
{
    /**
     * @var UriInterface
     */
    protected $uri;

    /**
     * @var int|null
     */
    protected $statusCode;

    /**
     * @var mixed
     */
    protected $body;

    /**
     * @var string|null
     */
    protected $contentType;

    /**
     * @var UriInterface|null
     */
    protected $convertedUri;

    /**
     * @var mixed
     */
    protected $prepareBody;

    public function __construct(UriInterface $uri)
    {
        $this->uri = $uri;
    }

    /**
     * @inheritDoc
     */
    public function getUri(): UriInterface
    {
        return $this->uri;
    }

    /**
     * @inheritDoc
     */
    public function setStatusCode(int $statusCode)
    {
        $this->statusCode = $statusCode;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getStatusCode(): ?int
    {
        return $this->statusCode;
    }

    /**
     * @inheritDoc
     */
    public function setBody($body)
    {
        $this->body = $body;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getBody()
    {
        return $this->body;
    }

    /**
     * @inheritDoc
     */
    public function setContentType(?string $contentType)
    {
        $this->contentType = $contentType;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getContentType(): ?string
    {
        return $this->contentType;
    }

    /**
     * @inheritDoc
     */
    public function setConvertedUri(UriInterface $uri)
    {
        $this->convertedUri = $uri;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getConvertedUri(): ?UriInterface
    {
        return $this->convertedUri;
    }

    /**
     * @inheritDoc
     */
    public function getAbsoluteUri(UriInterface $uri): UriInterface
    {
        if (!$uri->getHost()) {
            $uri = $uri->withScheme($this->getUri()->getScheme())
                ->withHost($this->getUri()->getHost())
                ->withPort($this->getUri()->getPort());
        }
        if (mb_substr($uri->getPath(), 0, 1) !== '/') {
            $uri = $uri->withPath($this->getUri()->getNormalizedBasePath() . $uri->getPath());
        }

        return $uri;
    }

    /**
     * @inheritDoc
     */
    public function setPrepareBody($body)
    {
        $this->prepareBody = $body;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getPrepareBody()
    {
        return $this->prepareBody;
    }
}
