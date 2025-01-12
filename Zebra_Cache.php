<?php

/**
 *  A lightweight PHP caching library supporting multiple storage mechanisms.
 *
 *  This library provides a unified interface for caching data, allowing the use of various storage backends like
 *  file-based caching, Redis, Memcached, or custom implementations.
 *
 *  It supports common caching operations such as storing, retrieving and deleting data, as well as checking for the
 *  existence of a cache entry.
 *
 *  **Features**:
 *
 *  - pluggable architecture for using different storage mechanisms
 *  - flexible key-value caching
 *  - automatic expiration of cache items based on a specified time-to-live (TTL) value
 *  - extensible design allowing developers to integrate new storage backends by implementing the {@link Storage_Interface}
 *  - easy-to-use API with consistent behavior across different backends
 *  - support for multiple instances, allowing you to use different cache configurations for different parts of your application
 *
 *  Read more {@link https://github.com/stefangabos/Zebra_Cache/ here}.
 *
 *  @author     Stefan Gabos <contact@stefangabos.ro>
 *  @version    2.0.0 (last revision: January 12, 2025)
 *  @copyright  © 2022 - 2025 Stefan Gabos
 *  @license    https://www.gnu.org/licenses/lgpl-3.0.txt GNU LESSER GENERAL PUBLIC LICENSE
 *  @package    Zebra_Cache
 */

namespace stefangabos\Zebra_Cache;

use stefangabos\Zebra_Cache\Storage\Storage_Interface;

class Zebra_Cache {

    /**
     *  Whether data should be {@link https://www.php.net/manual/en/function.gzcompress.php gzcompress}-ed before
     *  being {@link set()} (using the function's default settings).
     *
     *  <code>
     *  // enable gz-compressing
     *  $cache->cache_gzcompress = true;
     *  </code>
     *
     *  >   Note that the library will not check if the stored data is compressed or not - if this property is set
     *      to `TRUE`, it will always try to uncompress data when using {@link get()}! Therefore, be careful not to mix
     *      compressed and uncompressed data.
     *
     *  Default is `FALSE`
     *
     *  @var boolean
     */
    public $cache_gzcompress = false;

    /**
     *  The default number of seconds after which cached data will be considered as expired.
     *
     *  This is used by the {@link set()} method when the `timeout` argument is omitted.
     *
     *  Default is `300` (5 minutes)
     *
     *  @var integer
     */
    public $default_timeout = 300;

    /**
     * The storage backend used for caching.
     *
     * This is an implementation of the `Storage_Interface`, which defines the
     * contract for all storage mechanisms.
     *
     * @var Storage_Interface
     */
    private Storage_Interface $storage;

    /**
     *  Initializes the cache system with the provided storage backend.
     *
     *  Initializing **file-based storage**:
     *
     *  <code>
     *  // make sure you have this at the top of your script
     *  use stefangabos\Zebra_Cache\Storage\Storage_File;
     *
     *  // initialize file-based storage
     *  $storage = new Storage_File('/path/to/cache/folder');
     *
     *  // or, if you don't want the "use" at the top of the script, initialize the file-based storage like this
     *  // $storage = new stefangabos\Zebra_Cache\Storage\Storage_File('/path/to/cache/folder');
     *  </code>
     *
     *  Initializing **Redis-based storage**:
     *
     *  <code>
     *  // make sure you have this at the top of your script
     *  use stefangabos\Zebra_Cache\Storage\Storage_Redis;
     *
     *  // connect to a Redis server
     *  $redis = new Redis();
     *  $redis->connect('127.0.0.1', 6379);
     *
     *  // pass the $redis instance as argument to initialize the Redis-based storage
     *  $storage = new Storage_Redis($redis);
     *
     *  // or, if you don't want the "use" at the top of the script, initialize the Redis-based storage like this
     *  // $storage = new stefangabos\Zebra_Cache\Storage\Storage_Redis($redis);
     *
     *  // finally, instantiate the caching library using the storage engine configured above
     *  $cache = new stefangabos\Zebra_Cache\Zebra_Cache($storage);
     *  </code>
     *
     *  Initializing **Memcached-based storage**:
     *
     *  There are two PHP extensions for working with Memcached: the `memcache` extension, which is older and less commonly
     *  used, and `memcached` which is generally preferred for better features and compatibility.
     *
     *  This library supports both.
     *
     *  <code>
     *  // make sure you have this at the top of your script
     *  use stefangabos\Zebra_Cache\Storage\Storage_Memcached;
     *
     *  // connect to a Memcached server (using the `memcached` extension)
     *  $memcache = new Memcached();
     *  $memcache->addServer('localhost', 11211);
     *
     *  // OR using the `memcache` extension
     *  $memcache = new Memcache();
     *  $memcache->addServer('localhost', 11211);
     *
     *  // pass the $memcache instance as argument to initialize the Memcached-based storage
     *  $storage = new Storage_Memcached($memcache);
     *
     *  // or, if you don't want the "use" at the top of the script,
     *  // initialize the Memcached-based storage like this
     *  // $storage = new stefangabos\Zebra_Cache\Storage\Storage_Memcached($memcache);
     *  </code>
     *
     *  Once the storage engine is initialized:
     *
     *  <code>
     *  // instantiate the caching library using the chosen storage engine
     *  $cache = new stefangabos\Zebra_Cache\Zebra_Cache($storage);
     *
     *  // if a cached, non-expired value for the sought key does not exist
     *  if (!($my_data = $cache->get('my-key'))) {
     *
     *      // do whatever you need to retrieve data
     *      $my_data = 'my data';
     *
     *      // cache the values for one 10 minutes (10 x 60 seconds)
     *      $cache->set('my-key', $my_data, 10 * 600);
     *
     *  }
     *
     *  // at this point $my_data will always contain data, either from cache, or fresh
     *  </code>
     *
     *  @param  Storage_Interface   $storage   The storage backend to use.
     *
     *                                          An instance of one of the supported storage mechanisms:
     *
     *                                          -   {@link Storage_File File}
     *                                          -   {@link Storage_Redis Redis}
     *                                          -   {@link Storage_Memcached Memcached}
     */
    public function __construct(Storage_Interface $storage) {
        $this->storage = $storage;
    }

