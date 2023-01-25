<?php

declare(strict_types=1);

namespace Fi1a\Unit\Crawler\Restrictions;

use Fi1a\Crawler\Restrictions\DomainRestriction;
use Fi1a\Http\Uri;
use Fi1a\Unit\Crawler\TestCases\TestCase;

/**
 * Ограничение по доменам
 */
class DomainRestrictionTest extends TestCase
{
    /**
     * Разрешено
     */
    public function testAllow(): void
    {
        $restriction = new DomainRestriction([$this->getUrl('')]);
        $this->assertTrue($restriction->isAllow(new Uri($this->getUrl('/'))));
        $this->assertTrue($restriction->isAllow(new Uri($this->getUrl('/index.html'))));
        $this->assertTrue($restriction->isAllow(new Uri('/index.html')));
        $this->assertTrue($restriction->isAllow(new Uri('/')));
    }

    /**
     * Разрешено
     */
    public function testAllowUri(): void
    {
        $restriction = new DomainRestriction([new Uri($this->getUrl(''))]);
        $this->assertTrue($restriction->isAllow(new Uri($this->getUrl('/'))));
        $this->assertTrue($restriction->isAllow(new Uri($this->getUrl('/index.html'))));
        $this->assertTrue($restriction->isAllow(new Uri('/index.html')));
        $this->assertTrue($restriction->isAllow(new Uri('/')));
    }

    /**
     * Разрешено
     */
    public function testAllowDomains(): void
    {
        $restriction = new DomainRestriction([$this->getUrl(''), new Uri('https://domain.ru')]);
        $this->assertTrue($restriction->isAllow(new Uri('https://domain.ru')));
        $this->assertTrue($restriction->isAllow(new Uri('https://domain.ru/index.html')));
    }

    /**
     * Не разрешено
     */
    public function testNotAllow(): void
    {
        $restriction = new DomainRestriction([$this->getUrl('')]);
        $this->assertFalse($restriction->isAllow(new Uri('https://domain.ru')));
        $this->assertFalse($restriction->isAllow(new Uri('https://domain.ru/index.html')));
    }

    /**
     * Не разрешено
     */
    public function testNotAllowUri(): void
    {
        $restriction = new DomainRestriction([new Uri($this->getUrl(''))]);
        $this->assertFalse($restriction->isAllow(new Uri('https://domain.ru')));
        $this->assertFalse($restriction->isAllow(new Uri('https://domain.ru/index.html')));
    }
}
