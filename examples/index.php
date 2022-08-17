<?php

require '../Zebra_Cache.php';

// instantiate the class and tell it to store cache files in the "cache" folder
$cache = new Zebra_Cache('cache');

// this is the file that we are going to download
// it is a 100k test file with nothing but empty space in it
// the name of the file will be the "key"
$download_path = 'http://speedtest.ftp.otenet.gr/files/test100k.db';

// if download was not yet cached
if (!($response = $cache->fetch($download_path))) {

    echo 'File is not yet cached; Downloading...<br>';

    // download the file
    $file_content = file_get_contents($download_path);

    // cache the result for one hour
    $cache->store($download_path, $file_content, 3600);

    echo 'File downloaded and cached in the "./cache" folder<br>';

}

echo 'File is cached';
