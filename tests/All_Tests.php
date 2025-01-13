<?php

namespace stefangabos\Zebra_Cache\Tests;

use PHPUnit\Framework\TestCase;
use stefangabos\Zebra_Cache\Zebra_Cache;
use stefangabos\Zebra_Cache\Storage\Storage_File;
use stefangabos\Zebra_Cache\Storage\Storage_Memcached;
use stefangabos\Zebra_Cache\Storage\Storage_Redis;
use Redis;
use Memcached;
use Memcache;
use Exception;

/**
 *  Start a Memcached server with
 *  memcached -m 64 -p 11211
 *
 *  Start a Redis server with
 *  /opt/homebrew/opt/redis/bin/redis-server /opt/homebrew/etc/redis.conf
 */

class All_Tests extends TestCase {

    private Zebra_Cache $cache;
    private string $extension = '.cache';
    private int $ttl = 5;
    private string $key = 'test-key';
    private string $value = 'test-value';

    protected function setup(): void {
    }

    /**
     * @dataProvider storageProvider
     */
    public function test_set_and_get($cache, $storage_type) {

        // test setting a value
        $result = $cache->set($this->key, $this->value, $this->ttl);
        $this->assertTrue($result);

        if ($storage_type === 'File') {
            $this->assertFileExists(TEST_CACHE_DIR . md5($this->key) . '-' . $this->ttl . $this->extension, 'File should exist');
        }

        // test getting the value
        $result = $cache->get($this->key);
        $this->assertEquals($this->value, $result);

    }

    /**
     * @dataProvider storageProvider
     */
    public function test_set_and_get_array($cache, $storage_type) {

        $data = ['foo' => 'bar', 'foo2' => 23, [1, 2, 3], ['k' => 'v']];

        // test setting a value
        $result = $cache->set($this->key, $data, $this->ttl);
        $this->assertTrue($result);

        if ($storage_type === 'File') {
            $this->assertFileExists(TEST_CACHE_DIR . md5($this->key) . '-' . $this->ttl . $this->extension, 'File should exist');
        }

        // test getting the value
        $result = $cache->get($this->key);
        $this->assertEquals($data, $result);

    }

    /**
     * @dataProvider storageProvider
     */
    public function test_set_and_get_serialized_array($cache, $storage_type) {

        $data = serialize(['foo' => 'bar', 'foo2' => 23, [1, 2, 3], ['k' => 'v']]);

        // test setting a value
        $result = $cache->set($this->key, $data, $this->ttl);
        $this->assertTrue($result);

        if ($storage_type === 'File') {
            $this->assertFileExists(TEST_CACHE_DIR . md5($this->key) . '-' . $this->ttl . $this->extension, 'File should exist');
        }

        // test getting the value
        $result = $cache->get($this->key);
        $this->assertEquals($data, $result);

    }

    /**
     * @dataProvider storageProvider
     */
    public function test_has($cache, $storage_type) {

        // test setting a value
        $cache->set($this->key, $this->value, $this->ttl);
        $result = $cache->has($this->key);

        if ($storage_type === 'File') {
            $this->assertTrue(isset($result['path']) && $result['path'] !== '' && isset($result['timeout']) && is_integer($result['timeout']) && $result['timeout'] === $this->ttl && isset($result['ttl']) && is_integer($result['ttl']));
        } elseif ($storage_type === 'Redis') {
            $this->assertTrue(isset($result['ttl']) && is_integer($result['ttl']));
        } else {
            $this->assertTrue(isset($result['ttl']) && $result['ttl'] === 'N/A');
        }

    }

    /**
     * @dataProvider storageProvider
     */
    public function test_set_using_empty_key($cache, $storage_type) {

        if ($storage_type === 'Memcached (memcached)' || $storage_type === 'Memcached (memcache)') {
            $this->expectException(\InvalidArgumentException::class);
            $this->expectExceptionMessage('Empty key is not allowed');
        }

        $result = $cache->set('', $this->value, $this->ttl);

        if ($storage_type !== 'Memcached (memcached)' && $storage_type !== 'Memcached (memcache)') {
            $this->assertTrue($result);
        }

        if ($storage_type === 'File') {
            $this->assertFileExists(TEST_CACHE_DIR . md5('') . '-' . $this->ttl . $this->extension, 'File should exist');
        }

    }

