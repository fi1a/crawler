<?php

declare(strict_types=1);

namespace Fi1a\Unit\Crawler;

use Fi1a\Crawler\Config;
use Fi1a\Crawler\ConfigInterface;
use Fi1a\Crawler\Crawler;
use Fi1a\Crawler\CrawlerInterface;
use Fi1a\Crawler\ItemCollection;
use Fi1a\Crawler\ItemInterface;
use Fi1a\Crawler\ItemStorages\ItemStorage;
use Fi1a\Crawler\ItemStorages\StorageAdapters\LocalFilesystemAdapter;
use Fi1a\Crawler\PrepareItems\PrepareHtmlItem;
use Fi1a\Crawler\Proxy\ProxyCollection;
use Fi1a\Crawler\Proxy\Selections\OnlyActive;
use Fi1a\Crawler\Proxy\Selections\SortedByTime;
use Fi1a\Crawler\Restrictions\UriRestriction;
use Fi1a\Crawler\UriCollection;
use Fi1a\Crawler\UriParsers\HtmlUriParser;
use Fi1a\Crawler\UriTransformers\SiteUriTransformer;
use Fi1a\Crawler\Writers\FileWriter;
use Fi1a\Http\Mime;
use Fi1a\Http\MimeInterface;
use Fi1a\Http\Uri;
use Fi1a\HttpClient\Middlewares\ApiKeyAuthMiddleware;
use Fi1a\HttpClient\Request;
use Fi1a\HttpClient\RequestInterface;
use Fi1a\Unit\Crawler\TestCases\TestCase;
use InvalidArgumentException;

/**
 * Web Crawler
 */
class CrawlerTest extends TestCase
{
    /**
     * Web Crawler
     */
    protected function getCrawler(): CrawlerInterface
    {
        $crawler = new Crawler($this->getConfig(), new ItemStorage(new LocalFilesystemAdapter($this->runtimeFolder)));

        $crawler->setWriter(new FileWriter($this->runtimeFolder . '/web'));

        return $crawler;
    }

    /**
     * Исключение если не задана точка входа
     */
    public function testValidateEmptyStartUrls(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $config = new Config();
        $crawler = new Crawler($config, new ItemStorage(new LocalFilesystemAdapter($this->runtimeFolder)));
        $crawler->run();
    }

    /**
     * Исключение если не задана точка входа
     */
    public function testValidateEmptyStartUrlsOnDownload(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $config = new Config();
        $config->setVerbose(ConfigInterface::VERBOSE_NONE);
        $crawler = new Crawler($config, new ItemStorage(new LocalFilesystemAdapter($this->runtimeFolder)));
        $crawler->download();
    }

    /**
     * Исключение если не задан класс записывающий результат обхода
     */
    public function testValidateEmptyWriter(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $config = new Config();
        $config->addStartUri($this->getUrl('/index.html'));
        $config->setVerbose(ConfigInterface::VERBOSE_NONE);
        $crawler = new Crawler($config, new ItemStorage(new LocalFilesystemAdapter($this->runtimeFolder)));
        $crawler->run();
    }

    /**
     * Исключение если не задан класс записывающий результат обхода
     */
    public function testValidateEmptyWriterOnWrite(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $config = new Config();
        $config->setVerbose(ConfigInterface::VERBOSE_NONE);
        $config->addStartUri($this->getUrl('/index.html'));
        $crawler = new Crawler($config, new ItemStorage(new LocalFilesystemAdapter($this->runtimeFolder)));
        $crawler->write();
    }

    /**
     * Ограничения по умолчанию
     */
    public function testDefault(): void
    {
        $crawler = $this->getCrawler();

        $crawler->clearStorage();
        $crawler->run();
        $this->assertCount(1, $crawler->getRestrictions());
        $this->assertTrue($crawler->hasUriParser());
        $this->assertEquals(12, $crawler->getItems()->count());
        $this->assertEquals(7, $crawler->getItems()->getDownloaded()->count());
        $this->assertEquals(12, $crawler->getItems()->getProcessed()->count());
        $this->assertEquals(7, $crawler->getItems()->getWrited()->count());
    }

