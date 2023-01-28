<?php

declare(strict_types=1);

namespace Fi1a\Unit\Crawler\Writers;

use ErrorException;
use Fi1a\Crawler\Page;
use Fi1a\Crawler\Writers\FileWriter;
use Fi1a\Http\Uri;
use Fi1a\Unit\Crawler\TestCases\TestCase;
use InvalidArgumentException;

/**
 * Записывает результат обхода в файл
 */
class FileWriterTest extends TestCase
{
    /**
     * Записывает результат обхода в файл
     */
    public function testWrite(): void
    {
        $writer = $this->getMockBuilder(FileWriter::class)
            ->onlyMethods(['doWrite'])
            ->setConstructorArgs([$this->runtimeFolder . '/web'])
            ->getMock();

        $writer->expects($this->once())->method('doWrite')->willReturn(100);
        $this->assertTrue($writer->write($this->getPage()));
    }

    /**
     * Записывает результат обхода в файл
     */
    public function testWriteException(): void
    {
        $writer = $this->getMockBuilder(FileWriter::class)
            ->onlyMethods(['doWrite'])
            ->setConstructorArgs([$this->runtimeFolder . '/web'])
            ->getMock();

        $writer->expects($this->once())->method('doWrite')->willReturn(false);
        $this->assertFalse($writer->write($this->getPage()));
    }

    /**
     * Исключение при пустом пути к директории
     */
    public function testPathException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new FileWriter('');
    }

    /**
     * Исключение при создании директории
     */
    public function testDirNotCreateException(): void
    {
        $this->expectException(ErrorException::class);
        mkdir($this->runtimeFolder, 0000, true);
        try {
            new FileWriter($this->runtimeFolder . '/web');
        } catch (ErrorException $exception) {
            chmod($this->runtimeFolder, 0777);

            throw $exception;
        }
    }

    /**
     * Исключение при отсутсвии прав на запись в директорию
     */
    public function testDirNotWritable(): void
    {
        $this->expectException(ErrorException::class);
        mkdir($this->runtimeFolder . '/web', 0777, true);
        chmod($this->runtimeFolder . '/web', 0000);
        try {
            new FileWriter($this->runtimeFolder . '/web');
        } catch (ErrorException $exception) {
            chmod($this->runtimeFolder . '/web', 0777);

            throw $exception;
        }
    }

    /**
     * Пустой преобразованный uri для ссылки
     */
    public function testNotConvertedUri(): void
    {
        $this->expectException(ErrorException::class);
        $page = new Page(new Uri($this->getUrl('/index.html')));
        $writer = new FileWriter($this->runtimeFolder . '/web');
        $this->assertFalse($writer->write($page));
    }

    /**
     * Исключение при попытке создать директорию для файла
     */
    public function testNotCreatedPath(): void
    {
        $this->expectException(ErrorException::class);
        $writer = new FileWriter($this->runtimeFolder . '/web');
        $page = new Page(new Uri($this->getUrl('/path/index.html')));
        $page->setConvertedUri(new Uri('/path/index.html'));
        try {
            chmod($this->runtimeFolder . '/web', 0000);
            $writer->write($page);
        } catch (ErrorException $exception) {
            chmod($this->runtimeFolder . '/web', 0777);

            throw $exception;
        }
    }
}
