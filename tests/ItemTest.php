<?php

declare(strict_types=1);

namespace Fi1a\Unit\Crawler;

use Fi1a\Crawler\Item;
use Fi1a\Http\Mime;
use Fi1a\Http\Uri;
use Fi1a\Http\UriInterface;
use Fi1a\Unit\Crawler\TestCases\TestCase;
use InvalidArgumentException;

/**
 * Элементы
 */
class ItemTest extends TestCase
{
    /**
     * @var array<string, mixed>
     */
    protected $itemArray = [
        'itemUri' => 'https://127.0.0.1:3000/index.html',
        'allow' => true,
        'statusCode' => 200,
        'reasonPhrase' => 'OK',
        'downloadSuccess' => true,
        'processSuccess' => true,
        'writeSuccess' => true,
        'contentType' => '*/*',
        'newItemUri' => 'https://127.0.0.1:3000/index.html',
    ];

    /**
     * Uri
     */
    public function testItemUri(): void
    {
        $item = new Item(new Uri($this->getUrl('/index.html')));
        $this->assertInstanceOf(UriInterface::class, $item->getItemUri());
    }

    /**
     * Uri
     */
    public function testNewItemUri(): void
    {
        $item = new Item(new Uri($this->getUrl('/index.html')));
        $this->assertNull($item->getNewItemUri());
        $item->setNewItemUri(new Uri($this->getUrl('/index.html')));
        $this->assertInstanceOf(UriInterface::class, $item->getNewItemUri());
    }

    /**
     * Код ответа
     */
    public function testStatusCode(): void
    {
        $item = new Item(new Uri($this->getUrl('/index.html')));
        $this->assertNull($item->getStatusCode());
        $item->setStatusCode(200);
        $this->assertEquals(200, $item->getStatusCode());
    }

    /**
     * Текст ответа
     */
    public function testReasonPhrase(): void
    {
        $item = new Item(new Uri($this->getUrl('/index.html')));
        $this->assertNull($item->getReasonPhrase());
        $item->setReasonPhrase('Not found');
        $this->assertEquals('Not found', $item->getReasonPhrase());
    }

    /**
     * Тело ответа
     */
    public function testBody(): void
    {
        $item = new Item(new Uri($this->getUrl('/index.html')));

        $this->assertNull($item->getBody());
        $item->setBody('body');
        $this->assertEquals('body', $item->getBody());
    }

    /**
     * Подготовленное тело ответа
     */
    public function testPrepareBody(): void
    {
        $item = new Item(new Uri($this->getUrl('/index.html')));

        $this->assertNull($item->getPrepareBody());
        $item->setPrepareBody('body');
        $this->assertEquals('body', $item->getPrepareBody());
    }

    /**
     * Освободить тело ответа
     */
    public function testFree(): void
    {
        $item = new Item(new Uri($this->getUrl('/index.html')));
        $item->setBody('body');
        $this->assertEquals('body', $item->getBody());
        $item->setPrepareBody('body');
        $this->assertEquals('body', $item->getPrepareBody());
        $item->free();
        $this->assertNull($item->getBody());
        $this->assertNull($item->getPrepareBody());
    }

    /**
     * Тип контента
     */
    public function testContentType(): void
    {
        $item = new Item(new Uri($this->getUrl('/index.html')));
        $this->assertNull($item->getContentType());
        $item->setContentType(Mime::HTML);
        $this->assertEquals(Mime::HTML, $item->getContentType());
    }

    /**
     * Возвращает абсолютный uri
     */
    public function testAbsoluteUri(): void
    {
        $item = new Item(new Uri($this->getUrl('/index.html')));

        $this->assertEquals(
            'https://127.0.0.1:3000/some/path.html',
            $item->getAbsoluteUri(new Uri('/some/path.html'))->getUri()
        );
    }

    /**
     * Возвращает абсолютный uri
     */
    public function testAbsoluteUriFromRelative(): void
    {
        $item = new Item(new Uri($this->getUrl('/some/path/index.php')));

        $this->assertEquals(
            'https://127.0.0.1:3000/some/path.html',
            $item->getAbsoluteUri(new Uri('../path.html'))->getUri()
        );

        $this->assertEquals(
            'https://127.0.0.1:3000/path.html',
            $item->getAbsoluteUri(new Uri('../../path.html'))->getUri()
        );
    }

    /**
     * Загружен или нет
     */
    public function testDownload(): void
    {
        $item = new Item(new Uri($this->getUrl('/index.html')));
        $this->assertFalse($item->isDownloadSuccess());
        $item->setDownloadSuccess(true);
        $this->assertTrue($item->isDownloadSuccess());
    }

    /**
     * Обработан или нет
     */
    public function testProcess(): void
    {
        $item = new Item(new Uri($this->getUrl('/index.html')));
        $this->assertFalse($item->isProcessSuccess());
        $item->setProcessSuccess(true);
        $this->assertTrue($item->isProcessSuccess());
    }

    /**
     * Записан или нет
     */
    public function testWrite(): void
    {
        $item = new Item(new Uri($this->getUrl('/index.html')));
        $this->assertFalse($item->isWriteSuccess());
        $item->setWriteSuccess(true);
        $this->assertTrue($item->isWriteSuccess());
    }

    /**
     * Разрешена обработка или нет
     */
    public function testAllow(): void
    {
        $item = new Item(new Uri($this->getUrl('/index.html')));
        $this->assertFalse($item->isAllow());
        $item->setAllow(true);
        $this->assertTrue($item->isAllow());
    }

    /**
     * В массив
     */
    public function testToArray(): void
    {
        $item = new Item(new Uri($this->getUrl('/index.html')));
        $item->setStatusCode(200);
        $item->setReasonPhrase('OK');
        $item->setDownloadSuccess(true);
        $item->setProcessSuccess(true);
        $item->setWriteSuccess(true);
        $item->setAllow(true);
        $item->setBody('body');
        $item->setPrepareBody('body');
        $item->setContentType('*/*');
        $item->setNewItemUri(new Uri($this->getUrl('/index.html')));
        $this->assertEquals($this->itemArray, $item->toArray());
    }

    /**
     * Из массива
     */
    public function testFromArray(): void
    {
        $item = Item::fromArray($this->itemArray);
        $this->assertEquals('https://127.0.0.1:3000/index.html', $item->getItemUri()->getUri());
        $this->assertTrue($item->isAllow());
        $this->assertEquals(200, $item->getStatusCode());
        $this->assertEquals('OK', $item->getReasonPhrase());
        $this->assertTrue($item->isDownloadSuccess());
        $this->assertTrue($item->isProcessSuccess());
        $this->assertTrue($item->isWriteSuccess());
        $this->assertEquals('*/*', $item->getContentType());
        $this->assertEquals('https://127.0.0.1:3000/index.html', $item->getNewItemUri()->getUri());
    }

    /**
     * Из массива
     */
    public function testFromArrayException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        Item::fromArray([]);
    }
}
