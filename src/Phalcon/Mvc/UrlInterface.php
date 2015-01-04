<?php
/**
 * URL Interface
 *
 * @author Andres Gutierrez <andres@phalconphp.com>
 * @author Eduar Carvajal <eduar@phalconphp.com>
 * @version 1.2.6
 * @package Phalcon
*/
namespace Phalcon\Mvc;

/**
 * Phalcon\Mvc\UrlInterface initializer
 *
 * @see https://github.com/phalcon/cphalcon/blob/1.2.6/ext/mvc/urlinterface.c
 */
interface UrlInterface
{
    /**
     * Sets a prefix to all the urls generated
     *
     * @param string $baseUri
     */
    public function setBaseUri($baseUri);

    /**
     * Returns the prefix for all the generated urls. By default /
     *
     * @return string
     */
    public function getBaseUri();

    /**
     * Sets a base paths for all the generated paths
     *
     * @param string $basePath
     */
    public function setBasePath($basePath);

    /**
     * Returns a base path
     *
     * @return string
     */
    public function getBasePath();

    /**
     * Generates a URL
     *
     * @param string|array|null $uri
     * @param mixed $args
     * @return string
     */
    public function get($uri = null, $args = null);

    /**
     * Generates a local path
     *
     * @param string|null $path
     * @return string
     */
    public function path($path = null);
}
