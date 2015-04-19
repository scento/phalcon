<?php
/**
 * Cookies Interface
 *
 * @author Andres Gutierrez <andres@phalconphp.com>
 * @author Eduar Carvajal <eduar@phalconphp.com>
 * @version 1.2.6
 * @package Phalcon
*/
namespace Phalcon\Http\Response;

/**
 * Phalcon\Http\Response\CookiesInterface initializer
 *
 * @see https://github.com/phalcon/cphalcon/blob/1.2.6/ext/http/response/cookiesinterface.c
 */
interface CookiesInterface
{
    /**
     * Set if cookies in the bag must be automatically encrypted/decrypted
     *
     * @param boolean $useEncryption
     * @return \Phalcon\Http\Response\CookiesInterface
     */
    public function useEncryption($useEncryption);

    /**
     * Returns if the bag is automatically encrypting/decrypting cookies
     *
     * @return boolean
     */
    public function isUsingEncryption();

    /**
     * Sets a cookie to be sent at the end of the request
     *
     * @param string $name
     * @param mixed $value
     * @param int|null $expire
     * @param string|null $path
     * @param boolean|null $secure
     * @param string|null $domain
     * @param boolean|null $httpOnly
     * @return \Phalcon\Http\Response\CookiesInterface
     */
    public function set($name, $value = null, $expire = null,
        $path = null, $secure = null, $domain = null, $httpOnly = null);

    /**
     * Gets a cookie from the bag
     *
     * @param string $name
     * @return \Phalcon\Http\Cookie
     */
    public function get($name);

    /**
     * Check if a cookie is defined in the bag or exists in the $_COOKIE superglobal
     *
     * @param string $name
     * @return boolean
     */
    public function has($name);

    /**
     * Deletes a cookie by its name
     * This method does not removes cookies from the $_COOKIE superglobal
     *
     * @param string $name
     * @return boolean
     */
    public function delete($name);

    /**
     * Sends the cookies to the client
     *
     * @return boolean
     */
    public function send();

    /**
     * Reset set cookies
     *
     * @return \Phalcon\Http\Response\CookiesInterface
     */
    public function reset();
}
