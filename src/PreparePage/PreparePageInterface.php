<?php

declare(strict_types=1);

namespace Fi1a\Crawler\PreparePage;

use Fi1a\Crawler\PageCollectionInterface;
use Fi1a\Crawler\PageInterface;

/**
 * Подготавливает страницу
 */
interface PreparePageInterface
{
    /**
     * Подготавливает страницу
     *
     * @return mixed
     */
    public function prepare(PageInterface $page, PageCollectionInterface $pages);
}
