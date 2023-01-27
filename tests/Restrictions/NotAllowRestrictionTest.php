<?php

declare(strict_types=1);

namespace Fi1a\Unit\Crawler\Restrictions;

use Fi1a\Crawler\Restrictions\NotAllowRestriction;
use Fi1a\Http\Uri;
use Fi1a\Unit\Crawler\TestCases\TestCase;

/**
 * Запрет на обход страниц
 */
class NotAllowRestrictionTest extends TestCase
{
    /**
     * Не разрешено
     */
    public function testNotAllow(): void
    {
        $restriction = new NotAllowRestriction();
        $this->assertFalse($restriction->isAllow(new Uri('/other/')));
        $this->assertFalse($restriction->isAllow(new Uri('/other/path/index.html')));
    }
}
