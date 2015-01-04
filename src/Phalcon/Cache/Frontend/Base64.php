<?php
/**
 * Base64 Cache Frontend
 *
 * @author Andres Gutierrez <andres@phalconphp.com>
 * @author Eduar Carvajal <eduar@phalconphp.com>
 * @author Wenzel Pünter <wenzel@phelix.me>
 * @version 1.2.6
 * @package Phalcon
*/
namespace Phalcon\Cache\Frontend;

use \Phalcon\Cache\FrontendInterface;
use \Phalcon\Cache\Exception;

/**
 * Phalcon\Cache\Frontend\Base64
 *
 * Allows to cache data converting/deconverting them to base64.
 *
 * This adapters uses the base64_encode/base64_decode PHP's functions
 *
 *<code>
 *
 * // Cache the files for 2 days using a Base64 frontend
 * $frontCache = new Phalcon\Cache\Frontend\Base64(array(
 *    "lifetime" => 172800
 * ));
 *
 * //Create a MongoDB cache
 * $cache = new Phalcon\Cache\Backend\Mongo($frontCache, array(
 *      'server' => "mongodb://localhost",
 *      'db' => 'caches',
 *      'collection' => 'images'
 * ));
 *
 * // Try to get cached image
 * $cacheKey = 'some-image.jpg.cache';
 * $image    = $cache->get($cacheKey);
 * if ($image === null) {
 *
 *     // Store the image in the cache
 *     $cache->save($cacheKey, file_get_contents('tmp-dir/some-image.jpg'));
 * }
 *
 * header('Content-Type: image/jpeg');
 * echo $image;
 *</code>
 *
 * @see https://github.com/phalcon/cphalcon/blob/1.2.6/ext/cache/frontend/base64.c
 */
class Base64 implements FrontendInterface
{
    /**
     * Frontend Options
     *
     * @var null|array
     * @access protected
    */
    protected $_frontendOptions;

    /**
     * \Phalcon\Cache\Frontend\Base64 constructor
     *
     * @param array|null $frontendOptions
     * @throws Exception
     */
    public function __construct($frontendOptions = null)
    {
        if (is_array($frontendOptions) === false &&
            is_null($frontendOptions) === false) {
            throw new Exception('Invalid parameter type.');
        }

        $this->_frontendOptions = $frontendOptions;
    }

    /**
     * Returns the cache lifetime
     *
     * @return integer
     */
    public function getLifetime()
    {
        if (is_array($this->_frontendOptions) === true &&
            isset($this->_frontendOptions['lifetime']) === true) {
                return $this->_frontendOptions['lifetime'];
        }

        return 1;
    }

    /**
     * Check whether if frontend is buffering output
     *
     * @return boolean
     */
    public function isBuffering()
    {
        return false;
    }

    /**
     * Starts output frontend. Actually, does nothing
     */
    public function start()
    {
    }

    /**
     * Returns output cached content
     *
     * @return string|null
     */
    public function getContent()
    {
    }

    /**
     * Stops output frontend
     */
    public function stop()
    {
    }

    /**
     * Serializes data before storing them
     *
     * @param mixed $data
     * @return string
     */
    public function beforeStore($data)
    {
        return base64_encode($data);
    }

    /**
     * Unserializes data after retrieval
     *
     * @param mixed $data
     * @return mixed
     */
    public function afterRetrieve($data)
    {
        //@note base64_decode can return "false" if an error occurs
        return base64_decode($data);
    }
}
