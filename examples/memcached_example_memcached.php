<?php

// you don't need this "require"s when you are using composer
require '../Zebra_Cache.php';
require '../Storage/Storage_Interface.php';
require '../Storage/Storage_Memcached.php';

use stefangabos\Zebra_Cache\Storage\Storage_Memcached;
use stefangabos\Zebra_Cache\Zebra_Cache;

// connect to the Memcached server using the "Memcached" extension
$memcache = new Memcached();

try {

    $memcache->addServer('localhost', 11211);

// if we couldn't connect to the Redis server
} catch (Exception $e) {

    die('Error: ' . '<strong>' . $e->getMessage() . '</strong>');

}

// initialize the Redis-based storage engine by passing the Redis instance as argument
$storage = new Storage_Memcached($memcache);

// instantiate the caching library and tell it to use the storage engine initialized above
$cache = new Zebra_Cache($storage);

// if data is not yet cached
if (!($my_data = $cache->get('my-key'))) {

    // cache some data
    $my_data = 'my-data';

    // cache the result for 30 seconds
    $cache->set('my-key', $my_data, 5);

    // print some info
    echo 'Value <code><strong>' . $my_data . '</strong></code> was <u>not previously cached</u><br>';
    echo 'It was cached now for <strong>5 seconds</strong><br>';
    echo 'Refresh the page';

// if data was already cached
} else {

    // get TTL
    $info = $cache->has('my-key');

    // print some info
    echo 'Value <code><strong>' . $my_data . '</strong></code> was <u>retrieved from cache</u><br>';
    echo 'Memcached does not offer information about a cached item\'s TTL so you\'ll just have to wait...<br>';
    echo 'Refresh the page';

}
