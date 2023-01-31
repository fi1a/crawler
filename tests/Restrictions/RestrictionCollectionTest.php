<?php

declare(strict_types=1);

namespace Fi1a\Unit\Crawler\Restrictions;

use Fi1a\Crawler\Restrictions\NotAllowRestriction;
use Fi1a\Crawler\Restrictions\RestrictionCollection;
use Fi1a\Crawler\Restrictions\UriRestriction;
use Fi1a\Http\Uri;
use Fi1a\Unit\Crawler\TestCases\TestCase;

/**
 * Коллекция ограничений
 */
class RestrictionCollectionTest extends TestCase
{
    /**
     * Коллекция ограничений
     */
    public function testCollection(): void
    {
        $collection = new RestrictionCollection();
        $collection[] = new NotAllowRestriction();
        $collection[] = new UriRestriction(new Uri($this->getUrl('/')));
        $this->assertCount(2, $collection);
    }
}
