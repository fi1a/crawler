<?php

declare(strict_types=1);

namespace Fi1a\Unit\Crawler;

use DateTime;
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
        'downloadStatus' => true,
        'processStatus' => true,
        'writeStatus' => true,
        'contentType' => '*/*',
        'newItemUri' => 'https://127.0.0.1:3000/index.html',
        'expire' => '03.12.2030 06:07:34',
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
     * Сбрасывает состояние
     */
    public function testReset(): void
    {
        $item = new Item(new Uri($this->getUrl('/index.html')));
        $item->setBody('body');
        $this->assertEquals('body', $item->getBody());
        $item->setPrepareBody('body');
        $this->assertEquals('body', $item->getPrepareBody());

        $item->setStatusCode(200);
        $this->assertEquals(200, $item->getStatusCode());
        $item->setReasonPhrase('OK');
        $this->assertEquals('OK', $item->getReasonPhrase());
        $item->setDownloadStatus(true);
        $this->assertTrue($item->getDownloadStatus());
        $item->setProcessStatus(true);
        $this->assertTrue($item->getProcessStatus());
        $item->setWriteStatus(true);
        $this->assertTrue($item->getWriteStatus());
        $item->setContentType('*/*');
        $this->assertEquals('*/*', $item->getContentType());
        $item->setNewItemUri(new Uri($this->getUrl('/index.html')));
        $this->assertInstanceOf(UriInterface::class, $item->getNewItemUri());

        $item->reset();

        $this->assertNull($item->getBody());
        $this->assertNull($item->getPrepareBody());
        $this->assertNull($item->getStatusCode());
        $this->assertNull($item->getReasonPhrase());
        $this->assertNull($item->getDownloadStatus());
        $this->assertNull($item->getProcessStatus());
        $this->assertNull($item->getWriteStatus());
        $this->assertNull($item->getContentType());
        $this->assertNull($item->getNewItemUri());
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
            $item->getAbsoluteUri(new Uri('/some/path.html'))->uri()
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
            $item->getAbsoluteUri(new Uri('../path.html'))->uri()
        );

        $this->assertEquals(
            'https://127.0.0.1:3000/path.html',
            $item->getAbsoluteUri(new Uri('../../path.html'))->uri()
        );
    }

    /**
     * Возвращает абсолютный uri
     */
    public function testAbsoluteUriWithoutFilename(): void
    {
        $item = new Item(new Uri($this->getUrl('/some/path/path.php')));

        $this->assertEquals(
            'https://127.0.0.1:3000/some/path/path.php?foo=bar',
            $item->getAbsoluteUri(new Uri('?foo=bar'))->uri()
        );
    }

    /**
     * Загружен или нет
     */
    public function testDownload(): void
    {
        $item = new Item(new Uri($this->getUrl('/index.html')));
        $this->assertNull($item->getDownloadStatus());
        $item->setDownloadStatus(true);
        $this->assertTrue($item->getDownloadStatus());
    }

    /**
     * Обработан или нет
     */
    public function testProcess(): void
    {
        $item = new Item(new Uri($this->getUrl('/index.html')));
        $this->assertNull($item->getProcessStatus());
        $item->setProcessStatus(true);
        $this->assertTrue($item->getProcessStatus());
    }

    /**
     * Записан или нет
     */
    public function testWrite(): void
    {
        $item = new Item(new Uri($this->getUrl('/index.html')));
        $this->assertNull($item->getWriteStatus());
        $item->setWriteStatus(true);
        $this->assertTrue($item->getWriteStatus());
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
        $item->setDownloadStatus(true);
        $item->setProcessStatus(true);
        $item->setWriteStatus(true);
        $item->setAllow(true);
        $item->setBody('body');
        $item->setPrepareBody('body');
        $item->setContentType('*/*');
        $item->setNewItemUri(new Uri($this->getUrl('/index.html')));
        $item->expiresAt(DateTime::createFromFormat('d.m.Y H:i:s', '03.12.2030 06:07:34'));
        $this->assertEquals($this->itemArray, $item->toArray());
    }

    /**
     * Из массива
     */
    public function testFromArray(): void
    {
        $item = Item::fromArray($this->itemArray);
        $this->assertEquals('https://127.0.0.1:3000/index.html', $item->getItemUri()->uri());
        $this->assertTrue($item->isAllow());
        $this->assertEquals(200, $item->getStatusCode());
        $this->assertEquals('OK', $item->getReasonPhrase());
        $this->assertTrue($item->getDownloadStatus());
        $this->assertTrue($item->getProcessStatus());
        $this->assertTrue($item->getWriteStatus());
        $this->assertEquals('*/*', $item->getContentType());
        $this->assertEquals('https://127.0.0.1:3000/index.html', $item->getNewItemUri()->uri());
    }

    /**
     * Из массива
     */
    public function testFromArrayException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        Item::fromArray([]);
    }

    /**
     * Истечет в переданное время
     */
    public function testExpiresAt(): void
    {
        $item = new Item(new Uri($this->getUrl('/index.html')));
        $this->assertNull($item->getExpire());
        $this->assertFalse($item->isExpired());
        $item->expiresAt(new DateTime('- 1 hour'));
        $this->assertTrue($item->isExpired());
        $item->expiresAt(new DateTime('+ 1 hour'));
        $this->assertFalse($item->isExpired());
        $this->assertInstanceOf(DateTime::class, $item->getExpire());
    }

    /**
     * Истекает через переданное время
     */
    public function testExpiresAfter(): void
    {
        $item = new Item(new Uri($this->getUrl('/index.html')));
        $this->assertNull($item->getExpire());
        $this->assertFalse($item->isExpired());
        $item->expiresAfter(1);
        sleep(2);
        $this->assertTrue($item->isExpired());
        $item->expiresAfter(100);
        $this->assertFalse($item->isExpired());
        $this->assertInstanceOf(DateTime::class, $item->getExpire());
    }

    /**
     * Является ли изображением
     */
    public function testImage(): void
    {
        $item = new Item(new Uri($this->getUrl('/index.html')));
        $this->assertFalse($item->isImage());

        $item = new Item(new Uri($this->getUrl('/image.jpg')));
        $item->setContentType('image/jpeg');
        $this->assertTrue($item->isImage());
    }

    /**
     * Является ли "файлом"
     */
    public function testFile(): void
    {
        $item = new Item(new Uri($this->getUrl('/image.jpg')));
        $item->setContentType('image/jpeg');
        $this->assertFalse($item->isFile());

        $item = new Item(new Uri($this->getUrl('/file.pdf')));
        $this->assertTrue($item->isFile());
    }

    /**
     * Является ли "страницей"
     */
    public function testPage(): void
    {
        $item = new Item(new Uri($this->getUrl('/image.jpg')));
        $item->setContentType('image/jpeg');
        $this->assertFalse($item->isPage());

        $item = new Item(new Uri($this->getUrl('/index.html')));
        $item->setContentType(Mime::HTML);
        $this->assertTrue($item->isPage());
    }

    /**
     * Является ли Css файлом
     */
    public function testCss(): void
    {
        $item = new Item(new Uri($this->getUrl('/image.jpg')));
        $item->setContentType('image/jpeg');
        $this->assertFalse($item->isCss());

        $item = new Item(new Uri($this->getUrl('/style.css')));
        $this->assertTrue($item->isCss());
    }

    /**
     * Является ли Js файлом
     */
    public function testJs(): void
    {
        $item = new Item(new Uri($this->getUrl('/image.jpg')));
        $item->setContentType('image/jpeg');
        $this->assertFalse($item->isJs());

        $item = new Item(new Uri($this->getUrl('/style.css')));
        $item->setContentType('application/javascript');
        $this->assertTrue($item->isJs());
    }
}
