<?php

declare(strict_types=1);

namespace Fi1a\Crawler\Restrictions;

use Fi1a\Collection\Collection;

/**
 * Коллекция ограничений
 */
class RestrictionCollection extends Collection implements RestrictionCollectionInterface
{
    /**
     * @inheritDoc
     */
    public function __construct(?array $data = null)
    {
        parent::__construct(RestrictionInterface::class, $data);
    }
}
