<?php

/**
 *  A simple, file based, lightweight PHP caching library that uses file locking to ensure proper functionality under
 *  heavy load.
 *
 *  Read more {@link https://github.com/stefangabos/Zebra_Cache/ here}.
 *
 *  @author     Stefan Gabos <contact@stefangabos.ro>
 *  @version    1.3.0 (last revision: December 29, 2022)
 *  @copyright  © 2022 Stefan Gabos
 *  @license    https://www.gnu.org/licenses/lgpl-3.0.txt GNU LESSER GENERAL PUBLIC LICENSE
 *  @package    Zebra_Cache
 */
class Zebra_Cache {

    /**
     *  Whether cache files should be {@link https://www.php.net/manual/en/function.gzcompress.php gzcompress}-ed.
     *
     *  <code>
     *  // enable gz-compressing, saving disk space
     *  $cache->cache_gzcompress = true;
     *  </code>
     *
     *  Default is `FALSE`
     *
     *  @var boolean
     */
    public $cache_gzcompress = false;

    /**
     *  Whether content in cache files should be {@link https://www.php.net/manual/en/function.openssl-encrypt.php openssl_encrypt}-ed
     *  using the `aes-256-cbc`cipher method.
     *
     *  If you want this enabled you must set its value to a unique cipher to be used for encrypting and decrypting the data.
     *
     *  <code>
     *  // use something random, hard to guess
     *  $cache->cache_encrypt = 'Z&y&m^VBPJmCVtya';
     *  </code>
     *
     *  Default is `FALSE`
     *
     *  @var mixed
     */
    public $cache_encrypt = false;

    /**
     *  The default number of seconds after which cached data will be considered as expired.
     *
     *  This is used by the {@link set()} method when the `timeout` argument is omitted.
     *
     *  Default is `3600` (one hour)
     *
     *  @var integer
     */
    public $default_timeout = 3600;

    /**
     *  The extension to suffix cache files with.
     *
     *  <code>
     *  $cache->extension = '.cache';
     *  </code>
     *
     *  >   The `. (dot)` is not implied - you need to also add the dot if you need it
     *
     *  Default is `''` (an empty string, nothing)
     *
     *  @var string
     */
    public $extension = '';

    /**
     *  The path where the cache files to be stored at.
     *
     *  @var string
     */
    public $path = '';

    /**
     *  Constructor of the class.
     *
     *  <code>
     *  $cache = new Zebra_Cache('path/to/store/cache-files/');
     *
     *  // if a cached, non-expired value for the sought key does not exist
     *  if (!($some_data = $cache->get('my-key'))) {
     *
     *      // do whatever you need to retrieve data
     *      $some_data = 'get this data';
     *
     *      // cache the values for one hour (3600 seconds)
     *      $cache->set('my-key', $some_data, 3600);
     *
     *  }
     *
     *  // at this point $some_data will always contain your data, either from cache, or fresh
     *  </code>
     *
     *  @param  string      $path       The path where the cache files to be stored at.
     *
     *                                  >   This can be changed later by updating the {@link path} property.
     *
     *  @param  string      $extension  (Optional) The extension to suffix cache files with.
     *
     *                                  >   This can be changed later by updating the {@link extension} property.
     *
     *  @return void
     */
    public function __construct($path, $extension = '') {

        $this->path = rtrim($path, '/') . '/';
        $this->extension = $extension;

    }

    /**
     *  Deletes the cache file associated with a key.
     *
     *  <code>
     *  $cache->delete('my-key');
     *  </code>
     *
     *  @param  string      $key    The key for which to delete the associated cache file.
     *
     *  @return boolean             Returns `TRUE` if there was a file to be deleted or `FALSE` if there was nothing to
     *                              delete.
     */
    public function delete($key) {

        // make sure path exists and is writable
        $this->_check_path();

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
     *  Retrieves the cached value for the given key **if** the associated cache file exists **and** it is not expired.
     *
     *  <code>
     *  // if a cached, non-expired value for the sought key does not exist
     *  if (!($some_data = $cache->get('my-key'))) {
     *
     *      // do whatever you need to retrieve data
     *      $some_data = 'get this data';
     *
     *      // cache the values for one hour (3600 seconds)
     *      $cache->set('my-key', $some_data, 3600);
     *
     *  }
     *
     *  // at this point $some_data will always contain your data, either from cache, or fresh
     *  </code>
     *
     *  @param  string      $key    The key for which to return the cached value.
     *
     *  @return boolean             Returns the cached content associated with the given key **if** the associated cache
     *                              file exists **and** it is not expired, or `FALSE` otherwise.
     */
    public function get($key) {

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

            // if data was gz-compressed
            if ($this->cache_gzcompress) {
                $data = gzuncompress($data);
            }

            // if data was encrypted
            if ($this->cache_encrypt) {

                list($encrypted_data, $iv) = explode('::', base64_decode($data), 2);
                $data = openssl_decrypt($encrypted_data, 'aes-256-cbc', $this->cache_encrypt, 0, $iv);

            }

            // data is always serialized
            return unserialize($data);

        }

        // return false if we get this far
        return false;

    }

