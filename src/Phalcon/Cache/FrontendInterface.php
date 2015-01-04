<?php
/**
 * Frontend Interface
 *
 * @author Andres Gutierrez <andres@phalconphp.com>
 * @author Eduar Carvajal <eduar@phalconphp.com>
 * @version 1.2.6
 * @package Phalcon
*/
namespace Phalcon\Cache;

/**
 * Phalcon\Cache\FrontendInterface initializer
 *
 * @see https://github.com/phalcon/cphalcon/blob/1.2.6/ext/cache/frontendinterface.c
 */
interface FrontendInterface
{
    /**
     * Returns the cache lifetime
     *
     * @return int
     */
    public function getLifetime();

    /**
     * Check whether if frontend is buffering output
     *
     * @return boolean
     */
    public function isBuffering();

    /**
     * Starts the frontend
     */
    public function start();

    /**
     * Returns output cached content
     *
     * @return string
     */
    public function getContent();

    /**
     * Stops the frontend
     */
    public function stop();

    /**
     * Serializes data before storing it
     *
     * @param mixed $data
     */
    public function beforeStore($data);

    /**
     * Unserializes data after retrieving it
     *
     * @param mixed $data
     */
    public function afterRetrieve($data);
}
