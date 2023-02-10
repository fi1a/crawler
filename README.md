# PHP crawler (парсинг сайта)

[![Latest Version][badge-release]][packagist]
[![Software License][badge-license]][license]
[![PHP Version][badge-php]][php]
![Coverage Status][badge-coverage]
[![Total Downloads][badge-downloads]][downloads]
[![Support mail][badge-mail]][mail]

Этот пакет предоставляет API для обхода ссылок и скачивания файлов (парсинга сайта). С помощью данного пакета вы можете забирать
любую информацию со стороннего сайта. Есть возможность создать собственные обработчики, которые позволят
кастомизировать логику парсинга страницы, подготовки и сохранения.

## Установка

Установить этот пакет можно как зависимость, используя Composer.

``` bash
composer require fi1a/crawler
```

## Шаги процесса парсинга

Процесс парсинга разделен на три шага:

1. загрузка;
1. процесс;
1. запись.

На шаге "загрузка" осуществляется обход страниц или файлов и их загрузка. Парсинг новых ссылок  из
загруженной страницы осуществляется классом реализующим интерфейс `Fi1a\Crawler\UriParsers\UriParserInterface`.
Определение загружать адрес или нет осуществляется классом реализующим интерфейс
`Fi1a\Crawler\Restrictions\RestrictionInterface`.

Шаг "процесс" идет следующим за шагом "загрузка". На данном шаге осуществляется преобразование адресов
классом реализующим интерфейс `Fi1a\Crawler\UriTransformers\UriTransformerInterface`.

Последним шагом идет шаг "запись". Перед записью осуществляется подготовка контента с помощью класса реализующего
интерфейс `Fi1a\Crawler\PrepareItems\PrepareItemInterface` (класс `Fi1a\Crawler\PrepareItems\PrepareHtmlItem`
заменяет старые ссылки на новые). Запись осуществляется с помощью `Fi1a\Crawler\Writers\WriterInterface`.

Методом `run` класса `Fi1a\Crawler\Crawler` запускается все три шага последовательно, но можно запускать шаги поочередно
методами `download` (шаг загрузки), `process` (шаг процесса) и `write` (шаг записи).

## Примеры

Ниже представлены наиболее часто встречающиеся задачи для обхода веб-сайтов.

### Создание копии сайта

С помощью представленного кода можно создать копию веб-сайта `https://some-domain.ru`
в указанной директории `__DIR__ . '/local-site'`.

```php
use Fi1a\Crawler\Config;
use Fi1a\Crawler\ConfigInterface;
use Fi1a\Crawler\Crawler;
use Fi1a\Crawler\ItemStorages\ItemStorage;
use Fi1a\Crawler\ItemStorages\StorageAdapters\LocalFilesystemAdapter;
use Fi1a\Crawler\Writers\FileWriter;

$config = new Config();

$config->setVerbose(ConfigInterface::VERBOSE_DEBUG)
    ->setSizeLimit('5Mb')
    ->addStartUri('https://some-domain.ru');

$crawler = new Crawler($config, new ItemStorage(new LocalFilesystemAdapter(__DIR__ . '/runtime/storage')));

$crawler->setWriter(new FileWriter(__DIR__ . '/local-site'));

$crawler->run();
```

Для начала нужно создать объект конфигурации `Config` и передать его в конструктор класса `Crawler` вместе с 
объектом класса `ItemStorage`. Затем установить объект `FileWriter` с помощью метода `setWriter` класса `Crawler`
реализующего логику сохранения элементов (страниц, файлов) в локальную файловую систему.

Запуск парсинга сайта осуществляется с помощью метода `run` класса `Crawler`.

### Парсинг новостей

Класс парсера ссылок новостей. Находит на странице списка и возвращает ссылки относящиеся к
детальной новости и к списку.

