<?php

declare(strict_types=1);

namespace Fi1a\Crawler;

use Fi1a\Http\Uri;
use Fi1a\Http\UriInterface;
use InvalidArgumentException;

/**
 * Элемент
 */
class Item implements ItemInterface
{
    /**
     * @var UriInterface
     */
    protected $itemUri;

    /**
     * @var int|null
     */
    protected $statusCode;

    /**
     * @var string|null
     */
    protected $reasonPhrase;

    /**
     * @var bool
     */
    protected $downloadSuccess = false;

    /**
     * @var bool
     */
    protected $processSuccess = false;

    /**
     * @var bool
     */
    protected $writeSuccess = false;

    /**
     * @var bool
     */
    protected $allow = false;

    /**
     * @var string|null
     */
    protected $contentType;

    /**
     * @var string|null
     */
    protected $body;

    /**
     * @var mixed|null
     */
    protected $prepareBody;

    /**
     * @var UriInterface|null
     */
    protected $newItemUri;

    public function __construct(UriInterface $itemUri)
    {
        $this->itemUri = $itemUri;
    }

    /**
     * @inheritDoc
     */
    public function getItemUri(): UriInterface
    {
        return $this->itemUri;
    }

    /**
     * @inheritDoc
     */
    public function setStatusCode(?int $statusCode)
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
    public function setReasonPhrase(?string $reasonPhrase)
    {
        $this->reasonPhrase = $reasonPhrase;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getReasonPhrase(): ?string
    {
        return $this->reasonPhrase;
    }

    /**
     * @inheritDoc
     */
    public function setDownloadSuccess(bool $success)
    {
        $this->downloadSuccess = $success;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function isDownloadSuccess(): bool
    {
        return $this->downloadSuccess;
    }

    /**
     * @inheritDoc
     */
    public function setProcessSuccess(bool $success)
    {
        $this->processSuccess = $success;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function isProcessSuccess(): bool
    {
        return $this->processSuccess;
    }

    /**
     * @inheritDoc
     */
    public function setWriteSuccess(bool $success)
    {
        $this->writeSuccess = $success;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function isWriteSuccess(): bool
    {
        return $this->writeSuccess;
    }

    /**
     * @inheritDoc
     */
    public function setAllow(bool $allow)
    {
        $this->allow = $allow;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function isAllow(): bool
    {
        return $this->allow;
    }

    /**
     * @inheritDoc
     */
    public function setBody(string $body)
    {
        $this->body = $body;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getBody(): ?string
    {
        return $this->body;
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

    /**
     * @inheritDoc
     */
    public function free()
    {
        $this->body = null;
        $this->prepareBody = null;

        return $this;
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
    public function setNewItemUri(UriInterface $newItemUri)
    {
        $this->newItemUri = $newItemUri;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getNewItemUri(): ?UriInterface
    {
        return $this->newItemUri;
    }

    /**
     * @inheritDoc
     */
    public function getAbsoluteUri(UriInterface $uri): UriInterface
    {
        if (!$uri->getHost()) {
            $uri = $uri->withScheme($this->getItemUri()->getScheme())
                ->withHost($this->getItemUri()->getHost())
                ->withPort($this->getItemUri()->getPort());
        }
        if (mb_substr($uri->getPath(), 0, 1) !== '/') {
            $tokens = [];
            $parts = explode('/', $this->getItemUri()->getNormalizedBasePath() . $uri->getPath());
            foreach ($parts as $part) {
                if (!$part) {
                    continue;
                }
                if ($part === '.' || $part === '..') {
                    if ($part === '..' && count($tokens) !== 0) {
                        array_pop($tokens);
                    }

                    continue;
                }

                $tokens[] = $part;
            }

            $uri = $uri->withPath('/' . implode('/', $tokens));
        }

        return $uri;
    }

    /**
     * @inheritDoc
     */
    public function toArray(): array
    {
        $newItemUri = $this->getNewItemUri();

        return [
            'itemUri' => $this->getItemUri()->getUri(),
            'allow' => $this->isAllow(),
            'statusCode' => $this->getStatusCode(),
            'reasonPhrase' => $this->getReasonPhrase(),
            'downloadSuccess' => $this->isDownloadSuccess(),
            'processSuccess' => $this->isProcessSuccess(),
            'writeSuccess' => $this->isWriteSuccess(),
            'contentType' => $this->getContentType(),
            'newItemUri' =>  $newItemUri ? $newItemUri->getUri() : null,
        ];
    }

    /**
     * @inheritDoc
     */
    public static function fromArray(array $fields)
    {
        if (!isset($fields['itemUri']) || !is_string($fields['itemUri']) || !$fields['itemUri']) {
            throw new InvalidArgumentException('Не передан обязательный элемент itemUri');
        }
        $item = new Item(new Uri($fields['itemUri']));
        if (isset($fields['statusCode']) && is_int($fields['statusCode'])) {
            $item->setStatusCode($fields['statusCode']);
        }
        if (isset($fields['allow']) && is_bool($fields['allow'])) {
            $item->setAllow($fields['allow']);
        }
        if (isset($fields['reasonPhrase']) && is_string($fields['reasonPhrase'])) {
            $item->setReasonPhrase($fields['reasonPhrase']);
        }
        if (isset($fields['downloadSuccess']) && is_bool($fields['downloadSuccess'])) {
            $item->setDownloadSuccess($fields['downloadSuccess']);
        }
        if (isset($fields['processSuccess']) && is_bool($fields['processSuccess'])) {
            $item->setProcessSuccess($fields['processSuccess']);
        }
        if (isset($fields['writeSuccess']) && is_bool($fields['writeSuccess'])) {
            $item->setWriteSuccess($fields['writeSuccess']);
        }
        if (isset($fields['contentType']) && is_string($fields['contentType'])) {
            $item->setContentType($fields['contentType']);
        }
        if (isset($fields['newItemUri']) && is_string($fields['newItemUri'])) {
            $item->setNewItemUri(new Uri($fields['newItemUri']));
        }

        return $item;
    }
}