    /**
     * Тестирование фабрики запросов
     */
    public function testRequestFactory(): void
    {
        $config = new Config();

        $config->addStartUri($this->getUrl('/api-key-auth/'));
        $config->setVerbose(ConfigInterface::VERBOSE_NONE);

        $config->getHttpClientConfig()->setSslVerify(false);

        $crawler = new Crawler(
            $config,
            new ItemStorage(new LocalFilesystemAdapter($this->runtimeFolder))
        );

        $crawler->setWriter(new FileWriter($this->runtimeFolder . '/web'));

        $crawler->clearStorage();
        $crawler->run();
        $this->assertCount(1, $crawler->getRestrictions());
        $this->assertTrue($crawler->hasUriParser());
        $this->assertEquals(1, $crawler->getItems()->count());
        $this->assertEquals(0, $crawler->getItems()->getDownloaded()->count());
        $this->assertEquals(1, $crawler->getItems()->getProcessed()->count());
        $this->assertEquals(0, $crawler->getItems()->getWrited()->count());

        $crawler->restartDownload();
        $crawler->setRequestFactory(function (ItemInterface $item): RequestInterface {
            return Request::create()->withMiddleware(
                new ApiKeyAuthMiddleware('token', '123', ApiKeyAuthMiddleware::IN_HEADER)
            );
        });

        $crawler->clearStorage();
        $crawler->run();
        $this->assertCount(1, $crawler->getRestrictions());
        $this->assertTrue($crawler->hasUriParser());
        $this->assertEquals(1, $crawler->getItems()->count());
        $this->assertEquals(1, $crawler->getItems()->getDownloaded()->count());
        $this->assertEquals(1, $crawler->getItems()->getProcessed()->count());
        $this->assertEquals(1, $crawler->getItems()->getWrited()->count());
    }

    /**
     * Ограничения по умолчанию
     */
    public function testDefaultRestart(): void
    {
        $crawler = $this->getCrawler();

        $crawler->clearStorage();
        $crawler->run();
        $this->assertCount(1, $crawler->getRestrictions());
        $this->assertTrue($crawler->hasUriParser());
        $this->assertEquals(12, $crawler->getItems()->count());
        $this->assertEquals(7, $crawler->getItems()->getDownloaded()->count());
        $this->assertEquals(12, $crawler->getItems()->getProcessed()->count());
        $this->assertEquals(7, $crawler->getItems()->getWrited()->count());

        $crawler->restartErrors();
        $isErrorExist = false;
        foreach ($crawler->getItems() as $item) {
            assert($item instanceof ItemInterface);
            if ($item->getDownloadStatus() === false) {
                $isErrorExist = true;
            }
        }
        $this->assertFalse($isErrorExist);

        $crawler->restartWrite();
        $this->assertEquals(0, $crawler->getItems()->getWrited()->count());
        $crawler->restartProcess();
        $this->assertEquals(0, $crawler->getItems()->getProcessed()->count());
        $crawler->restartDownload();
        $this->assertEquals(0, $crawler->getItems()->getDownloaded()->count());

        $crawler->run();
        $this->assertCount(1, $crawler->getRestrictions());
        $this->assertTrue($crawler->hasUriParser());
        $this->assertEquals(12, $crawler->getItems()->count());
        $this->assertEquals(7, $crawler->getItems()->getDownloaded()->count());
        $this->assertEquals(12, $crawler->getItems()->getProcessed()->count());
        $this->assertEquals(7, $crawler->getItems()->getWrited()->count());
    }

