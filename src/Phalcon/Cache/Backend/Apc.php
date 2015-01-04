<?php
/**
 * APC Cache Backend
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
use \Phalcon\Cache\Exception;
use \APCIterator;
use \Iterator;

/**
 * Phalcon\Cache\Backend\Apc
 *
 * Allows to cache output fragments, PHP data and raw data using an APC backend
 *
 *<code>
 *  //Cache data for 2 days
 *  $frontCache = new Phalcon\Cache\Frontend\Data(array(
 *      'lifetime' => 172800
 *  ));
 *
 *  $cache = new Phalcon\Cache\Backend\Apc($frontCache, array(
 *      'prefix' => 'app-data'
 *  ));
 *
 *  //Cache arbitrary data
 *  $cache->save('my-data', array(1, 2, 3, 4, 5));
 *
 *  //Get data
 *  $data = $cache->get('my-data');
 *
 *</code>
 *
 * @see https://github.com/phalcon/cphalcon/blob/1.2.6/ext/cache/backend/apc.c
 */
class Apc extends Backend implements BackendInterface
{
    /**
     * Returns a cached content
     *
     * @param string $keyName
     * @param int|null $lifetime
     * @return mixed
     * @throws Exception
     */
    public function get($keyName, $lifetime = null)
    {
        /* Type check */
        if (is_string($keyName) === false) {
            throw new Exception('Invalid parameter type.');
        }

        if (is_null($lifetime) === false && is_int($lifetime) === false) {
            throw new Exception('Invalid parameter type.');
        }

        /* Fetch data */
        $this->_lastKey = '_PHCA'.$this->_prefix.$keyName;

        $cachedContent = apc_fetch($this->_lastKey);
        if ($cachedContent === false) {
            return null;
        }

        /* Processing */
        return $this->_frontend->afterRetrieve($cachedContent);
    }

    /**
     * Stores cached content into the APC backend and stops the frontend
     *
     * @param string|null $keyName
     * @param string|null $content
     * @param int|null $lifetime
     * @param boolean|null $stopBuffer
     * @throws Exception
     */
    public function save($keyName = null, $content = null, $lifetime = null, $stopBuffer = null)
    {
        /* Prepare input data */
        if (is_null($keyName) === true) {
            $lastKey = $this->_lastKey;

            if (isset($lastKey) === false) {
                throw new Exception('The cache must be started first');
            }
        } elseif (is_string($keyName) === true) {
            $lastKey = '_PHCA'.$this->_prefix.$keyName;
        } else {
            throw new Exception('Invalid parameter type.');
        }

        if (is_null($content) === true) {
            $content = $this->_frontend->getContent();
        } elseif (is_string($content) === false) {
            throw new Exception('Invalid parameter type.');
        }

        $storeableContent = $this->_frontend->beforeStore($content);

        if (is_null($lifetime) === true) {
            $lifetime = $this->_lastLifetime;
            if (is_null($lifetime) === true) {
                $lifetime = $this->_frontend->getLifetime();
            }
        } elseif (is_int($lifetime) === false) {
            throw new Exception('Invalid parameter type.');
        }

        if (is_bool($stopBuffer) === true) {
            $stopBuffer = true;
        } elseif (is_bool($stopBuffer) === false) {
            throw new Exception('Invalid parameter type.');
        }

        /* Store data */
        apc_store($lastKey, $storeableContent, $lifetime);

        /* Buffer */
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
     * @param string $keyName
     * @return boolean
     * @throws Exception
     */
    public function delete($keyName)
    {
        if (is_string($keyName) === false) {
            throw new Exception('Invalid parameter type.');
        }

        return apc_delete('_PHCA'.$this->_prefix.$keyName);
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
        if (is_null($prefix) === false) {
            $prefix = '/^_PHCA/';
        } elseif (is_string($prefix) === true) {
            $prefix = '/^_PHCA'.$prefix.'/';
        } else {
            throw new Exception('Invalid parameter types.');
        }

        $prefixlength = strlen($prefix);

        $keys = array();

        $iterator = new APCIterator('user', $prefix);

        //APCIterator implements Iterator
        if ($iterator instanceof Iterator === false) {
            throw new Exception('Invalid APC iteration class.');
        }

        foreach ($iterator as $key => $value) {
            if (is_string($key) === true) {
                $keys[] = substr($key, $prefixlength);
            }
        }

        return $keys;
    }

    /**
     * Checks if cache exists and it hasn't expired
     *
     * @param string|null $keyName
     * @param int|null $lifetime
     * @return boolean
     * @throws Exception
     */
    public function exists($keyName = null, $lifetime = null)
    {
        if (is_null($keyName) === true) {
            $lastKey = $this->_lastKey;
        } elseif (is_string($keyName) === true) {
            $lastKey = '_PHCA'.$this->_prefix.$keyName;
        } else {
            throw new Exception('Invalid parameter type.');
        }

        if (isset($lastKey) === true &&
            apc_exists($lastKey) !== false) {
            return true;
        }

        return false;
    }
}
