<?php

declare(strict_types=1);

namespace Fi1a\Crawler\Proxy\Selections;

use Fi1a\Crawler\ItemInterface;
use Fi1a\Crawler\Proxy\ProxyCollection;
use Fi1a\Crawler\Proxy\ProxyCollectionInterface;
use Fi1a\Crawler\Proxy\ProxyInterface;
use InvalidArgumentException;

/**
 * Фильтрация прокси по числу ошибок соединения
 */
class FilterByAttempts implements ProxySelectionInterface
{
    /**
     * @var ProxySelectionInterface|null
     */
    protected $proxySelection;

    /**
     * @var int
     */
    protected $attempts;

    public function __construct(?ProxySelectionInterface $proxySelection = null, int $attempts = 3)
    {
        if ($attempts <= 0) {
            throw new InvalidArgumentException('Число попыток может быть только положительным числом');
        }
        $this->proxySelection = $proxySelection;
        $this->attempts = $attempts;
    }

    /**
     * @inheritDoc
     */
    public function selection(ProxyCollectionInterface $collection, ItemInterface $item): ProxyCollectionInterface
    {
        if ($this->proxySelection) {
            $collection = $this->proxySelection->selection($collection, $item);
        }

        $array = [];
        foreach ($collection as $proxy) {
            assert($proxy instanceof ProxyInterface);
            if ($proxy->getAttempts() <= $this->attempts) {
                $array[] = $proxy;
            }
        }

        return new ProxyCollection($array);
    }
}
