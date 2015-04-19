<?php
/**
 * Cookies
 *
 * @author Andres Gutierrez <andres@phalconphp.com>
 * @author Eduar Carvajal <eduar@phalconphp.com>
 * @author Wenzel Pünter <wenzel@phelix.me>
 * @version 1.2.6
 * @package Phalcon
*/
namespace Phalcon\Http\Response;

use \Phalcon\Http\Response\CookiesInterface;
use \Phalcon\Http\Response\Exception;
use \Phalcon\Http\Cookie;
use \Phalcon\Http\ResponseInterface;
use \Phalcon\DI\InjectionAwareInterface;
use \Phalcon\DiInterface;

/**
 * Phalcon\Http\Response\Cookies
 *
 * This class is a bag to manage the cookies
 * A cookies bag is automatically registered as part of the 'response' service in the DI
 *
 * @see https://github.com/phalcon/cphalcon/blob/1.2.6/ext/http/response/cookies.c
 */
class Cookies implements CookiesInterface, InjectionAwareInterface
{
    /**
     * Dependency Injector
     *
     * @var null|\Phalcon\DiInterface
     * @access protected
    */
    protected $_dependencyInjector;

    /**
     * Registered
     *
     * @var boolean
     * @access protected
    */
    protected $_registered = false;

    /**
     * Use Encryption
     *
     * @var boolean
     * @access protected
    */
    protected $_useEncryption = true;

    /**
     * Cookies
     *
     * @var null|array
     * @access protected
    */
    protected $_cookies;

    /**
     * Sets the dependency injector
     *
     * @param \Phalcon\DiInterface $dependencyInjector
     * @throws Exception
     */
    public function setDI($dependencyInjector)
    {
        if (is_object($dependencyInjector) === false ||
            $dependencyInjector instanceof DiInterface === false) {
            throw new Exception('Invalid parameter type.');
        }

        $this->_dependencyInjector = $dependencyInjector;
    }

    /**
     * Returns the internal dependency injector
     *
     * @return \Phalcon\DiInterface
     */
    public function getDI()
    {
        return $this->_dependencyInjector;
    }

    /**
     * Set if cookies in the bag must be automatically encrypted/decrypted
     *
     * @param boolean $useEncryption
     * @return \Phalcon\Http\Response\Cookies
     * @throws Exception
     */
    public function useEncryption($useEncryption)
    {
        if (is_bool($useEncryption) === false) {
            throw new Exception('Invalid parameter type.');
        }

        $this->_useEncryption = $useEncryption;
    }

    /**
     * Returns if the bag is automatically encrypting/decrypting cookies
     *
     * @return boolean
     */
    public function isUsingEncryption()
    {
        return $this->_useEncryption;
    }

