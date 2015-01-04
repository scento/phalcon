<?php
/**
 * Backend Interface
 *
 * @author Andres Gutierrez <andres@phalconphp.com>
 * @author Eduar Carvajal <eduar@phalconphp.com>
 * @version 1.2.6
 * @package Phalcon
*/
namespace Phalcon\Cache;

/**
 * Phalcon\Cache\BackendInterface initializer
 *
 * @see https://github.com/phalcon/cphalcon/blob/1.2.6/ext/cache/backendinterface.c
 */
interface BackendInterface
{
    /**
     * Starts a cache. The $keyname allows to identify the created fragment
     *
     * @param int|string $keyName
     * @param int|null $lifetime
     * @return mixed
     */
    public function start($keyName, $lifetime = null);

    /**
     * Stops the frontend without store any cached content
     *
     * @param boolean|null $stopBuffer
     */
    public function stop($stopBuffer = null);

    /**
     * Returns front-end instance adapter related to the back-end
     *
     * @return mixed
     */
    public function getFrontend();

    /**
     * Returns the backend options
     *
     * @return array
     */
    public function getOptions();

    /**
     * Checks whether the last cache is fresh or cached
     *
     * @return boolean
     */
    public function isFresh();

    /**
     * Checks whether the cache has starting buffering or not
     *
     * @return boolean
     */
    public function isStarted();

    /**
     * Sets the last key used in the cache
     *
     * @param string $lastKey
     */
    public function setLastKey($lastKey);

    /**
     * Gets the last key stored by the cache
     *
     * @return string
     */
    public function getLastKey();

    /**
     * Returns a cached content
     *
     * @param int|string $keyName
     * @param int|null $lifetime
     * @return mixed
     */
    public function get($keyName, $lifetime = null);

    /**
     * Stores cached content into the file backend and stops the frontend
     *
     * @param int|string|null $keyName
     * @param string|null $content
     * @param int|null $lifetime
     * @param boolean|null $stopBuffer
     */
    public function save($keyName = null, $content = null, $lifetime = null, $stopBuffer = null);

    /**
     * Deletes a value from the cache by its key
     *
     * @param int|string $keyName
     * @return boolean
     */
    public function delete($keyName);

    /**
     * Query the existing cached keys
     *
     * @param string|null $prefix
     * @return array
     */
    public function queryKeys($prefix = null);

    /**
     * Checks if cache exists and it hasn't expired
     *
     * @param string|null $keyName
     * @param int|null $lifetime
     * @return boolean
     */
    public function exists($keyName = null, $lifetime = null);
}
