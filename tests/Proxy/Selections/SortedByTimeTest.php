<?php

declare(strict_types=1);

namespace Fi1a\Unit\Crawler\Proxy\Selections;

use DateTime;
use Fi1a\Crawler\Proxy\Proxy;
use Fi1a\Crawler\Proxy\ProxyCollection;
use Fi1a\Crawler\Proxy\ProxyInterface;
use Fi1a\Crawler\Proxy\Selections\OnlyActive;
use Fi1a\Crawler\Proxy\Selections\SortedByTime;
use Fi1a\Unit\Crawler\TestCases\TestCase;

/**
 * Отсортированные по времени использования
 */
class SortedByTimeTest extends TestCase
{
    /**
     * Провайдер данных для тестирования подбора прокси по возрастанию/убыванию времени использования
     *
     * @return array<array-key,array<array-key, mixed>>
     */
    public function dataProviderSortedByTime(): array
    {
        return [
            [
                SortedByTime::ORDER_BY_ASC,
                null,
                null,
                new DateTime('-3 hour'),
                new DateTime('-2 hour'),
                new DateTime('-1 hour'),
            ],
            [
                SortedByTime::ORDER_BY_DESC,
                new DateTime('-1 hour'),
                new DateTime('-2 hour'),
                new DateTime('-3 hour'),
                null,
                null,
            ],
        ];
    }

    /**
     * Подбор прокси по возрастанию/убыванию времени использования
     *
     * @dataProvider dataProviderSortedByTime
     */
    public function testSortedByTime(
        string $orderBy,
        ?DateTime $time1,
        ?DateTime $time2,
        ?DateTime $time3,
        ?DateTime $time4,
        ?DateTime $time5
    ): void {
        $collection = new ProxyCollection();
        $proxy = Proxy::factory(static::$httpProxy);
        $proxy->setLastUse($time2);
        $collection[] = $proxy;
        $proxy = Proxy::factory(static::$httpProxy);
        $proxy->setLastUse($time4);
        $collection[] = $proxy;
        $proxy = Proxy::factory(static::$httpProxy);
        $proxy->setLastUse($time3);
        $collection[] = $proxy;
        $proxy = Proxy::factory(static::$httpProxy);
        $proxy->setLastUse($time1);
        $collection[] = $proxy;
        $proxy = Proxy::factory(static::$httpProxy);
        $proxy->setLastUse($time5);
        $collection[] = $proxy;
        $selections = new SortedByTime(new OnlyActive(), $orderBy);
        $collection = $selections->selection($collection, $this->getItem());
        /** @var ProxyInterface $proxy */
        $proxy = $collection[0];
        $this->assertEquals(
            $time1 ? $time1->format('d.m.Y H:i:s') : $time1,
            $proxy->getLastUse() ? $proxy->getLastUse()->format('d.m.Y H:i:s') : $proxy->getLastUse()
        );
        /** @var ProxyInterface $proxy */
        $proxy = $collection[1];
        $this->assertEquals(
            $time2 ? $time2->format('d.m.Y H:i:s') : $time2,
            $proxy->getLastUse() ? $proxy->getLastUse()->format('d.m.Y H:i:s') : $proxy->getLastUse()
        );
        /** @var ProxyInterface $proxy */
        $proxy = $collection[2];
        $this->assertEquals(
            $time3 ? $time3->format('d.m.Y H:i:s') : $time3,
            $proxy->getLastUse() ? $proxy->getLastUse()->format('d.m.Y H:i:s') : $proxy->getLastUse()
        );
        /** @var ProxyInterface $proxy */
        $proxy = $collection[3];
        $this->assertEquals(
            $time4 ? $time4->format('d.m.Y H:i:s') : $time4,
            $proxy->getLastUse() ? $proxy->getLastUse()->format('d.m.Y H:i:s') : $proxy->getLastUse()
        );
        /** @var ProxyInterface $proxy */
        $proxy = $collection[4];
        $this->assertEquals(
            $time5 ? $time5->format('d.m.Y H:i:s') : $time5,
            $proxy->getLastUse() ? $proxy->getLastUse()->format('d.m.Y H:i:s') : $proxy->getLastUse()
        );
    }
}