    /**
     * Sets a cookie to be sent at the end of the request
     * This method overrides any cookie set before with the same name
     *
     * @param string $name
     * @param mixed $value
     * @param int|null $expire
     * @param string|null $path
     * @param boolean|null $secure
     * @param string|null $domain
     * @param boolean|null $httpOnly
     * @return \Phalcon\Http\Response\Cookies
     * @throws Exception
     */
    public function set($name, $value = null, $expire = null, $path = null,
        $secure = null, $domain = null, $httpOnly = null)
    {
        /* Type check */
        if (is_string($name) === false) {
            throw new Exception('The cookie name must be a string.');
        }

        if (is_null($expire) === true) {
            $expire = 0;
        } elseif (is_int($expire) === false) {
            throw new Exception('Invalid parameter type.');
        }

        if (is_null($path) === true) {
            $path = '/';
        } elseif (is_string($path) === false) {
            throw new Exception('Invalid parameter type.');
        }

        if (is_null($secure) === false && is_bool($secure) === false) {
            throw new Exception('Invalid parameter type.');
        }

        if (is_null($domain) === false && is_string($domain) === false) {
            throw new Exception('Invalid parameter type.');
        }

        if (is_null($httpOnly) === false && is_bool($httpOnly) === false) {
            throw new Exception('Invalid parameter type.');
        }

        if (is_array($this->_cookies) === false) {
            $this->_cookies = array();
        }

        /* Processing */
        //Check if the cookie needs to be updated
        if (isset($this->_cookies[$name]) === false) {
            //@note no validation
            $dependencyInjector = $this->_dependencyInjector;
            $cookie = new Cookie($name, $value, $expire, $path, $secure, $domain, $httpOnly);

            //Pass the DI to created cookies
            $cookie->setDi($dependencyInjector);

            //Enable encryption in the cookie
            if ($this->_useEncryption === true) {
                $cookie->useEncryption(true);
            }

            $this->_cookies[$name] = $cookie;
        } else {
            $cookie = $this->_cookies[$name];

            //Override any settings in the cookie
            $cookie->setValue($value);
            $cookie->setExpiration($expire);
            $cookie->setPath($path);
            $cookie->setSecure($secure);
            $cookie->setDomain($domain);
            $cookie->setHttpOnly($httpOnly);
        }

        //Register the cookies bag in the response
        if ($this->_registered === false) {
            $dependencyInjector = $this->_dependencyInjector;
            if (is_object($dependencyInjector) === false) {
                throw new Exception("A dependency injection object is required to access the 'response' service");
            }

            $response = $dependencyInjector->getShared('response');
            if (is_object($response) === false ||
                $response instanceof ResponseInterface === false) {
                throw new Exception('Wrong response service.');
            }

            /*
             * Pass the cookies bag to the response so it can send the headers at the of the
             * request
            */
            $response->setCookies($this);
        }

        return $this;
    }

    /**
     * Gets a cookie from the bag
     *
     * @param string $name
     * @return \Phalcon\Http\Cookie
     * @throws Exception
     */
    public function get($name)
    {
        if (is_string($name) === false) {
            throw new Exception('The cookie name must be string');
        }

        if (is_array($this->_cookies) === false) {
            $this->_cookies = array();
        }

        if (isset($this->_cookies[$name]) === true) {
            return $this->_cookies[$name];
        }

        //Create the cookie if it does not exist
        $cookie = new Cookie($name);

        $dependencyInjector = $this->_dependencyInjector;
        if (is_object($dependencyInjector) === true) {
            //Pass the DI to created cookies
            $cookie->setDi($dependencyInjector);

            //Enable encryption in the cookie
            if ($this->_useEncryption === true) {
                $cookie->useEncryption(true);
            }
        }

        $this->_cookies[$name] = $cookie;

        return $cookie;
    }

    /**
     * Check if a cookie is defined in the bag or exists in the $_COOKIE superglobal
     *
     * @param string $name
     * @return boolean
     * @throws Exception
     */
    public function has($name)
    {
        if (is_string($name) === false) {
            throw new Exception('Invalid parameter type.');
        }

        if (is_array($this->_cookies) === false) {
            $this->_cookies = array();
        }

        //Check the internal bag
        if (isset($this->_cookies[$name]) === true) {
            return true;
        }

        //Check the superglobal
        return isset($_COOKIE[$name]);
    }

    /**
     * Deletes a cookie by its name
     * This method does not removes cookies from the $_COOKIE superglobal
     *
     * @param string $name
     * @return boolean
     * @throws Exception
     */
    public function delete($name)
    {
        if (is_string($name) === false) {
            throw new Exception('Invalid parameter type.');
        }

        if (is_array($this->_cookies) === false) {
            $this->_cookies = array();
        }

        //Check the internal bag
        if (isset($this->_cookies[$name]) === true) {
            //@note no unset call?
            $this->_cookies[$name]->delete();
            return true;
        }

        return false;
    }

    /**
     * Sends the cookies to the client
     * Cookies aren't sent if headers are sent in the current request
     *
     * @return boolean
     */
    public function send()
    {
        if (is_array($this->_cookies) === false) {
            $this->_cookies = array();
        }

        if (headers_sent() === false) {
            foreach ($this->_cookies as $cookie) {
                $cookie->send();
            }

            return true;
        }

        return false;
    }

    /**
     * Reset set cookies
     *
     * @return \Phalcon\Http\Response\Cookies
     */
    public function reset()
    {
        $this->_cookies = array();

        return $this;
    }
}
