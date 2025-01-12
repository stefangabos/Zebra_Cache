<?php

/**
 *  Interface for cache storage mechanisms used in the Zebra Cache library.
 *
 *  This interface defines the contract for all storage backends, allowing the Zebra Cache library to support multiple
 *  storage mechanisms, such as file-based storage, Memcache, Redis, or any other custom implementation.
 *
 *  @author     Stefan Gabos <contact@stefangabos.ro>
 *  @copyright  © 2022 - 2025 Stefan Gabos
 *  @license    https://www.gnu.org/licenses/lgpl-3.0.txt GNU LESSER GENERAL PUBLIC LICENSE
 *  @package    Zebra_Cache
 */

namespace stefangabos\Zebra_Cache\Storage;

interface Storage_Interface {

    /**
     *  Deletes a cache entry.
     *
     *  Deletes the cache entry associated with the specified key.
     *
     *  <code>
     *  $cache->delete('my-key');
     *  </code>
     *
     *  @param  string      $key    The key identifying the cache entry to delete.
     *
     *  @return boolean             Returns `TRUE` if an entry was deleted, or `FALSE` if there was nothing to delete.
     */
    public function delete(string $key): bool;

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
     *                      and it is not expired, or `FALSE` otherwise.
     */
    public function get(string $key);

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
     *      // could output something like
     *      // depending on the used storage engine
     *      // [
     *      //     'ttl' => 28
     *      // ]
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
    public function has(string $key);

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
     *  @return boolean                 Returns `TRUE` on success or `FALSE` if data could not be cached.
     */
    public function set(string $key, $data, int $timeout = 0): bool;

}
