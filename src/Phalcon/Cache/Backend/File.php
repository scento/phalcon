<?php
/**
 * File Cache Backend
 *
 * @author Andres Gutierrez <andres@phalconphp.com>
 * @author Eduar Carvajal <eduar@phalconphp.com>
 * @author Wenzel Pünter <wenzel@phelix.me>
 * @version 1.2.6
 * @package Phalcon
*/
namespace Phalcon\Cache\Backend;

use \Phalcon\Cache\Backend;
use \Phalcon\Cache\BackendInterface;
use \Phalcon\Cache\FrontendInterface;
use \Phalcon\Cache\Exception;
use \DirectoryIterator;
use \Phalcon\Text;

/**
 * Phalcon\Cache\Backend\File
 *
 * Allows to cache output fragments using a file backend
 *
 *<code>
 *  //Cache the file for 2 days
 *  $frontendOptions = array(
 *      'lifetime' => 172800
 *  );
 *
 *  //Create a output cache
 *  $frontCache = \Phalcon\Cache\Frontend\Output($frontOptions);
 *
 *  //Set the cache directory
 *  $backendOptions = array(
 *      'cacheDir' => '../app/cache/'
 *  );
 *
 *  //Create the File backend
 *  $cache = new \Phalcon\Cache\Backend\File($frontCache, $backendOptions);
 *
 *  $content = $cache->start('my-cache');
 *  if ($content === null) {
 *      echo '<h1>', time(), '</h1>';
 *      $cache->save();
 *  } else {
 *      echo $content;
 *  }
 *</code>
 *
 * @see https://github.com/phalcon/cphalcon/blob/1.2.6/ext/cache/backend/file.c
 */
class File extends Backend implements BackendInterface
{
    /**
     * \Phalcon\Cache\Backend\File constructor
     *
     * @param \Phalcon\Cache\FrontendInterface $frontend
     * @param array $options
     * @throws Exception
     */
    public function __construct($frontend, $options = null)
    {
        if (is_array($options) === false ||
            isset($options['cacheDir']) === false) {
            throw new Exception('Cache directory must be specified with the option cacheDir');
        }

        parent::__construct($frontend, $options);
    }

    /**
     * Returns a cached content
     *
     * @param int|string $keyName
     * @param int|null $lifetime
     * @return mixed
     * @throws Exception
     */
    public function get($keyName, $lifetime = null)
    {
        /* Type check */
        if (is_int($keyName) === false && is_string($keyName) === false) {
            throw new Exception('Invalid parameter type.');
        }

        if (is_int($lifetime) === false && is_null($lifetime) === false) {
            throw new Exception('Invalid parameter type.');
        }

        $this->_lastKey = $this->_prefix.$keyName;
        $cacheFile = $this->_options['cacheDir'].$this->_lastKey;

        if (file_exists($cacheFile) === true) {
            //Check if the file has expired
            if (is_null($lifetime) === true) {
                $lifetime = $this->_lastLifetime;
                if (is_null($lifetime) === true) {
                    $lifetime = $this->_frontend->getLifetime();
                }
            }

            //The content is only retrieved if the content has not expired
            if (filemtime($cacheFile) > (time() - $lifetime)) {
                //Use file_get_contents to control that the openbase_dir can't be skipped
                $cachedContent = file_get_contents($cacheFile);
                if ($cachedContent === false) {
                    throw new Exception('Cache file '.$cacheFile.' could not be openend');
                }

                //Use the fronted to process the content of the cache
                return $this->_frontend->afterRetrieve($cachedContent);
            }
        }

        return null;
    }

