<?php
/**
 * Cache Backend
 *
 * @author Andres Gutierrez <andres@phalconphp.com>
 * @author Eduar Carvajal <eduar@phalconphp.com>
 * @author Wenzel Pünter <wenzel@phelix.me>
 * @version 1.2.6
 * @package Phalcon
*/
namespace Phalcon\Cache;

use \Phalcon\Cache\Exception;

/**
 * Phalcon\Cache\Backend
 *
 * This class implements common functionality for backend adapters. A backend cache adapter may extend this class
 *
 * @see https://github.com/phalcon/cphalcon/blob/1.2.6/ext/cache/backend.c
 */
abstract class Backend
{
    /**
     * Frontend
     *
     * @var null|\Phalcon\Cache\FrontendInterface
     * @access protected
    */
    protected $_frontend;

    /**
     * Options
     *
     * @var null|array
     * @access protected
    */
    protected $_options;

    /**
     * Prefix
     *
     * @var string
     * @access protected
    */
    protected $_prefix = '';

    /**
     * Last Key
     *
     * @var string
     * @access protected
    */
    protected $_lastKey = '';

    /**
     * Last Lifetime
     *
     * @var null|int
     * @access protected
    */
    protected $_lastLifetime;

    /**
     * Fresh
     *
     * @var boolean
     * @access protected
    */
    protected $_fresh = false;

    /**
     * Started
     *
     * @var boolean
     * @access protected
    */
    protected $_started = false;

    /**
     * \Phalcon\Cache\Backend constructor
     *
     * @param \Phalcon\Cache\FrontendInterface $frontend
     * @param array|null $options
     * @throws Exception
     */
    public function __construct($frontend, $options = null)
    {
        if (is_null($options) === false && is_array($options) === false) {
            throw new Exception('Invalid parameter type.');
        }

        if (is_object($frontend) === false ||
            $frontend instanceof FrontendInterface === false) {
            throw new Exception('Frontend must be an Object');
        }

        //A common option is the prefix
        if (is_array($options) === true && isset($options['prefix']) === true) {
            $this->_prefix = $options['prefix'];
        }

        $this->_frontend = $frontend;
        $this->_options = $options;
    }

    /**
     * Starts a cache. The $keyname allows to identify the created fragment
     *
     * @param int|string $keyName
     * @param int|null $lifetime
     * @return mixed
     * @throws Exception
     */
    public function start($keyName, $lifetime = null)
    {
        /* Type check */
        if (is_string($keyName) === false && is_int($keyName) === false) {
            throw new Exception('Invalid parameter type.');
        }

        if (is_null($lifetime) == false && is_int($lifetime) === false) {
            throw new Exception('Invalid parameter type.');
        }

        //Get the cache content verifying if it was expired
        $existingCache = $this->get($keyName, $lifetime);
        if (is_null($existingCache) === true) {
            $this->_fresh = true;
            $this->_frontend->start();
        } else {
            $this->_fresh = false;
        }

        $this->_started = true;

        //Update the last lifetime to be used in save()
        if (is_null($lifetime) === false) {
            $this->_lastLifetime = $lifetime;
        }

        return $existingCache;
    }

    /**
     * Stops the frontend without store any cached content
     *
     * @param boolean|null $stopBuffer
     * @throws Exception
     */
    public function stop($stopBuffer = null)
    {
        if (is_null($stopBuffer) === true) {
            $stopBuffer = true;
        } elseif (is_bool($stopBuffer) === false) {
            throw new Exception('Invalid parameter type.');
        }

        if ($stopBuffer === true) {
            $this->_frontend->stop();
        }

        $this->_started = false;
    }

    /**
     * Returns front-end instance adapter related to the back-end
     *
     * @return \Phalcon\Cache\FrontendInterface
     */
    public function getFrontend()
    {
        return $this->_frontend;
    }

    /**
     * Returns the backend options
     *
     * @return array|null
     */
    public function getOptions()
    {
        return $this->_options;
    }

    /**
     * Checks whether the last cache is fresh or cached
     *
     * @return boolean
     */
    public function isFresh()
    {
        return $this->_fresh;
    }

    /**
     * Checks whether the cache has starting buffering or not
     *
     * @return boolean
     */
    public function isStarted()
    {
        return $this->_started;
    }

    /**
     * Sets the last key used in the cache
     *
     * @param string $lastKey
     * @throws Exception
     */
    public function setLastKey($lastKey)
    {
        if (is_string($lastKey) === false) {
            throw new Exception('Invalid parameter type.');
        }

        $this->_lastKey = $lastKey;
    }

    /**
     * Gets the last key stored by the cache
     *
     * @return string
     */
    public function getLastKey()
    {
        return $this->_lastKey;
    }

    /**
     * Gets the last lifetime set
     *
     * @return int|null
     */
    public function getLifetime()
    {
        return $this->_lastLifetime;
    }
}