```php
namespace Foo\UriParsers;

use Fi1a\Console\IO\ConsoleOutputInterface;
use Fi1a\Crawler\ItemInterface;
use Fi1a\Crawler\UriCollection;
use Fi1a\Crawler\UriCollectionInterface;
use Fi1a\Crawler\UriParsers\UriParserInterface;
use Fi1a\Http\Uri;
use Fi1a\Log\LoggerInterface;
use Fi1a\SimpleQuery\SimpleQuery;
use InvalidArgumentException;

/**
 * Парсер ссылок новостей
 */
class NewsUriParser implements UriParserInterface
{
    /**
     * @inheritDoc
     */
    public function parse(
        ItemInterface $item,
        ConsoleOutputInterface $output,
        LoggerInterface $logger
    ): UriCollectionInterface {
        $collection = new UriCollection();

        if (
            !$item->isAllow()
            || $item->getItemUri()->host() !== 'news-domain.ru'
            || $item->getItemUri()->path() !== '/news/'
        ) {
            return $collection;
        }

        $sq = new SimpleQuery((string) $item->getBody());

        // выбираем ссылки ведущие на детальную новости и ссылки постраничной навигации
        $nodes = $sq('#news .header, #news .pm_s, #news .pm_n');
        /** @var \DOMElement $node */
        foreach ($nodes as $node) {
            $value = $sq($node)->attr('href');
            if (!is_string($value) || !$value) {
                continue;
            }
            try {
                $uri = new Uri($value);
            } catch (InvalidArgumentException $exception) {
                continue;
            }

            $collection[] = $uri;
        }

        return $collection;
    }
}
```