    /**
     * Stores cached content into the file backend and stops the frontend
     *
     * @param int|string|null $keyName
     * @param string|null $content
     * @param int|null $lifetime
     * @param boolean|null $stopBuffer
     * @throws Exception
     */
    public function save($keyName = null, $content = null, $lifetime = null, $stopBuffer = null)
    {
        /* Input processing */
        if (is_null($keyName) === true) {
            $keyName = $this->_lastKey;
            if (isset($keyName) === false) {
                throw new Exception('The cache must be started first.');
            }
        } elseif (is_string($keyName) === true ||
            is_int($keyName) === true) {
            $keyName = $this->_prefix.$keyName;
        } else {
            throw new Exception('Invalid parameter type.');
        }

        if (is_string($content) === false &&
            is_null($content) === false) {
            throw new Exception('Invalid parameter type.');
        }

        if (is_int($lifetime) === false &&
            is_null($lifetime) === false) {
            throw new Exception('Invalid parameter type.');
        }

        if (is_null($stopBuffer) === true) {
            $stopBuffer = true;
        } elseif (is_bool($stopBuffer) === false) {
            throw new Exception('Invalid parameter type.');
        }

        /* Content preprocessing */
        if (isset($content) === false) {
            $content = $this->_frontend->getContent();
        }
        $preparedContent = $this->_frontend->beforeStore($content);

        /* Store data */
        //We use file_put_contents to respect open_base_dir directive
        if (file_put_contents($this->_options['cacheDir'].$keyName, $preparedContent) === false) {
            throw new Exception('Cache directory can\'t be written');
        }

        /* Buffer handeling */
        $isBuffering = $this->_frontend->isBuffering();

        if ($stopBuffer === true) {
            $this->_frontend->stop();
        }

        if ($isBuffering === true) {
            echo $content;
        }

        $this->_started = false;
    }

    /**
     * Deletes a value from the cache by its key
     *
     * @param int|string $keyName
     * @return boolean
     * @throws Exception
     */
    public function delete($keyName)
    {
        if (is_string($keyName) === false &&
            is_int($keyName) === false) {
            throw new Exception('Invalid parameter type.');
        }

        $cacheFile = $this->_options['cacheDir'].$this->_prefix.$keyName;
        if (file_exists($cacheFile) === true) {
            return unlink($cacheFile);
        }

        return false;
    }

    /**
     * Query the existing cached keys
     *
     * @param string|null $prefix
     * @return array
     * @throws Exception
     */
    public function queryKeys($prefix = null)
    {
        if (is_string($prefix) === false && is_null($prefix) === false) {
            throw new Exception('Invalid parameter type.');
        }

        $keys = array();

        //We use a directory iterator to traverse the cache dir directory
        $ce = new DirectoryIterator($this->_options['cacheDir']);

        if (is_null($prefix) === false) {
            //Prefix is set
            foreach ($ce as $item) {
                if (is_dir($item) === false) {
                    $key = $item->getFileName();
                    if (Text::startsWith($key, $prefix) === false) {
                        continue;
                    }

                    $keys[] = $key;
                }
            }
        } else {
            //Without using a prefix
            foreach ($ce as $item) {
                if (is_dir($item) === false) {
                    $keys[] =  $item->getFileName();
                }
            }
        }

        return $keys;
    }

    /**
     * Checks if cache exists and it isn't expired
     *
     * @param string|null $keyName
     * @param int|null $lifetime
     * @return boolean
     */
    public function exists($keyName = null, $lifetime = null)
    {
        /* Input preprocessing */
        if (is_null($keyName) === true) {
            $keyName = $this->_lastKey;
        } elseif (is_string($keyName) === true) {
            $keyName = $this->_prefix.$keyName;
        } else {
            throw new Exception('Invalid parameter type.');
        }

        if (is_int($lifetime) === false && is_null($lifetime) === false) {
            throw new Exception('Invalid parameter type.');
        }

        /* Check for file */
        if (isset($keyName) === true) {
            $cacheFile = $this->_options['cacheDir'].$keyName;
            if (file_exists($cacheFile) === true) {
                //Check if the file has expired
                if (is_null($lifetime) === true) {
                    $lifetime = $this->_frontend->getLifetime();
                }

                if (filemtime($cacheFile) > (time() - $lifetime)) {
                    return true;
                }
            }
        }

        return false;
    }
}
