<?php

/**
 *  A Memcache-based implementation of the {@link Storage_Interface}.
 *
 *  This class provides a caching mechanism using a {@link https://memcached.org/about Memcached} server as the backend.
 *
 *  It leverages Memcached's fast in-memory storage for high-performance caching and supports features like key expiration
 *  and atomic operations.
 *
 *  >   There are two PHP extensions for working with Memcached: the `memcache` extension, which is older and less commonly
 *      used, and `memcached` which is generally preferred for better features and compatibility.
 *
 *  >   When using **Memcached** as a storage engine, PHP must be compiled with either the {@link https://pecl.php.net/package/memcache memcache
 *      extension} ({@link https://www.php.net/manual/en/book.memcache.php PHP manual}) or the {@link https://pecl.php.net/package/memcached memcached extension}
 *      ({@link https://www.php.net/manual/en/book.memcached.php PHP manual}).
 *
 *  @author     Stefan Gabos <contact@stefangabos.ro>
 *  @copyright  © 2022 - 2025 Stefan Gabos
 *  @license    https://www.gnu.org/licenses/lgpl-3.0.txt GNU LESSER GENERAL PUBLIC LICENSE
 *  @package    Zebra_Cache
 *  @subpackage Storage
 */

namespace stefangabos\Zebra_Cache\Storage;

use Exception;
use Memcache;
use Memcached;

class Storage_Memcached implements Storage_Interface {

    /**
     * @var mixed   The Memcache client instance.
     */
    private $memcache;

    /**
     * @var string  Used memcache extension (memcache/memcached)
     */
    private $memcache_extension;

    /**
     *  Constructor of the class.
     *
     *  Initializes the Memcache client and connects to the Memcached server.
     *
     *  <code>
     *  // connect to a Memcached server using PHP's "Memcache" extension
     *  $memcache = new Memcache();
     *  $memcache->addServer('localhost', 11211);
     *
     *  // OR
     *
     *  // connect to a Memcached server using PHP's "Memcached" extension
     *  $memcache = new Memcached();
     *  $memcache->addServer('localhost', 11211);
     *
     *  // pass the $memcache instance as argument when initializing the Memcached-based storage
     *  $storage = new stefangabos\Zebra_Cache\Storage\Storage_Memcached($memcache);
     *
     *  // instantiate the caching library using Memcached for storage
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
     *  @param mixed    $memcache   An instance of either {@link https://www.php.net/manual/en/book.memcache.php Memcache} or
     *                              {@link https://www.php.net/manual/en/book.memcached.php Memcached}.
     *
     *  @return void
     */
    public function __construct($memcache) {

        if (!($memcache instanceof Memcache) && !($memcache instanceof Memcached)) {
            throw new \InvalidArgumentException('Expected Memcache or Memcached instance');
        }

        // store the type of extension used
        $this->memcache_extension = $memcache instanceof Memcached ? 'memcached' : 'memcache';

        $this->memcache = $memcache;

    }

    /**
     *  See {@link Zebra_Cache::delete()} for documentation.
     */
    public function delete(string $key): bool {

        // make sure key is valid
        $key = $this->_validate_key($key);

        return $this->memcache->delete($key);

    }

    /**
     *  See {@link Zebra_Cache::get()} for documentation.
     */
    public function get(string $key) {

        // make sure key is valid
        $key = $this->_validate_key($key);

        // if key exists and it is not expired
        if ($data = $this->memcache->get($key)) {
            return $data;
        }

        // return false if we get this far
        return false;

    }

    /**
     *  See {@link Zebra_Cache::has()} for full documentation.
     *
     *  @return mixed   If a cached values exists and it is not expired, the returned array will look like
     *
     *                  <code>
     *                  [
     *                      'ttl' => 'N/A',  //  memcache does not provide the remaining TTL
     *                  ]
     *                  </code>
     */
    public function has(string $key) {

        // make sure key is valid
        $key = $this->_validate_key($key);

        // if key exists and it is not expired
        if ($this->memcache->get($key)) {

            // return info
            return [
                'ttl'   =>  'N/A',
            ];

        }

        // if we get this far it means the key does not exist or it is expired
        return false;
    }

    /**
     *  See {@link Zebra_Cache::set()} for documentation.
     */
    public function set(string $key, $data, int $timeout = 0): bool {

        // make sure key is valid
        $key = $this->_validate_key($key);

        // anything that evaluates to false, null, '', boolean false or 0 will not be cached
        if (!$data) {
            return false;
        }

        // delete any other cache file in case it already exists with a different timeout
        $this->delete($key);

        // the two extensions have slightly different arguments
        if ($this->memcache_extension === 'memcached') {
            return $this->memcache->set($key, $data, time() + $timeout);
        } else {
            return $this->memcache->set($key, $data, 0, time() + $timeout);
        }

    }

    /**
     *  Validates keys and returns the key with spaces replaced by underscores (_)
     *
     *  @param  string  $key    Key to validate
     *
     *  @return string
     *
     *  @access private
     */
    private function _validate_key(string $key) {

        if (trim($key) === '') {
            throw new \InvalidArgumentException('Empty key is not allowed');
        } elseif (strlen($key) > 250) {
            throw new \InvalidArgumentException('Key value must not exceed 250 characters in length');
        }

        // while Memcache automatically replaces spaces with underscores for keys, we also do it ourselves here in order
        // to ensure predictable behavior and prevent potential key collisions
        return str_replace(' ', '_', $key);

    }

}