    /**
     *  Deletes the cache entry associated with the specified key.
     *
     *  <code>
     *  $cache->delete('my-key');
     *  </code>
     *
     *  @param  string      $key    The key identifying the cache entry to delete.
     *
     *  @return boolean     Returns `TRUE` if an entry was deleted, or `FALSE` if there was nothing to delete.
     */
    public function delete(string $key): bool {
        return $this->storage->delete($key);
    }

    /**
     *  Retrieves an item from the cache.
     *
     *  Retrieves the cached value for the specified key **if** the corresponding cache entry exists and it is not expired,
     *  or `FALSE` otherwise.
     *
     *  <code>
     *  // if a cached, non-expired value for the sought key does not exist
     *  if (!($my_data = $cache->get('my-key'))) {
     *
     *      // do whatever you need to retrieve data
     *      $my_data = 'my data';
     *
     *      // cache the values for one hour (3600 seconds)
     *      $cache->set('my-key', $my_data, 3600);
     *
     *  }
     *
     *  // at this point $my_data will always contain data, either from cache, or fresh
     *  </code>
     *
     *  @param  string      $key    The key for which to return the cached value.
     *
     *  @return mixed       Retrieves the cached value for the specified key **if** the corresponding cache entry exists
     *                      and is not expired, or `FALSE` otherwise.
     */
    public function get(string $key) {

        // get the data
        $data = $this->storage->get($key);

        // if data was gz-compressed
        if ($this->cache_gzcompress) {
            $data = gzuncompress($data);
        }

        // data is always serialized
        return unserialize($data);

    }

    /**
     *  Checks if a cache entry exists.
     *
     *  Checks whether a cached, non-expired value exists for the given key, and returns information about it.
     *
     *  <code>
     *  if ($cached_info = $cache->has('my-key')) {
     *
     *      print_r('<pre>');
     *      print_r($cached_info);
     *
     *  }
     *  </code>
     *
     *  @param  string      $key    The key for which to check if a cached value exists.
     *
     *  @return mixed       Returns information about the cache associated with the given key as an array, **if**
     *                      the associated cache entry exists **and** it is not expired, or `FALSE` otherwise.
     *
     *                      >   For details on the exact return values, please refer to the documentation of the specific
     *                          storage engine being used, as each may provide additional information unique to its
     *                          implementation.
     */
    public function has(string $key) {
        return $this->storage->has($key);
    }

    /**
     *  Stores an item in the cache.
     *
     *  Caches data identified by a unique key for a specific number of seconds. If the key already exists, the data
     *  will be overwritten.
     *
     *  <code>
     *  // if a cached, non-expired value for the sought key does not exist
     *  if (!($my_data = $cache->get('my-key'))) {
     *
     *      // do whatever you need to retrieve data
     *      $my_data = 'my data';
     *
     *      // cache the values for one hour (3600 seconds)
     *      $cache->set('my-key', $my_data, 3600);
     *
     *  }
     *
     *  // at this point $my_data will always contain data, either from cache, or fresh
     *  </code>
     *
     *  @param  string      $key        Unique name identifying the data that is about to be stored.
     *
     *  @param  mixed       $data       The data to be stored.
     *
     *                                  >   Anything that evaluates as *falsy*  will not be cached!<br>
     *                                      These values are:<br>
     *                                      <br>the keyword `false`
     *                                      <br>the integer zero (`0`)
     *                                      <br>the floating-point number zero (`0.0`)
     *                                      <br>the empty string (`''`) and the string `"0"`
     *                                      <br>the empty string (`''`) and the string `"0"`
     *                                      <br>the `NULL` value
     *                                      <br>an empty array (an array with zero elements)
     *
     *  @param  integer     $timeout    (Optional) The number of seconds after which the cached data will be considered
     *                                  expired.
     *
     *                                  Valid values are integers greater than `0`. The value `0` is also valid and it
     *                                  indicates that the value of {@link Zebra_Cache::$default_timeout $default_timeout}
     *                                  will be applied.
     *
     *                                  >   Providing an invalid value will result in the {@link Zebra_Cache::$default_timeout $default_timeout}
     *                                      being applied instead.
     *
     *                                  Default is `0` ({@link Zebra_Cache::$default_timeout $default_timeout})
     *
     *  @return boolean     Returns `TRUE` on success or `FALSE` if data could not be cached.
     */
    public function set(string $key, $data, int $timeout = 0): bool {

        // data is always serialized
        $data = serialize($data);

        return $this->storage->set(
            $key,
            $this->cache_gzcompress ? gzcompress($data) : $data,
            !is_numeric($timeout) || $timeout < 1 || !preg_match('/^[0-9]+$/', (string)$timeout) ? $this->default_timeout : $timeout
        );

    }

}
