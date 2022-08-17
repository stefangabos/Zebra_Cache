<img src="https://github.com/stefangabos/zebrajs/blob/master/docs/images/logo.png" alt="zebra-curl-logo" align="right" width="90">

# Zebra Cache &nbsp;[![Tweet](https://img.shields.io/twitter/url/http/shields.io.svg?style=social)](https://twitter.com/intent/tweet?text=A+simple,+file+based,+lightweight+PHP+library+for+caching+data&url=https://github.com/stefangabos/Zebra_Cache&via=stefangabos&hashtags=php,cache)

*A simple, file based, lightweight PHP library for caching data*

[![Latest Stable Version](https://poser.pugx.org/stefangabos/zebra_cache/v/stable)](https://packagist.org/packages/stefangabos/zebra_cache) [![Total Downloads](https://poser.pugx.org/stefangabos/zebra_cache/downloads)](https://packagist.org/packages/stefangabos/zebra_cache) [![Monthly Downloads](https://poser.pugx.org/stefangabos/zebra_cache/d/monthly)](https://packagist.org/packages/stefangabos/zebra_cache) [![Daily Downloads](https://poser.pugx.org/stefangabos/zebra_cache/d/daily)](https://packagist.org/packages/stefangabos/zebra_cache) [![License](https://poser.pugx.org/stefangabos/zebra_cache/license)](https://packagist.org/packages/stefangabos/zebra_cache)

**Zebra Cache** is a simple, file based caching environment for PHP.

## :notebook_with_decorative_cover: Documentation

Check out the [awesome documentation](https://stefangabos.github.io/Zebra_Cache/Zebra_Cache/Zebra_Cache.html)!

## 🎂 Support the development of this project

Your support means a lot and it keeps me motivated to keep working on open source projects.<br>
If you like this project please ⭐ it by clicking on the star button at the top of the page.<br>
If you are feeling generous, you can buy me a coffee by donating through PayPal, or you can become a sponsor.<br>
Either way - **Thank you!** 🎉

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

```php
// instantiate the library
$cache = new Zebra_Cache();

// if a cached, non-expired value for the sought key does not exist
if (!($data = $cache->get('my-key'))) {

    // do whatever you need to retrieve data
    $data = 'get this data';

    // cache the values for one hour (3600 seconds)
    $cache->set('my-key', $some_data, 3600);

}

// return the data
return $data;
```
