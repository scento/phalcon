<?php
/**
 * Output Cache Frontend
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
 * Phalcon\Cache\Frontend\Output
 *
 * Allows to cache output fragments captured with ob_* functions
 *
 *<code>
 *
 * //Create an Output frontend. Cache the files for 2 days
 * $frontCache = new Phalcon\Cache\Frontend\Output(array(
 *   "lifetime" => 172800
 * ));
 *
 * // Create the component that will cache from the "Output" to a "File" backend
 * // Set the cache file directory - it's important to keep the "/" at the end of
 * // the value for the folder
 * $cache = new Phalcon\Cache\Backend\File($frontCache, array(
 *     "cacheDir" => "../app/cache/"
 * ));
 *
 * // Get/Set the cache file to ../app/cache/my-cache.html
 * $content = $cache->start("my-cache.html");
 *
 * // If $content is null then the content will be generated for the cache
 * if ($content === null) {
 *
 *     //Print date and time
 *     echo date("r");
 *
 *     //Generate a link to the sign-up action
 *     echo Phalcon\Tag::linkTo(
 *         array(
 *             "user/signup",
 *             "Sign Up",
 *             "class" => "signup-button"
 *         )
 *     );
 *
 *     // Store the output into the cache file
 *     $cache->save();
 *
 * } else {
 *
 *     // Echo the cached output
 *     echo $content;
 * }
 *</code>
 *
 * @see https://github.com/phalcon/cphalcon/blob/1.2.6/ext/cache/frontend/output.c
 */
class Output implements FrontendInterface
{
    /**
     * Buffering
     *
     * @var boolean
     * @access protected
    */
    protected $_buffering = false;

    /**
     * Frontend Options
     *
     * @var array|null
     * @access protected
    */
    protected $_frontendOptions;

    /**
     * \Phalcon\Cache\Frontend\Output constructor
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
     * Returns cache lifetime
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
        return $this->_buffering;
    }

    /**
     * Starts output frontend
     */
    public function start()
    {
        $this->_buffering = true;
        ob_start();
    }

    /**
     * Returns output cached content
     *
     * @return string|null
     */
    public function getContent()
    {
        if ($this->_buffering === true) {
            return ob_get_contents();
        }

        return null;
    }

    /**
     * Stops output frontend
     */
    public function stop()
    {
        if ($this->_buffering === true) {
            ob_end_clean();
        }

        $this->_buffering = false;
    }

    /**
     * Prepare data to be stored
     *
     * @param mixed $data
     * @return mixed
     */
    public function beforeStore($data)
    {
        return $data;
    }

    /**
     * Prepares data to be retrieved to user
     *
     * @param mixed $data
     * @return mixed
     */
    public function afterRetrieve($data)
    {
        return $data;
    }
}
