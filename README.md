<img src="https://github.com/stefangabos/zebrajs/blob/master/docs/images/logo.png" alt="zebra-curl-logo" align="right" width="90">

# Zebra Cache &nbsp;[![Tweet](https://img.shields.io/twitter/url/http/shields.io.svg?style=social)](https://twitter.com/intent/tweet?text=A+simple,+file+based,+lightweight+PHP+library+for+caching+data&url=https://github.com/stefangabos/Zebra_Cache&via=stefangabos&hashtags=php,cache)

*A file-based lightweight PHP caching library that uses file locking to ensure proper functionality under heavy load.*

[![Latest Stable Version](https://poser.pugx.org/stefangabos/zebra_cache/v/stable)](https://packagist.org/packages/stefangabos/zebra_cache) [![Total Downloads](https://poser.pugx.org/stefangabos/zebra_cache/downloads)](https://packagist.org/packages/stefangabos/zebra_cache) [![Monthly Downloads](https://poser.pugx.org/stefangabos/zebra_cache/d/monthly)](https://packagist.org/packages/stefangabos/zebra_cache) [![Daily Downloads](https://poser.pugx.org/stefangabos/zebra_cache/d/daily)](https://packagist.org/packages/stefangabos/zebra_cache) [![License](https://poser.pugx.org/stefangabos/zebra_cache/license)](https://packagist.org/packages/stefangabos/zebra_cache)

## Features

- uses file locking to ensure proper functionality under heavy load - meaning that in a concurent environment, writing of cache file will acquire an exclusive lock on the file and reads will wait for the writing to finish before fetching the cached data
- automatic serialization and compression of cache data to save space and improve performance
- automatic expiration of cache items based on a specified time-to-live (TTL) value
- support for multiple instances, allowing you to use different cache configurations for different parts of your application

## :notebook_with_decorative_cover: Documentation

Check out the [awesome documentation](https://stefangabos.github.io/Zebra_Cache/Zebra_Cache/Zebra_Cache.html)!

## 🎂 Support the development of this project

Your support is greatly appreciated and it keeps me motivated continue working on open source projects. If you enjoy this project please star it by clicking on the star button at the top of the page. If you're feeling generous, you can also buy me a coffee through PayPal or become a sponsor.
**Thank you for your support!** 🎉


[<img src="https://img.shields.io/github/stars/stefangabos/zebra_cache?color=green&label=star%20it%20on%20GitHub" width="132" height="20" alt="Star it on GitHub">](https://github.com/stefangabos/Zebra_Cache) [![Donate](https://img.shields.io/badge/Donate-PayPal-green.svg)](https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=W6MCFT65DRN64) [<img src="https://img.shields.io/badge/-Sponsor-fafbfc?logo=GitHub%20Sponsors">](https://github.com/sponsors/stefangabos)


## Requirements

PHP 5.3.0+

## Installation

You can install via [Composer](https://packagist.org/packages/stefangabos/zebra_cache)

```bash
# get the latest stable release
composer require stefangabos/zebra_cache

# get the latest commit
composer require stefangabos/zebra_cache:dev-master
```

Or you can install it manually by downloading the latest version, unpacking it, and then including it in your project

```php
require_once 'path/to/Zebra_Cache.php';
```

## How to use

### Caching data:

```php
// instantiate the library
$cache = new Zebra_Cache('path/to/store/cache-files/');

// if a cached, non-expired value for the sought key does not exist
if (!($some_data = $cache->get('my-key'))) {

    // do whatever you need to retrieve data
    $some_data = 'get this data';

    // cache the values for one hour (3600 seconds)
    $cache->set('my-key', $some_data, 3600);

}

// $data now holds the values either fresh or from cache
```


### Getting information about cached data

```php
if ($cached_info = $cache->has('my-key')) {

    // will output something in the  likes of
    //  Array (
    //    'path'     => '',  //  path to the cache file
    //    'timeout'  => '',  //  the number of seconds the cache was supposed to be valid
    //    'ttl'      => '',  //  number of seconds remaining until the cache expires
    //  )

    print_r('<pre>');
    print_r($cached_info);

}
```

### Deleting cached data

```php
$cache->delete('my-key');
```
