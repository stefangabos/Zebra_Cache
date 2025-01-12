<?php

// you don't need this "require"s when you are using composer
require '../Zebra_Cache.php';
require '../Storage/Storage_Interface.php';
require '../Storage/Storage_Redis.php';

use stefangabos\Zebra_Cache\Storage\Storage_Redis;
use stefangabos\Zebra_Cache\Zebra_Cache;

// connect to the Redis server
$redis = new Redis();

try {

    $redis->connect('127.0.0.1', 6379);

// if we couldn't connect to the Redis server
} catch (Exception $e) {

    die('Error: ' . '<strong>' . $e->getMessage() . '</strong>');

}

// pass the $redis instance as argument to initialize the Redis-based storage
$storage = new Storage_Redis($redis);

// instantiate the caching library and tell it to use the storage engine initialized above
$cache = new Zebra_Cache($storage);

// if data is not yet cached
if (!($my_data = $cache->get('my-key'))) {

    // cache some data
    $my_data = 'my-data';

    // cache the result for 30 seconds
    $cache->set('my-key', $my_data, 30);

    // print some info
    echo 'Value <code><strong>' . $my_data . '</strong></code> was <u>not previously cached</u><br>';
    echo 'It was cached now for <strong>30 seconds</strong><br>';
    echo 'Refresh the page';

// if data was already cached
} else {

    // get TTL
    $info = $cache->has('my-key');

    // print some info
    echo 'Value <code><strong>' . $my_data . '</strong></code> was <u>retrieved from cache</u><br>';
    echo 'The cache will expire in <strong>' . $info['ttl'] . '</strong> seconds<br>';
    echo 'Refresh the page';

}
