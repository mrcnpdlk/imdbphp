Fork from [tboothman/imdbphp](https://github.com/tboothman/imdbphp)
  - PHP 7 support
  - PSR16 cache support

imdbphp
=======

PHP library for retrieving film and TV information from IMDb.
Retrieve most of the information you can see on IMDb including films, TV series, TV episodes, people.
Search for titles on IMDb, including filtering by type (film, tv series, etc).
Download film posters and actor images.


Quick Start
===========

* Include [imdbphp/imdbphp](https://packagist.org/packages/imdbphp/imdbphp) using [composer](https://www.getcomposer.org), clone this repo or download the latest [release zip](https://github.com/tboothman/imdbphp/releases).
* Find a film you want the metadata for e.g. Lost in translation http://www.imdb.com/title/tt0335266/
* If you're not using composer or an autoloader include `bootstrap.php`.
* Get some data
```php
$title = new \Imdb\Title(335266);
$rating = $title->rating();
$plotOutline = $title->plotoutline();
```

Installation
============

This library scrapes imdb.com so changes their site can cause parts of this library to fail. You will probably need to update a few times a year. Keep this in mind when choosing how to install/configure.

For notifications of new releases try [Sibbell](https://sibbell.com)

Install the files:
* [Composer](https://www.getcomposer.org) (recommended). Include the [imdbphp/imdbphp](https://packagist.org/packages/imdbphp/imdbphp) package.
* Git clone. Checkout the latest release tag.

Install/enable the curl PHP extension


Configuration
=============

### LogInterface `Psr\Log\LoggerInterface`

```php
$oInstanceLogger = new \Monolog\Logger('IMDB');
$oInstanceLogger->pushHandler(new \Monolog\Handler\ErrorLogHandler(
        \Monolog\Handler\ErrorLogHandler::OPERATING_SYSTEM,
        \Psr\Log\LogLevel::DEBUG
    )
);
```

### CacheInterface (PSR16) `Psr\SimpleCache\CacheInterface`

```php
$oInstanceCacheRedis = new \phpFastCache\Helper\Psr16Adapter(
    'redis',
    [
        "host"                => null, // default localhost
        "port"                => null, // default 6379
        'defaultTtl'          => 3600 * 24, // 24h
        'ignoreSymfonyNotice' => true,
    ]);
```

### Dependency injection
```php
$oImdb = new Imdb\TitleSearch(null,$oInstanceLogger,$oInstanceCacheRedis);
```

Searching for a film
====================

```php
// include "bootstrap.php"; // Load the class in if you're not using an autoloader
$search = new \Imdb\TitleSearch(); // Optional $config parameter
$results = $search->search('The Matrix', [\Imdb\TitleSearch::MOVIE]); // Optional second parameter restricts types returned

// $results is an array of Title objects
// The objects will have title, year and movietype available
//  immediately, but any other data will have to be fetched from IMDb
foreach ($results as $result) { /* @var $result \Imdb\Title */
    echo $result->title() . ' ( ' . $result->year() . ')';
}
```

Searching for a person
======================
```php
// include "bootstrap.php"; // Load the class in if you're not using an autoloader
$search = new \Imdb\PersonSearch(); // Optional $config parameter
$results = $search->search('Forest Whitaker');

// $results is an array of Person objects
// The objects will have name available, everything else must be fetched from IMDb
foreach ($results as $result) { /* @var $result \Imdb\Person */
    echo $result->name();
}
```
