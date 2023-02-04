<?php

declare(strict_types=1);

namespace Fi1a\Crawler\Proxy;

use Fi1a\HttpClient\Proxy\HttpProxy;
use Fi1a\HttpClient\Proxy\ProxyInterface as HttpClientProxyInterface;
use Fi1a\HttpClient\Proxy\Socks5Proxy;
use InvalidArgumentException;

/**
 * Прокси
 */
class Proxy implements ProxyInterface
{
    /**
     * @var HttpClientProxyInterface
     */
    protected $proxy;

    /**
     * @var int
     */
    protected $attempts = 0;

    /**
     * @var bool
     */
    protected $active = true;

    protected function __construct(HttpClientProxyInterface $proxy)
    {
        $this->proxy = $proxy;
    }

    /**
     * @inheritDoc
     */
    public function getHost(): string
    {
        return $this->proxy->getHost();
    }

    /**
     * @inheritDoc
     */
    public function setHost(string $host)
    {
        $this->proxy->setHost($host);

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getPort(): int
    {
        return $this->proxy->getPort();
    }

    /**
     * @inheritDoc
     */
    public function setPort(int $port)
    {
        $this->proxy->setPort($port);

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getUserName(): ?string
    {
        return $this->proxy->getUserName();
    }

    /**
     * @inheritDoc
     */
    public function setUserName(?string $username)
    {
        $this->proxy->setUserName($username);

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getPassword(): ?string
    {
        return $this->proxy->getPassword();
    }

    /**
     * @inheritDoc
     */
    public function setPassword(?string $password)
    {
        $this->proxy->setPassword($password);

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function setAttempts(int $attempts)
    {
        $this->attempts = $attempts;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getAttempts(): int
    {
        return $this->attempts;
    }

    /**
     * @inheritDoc
     */
    public function incrementAttempts()
    {
        $this->attempts++;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function resetAttempts()
    {
        $this->attempts = 0;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function setActive(bool $active)
    {
        $this->active = $active;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function isActive(): bool
    {
        return $this->active;
    }

    /**
     * @inheritDoc
     */
    public function toArray(): array
    {
        $type = 'http';
        if ($this->proxy instanceof Socks5Proxy) {
            $type = 'socks5';
        }

        return [
            'type' => $type,
            'host' => $this->proxy->getHost(),
            'port' => $this->proxy->getPort(),
            'userName' => $this->proxy->getUserName(),
            'password' => $this->proxy->getPassword(),
            'attempts' => $this->getAttempts(),
            'active' => $this->isActive(),
        ];
    }

    /**
     * @inheritDoc
     */
    public static function factory(array $item): ProxyInterface
    {
        if (!isset($item['type'])) {
            throw new InvalidArgumentException('Не передан тип прокси');
        }
        $type = mb_strtolower((string) $item['type']);
        if (!in_array($type, ['http', 'socks5'])) {
            throw new InvalidArgumentException(sprintf('Неизвестный тип прокси %s', $type));
        }

        $host = isset($item['host']) ? (string) $item['host'] : '';
        $port = isset($item['port']) ? (int) $item['port'] :  0;
        $userName = isset($item['userName']) && $item['userName'] ? (string) $item['userName'] : null;
        $password = isset($item['password']) && $item['password'] ? (string) $item['password'] : null;

        switch ($type) {
            case 'http':
                $proxy = new HttpProxy(
                    $host,
                    $port,
                    $userName,
                    $password
                );

                break;
            default:
                $proxy = new Socks5Proxy(
                    $host,
                    $port,
                    $userName,
                    $password
                );

                break;
        }

        $attempts = isset($item['attempts']) ? (int) $item['attempts'] :  0;
        $active = !isset($item['active']) || (bool) $item['active'] === true;

        $instance = new Proxy($proxy);
        $instance->setAttempts($attempts);
        $instance->setActive($active);

        return $instance;
    }
}
