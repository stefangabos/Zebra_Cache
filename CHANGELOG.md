## version 1.4.0 (December 30, 2024)

- the library is now namespace-d and uses standard psr-4 autoloading

## version 1.3.2 (January 03, 2023)

- added new argument to the `get` method for being able to get the cached data even when it is expired (useful for when the caching itself is done by a different process)

## version 1.3.1 (December 30, 2022)

- various fixes to the documentation and examples

## version 1.3.0 (December 29, 2022)

- implemented file locking to ensure proper functionality under heavy load

## version 1.2.0 (August 17, 2022)

- renamed all methods to compy with [PSR-16](https://www.php-fig.org/psr/psr-16/)
- added a new [default_timeout](https://stefangabos.github.io/Zebra_Cache/Zebra_Cache/Zebra_Cache.html#var$default_timeout) property

## version 1.1.0 (August 17, 2022)

- added [is_cached](https://stefangabos.github.io/Zebra_Cache/Zebra_Cache/Zebra_Cache.html#methodis_cached) method which will also return the ttl (time to live) of the cached value
- minor source code formatting and fixes
- added an example

## version 1.0.0 (August 15, 2022)

- initial release
