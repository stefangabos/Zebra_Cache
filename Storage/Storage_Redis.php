<?php

/**
 *  A Redis-based implementation of the {@link Storage_Interface}.
 *
 *  This class provides a caching mechanism using a {@link https://redis.io/ Redis} server as the backend.
 *
 *  It leverages Redis' fast in-memory storage for high-performance caching and supports features like key expiration
 *  and atomic operations.
 *
 *  >   When using **Redis** as a storage engine, PHP must be compiled with the {@link https://pecl.php.net/package/redis
 *      redis extension}.
 *
 *  @author     Stefan Gabos <contact@stefangabos.ro>
 *  @copyright  © 2022 - 2025 Stefan Gabos
 *  @license    https://www.gnu.org/licenses/lgpl-3.0.txt GNU LESSER GENERAL PUBLIC LICENSE
 *  @package    Zebra_Cache
 *  @subpackage Storage
 */

namespace stefangabos\Zebra_Cache\Storage;

use Redis;
use Exception;

class Storage_Redis implements Storage_Interface {

    /**
     * @var Redis   The Redis client instance.
     */
    private Redis $redis;

    /**
     *  Constructor of the class.
     *
     *  Initializes the Redis client and connects to the Redis server.
     *
     *  <code>
     *  // connect to a Redis server
     *  $redis = new Redis();
     *  $redis->connect('127.0.0.1', 6379);
     *
     *  // pass the $redis instance as argument to initialize the Redis-based storage
     *  $storage = new stefangabos\Zebra_Cache\Storage\Storage_Redis($redis);
     *
     *  // instantiate the caching library using Redis for storage
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
     *  @param Redis    $redis      An instance of {@link https://www.php.net/manual/en/book.redis.php Redis}.
     *
     *  @return void
     */
    public function __construct($redis) {

        if (!($redis instanceof Redis)) {
            throw new \InvalidArgumentException('Expected Redis instance');
        }

        $this->redis = $redis;

    }

    /**
     *  See {@link Zebra_Cache::delete()} for documentation.
     */
    public function delete(string $key): bool {

        $result = $this->redis->del($key);

        // return whether the key existed and could be deleted
        return is_integer($result) && $result > 0;

    }

    /**
     *  See {@link Zebra_Cache::get()} for documentation.
     */
    public function get(string $key) {

        // if key exists and it is not expired
        if ($data = $this->redis->get($key)) {
            return $data;
        }

        // return false if we get this far
        return false;

    }

    /**
     *  See {@link Zebra_Cache::has()} for documentation.
     */
    public function has(string $key) {

        $expiration_time = $this->redis->expiretime($key);

        // if key exists and it is not expired
        if (is_integer($expiration_time) && $expiration_time > 0) {

            // return info
            return [
                'ttl' => $expiration_time - time(),
            ];

        }

        // if we get this far it means the key does not exist or it is expired
        return false;

    }

    /**
     *  See {@link Zebra_Cache::set()} for documentation.
     */
    public function set(string $key, $data, int $timeout = 0): bool {

        // anything that evaluates to false, null, '', boolean false or 0 will not be cached
        if (!$data) {
            return false;
        }

        // delete any other cache file in case it already exists with a different timeout
        $this->delete($key);

        return $this->redis->setex($key, $timeout, $data);

    }

}
