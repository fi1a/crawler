<?php

declare(strict_types=1);

namespace Fi1a\Crawler;

use DateTime;
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
     * @var bool|null
     */
    protected $downloadStatus;

    /**
     * @var bool|null
     */
    protected $processStatus;

    /**
     * @var bool|null
     */
    protected $writeStatus;

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

    /**
     * @var DateTime|null
     */
    protected $expire;

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
    public function setDownloadStatus(?bool $status)
    {
        $this->downloadStatus = $status;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getDownloadStatus(): ?bool
    {
        return $this->downloadStatus;
    }

    /**
     * @inheritDoc
     */
    public function setProcessStatus(?bool $status)
    {
        $this->processStatus = $status;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getProcessStatus(): ?bool
    {
        return $this->processStatus;
    }

    /**
     * @inheritDoc
     */
    public function setWriteStatus(?bool $status)
    {
        $this->writeStatus = $status;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getWriteStatus(): ?bool
    {
        return $this->writeStatus;
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
    public function reset()
    {
        $this->free();
        $this->statusCode = null;
        $this->reasonPhrase = null;
        $this->downloadStatus = null;
        $this->processStatus = null;
        $this->writeStatus = null;
        $this->contentType = null;
        $this->newItemUri = null;
        $this->expire = null;

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
    public function expiresAt(?DateTime $dateTime)
    {
        $this->expire = $dateTime;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function expiresAfter(?int $lifetime)
    {
        return $this->expiresAt($lifetime ? (new DateTime())->setTimestamp(time() + $lifetime) : null);
    }

    /**
     * @inheritDoc
     */
    public function getExpire(): ?DateTime
    {
        return $this->expire;
    }

    /**
     * @inheritDoc
     */
    public function isExpired(): bool
    {
        return $this->expire && $this->expire->getTimestamp() <= time();
    }

    /**
     * @inheritDoc
     */
    public function getAbsoluteUri(UriInterface $uri): UriInterface
    {
        if (!$uri->host()) {
            $uri = $uri->withScheme($this->getItemUri()->scheme())
                ->withHost($this->getItemUri()->host())
                ->withPort($this->getItemUri()->port());
        }
        if (mb_substr($uri->path(), 0, 1) !== '/') {
            $tokens = [];
            $parts = explode('/', $this->getItemUri()->normalizedBasePath() . $uri->path());
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
        $expire = $this->getExpire();

        return [
            'itemUri' => $this->getItemUri()->uri(),
            'allow' => $this->isAllow(),
            'statusCode' => $this->getStatusCode(),
            'reasonPhrase' => $this->getReasonPhrase(),
            'downloadStatus' => $this->getDownloadStatus(),
            'processStatus' => $this->getProcessStatus(),
            'writeStatus' => $this->getWriteStatus(),
            'contentType' => $this->getContentType(),
            'newItemUri' =>  $newItemUri ? $newItemUri->uri() : null,
            'expire' => $expire ? $expire->format('d.m.Y H:i:s') : null,
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
        if (isset($fields['downloadStatus']) && is_bool($fields['downloadStatus'])) {
            $item->setDownloadStatus($fields['downloadStatus']);
        }
        if (isset($fields['processStatus']) && is_bool($fields['processStatus'])) {
            $item->setProcessStatus($fields['processStatus']);
        }
        if (isset($fields['writeStatus']) && is_bool($fields['writeStatus'])) {
            $item->setWriteStatus($fields['writeStatus']);
        }
        if (isset($fields['contentType']) && is_string($fields['contentType'])) {
            $item->setContentType($fields['contentType']);
        }
        if (isset($fields['newItemUri']) && is_string($fields['newItemUri'])) {
            $item->setNewItemUri(new Uri($fields['newItemUri']));
        }
        if (isset($fields['expire']) && is_string($fields['expire'])) {
            $item->expiresAt(DateTime::createFromFormat('d.m.Y H:i:s', $fields['expire']));
        }

        return $item;
    }
}