    /**
     * @dataProvider storageProvider
     */
    public function test_set_negative_and_zero_timeout($cache, $storage_type) {

        // test setting a negative timeout
        $result = $cache->set($this->key, $this->value, -100);
        $this->assertTrue($result);

        // check that the file was created with default timeout
        if ($storage_type === 'File') {
            $this->assertFileExists(TEST_CACHE_DIR . md5($this->key) . '-' . $cache->default_timeout . $this->extension, 'File should exist with default timeout');
        }

        // test getting the value
        $result = $cache->get($this->key);
        $this->assertEquals($this->value, $result);

        // test setting 0 as timeout
        $result = $cache->set($this->key, $this->value, 0);
        $this->assertTrue($result);

        // check that the file was created with default timeout
        if ($storage_type === 'File') {
            $this->assertFileExists(TEST_CACHE_DIR . md5($this->key) . '-' . $cache->default_timeout . $this->extension, 'File should exist with default timeout');
        }

        // test getting the value
        $result = $cache->get($this->key);
        $this->assertEquals($this->value, $result);

    }

    /**
     * @dataProvider storageProvider
     */
    public function test_get_non_existent_key($cache, $storage_type) {

        // test retrieving a non-existent key
        $result = $cache->get('non-existent-key');
        $this->assertFalse($result);

    }

    /**
     * @dataProvider storageProvider
     */
    public function test_set_using_gzcompress($cache, $storage_type) {

        // test setting a value
        $result = $cache->set($this->key, $this->value, $this->ttl);
        $this->assertTrue($result);

        // check that the file was created
        if ($storage_type === 'File') {
            $this->assertFileExists(TEST_CACHE_DIR . md5($this->key) . '-' . $this->ttl . $this->extension, 'File should exist');
        }

        // test getting the value
        $result = $cache->get($this->key);
        $this->assertEquals($this->value, $result);

    }

    /**
     * @dataProvider storageProvider
     */
    public function test_delete($cache, $storage_type) {

        // set a value
        $cache->set($this->key, $this->value, $this->ttl);

        // delete the key
        $result = $cache->delete($this->key);
        $this->assertTrue($result);

        // assert that the file was created with default timeout
        if ($storage_type === 'File') {
            $this->assertFileDoesNotExist(TEST_CACHE_DIR . md5($this->key) . '-' . $this->ttl . $this->extension, 'File should not exist');
        }

        // ensure the key is deleted
        $result = $cache->get('test-key');
        $this->assertFalse($result);

    }

    /**
     * @dataProvider storageProvider
     */
    public function test_expiration($cache, $storage_type) {

        // set a value with a short expiration time
        $cache->set($this->key, $this->value, 1);

        // wait for the key to expire
        sleep(2);

        // ensure the key is expired
        $result = $cache->get($this->key);
        $this->assertFalse($result);

    }

    /**
     * @dataProvider storageProvider
     */
    public function test_incorrect_path($cache, $storage_type) {

        if ($storage_type !== 'File') {
            $this->markTestSkipped('Not available');
        } else {
            $storage = new Storage_File('bogus-path', $this->extension);
            $cache = new Zebra_Cache($storage);
        }

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Cache directory does not exists or it is not writable');

        // set a value with a short expiration time
        $cache->set($this->key, $this->value, 1);

    }

    private function createStorage($type) {

        switch ($type) {

            case 'File':

                // clear the test directory after each test
                array_map('unlink', glob(TEST_CACHE_DIR . '*'));

                $storage = new Storage_File(TEST_CACHE_DIR, $this->extension);

                break;

            case 'Redis':

                // connect to the Redis server
                $redis = new Redis();
                $redis->connect('127.0.0.1', 6379);

                // initialize Redis-based storage engine
                $storage = new Storage_Redis($redis);

                break;

            case 'Memcached':

                // connect to Memcached using the "Memcached" extension
                $memcache = new Memcached();
                $memcache->addServer('localhost', 11211);

                // initialize Memcached-based storage
                $storage = new Storage_Memcached($memcache);

                break;

            case 'Memcache':

                // connect to Memcached using the "Memcache" extension
                $memcache = new Memcache();
                $memcache->addServer('localhost', 11211);

                // initialize Memcached-based storage
                $storage = new Storage_Memcached($memcache);

                break;

            default:

                throw new Exception('Unknown storage type: ' . $type);

        }

        // initialize the file storage with the test directory
        $cache = new Zebra_Cache($storage);

        if ($this->getName() === 'test_set_using_gzcompress') $cache->cache_gzcompress = true;

        return $cache;

    }


    public function storageProvider(): array {

        return [
            'Storage: File'                     =>  [$this->createStorage('File'), 'File'],
            'Storage: Redis'                    =>  [$this->createStorage('Redis'), 'Redis'],
            'Storage: Memcached (memcached)'    =>  [$this->createStorage('Memcached'), 'Memcached (memcached)'],
            'Storage: Memcached (memcache)'     =>  [$this->createStorage('Memcache'), 'Memcached (memcache)'],
        ];

    }

}
