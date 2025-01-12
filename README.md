<img src="https://github.com/stefangabos/zebrajs/blob/master/docs/images/logo.png" alt="zebra-curl-logo" align="right" width="90">

# Zebra Cache &nbsp;[![Tweet](https://img.shields.io/twitter/url/http/shields.io.svg?style=social)](https://twitter.com/intent/tweet?text=A+file-based+lightweight+PHP+caching+library+that+uses+file+locking+to+ensure+proper+functionality+under+heavy+load&url=https://github.com/stefangabos/Zebra_Cache&via=stefangabos&hashtags=php,cache)

*A lightweight and flexible PHP caching library with support for file, Redis, and Memcached storage backends.*

[![Latest Stable Version](https://poser.pugx.org/stefangabos/zebra_cache/v/stable)](https://packagist.org/packages/stefangabos/zebra_cache) [![Total Downloads](https://poser.pugx.org/stefangabos/zebra_cache/downloads)](https://packagist.org/packages/stefangabos/zebra_cache) [![Monthly Downloads](https://poser.pugx.org/stefangabos/zebra_cache/d/monthly)](https://packagist.org/packages/stefangabos/zebra_cache) [![Daily Downloads](https://poser.pugx.org/stefangabos/zebra_cache/d/daily)](https://packagist.org/packages/stefangabos/zebra_cache) [![License](https://poser.pugx.org/stefangabos/zebra_cache/license)](https://packagist.org/packages/stefangabos/zebra_cache)

It supports common caching operations such as storing, retrieving and deleting data, as well as checking for the existence of a cache entry.

## Features

- pluggable architecture for using different storage mechanisms
- flexible key-value caching
- automatic expiration of cache items based on a specified time-to-live (TTL) value
- extensible design allowing developers to integrate new storage backends
- easy-to-use API with consistent behavior across different backends
- support for multiple instances, allowing you to use different cache configurations for different parts of your application

## :notebook_with_decorative_cover: Documentation

Check out the [awesome documentation](https://stefangabos.github.io/Zebra_Cache/Zebra_Cache/Zebra_Cache.html)!

## 🎂 Support the development of this project

Your support is greatly appreciated and it keeps me motivated continue working on open source projects. If you enjoy this project please star it by clicking on the star button at the top of the page. If you're feeling generous, you can also buy me a coffee through PayPal or become a sponsor.
**Thank you for your support!** 🎉

[<img src="https://img.shields.io/github/stars/stefangabos/zebra_cache?color=green&label=star%20it%20on%20GitHub" width="132" height="20" alt="Star it on GitHub">](https://github.com/stefangabos/Zebra_Cache) [![Donate](https://img.shields.io/badge/Donate-PayPal-green.svg)](https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=W6MCFT65DRN64) [<img src="https://img.shields.io/badge/-Sponsor-fafbfc?logo=GitHub%20Sponsors">](https://github.com/sponsors/stefangabos)


## Requirements

PHP 7.0.0+

Use version [1.3.2](https://github.com/stefangabos/Zebra_Cache/releases/tag/1.3.2) if you need support for PHP 5.3.0+

## Installation

You can install via [Composer](https://packagist.org/packages/stefangabos/zebra_cache)

```bash
# get the latest stable release
composer require stefangabos/zebra_cache

# get the latest commit
composer require stefangabos/zebra_cache:dev-master
```

## How to use

Initializing **file-based storage**:

```php
// make sure you have this at the top of your script
use stefangabos\Zebra_Cache\Zebra_Cache;
use stefangabos\Zebra_Cache\Storage\Storage_File;

// initialize file-based storage
$storage = new Storage_File('/path/to/cache/folder');
```

Initializing **Redis-based storage**:

```php
// make sure you have this at the top of your script
use stefangabos\Zebra_Cache\Zebra_Cache;
use stefangabos\Zebra_Cache\Storage\Storage_Redis;

// connect to a Redis server
$redis = new Redis();
$redis->connect('127.0.0.1', 6379);

// pass the $redis instance as argument to initialize the Redis-based storage
$storage = new Storage_Redis($redis);

// finally, instantiate the caching library using the storage engine configured above
$cache = new stefangabos\Zebra_Cache\Zebra_Cache($storage);
```

Initializing **Memcached-based storage**:

There are two PHP extensions for working with [Memcached](https://memcached.org/): the [memcache](https://www.php.net/manual/en/book.memcache.php) extension, which is older and less commonly used, and [memcached](https://www.php.net/manual/en/book.memcached.php) which is generally preferred for better features and compatibility.
This library supports both.

```php
// make sure you have this at the top of your script
use stefangabos\Zebra_Cache\Zebra_Cache;
use stefangabos\Zebra_Cache\Storage\Storage_Memcached;

// connect to a Memcached server (using the `memcached` extension)
$memcache = new Memcached();
$memcache->addServer('localhost', 11211);

// OR using the `memcache` extension
$memcache = new Memcache();
$memcache->addServer('localhost', 11211);

// pass the $memcache instance as argument to initialize the Memcached-based storage
$storage = new Storage_Memcached($memcache);
```

Once the storage engine is initialized:

```php
// instantiate the caching library using the chosen storage engine
$cache = new Zebra_Cache($storage);

// if a cached, non-expired value for the sought key does not exist
if (!($my_data = $cache->get('my-key'))) {

    // do whatever you need to retrieve data
    $my_data = 'my data';

    // cache the values for one 10 minutes (10 x 60 seconds)
    $cache->set('my-key', $my_data, 10 * 600);

}

// at this point $my_data will always contain data, either from cache, or fresh
```

### Getting information about cached data

```php
if ($info = $cache->has('my-key')) {

    print_r('<pre>');
    print_r($info);

    // for file-based storage the output will look something like
    // [
    //  'path'     => '',  //  path to the cache file
    //  'timeout'  => '',  //  the number of seconds the cache was supposed to be valid
    //  'ttl'      => '',  //  number of seconds remaining until the cache expires
    // ]

}
```

### Deleting cached data

```php
$cache->delete('my-key');
```