    /**
     *  Checks whether a cached, non-expired value exists for the given key.
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
     *  @return boolean             Returns information about the cache associated with the given key **if** the associated
     *                              cache file exists **and** it is not expired, or `FALSE` otherwise.
     *
     *                              If a cached values exists and it is not expired, the returned array will look like
     *
     *                              <code>
     *                              Array (
     *                                  'path'       => '',  //  path to the cache file
     *                                  'timeout'    => '',  //  the number of seconds the cache was supposed to be valid
     *                                  'ttl'        => '',  //  time to live, the number of seconds remaining until the cache expires
     *                              )
     *                              </code>
     */
    public function has($key) {

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
     *  Caches data identified by a unique key for a specific number of seconds.
     *
     *  <code>
     *  // if a cached, non-expired value for the sought key does not exist
     *  if (!($some_data = $cache->get('my-key'))) {
     *
     *      // do whatever you need to retrieve data
     *      $some_data = 'get this data';
     *
     *      // cache the values for one hour (3600 seconds)
     *      $cache->set('my-key', $some_data, 3600);
     *
     *  }
     *
     *  // at this point $some_data will always contain your data, either from cache, or fresh
     *  </code>
     *
     *  @param  string      $key         unique name identifying the data that is about to be stored.
     *
     *                                  The data will be saved in a file on disk at {@link path} and in the form of
     *
     *                                  `md5($key) . '-' . $timeout . {@link extension}`
     *
     *                                  Therefore, a cache file for the key `my-key`, having a validity of `3600` seconds
     *                                  (one hour) and with {@link extension} set to `.cache` would look like
     *
     *                                  `515780610702189dabd912e9c9ef6f38-3600.cache`
     *
     *  @param  mixed       $data       The data to be stored.
     *
     *                                  The data will be stored in {@link https://www.php.net/manual/en/function.serialize.php serialized} form.
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
     *                                  as expired.
     *
     *                                  If omitted, the value of {@link default_timeout} will be used.
     *
     *  @return boolean                 Returns `TRUE`
     */
    public function set($key, $data, $timeout = -1) {

        // make sure path exists and is writable
        $this->_check_path();

        // anything that evaluates to false, null, '', boolean false or 0 will not be cached
        if (!$data) {
            return false;
        }

        // if timeout is not specified
        // use the global value
        if ($timeout == -1) {
            $timeout = $this->default_timeout;
        }

        // if timeout value is invalid
        if (!is_numeric($timeout) || $timeout < 1 || !preg_match('/^[0-9]+$/', (string)$timeout)) {
            $this->_error('invalid timeout value argument in set() method');
        }

        // delete any other cache file in case it already exists with a different timeout
        $this->delete($key);

        // data is always serialized
        $data = serialize($data);

        // if data needs to be encrypted
        if ($this->cache_encrypt) {

            $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length('aes-256-cbc'));
            $encrypted = openssl_encrypt($data, 'aes-256-cbc', $this->cache_encrypt, 0, $iv);
            $data = base64_encode($encrypted . '::' . $iv);

        }

        // if data needs to also be gz-compressed
        if ($this->cache_gzcompress) {
            $data = gzcompress($data);
        }

        // cache content to file
        // get an exclusive lock on the file while doing this so that other instances cannot write at the same time
        file_put_contents($this->path . md5($key) . '-' . $timeout . $this->extension, $data, LOCK_EX);

        return true;

    }

    /**
     *  Checks if {@link path} exists and is writable and triggers a fatal error if it isn't.
     *
     *  @return void
     *
     *  @access private
     */
    private function _check_path() {

        if (!file_exists($this->path) || !is_writable($this->path)) {
            $this->_error('path "' . str_replace('\\', '/', getcwd()) . '/' . $this->path . '" does not exists or is not writable');
        }

    }

    /**
     *  Custom error function.
     *
     *  @param  string  $message        The error message to display.
     *
     *  @return void
     *
     *  @access private
     */
    private function _error($message) {

        trigger_error('<strong>Zebra_Cache</strong>: ' . $message, E_USER_ERROR);

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
     *                                          array(
     *                                              'path'      =>  '' // full path to the cache file
     *                                              'timeout'   =>  '' // number of seconds the cache file is to be considered valid
     *                                          )
     *                                          </code>
     *
     *                                          If the cache file doesn't exists, the returned value is `FALSE`.
     *
     *  @access private
     */
    private function _get_file_info($key) {

        // make sure path exists and is writable
        $this->_check_path();

        // get the matching cache file, if any
        $file = glob($this->path . md5($key) . '-*' . $this->extension);

        // if, for some reason, there are multiple cache files with the same key
        // (this should never happen, but we check, just in case)
        // we delete all
        if (count($file) > 1) {

            $this->delete($key);

        // if we found exactly one file
        } elseif (count($file) == 1) {

            // get the timeout value
            list($prefix, $timeout) = explode('-', $this->extension !== '' ? substr($file[0], 0, -strlen($this->extension)) : $file[0]);

            // if timeout value is valid
            if (is_numeric($timeout) && $timeout > 1 && preg_match('/^[0-9]+$/', $timeout)) {

                return array(
                    'path'      => $file[0],
                    'timeout'   => $timeout,
                );

            }

        }

        // return false if we get this far
        return false;

    }

}
