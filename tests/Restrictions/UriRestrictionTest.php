<?php

declare(strict_types=1);

namespace Fi1a\Unit\Crawler\Restrictions;

use Fi1a\Crawler\Restrictions\UriRestriction;
use Fi1a\Http\Uri;
use Fi1a\Unit\Crawler\TestCases\TestCase;
use InvalidArgumentException;

/**
 * Ограничение по доменам
 */
class UriRestrictionTest extends TestCase
{
    /**
     * Разрешено
     */
    public function testAllowHost(): void
    {
        $restriction = new UriRestriction($this->getUrl(''));
        $this->assertTrue($restriction->isAllow(new Uri($this->getUrl('/'))));
        $this->assertTrue($restriction->isAllow(new Uri($this->getUrl('/index.html'))));
        $this->assertTrue($restriction->isAllow(new Uri('/index.html')));
        $this->assertTrue($restriction->isAllow(new Uri('/')));
    }

    /**
     * Разрешено
     */
    public function testAllow(): void
    {
        $restriction = new UriRestriction($this->getUrl('/path/'));
        $this->assertTrue($restriction->isAllow(new Uri($this->getUrl('/path/'))));
        $this->assertTrue($restriction->isAllow(new Uri($this->getUrl('/path/index.html'))));
        $this->assertTrue($restriction->isAllow(new Uri('/path/sub/')));
        $this->assertTrue($restriction->isAllow(new Uri('/path/sub/index.html')));
    }

    /**
     * Разрешено
     */
    public function testAllowUriHost(): void
    {
        $restriction = new UriRestriction(new Uri($this->getUrl('')));
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
        $restriction = new UriRestriction(new Uri($this->getUrl('')));
        $this->assertTrue($restriction->isAllow(new Uri($this->getUrl('/path/'))));
        $this->assertTrue($restriction->isAllow(new Uri($this->getUrl('/path/index.html'))));
        $this->assertTrue($restriction->isAllow(new Uri('/path/sub/')));
        $this->assertTrue($restriction->isAllow(new Uri('/path/sub/index.html')));
    }

    /**
     * Не разрешено
     */
    public function testNotAllowHost(): void
    {
        $restriction = new UriRestriction($this->getUrl(''));
        $this->assertFalse($restriction->isAllow(new Uri('https://domain.ru')));
        $this->assertFalse($restriction->isAllow(new Uri('https://domain.ru/index.html')));
    }

    /**
     * Не разрешено
     */
    public function testNotAllow(): void
    {
        $restriction = new UriRestriction($this->getUrl('/path/'));
        $this->assertFalse($restriction->isAllow(new Uri('/other/')));
        $this->assertFalse($restriction->isAllow(new Uri('/other/path/index.html')));
    }

    /**
     * Не разрешено
     */
    public function testNotAllowUriHost(): void
    {
        $restriction = new UriRestriction(new Uri($this->getUrl('')));
        $this->assertFalse($restriction->isAllow(new Uri('https://domain.ru')));
        $this->assertFalse($restriction->isAllow(new Uri('https://domain.ru/index.html')));
    }

    /**
     * Не разрешено
     */
    public function testNotAllowUri(): void
    {
        $restriction = new UriRestriction(new Uri($this->getUrl('/path/')));
        $this->assertFalse($restriction->isAllow(new Uri('/other/')));
        $this->assertFalse($restriction->isAllow(new Uri('/other/path/index.html')));
    }

    /**
     * Исключение при пустом разрешенном хосте
     */
    public function testException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new UriRestriction('');
    }
}
