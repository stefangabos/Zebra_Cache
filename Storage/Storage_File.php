<?php

/**
 *  A file-based implementation of the {@link Storage_Interface}.
 *
 *  This class provides a caching mechanism that uses files on disk for storage.
 *
 *  It employs file locking to ensure proper functionality under heavy load. In a concurrent environment, an exclusive
 *  lock is acquired when writing to a cache file, ensuring that reads wait until the write operation is complete before
 *  fetching the cached data.
 *
 *  @author     Stefan Gabos <contact@stefangabos.ro>
 *  @copyright  © 2022 - 2025 Stefan Gabos
 *  @license    https://www.gnu.org/licenses/lgpl-3.0.txt GNU LESSER GENERAL PUBLIC LICENSE
 *  @package    Zebra_Cache
 *  @subpackage Storage
 */

namespace stefangabos\Zebra_Cache\Storage;

use Exception;

class Storage_File implements Storage_Interface {

    /**
     *  The path where the cache files to be stored at.
     *
     *  @var string
     */
    private $path;

    /**
     *  The extension to suffix cache files with.
     *
     *  @var string
     */
    private $extension;

    /**
     *  Constructor of the class.
     *
     *  <code>
     *  // file-based storage
     *  $storage = new stefangabos\Zebra_Cache\Storage\Storage_File('/path/to/cache');
     *
     *  // instantiate the caching library using the file-based storage
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
     *  @param  string      $path       The path where the cache files to be stored at.
     *
     *  @param  string      $extension  (Optional) The extension to suffix cache files with.
     *
     *                                  >   The `. (dot)` is not implied - you need to also add the dot if you need it
     *
     *                                  Default is `''` (an empty string, nothing)
     *
     *  @return void
     */
    public function __construct($path, $extension = '') {

        $this->path = rtrim($path, '/') . '/';
        $this->extension = $extension;

    }

    /**
     *  See {@link Zebra_Cache::delete()} for documentation.
     */
    public function delete(string $key): bool {

        // make sure path exists and is writable
        $this->_check_path($this->path);

        // get the matching file
        // (there should never be more than just one file, but just in case)
        $files = glob($this->path . md5($key) . '-*' . $this->extension);

        // delete the file(s)
        foreach ($files as $file) {
            @unlink($file);
        }

        return !empty($files);

    }

    /**
     *  See {@link Zebra_Cache::get()} for documentation.
     */
    public function get(string $key) {

        // make sure path exists and is writable
        $this->_check_path($this->path);

        // if cache file exists and it is not expired, return the cached content
        if (($file_info = $this->_get_file_info($key)) && time() - filemtime($file_info['path']) < $file_info['timeout']) {

            // in case there is a lock on the file (cache is being written) wait for it to finish
            $file = fopen($file_info['path'], 'r');

            // a shared lock isn't blocking other instances accessing the file
            $lock = flock($file, LOCK_SH);

            $data = file_get_contents($file_info['path']);

            // release lock
            flock($file, LOCK_UN);
            fclose($file);

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
     *                      'path'       => '', //  path to the cache file
     *                      'timeout'    => 0,  //  the original number of seconds the cache entry was supposed to be valid
     *                      'ttl'        => 0,  //  time to live, the number of seconds remaining until the cache expires
     *                  ]
     *                  </code>
     */
    public function has(string $key) {

        // make sure path exists and is writable
        $this->_check_path($this->path);

        // if cache file exists and it is not expired
        if (($file_info = $this->_get_file_info($key)) && ($ttl = time() - filemtime($file_info['path'])) < $file_info['timeout']) {

            // the remaining number of seconds
            $file_info['ttl'] = $file_info['timeout'] - $ttl;

            // return the info
            return $file_info;

        }

        // if we get this far it means the cache file does not exist or it is expired
        return false;

    }

    /**
     *  The data will be stored in a file on disk at the path specified in the {@link Storage_File::__construct() constructor},
     *  having the name:
     *
     *  `md5($key) . '-' . $timeout . $extension`
     *
     *  (where `$extension` is the one defined in the {@link Storage_File::__construct() constructor})
     *
     *  Therefore, a cache file for the key `my-key`, having a `timeout` of `3600` seconds (one hour) and with
     *  {@link extension} set to `.cache` would look like
     *
     *  `515780610702189dabd912e9c9ef6f38-3600.cache`
     *
     *  See {@link Zebra_Cache::set()} for full documentation.
     */
    public function set(string $key, $data, int $timeout = 0): bool {

        // make sure path exists and is writable
        $this->_check_path($this->path);

        // anything that evaluates to false, null, '', boolean false or 0 will not be cached
        if (!$data) {
            return false;
        }

        // delete any other cache file in case it already exists with a different timeout
        $this->delete($key);

        // cache content to file
        // get an exclusive lock on the file while doing this so that other instances cannot write at the same time
        file_put_contents($this->path . md5($key) . '-' . $timeout . $this->extension, $data, LOCK_EX);

        return true;

    }

    /**
     *  Retrieves the associated cache file and its timeout for a given key, or FALSE if the cache file does not exist.
     *
     *  @param  string      $key                The key for which to return the associated cache file (with full path)
     *                                          and timeout (time after which the cache file should be considered as
     *                                          expired).
     *
     *  @return mixed                           Returns an array in the form of
     *
     *                                          <code>
     *                                          [
     *                                              'path'      =>  '' // full path to the cache file
     *                                              'timeout'   =>  '' // number of seconds the cache file is to be considered valid
     *                                          ]
     *                                          </code>
     *
     *                                          If the cache file doesn't exists, the returned value is `FALSE`.
     *
     *  @access private
     */
    private function _get_file_info($key) {

        // get the matching cache file, if any
        $file = glob($this->path . md5($key) . '-*' . $this->extension);

        // if, for some reason, there are multiple cache files with the same key
        // (this should never happen because set() deletes the file associated with a key before creating a new one, but we check, just in case)
        if (count($file) > 1) {

            // we delete the file associated with the key
            $this->delete($key);

        // if we found exactly one file
        } elseif (count($file) == 1) {

            // get the timeout value
            list($prefix, $timeout) = explode('-', $this->extension !== '' ? substr($file[0], 0, -strlen($this->extension)) : $file[0]);

            // if timeout value is valid
            if (is_numeric($timeout) && $timeout > 1 && preg_match('/^[0-9]+$/', $timeout)) {

                return [
                    'path'      => $file[0],
                    'timeout'   => (int)$timeout,
                ];

            }

        }

        // return false if we get this far
        return false;

    }

    /**
     *  Checks if path exists and is writable.
     *
     *  @param  string  $path   Path to check
     *
     *  @return void
     *
     *  @access private
     */
    private function _check_path($path) {

        // make sure path exists and is writable
        if (!is_dir($path) || !is_writable($path)) {
            throw new \RuntimeException('Cache directory does not exists or it is not writable');
        }

    }

}