    /**
     * Ограничения по размеру скачиваемого файла
     */
    public function testSizeLimits(): void
    {
        $config = $this->getConfig();

        $config->setSizeLimit(1);

        $crawler = new Crawler($config, new ItemStorage(new LocalFilesystemAdapter($this->runtimeFolder)));

        $crawler->setWriter(new FileWriter($this->runtimeFolder . '/web'));

        $crawler->clearStorage();
        $crawler->run();
        $this->assertCount(1, $crawler->getRestrictions());
        $this->assertTrue($crawler->hasUriParser());
        $this->assertEquals(2, $crawler->getItems()->count());
        $this->assertEquals(0, $crawler->getItems()->getDownloaded()->count());
        $this->assertEquals(2, $crawler->getItems()->getProcessed()->count());
        $this->assertEquals(0, $crawler->getItems()->getWrited()->count());
    }

    /**
     * Ограничения по размеру скачиваемого файла
     */
    public function testSizeLimitsByMimeType(): void
    {
        $config = $this->getConfig();

        $config->setSizeLimit(10000000);
        $config->setSizeLimit(1, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document');

        $crawler = new Crawler($config, new ItemStorage(new LocalFilesystemAdapter($this->runtimeFolder)));

        $crawler->setWriter(new FileWriter($this->runtimeFolder . '/web'));

        $crawler->clearStorage();
        $crawler->run();
        $this->assertCount(1, $crawler->getRestrictions());
        $this->assertTrue($crawler->hasUriParser());
        $this->assertEquals(12, $crawler->getItems()->count());
        $this->assertEquals(6, $crawler->getItems()->getDownloaded()->count());
        $this->assertEquals(12, $crawler->getItems()->getProcessed()->count());
        $this->assertEquals(6, $crawler->getItems()->getWrited()->count());
    }

    /**
     * Пауза между запросами
     */
    public function testWithDelay(): void
    {
        $config = $this->getConfig();
        $config->setDelay(1);

        $crawler = new Crawler($config, new ItemStorage(new LocalFilesystemAdapter($this->runtimeFolder)));
        $crawler->setWriter(new FileWriter($this->runtimeFolder . '/web'));

        $crawler->clearStorage();
        $startTime = time();
        $crawler->run();
        $this->assertTrue(time() - $startTime >= $crawler->getItems()->getDownloaded()->count());
        $this->assertCount(1, $crawler->getRestrictions());
        $this->assertTrue($crawler->hasUriParser());
        $this->assertEquals(12, $crawler->getItems()->count());
        $this->assertEquals(7, $crawler->getItems()->getDownloaded()->count());
        $this->assertEquals(12, $crawler->getItems()->getProcessed()->count());
        $this->assertEquals(7, $crawler->getItems()->getWrited()->count());
    }

    /**
     * Загрузка с использованием прокси
     */
    public function testWithProxy(): void
    {
        $crawler = new Crawler(
            $this->getConfig(),
            new ItemStorage(new LocalFilesystemAdapter($this->runtimeFolder)),
            $this->getProxyStorageWithSavedProxy()
        );
        $crawler->setProxySelection(new SortedByTime(new OnlyActive()));
        $crawler->setWriter(new FileWriter($this->runtimeFolder . '/web'));

        $crawler->clearStorage();
        $crawler->run();
        $this->assertCount(1, $crawler->getRestrictions());
        $this->assertTrue($crawler->hasUriParser());
        $this->assertEquals(12, $crawler->getItems()->count());
        $this->assertEquals(7, $crawler->getItems()->getDownloaded()->count());
        $this->assertEquals(12, $crawler->getItems()->getProcessed()->count());
        $this->assertEquals(7, $crawler->getItems()->getWrited()->count());
    }

    /**
     * Загрузка с использованием прокси
     */
    public function testWithProxySetProxyStorageBySetter(): void
    {
        $crawler = new Crawler(
            $this->getConfig(),
            new ItemStorage(new LocalFilesystemAdapter($this->runtimeFolder))
        );
        $crawler->setProxySelection(new SortedByTime(new OnlyActive()));
        $crawler->setWriter(new FileWriter($this->runtimeFolder . '/web'));
        $crawler->setProxyStorage($this->getProxyStorageWithSavedProxy());

        $crawler->clearStorage();
        $crawler->run();
        $this->assertCount(1, $crawler->getRestrictions());
        $this->assertTrue($crawler->hasUriParser());
        $this->assertEquals(12, $crawler->getItems()->count());
        $this->assertEquals(7, $crawler->getItems()->getDownloaded()->count());
        $this->assertEquals(12, $crawler->getItems()->getProcessed()->count());
        $this->assertEquals(7, $crawler->getItems()->getWrited()->count());
    }

    /**
     * Ошибка при использовании прокси
     */
    public function testProxyError(): void
    {
        $collection = new ProxyCollection();
        $collection[] = [
            'id' => null,
            'type' => 'http',
            'host' => HTTP_PROXY_HOST,
            'port' => 100500,
            'userName' => HTTP_PROXY_USERNAME,
            'password' => HTTP_PROXY_PASSWORD,
            'attempts' => 0,
            'active' => true,
            'lastUse' => null,
        ];
        $crawler = $this->getCrawler();

        $crawler->setWriter(new FileWriter($this->runtimeFolder . '/web'));
        $crawler->setProxyCollection($collection);
        $crawler->clearStorage();
        $crawler->run();
        $this->assertCount(1, $crawler->getRestrictions());
        $this->assertTrue($crawler->hasUriParser());
        $this->assertEquals(2, $crawler->getItems()->count());
        $this->assertEquals(0, $crawler->getItems()->getDownloaded()->count());
        $this->assertEquals(2, $crawler->getItems()->getProcessed()->count());
        $this->assertEquals(0, $crawler->getItems()->getWrited()->count());
    }

    /**
     * Web Crawler
     */
    public function testVerboseDebug(): void
    {
        $config = $this->getConfig();
        $config->setVerbose(ConfigInterface::VERBOSE_DEBUG);
        $crawler = new Crawler(
            $config,
            new ItemStorage(new LocalFilesystemAdapter($this->runtimeFolder)),
            null,
            $this->getOutput()
        );
        $crawler->setWriter(new FileWriter($this->runtimeFolder . '/web'));
        $crawler->setUriTransformer(new SiteUriTransformer());

        $crawler->clearStorage();
        $crawler->run();
        $this->assertCount(1, $crawler->getRestrictions());
        $this->assertTrue($crawler->hasUriParser());
        $this->assertEquals(12, $crawler->getItems()->count());
        $this->assertEquals(7, $crawler->getItems()->getDownloaded()->count());
        $this->assertEquals(12, $crawler->getItems()->getProcessed()->count());
        $this->assertEquals(7, $crawler->getItems()->getWrited()->count());
    }

    /**
     * Запуск из хранилища
     */
    public function testRunFromStorage(): void
    {
        $config = new Config();

        $config->addStartUri($this->getUrl('/path/to/index.html'));
        $config->setVerbose(ConfigInterface::VERBOSE_NONE);

        $config->getHttpClientConfig()->setSslVerify(false);

        $crawler = new Crawler($config, new ItemStorage(new LocalFilesystemAdapter($this->runtimeFolder)));
        $crawler->setWriter(new FileWriter($this->runtimeFolder . '/web'));
        $crawler->addRestriction(new UriRestriction($this->getUrl('/')));

        $crawler->run();
        $this->assertCount(1, $crawler->getRestrictions());
        $this->assertTrue($crawler->hasUriParser());
        $this->assertEquals(26, $crawler->getItems()->count());
        $this->assertEquals(18, $crawler->getItems()->getDownloaded()->count());
        $this->assertEquals(26, $crawler->getItems()->getProcessed()->count());
        $this->assertEquals(18, $crawler->getItems()->getWrited()->count());

        $config->setLifetime(0);
        $crawler2 = new Crawler($config, new ItemStorage(new LocalFilesystemAdapter($this->runtimeFolder)));
        $crawler2->setWriter(new FileWriter($this->runtimeFolder . '/web'));
        $crawler2->addRestriction(new UriRestriction($this->getUrl('/')));

        $crawler2->run();
        $this->assertCount(1, $crawler2->getRestrictions());
        $this->assertTrue($crawler2->hasUriParser());
        $this->assertEquals(26, $crawler2->getItems()->count());
        $this->assertEquals(18, $crawler2->getItems()->getDownloaded()->count());
        $this->assertEquals(26, $crawler2->getItems()->getProcessed()->count());
        $this->assertEquals(18, $crawler2->getItems()->getWrited()->count());
    }

    /**
     * Возвращает адреса
     */
    public function testItems(): void
    {
        $crawler = $this->getCrawler();

        $crawler->run();
        $this->assertCount(12, $crawler->getItems());
        $crawler->setItems(new ItemCollection());
        $this->assertCount(0, $crawler->getItems());
    }

    /**
     * Методы по работе с парсерами uri
     */
    public function testUriParsersMethods(): void
    {
        $crawler = $this->getCrawler();
        $this->assertFalse($crawler->hasUriParser());
        $this->assertFalse($crawler->hasUriParser(MimeInterface::HTML));
        $crawler->setUriParser(new HtmlUriParser());
        $crawler->setUriParser(new HtmlUriParser(), MimeInterface::HTML);
        $this->assertTrue($crawler->hasUriParser());
        $this->assertTrue($crawler->hasUriParser(MimeInterface::HTML));
        $crawler->removeUriParser();
        $crawler->removeUriParser(MimeInterface::HTML);
        $crawler->removeUriParser();
        $crawler->removeUriParser(MimeInterface::HTML);
        $this->assertFalse($crawler->hasUriParser());
        $this->assertFalse($crawler->hasUriParser(MimeInterface::HTML));
    }

    /**
     * Вызов парсера uri по типу контента
     */
    public function testUriParsersCallByMime(): void
    {
        $uriParser = $this->getMockBuilder(HtmlUriParser::class)
            ->onlyMethods(['parse'])
            ->getMock();

        $uriParser->expects($this->exactly(2))
            ->method('parse')
            ->willReturn(new UriCollection());

        $crawler = $this->getCrawler();
        $crawler->setUriParser($uriParser, MimeInterface::HTML);

        $crawler->run();
    }

    /**
     * Возвращает тело ответа с ошибкой
     */
    public function testBodyReturnFalse(): void
    {
        $storage = $this->getMockBuilder(ItemStorage::class)
            ->onlyMethods(['getBody'])
            ->setConstructorArgs([new LocalFilesystemAdapter($this->runtimeFolder)])
            ->getMock();

        $storage->expects($this->any())->method('getBody')->willReturn(false);

        $crawler = new Crawler($this->getConfig(), $storage);
        $crawler->setWriter(new FileWriter($this->runtimeFolder . '/web'));

        $crawler->run();
        $this->assertEquals(0, $crawler->getItems()->getWrited()->count());
    }

    /**
     * Возвращает тело ответа (ошибка записи)
     */
    public function testWriterReturnFalse(): void
    {
        $writer = $this->getMockBuilder(FileWriter::class)
            ->onlyMethods(['write'])
            ->setConstructorArgs([$this->runtimeFolder . '/web'])
            ->getMock();

        $writer->expects($this->any())->method('write')->willReturn(false);

        $crawler = new Crawler($this->getConfig(), new ItemStorage(new LocalFilesystemAdapter($this->runtimeFolder)));
        $crawler->setWriter($writer);

        $crawler->run();
        $this->assertEquals(0, $crawler->getItems()->getWrited()->count());
    }

    /**
     * Метод добавления Uri
     */
    public function testAddUri(): void
    {
        $crawler = $this->getCrawler();
        $crawler->addUri($this->getUrl('/index.html'));
        $this->assertCount(1, $crawler->getItems());
        $crawler->addUri($this->getUrl('/index.html'));
        $this->assertCount(1, $crawler->getItems());
        $crawler->addUri(new Uri($this->getUrl('/path/to/index.html')));
        $this->assertCount(2, $crawler->getItems());
    }

    /**
     * Методы по работе с классом подготавливающего элемент
     */
    public function testPrepareItemMethods(): void
    {
        $crawler = $this->getCrawler();
        $this->assertFalse($crawler->hasPrepareItem());
        $this->assertFalse($crawler->hasPrepareItem(MimeInterface::HTML));
        $crawler->setPrepareItem(new PrepareHtmlItem());
        $crawler->setPrepareItem(new PrepareHtmlItem(), MimeInterface::HTML);
        $this->assertTrue($crawler->hasPrepareItem());
        $this->assertTrue($crawler->hasPrepareItem(MimeInterface::HTML));
        $crawler->removePrepareItem();
        $crawler->removePrepareItem(MimeInterface::HTML);
        $crawler->removePrepareItem();
        $crawler->removePrepareItem(MimeInterface::HTML);
        $this->assertFalse($crawler->hasPrepareItem());
        $this->assertFalse($crawler->hasPrepareItem(MimeInterface::HTML));
    }

    /**
     * Методы по работе с классом записывающим результат обхода
     */
    public function testWriterMethods(): void
    {
        $crawler = new Crawler($this->getConfig(), new ItemStorage(new LocalFilesystemAdapter($this->runtimeFolder)));
        $this->assertFalse($crawler->hasWriter());
        $this->assertFalse($crawler->hasWriter(MimeInterface::HTML));
        $crawler->setWriter(new FileWriter($this->runtimeFolder . '/web'));
        $crawler->setWriter(new FileWriter($this->runtimeFolder . '/web'), MimeInterface::HTML);
        $this->assertTrue($crawler->hasWriter());
        $this->assertTrue($crawler->hasWriter(MimeInterface::HTML));
        $crawler->removeWriter();
        $crawler->removeWriter(MimeInterface::HTML);
        $crawler->removeWriter();
        $crawler->removeWriter(MimeInterface::HTML);
        $this->assertFalse($crawler->hasWriter());
        $this->assertFalse($crawler->hasWriter(MimeInterface::HTML));
    }

    /**
     * Вызов класса подготавливающего элемент бля всех типов контента
     */
    public function testPrepareCallByAllMime(): void
    {
        $uriParser = $this->getMockBuilder(HtmlUriParser::class)
            ->onlyMethods(['parse'])
            ->getMock();

        $uriParser->expects($this->exactly(2))
            ->method('parse')
            ->willReturn(new UriCollection());

        $prepare = $this->getMockBuilder(PrepareHtmlItem::class)
            ->onlyMethods(['prepare'])
            ->getMock();

        $prepare->expects($this->atLeastOnce())->method('prepare');

        $crawler = $this->getCrawler();
        $crawler->setUriParser($uriParser);
        $crawler->setPrepareItem($prepare);

        $crawler->clearStorage();
        $crawler->run();
    }

    /**
     * Вызов класса записывающего результат обхода для конкретного типа контента
     */
    public function testWriteCallByMime(): void
    {
        $uriParser = $this->getMockBuilder(HtmlUriParser::class)
            ->onlyMethods(['parse'])
            ->getMock();

        $uriParser->expects($this->exactly(2))
            ->method('parse')
            ->willReturn(new UriCollection());

        $writer = $this->getMockBuilder(FileWriter::class)
            ->onlyMethods(['write'])
            ->setConstructorArgs([$this->runtimeFolder . '/web'])
            ->getMock();

        $writer->expects($this->atLeastOnce())->method('write')->willReturn(true);

        $crawler = $this->getCrawler();
        $crawler->setUriParser($uriParser);
        $crawler->setWriter($writer, Mime::HTML);

        $crawler->clearStorage();
        $crawler->run();
    }

    /**
     * Работа с данными из хранилища
     */
    public function testStorage(): void
    {
        $crawler = $this->getCrawler();
        $crawler->run();
        $this->assertCount(12, $crawler->getItems());

        $crawler = $this->getCrawler();
        $crawler->loadFromStorage();
        $this->assertCount(12, $crawler->getItems());

        $crawler = $this->getCrawler();
        $crawler->clearStorage();
        $crawler->loadFromStorage();
        $this->assertCount(0, $crawler->getItems());
    }
}