Класс преобразования ссылок. Преобразует ссылки новостей из формата источника в новый формат
(https://news-domain.ru/news/news-code-1.html => /news/news-code-1/).

```php
namespace Foo\UriTransformers;

use Fi1a\Console\IO\ConsoleOutputInterface;
use Fi1a\Crawler\ItemInterface;
use Fi1a\Crawler\UriTransformers\UriTransformerInterface;
use Fi1a\Http\UriInterface;
use Fi1a\Log\LoggerInterface;

/**
 * Преобразует uri новостей из внешних адресов в новые
 */
class NewsUriTransformer implements UriTransformerInterface
{
    /**
     * @inheritDoc
     */
    public function transform(
        ItemInterface $item,
        ConsoleOutputInterface $output,
        LoggerInterface $logger
    ): UriInterface {
        if (!$item->isAllow()) {
            return $item->getItemUri();
        }

        $isNewsPage = preg_match(
            '#https://news-domain.ru/news/(.+)\.html#mui',
            $item->getItemUri()->uri(),
                $matches
        ) > 0;

        if (!$isNewsPage) {
            $output->writeln('    <color=blue>- Не является ссылкой на новость</>');

            return $item->getItemUri();
        }

        // Преобразуем ссылки на новости в новый формат
        $object = $item->getItemUri()
            ->withHost('')
            ->withPort(null)
            ->withPath('/news/' . $matches[1] . '/');

        return $object;
    }
}
```

Класс подготавливает HTML новости удаляя "хлебные крошки" и блоки не относящиеся к контенту новости.

```php
namespace Foo\PrepareItems;

use Fi1a\Console\IO\ConsoleOutputInterface;
use Fi1a\Crawler\ItemCollectionInterface;
use Fi1a\Crawler\ItemInterface;
use Fi1a\Crawler\PrepareItems\PrepareHtmlItem;
use Fi1a\Log\LoggerInterface;
use Fi1a\SimpleQuery\SimpleQuery;

/**
 * Подготавливает HTML новости
 */
class NewsPrepareItem extends PrepareHtmlItem
{
    /**
     * @inheritDoc
     */
    public function prepare(
        ItemInterface $item,
        ItemCollectionInterface $items,
        ConsoleOutputInterface $output,
        LoggerInterface $logger
    ) {
        $isNewsPage = preg_match(
            '#https://news-domain.ru/news/(.+)\.html#mui',
            $item->getItemUri()->uri()
        ) > 0;

        if (!$isNewsPage) {
            return false;
        }

        $sq = new SimpleQuery((string) $item->getBody(), 'UTF-8');

        $news = $sq('#news');
        // Удаляем лишние элементы, остается только новость с заголовком и контентом
        $news('.share, .breadcrumbs, p:last-child')->remove();

        // Заменяем ссылки на новые ссылки новостей
        $this->replace('a', 'href', $news, $item, $items);

        return $news->html();
    }
}
```

Класс добавляющий/обновляющий новости на сайте 1С-Битрикс записывая их в ИБ.

```php
namespace Foo\Writers;

use ErrorException;
use Fi1a\Console\IO\ConsoleOutputInterface;
use Fi1a\Crawler\ItemInterface;
use Fi1a\Crawler\Writers\WriterInterface;
use Fi1a\Log\LoggerInterface;
use Fi1a\SimpleQuery\SimpleQuery;
use Bitrix\Main\Loader;
use Bitrix\Iblock\IblockTable;

/**
 * Записывает результат в ИБ 1С-Битрикса
 */
class NewsWriter implements WriterInterface
{
    /**
     * @var int
     */
    protected $newsIblockId;

    public function __construct()
    {
        Loader::includeModule('iblock');

        $iblock = IblockTable::query()
            ->setSelect(['ID',])
            ->where('CODE', '=', 'furniture_news_s1')
            ->exec()
            ->fetch();

        if (!$iblock) {
            throw new ErrorException('Инфоблок новостей не найден');
        }

        $this->newsIblockId = (int) $iblock['ID'];
    }

    /**
     * @inheritDoc
     */
    public function write(ItemInterface $item, ConsoleOutputInterface $output, LoggerInterface $logger): bool
    {
        $isNewsPage = preg_match(
                '#https://news-domain.ru/news/(.+)\.html#mui',
                $item->getItemUri()->uri(),
                $matches
            ) > 0;

        if (!$isNewsPage) {
            $output->writeln('    <color=blue>- Не является страницей новости</>');

            return false;
        }

        $sq = new SimpleQuery((string) $item->getPrepareBody(), 'UTF-8');

        $name = $sq('h1')->html();
        $sq('h1')->remove();
        $code = $matches[1];
        $detailText = $sq('body')->html();
        $previewText = \TruncateText(strip_tags($detailText), 50);

        $fields = [
            'IBLOCK_ID' => $this->newsIblockId,
            'NAME' => $name,
            'CODE' => $code,
            'DETAIL_TEXT' => $detailText,
            'DETAIL_TEXT_TYPE' => 'html',
            'PREVIEW_TEXT' => $previewText,
            'ACTIVE' => 'Y',
        ];

        $news = \CIBlockElement::GetList([], [
            '=IBLOCK_ID' => $this->newsIblockId,
            '=CODE' => $code,
        ], false, false, ['ID'])->Fetch();

        $instance = new \CIBlockElement();

        if ($news) {
            $result = $instance->Update($news['ID'], $fields);
            if ($result === false) {
                $output->writeln('    <error>Не удалось обновить новость: {{}}</>', [$instance->LAST_ERROR]);
            }

            return $result;
        }

        $newsId = (int) $instance->Add($fields);

        if (!$newsId) {
            $output->writeln('    <error>Не удалось создать новость: {{}}</>', [$instance->LAST_ERROR]);

            return false;
        }

        return true;
    }
}
```

Создается объект конфигурации `Config` со значениями:
- уровень подробности вывода;
- время жизни элементов в хранилище (0 - без ограничения);
- ограничение на загружаемый файл (5Mb для всех типов файлов);
- добавляется точка входа, с которой начинается обход (https://news-domain.ru/news/ - список новостей)

Устанавливаем классы определяющие поведение:
- метод `setUriParser` устанавливает парсер uri для обхода (в зависимости от типа контента);
- метод `setUriTransformer` устанавливает класс преобразователь адресов из внешних во внутренние;
- метод `setPrepareItem` устанавливает класс подготавливающий контент (удаляет лишние теги не относящиеся к новости);
- метод `setWriter` устанавливает класс записывающий результат обхода (записывает новость в ИБ 1С-Битрикса).

Методом `loadFromStorage` класса `Fi1a\Crawler\Crawler` загружаем из хранилища обработанные элементы с последнего запуска и
для страниц списка новостей, отмечаем повторную обработку с целью найти новые добавленные новости.

Запускаем парсинг новостей методом `run` класса `Fi1a\Crawler\Crawler`.

```php
use Fi1a\Crawler\Config;
use Fi1a\Crawler\ConfigInterface;
use Fi1a\Crawler\Crawler;
use Fi1a\Crawler\ItemInterface;
use Fi1a\Crawler\ItemStorages\ItemStorage;
use Fi1a\Crawler\ItemStorages\StorageAdapters\LocalFilesystemAdapter;
use Fi1a\Http\Mime;
use Foo\PrepareItems\NewsPrepareItem;
use Foo\UriParsers\NewsUriParser;
use Foo\UriTransformers\NewsUriTransformer;
use Foo\Writers\NewsWriter;

$config = new Config();

$config->setVerbose(ConfigInterface::VERBOSE_DEBUG)
    ->setLifetime(0)
    ->setSizeLimit('5Mb')
    ->addStartUri('https://news-domain.ru/news/');

$crawler = new Crawler($config, new ItemStorage(new LocalFilesystemAdapter(__DIR__ . '/runtime/storage')));

$crawler->setUriParser(new NewsUriParser(), Mime::HTML)
    ->setUriTransformer(new NewsUriTransformer())
    ->setPrepareItem(new NewsPrepareItem())
    ->setWriter(new NewsWriter(), Mime::HTML);

$crawler->loadFromStorage();

// При повторном запуске страницы списка помечаем на повторную обработку для добавления новых новостей
foreach ($crawler->getItems() as $item) {
    assert($item instanceof ItemInterface);
    if (
        !$item->isAllow()
        || $item->getItemUri()->host() !== 'news-domain.ru'
        || $item->getItemUri()->path() !== '/news/'
    ) {
        continue;
    }

    $item->setDownloadStatus(null);
    $item->setProcessStatus(null);
    $item->setWriteStatus(null);
}

$crawler->run();
```

## Основные классы пакета:

Ниже представлены основные классы пакета. С помощью одних можно настроить поведение парсера,
а с помощью других расширить его.

- `Fi1a\Crawler\Crawler` - основной класс пакета;
- `Fi1a\Crawler\Config` - конфигурация парсинга;
- `Fi1a\Crawler\UriCollection` - коллекция адресов.
- `Fi1a\Crawler\Item` - элемент обхода;
- `Fi1a\Crawler\ItemCollection` - коллекция элементов обхода.
- `Fi1a\Crawler\ItemStorages\ItemStorage` - реализует хранилище элементов парсинга;
  - `Fi1a\Crawler\ItemStorages\StorageAdapters\LocalFilesystemAdapter` - адаптер для хранения в локальной файловой системе;
  - `Fi1a\Crawler\ItemStorages\StorageAdapters\FilesystemAdapter` - адаптер для хранения в файловой системе;
- Прокси
  - `Fi1a\Crawler\Proxy\Proxy` - прокси для запроса;
  - `Fi1a\Crawler\Proxy\ProxyCollection` - коллекция прокси;
  - `Fi1a\Crawler\Proxy\ProxyStorage` - реализует хранилище для прокси;
    - `Fi1a\Crawler\Proxy\StorageAdapters\LocalFilesystemAdapter` - адаптер для хранения в локальной файловой системе;
    - `Fi1a\Crawler\Proxy\StorageAdapters\FilesystemAdapter` - адаптер для хранения в файловой системе;
  - Подбор подходящих прокси для запроса
    - `Fi1a\Crawler\Proxy\Selections\FilterByAttempts` - фильтрация прокси по числу ошибок соединения;
    - `Fi1a\Crawler\Proxy\Selections\Limit` - ограничение на кол-во подобранных прокси;
    - `Fi1a\Crawler\Proxy\Selections\OnlyActive` - фильтрация прокси по активности;
    - `Fi1a\Crawler\Proxy\Selections\SortedByTime` - отсортированные по времени использования;
- Классы расширяющие операции
  - Ограничение обхода uri
    - `Fi1a\Crawler\Restrictions\NotAllowRestriction` - запрет на обход для всех uri;
    - `Fi1a\Crawler\Restrictions\UriRestriction` - ограничение по домену и пути;
  - Шаг загрузки
    - `Fi1a\Crawler\UriParsers\HtmlUriParser` - парсит html и возвращает uri для обхода;
  - Шаг преобразования uri
    - `Fi1a\Crawler\UriTransformers\SiteUriTransformer` - преобразует uri из внешних адресов в локальные;
  - Шаг записи
    - `Fi1a\Crawler\PrepareItem\PrepareHtmlItem` - подготавливает HTML элемент (заменяет ссылки страницы на новые);
    - `Fi1a\Crawler\Writers\FileWriter` - записывает результат обхода в файл;

## Объект настроек

- `startUri` - точка входа, с которой начинается обход;
  - `addStartUri(string $startUri)` - добавить точку входа;
  - `getStartUri(): array` - возвращает добавленные точки входа;
- `httpClientConfig` - объект настроек http-клиента ([подробнее об объекте настроек](https://github.com/fi1a/http-client#объект-настроек));
  - `setHttpClientConfig(Fi1a\HttpClient\ConfigInterface $config)` - установить объект настроек http-клиента;
  - `getHttpClientConfig(): Fi1a\HttpClient\ConfigInterface` - возвращает объект настроек http-клиента;
- `httpClientHandler` ("Fi1a\HttpClient\Handlers\StreamHandler") - обработчик запросов (возможные значения: "Fi1a\HttpClient\Handlers\StreamHandler", "Fi1a\HttpClient\Handlers\CurlHandler")
  - `setHttpClientHandler(string $handler)` - установить обработчик запросов;
  - `getHttpClientHandler(): string` - вернуть обработчик запросов;
- `verbose` (ConfigInterface::VERBOSE_NORMAL) - уровень подробности вывода (возможные значения: ConfigInterface::VERBOSE_NONE, ConfigInterface::VERBOSE_NORMAL,
ConfigInterface::VERBOSE_HIGHT, ConfigInterface::VERBOSE_HIGHTEST, ConfigInterface::VERBOSE_DEBUG);
  - `setVerbose(int $verbose)` - установить уровень подробности вывода;
  - `getVerbose(): int` - вернуть уровень подробности вывода;
- `logChannel` ("crawler") - канал логирования;
  - `setLogChannel(string $logChannel)` - установить канал логирования;
  - `getLogChannel(): string` - вернуть канал логирования;
- `saveAfterQuantity` (10) - параметр, определяющий через какое новое кол-во элементов сохранять элементы в хранилище;
  - `setSaveAfterQuantity(int $quantity)` - установить параметр, определяющий через какое новое кол-во элементов сохранять элементы в хранилище;
  - `getSaveAfterQuantity(): int` - возвращает параметр, определяющий через какое новое кол-во элементов сохранять элементы в хранилище;
- `lifeTime` (24 * 60 * 60) - время жизни элементов в хранилище;
  - `setLifetime(int $lifeTime)` - установить время жизни элементов в хранилище;
  - `getLifetime(): int` - вернуть время жизни элементов в хранилище;
- `delay` ([0, 0]) - пауза между запросами;
  - `setDelay($delay)` - установить паузу между запросами (возможные значения: int|array<array-key, int>);
  - `getDelay(): array` - вернуть паузу между запросами;
- `sizeLimits` - ограничение на загружаемый файл по типу контента;
  - `setSizeLimit($sizeLimit, ?string $mime = null)` - установить ограничение на загружаемый файл по типу контента;
  - `getSizeLimits(): array` - возвращает ограничения на загружаемые файлы по типу контента;
- `retry` (3) - кол-во попыток запросов к адресу при http ошибки;
  - `setRetry(int $retry)` - установить кол-во попыток запросов к адресу при http ошибки;
  - `getRetry(): int` - вернуть кол-во попыток запросов к адресу при http ошибки.

Пример:

- установить уровень подробности вывода на самый наивысший уровень ConfigInterface::VERBOSE_DEBUG;
- ограничение на все загружаемые файлы в 5Mb и на файл типа jpeg в 1Mb;
- пауза между запросами случайным образом от 3 до 10 секунд;
- добавить точку входа `https://some-domain.ru`.

```php
use Fi1a\Crawler\Config;
use Fi1a\Crawler\ConfigInterface;

$config = new Config();

$config->setVerbose(ConfigInterface::VERBOSE_DEBUG)
    ->setSizeLimit('5Mb')
    ->setSizeLimit('1Mb', 'image/jpeg')
    ->setDelay([3, 10])
    ->addStartUri('https://some-domain.ru');
```

## Ограничение обхода

Для ограничения обхода парсером используется класс реализующий интерфейс `Fi1a\Crawler\Restrictions\RestrictionInterface`,
добавленный методом `addRestriction` класса `Fi1a\Crawler\Crawler`.

В пакете имеются два класса для реализации ограничения:

- `Fi1a\Crawler\Restrictions\NotAllowRestriction` - запрет на обход;
- `Fi1a\Crawler\Restrictions\UriRestriction` - ограничение по домену и пути;

Пример ограничения обхода папкой news домена some-domain.ru:

```php
use Fi1a\Crawler\Restrictions\UriRestriction;

$crawler->addRestriction(new UriRestriction('https://some-domain.ru/news/'));
```

Если не были заданы ограничения при начале шага загрузки, они `Fi1a\Crawler\Restrictions\UriRestriction`
добавляются автоматически на основе точек входа заданных методом `addStartUri` объекта конфигурации.

## Элемент обхода

При парсинге объект класса `Fi1a\Crawler\Item` используется как конечная точка адреса содержащая в себе всю
необходимую информацию для парсинга.

Методы класса:

| Метод                                           | Описание                                                    |
|-------------------------------------------------|-------------------------------------------------------------|
| getItemUri(): UriInterface                      | Возвращает uri                                              |
| setStatusCode(?int $statusCode)                 | Устанавливает код статуса ответа                            |
| getStatusCode(): ?int                           | Возвращает код статуса ответа                               |
| setReasonPhrase(?string $reasonPhrase)          | Устанавливает текст причины ассоциированный с кодом статуса |
| getReasonPhrase(): ?string                      | Возвращает текст причины ассоциированный с кодом статуса    |
| setDownloadStatus(?bool $status)                | Запрос выполнен успешно или нет                             |
| getDownloadStatus(): ?bool                      | Запрос выполнен успешно или нет                             |
| setProcessStatus(?bool $status)                 | Обработка выполнена успешно или нет                         |
| getProcessStatus(): ?bool                       | Обработка выполнена успешно или нет                         |
| setWriteStatus(?bool $status)                   | Запись выполнена успешно или нет                            |
| getWriteStatus(): ?bool                         | Запись выполнена успешно или нет                            |
| setAllow(bool $allow)                           | Разрешено к обработке или нет                               |
| isAllow(): bool                                 | Разрешено к обработке или нет                               |
| setBody(string $body)                           | Установить тело ответа                                      |
| getBody(): ?string                              | Вернуть тело ответа                                         |
| setPrepareBody($body)                           | Установить подготовленное тело ответа                       |
| getPrepareBody()                                | Вернуть подготовленное тело ответа                          |
| free()                                          | Очищает тело запроса                                        |
| reset()                                         | Сбрасывает состояние                                        |
| setContentType(?string $contentType)            | Установить тип контента                                     |
| getContentType(): ?string                       | Вернуть тип контента                                        |
| setNewItemUri(UriInterface $newItemUri)         | Установить новый uri                                        |
| getNewItemUri(): ?UriInterface                  | Вернуть новый uri                                           |
| expiresAt(?DateTime $dateTime)                  | Истечет в переданное время                                  |
| expiresAfter(?int $lifetime)                    | Истекает через переданное время                             |
| getExpire(): ?DateTime                          | Возвращает когда закончится срок жизни                      |
| isExpired(): bool                               | Срок жизни истек                                            |
| getAbsoluteUri(UriInterface $uri): UriInterface | Возвращает абсолютный путь относительно элемента            |
| isImage(): bool                                 | Является ли изображением                                    |
| isFile(): bool                                  | Является ли "файлом"                                        |
| isPage(): bool                                  | Является ли "страницей"                                     |
| isCss(): bool                                   | Является ли Css файлом                                      |
| isJs(): bool                                    | Является ли Js файлом                                       |
| toArray(): array                                | В массив                                                    |
| static fromArray(array $fields)                 | Из массива                                                  |

Получить коллекцию элементов обхода можно методом `getItems` класса `Fi1a\Crawler\Crawler`.

## Геттеры коллекций элементов обхода

После выполнения парсинга или загрузки элементов из хранилища с помощью метода `loadFromStorage`
класса `Fi1a\Crawler\Crawler` становится доступна коллекция элементов `Fi1a\Crawler\ItemCollectionInterface`, которую
можно получить методом `getItems` класса `Fi1a\Crawler\Crawler`.

У данной коллекции есть вспомогательные методы, позволяющие отфильтровать элементы коллекции
по какому либо признаку:

- `getDownloaded` - возвращает успешно загруженные элементы;
- `getProcessed` - возвращает успешно обработанные элементы;
- `getWrited` - возвращает успешно записанные элементы;
- `getImages` - возвращает все элементы изображений;
- `getFiles` - возвращает все элементы файлов;
- `getPages` - возвращает все элементы страниц;
- `getCss` - возвращает все элементы css файлов;
- `getJs` - возвращает все элементы js файлов.

Пример выведет все ссылки на загруженные изображения:

```php
$crawler->loadFromStorage();
$collection = $crawler->getItems();

foreach ($collection->getDownloaded()->getImages() as $item) {
    echo $item->getItemUri()->uri() . PHP_EOL;
}
```

## Использование прокси при запросах

При парсинге сайтов часто требуется использовать прокси. Данный пакет имеет вспомогательные классы для работы с
прокси при запросах.

При работе для записи или чтения информации о прокси используется класс `Fi1a\Crawler\Proxy\ProxyStorage`. Данный класс
реализует хранилище прокси. Каким образом будет осуществляться хранение определяется адаптером передаваемым
первым аргументов в конструктор (адаптер `Fi1a\Crawler\Proxy\StorageAdapters\LocalFilesystemAdapter` осуществляет
хранение прокси в json-файле).

Следующий код добавляет прокси в хранилище:

```php
use Fi1a\Crawler\Proxy\Proxy;
use Fi1a\Crawler\Proxy\ProxyStorage;
use Fi1a\Crawler\Proxy\StorageAdapters\LocalFilesystemAdapter;

$proxyStorage = new ProxyStorage(new LocalFilesystemAdapter(__DIR__ . '/runtime'));

$httpProxy = Proxy::factory([
    'type' => 'http',
    'host' => '127.0.0.1',
    'port' => 50100,
    'userName' => 'username',
    'password' => 'password',
]);
$proxyStorage->save($httpProxy);

$httpProxy = Proxy::factory([
    'type' => 'socks5',
    'host' => '127.0.0.1',
    'port' => 50101,
    'userName' => 'username',
    'password' => 'password',
]);
$proxyStorage->save($httpProxy);
```

При следующем запуске парсера данные прокси будут загружены из хранилища и использованы для запросов.

Подбор подходящих прокси осуществляется с помощью классов `Fi1a\Crawler\Proxy\Selections\ProxySelectionInterface`:

- `Fi1a\Crawler\Proxy\Selections\FilterByAttempts` - фильтрация прокси по числу ошибок соединения;
- `Fi1a\Crawler\Proxy\Selections\Limit` - ограничение на кол-во подобранных прокси;
- `Fi1a\Crawler\Proxy\Selections\OnlyActive` - фильтрация прокси по активности;
- `Fi1a\Crawler\Proxy\Selections\SortedByTime` - отсортированные по времени использования;

Следующий код выберет только активные прокси (`OnlyActive`), отфильтрует по числу ошибок (`FilterByAttempts`),
отсортирует по времени использования (`SortedByTime`) и вернет одну прокси (`Limit`) для использования в запросе:

```php
$crawler->setProxySelection(new Limit(new SortedByTime(new FilterByAttempts(new OnlyActive(), 3)), 1));
```

Пример парсинга сайта с использованием сохраненных прокси в хранилище:

```php
use Fi1a\Crawler\Config;
use Fi1a\Crawler\ConfigInterface;
use Fi1a\Crawler\Crawler;
use Fi1a\Crawler\ItemStorages\ItemStorage;
use Fi1a\Crawler\ItemStorages\StorageAdapters\LocalFilesystemAdapter;
use Fi1a\Crawler\Proxy\ProxyStorage;
use Fi1a\Crawler\Proxy\Selections\FilterByAttempts;
use Fi1a\Crawler\Proxy\Selections\Limit;
use Fi1a\Crawler\Proxy\Selections\OnlyActive;
use Fi1a\Crawler\Proxy\Selections\SortedByTime;
use Fi1a\Crawler\Proxy\StorageAdapters\LocalFilesystemAdapter as ProxyStorageLocalFilesystemAdapter;
use Fi1a\Crawler\Writers\FileWriter;

$config = new Config();

$config->setVerbose(ConfigInterface::VERBOSE_DEBUG)
    ->setSizeLimit('5Mb')
    ->addStartUri('https://some-domain.ru');

$crawler = new Crawler(
    $config,
    new ItemStorage(new LocalFilesystemAdapter(__DIR__ . '/runtime/storage')),
    new ProxyStorage(new ProxyStorageLocalFilesystemAdapter(__DIR__ . '/runtime'))
);

$crawler->setProxySelection(new Limit(new SortedByTime(new FilterByAttempts(new OnlyActive(), 3)), 1))
    ->setWriter(new FileWriter(__DIR__ . '/local-site'));

$crawler->run();
```

[badge-release]: https://img.shields.io/packagist/v/fi1a/crawler?label=release
[badge-license]: https://img.shields.io/github/license/fi1a/crawler?style=flat-square
[badge-php]: https://img.shields.io/packagist/php-v/fi1a/crawler?style=flat-square
[badge-coverage]: https://img.shields.io/badge/coverage-100%25-green
[badge-downloads]: https://img.shields.io/packagist/dt/fi1a/crawler.svg?style=flat-square&colorB=mediumvioletred
[badge-mail]: https://img.shields.io/badge/mail-support%40fi1a.ru-brightgreen

[packagist]: https://packagist.org/packages/fi1a/crawler
[license]: https://github.com/fi1a/crawler/blob/master/LICENSE
[php]: https://php.net
[downloads]: https://packagist.org/packages/fi1a/crawler
[mail]: mailto:support@fi1a.ru