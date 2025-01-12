<?php

// autoload classes using composer
require_once __DIR__ . '/../vendor/autoload.php';

// define temporary directory for file-based storage during tests
define('TEST_CACHE_DIR', __DIR__ . '/temp/');

// ensure the directory exists
if (!is_dir(TEST_CACHE_DIR)) {
    mkdir(TEST_CACHE_DIR, 0777, true);
}
