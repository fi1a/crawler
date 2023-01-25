<?php

declare(strict_types=1);

namespace Fi1a\Crawler;

use Fi1a\Crawler\Restrictions\DomainRestriction;
use Fi1a\Crawler\Restrictions\RestrictionCollection;
use Fi1a\Crawler\Restrictions\RestrictionCollectionInterface;
use Fi1a\Crawler\Restrictions\RestrictionInterface;
use InvalidArgumentException;

/**
 * Web Crawler
 */
class Crawler implements CrawlerInterface
{
    /**
     * @var RestrictionCollectionInterface
     */
    protected $restrictions;

    /**
     * @var ConfigInterface
     */
    protected $config;

    public function __construct(ConfigInterface $config)
    {
        $this->config = $config;
        $this->restrictions = new RestrictionCollection();
    }

    /**
     * @inheritDoc
     */
    public function run(): void
    {
        $this->validateConfig();
        if (!count($this->restrictions)) {
            $this->addDefaultRestrictions();
        }
    }

    /**
     * @inheritDoc
     */
    public function addRestriction(RestrictionInterface $restriction)
    {
        $this->restrictions[] = $restriction;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getRestrictions(): RestrictionCollectionInterface
    {
        return $this->restrictions;
    }

    /**
     * Добавить ограничения по домену используя точки входа
     */
    protected function addDefaultRestrictions(): void
    {
        $hosts = [];
        foreach ($this->config->getStartUrls() as $startUrl) {
            $host = mb_strtolower($startUrl->getHost());
            if (in_array($host, $hosts)) {
                continue;
            }
            $hosts[] = $host;
            $this->addRestriction(new DomainRestriction($startUrl));
        }
    }

    /**
     * Валидация конфига
     */
    protected function validateConfig(): void
    {
        if (!count($this->config->getStartUrls())) {
            throw new InvalidArgumentException('Не задана точка входа ($config->addStartUrl())');
        }
    }
}
