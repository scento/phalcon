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
namespace Phalcon\Http;

use \Phalcon\DI\InjectionAwareInterface;
use \Phalcon\DiInterface;
use \Phalcon\CryptInterface;
use \Phalcon\FilterInterface;
use \Phalcon\Http\Cookie\Exception;
use \Phalcon\Session\AdapterInterface as SessionInterface;

/**
 * Phalcon\Http\Cookie
 *
 * Provide OO wrappers to manage a HTTP cookie
 *
 * @see https://github.com/phalcon/cphalcon/blob/1.2.6/ext/http/cookie.c
 */
class Cookie implements InjectionAwareInterface
{
    /**
     * Readed
     *
     * @var boolean
     * @access protected
    */
    protected $_readed = false;

    /**
     * Restored
     *
     * @var boolean
     * @access protected
    */
    protected $_restored = false;

    /**
     * Use Encryption?
     *
     * @var boolean
     * @access protected
    */
    protected $_useEncryption = false;

    /**
     * Dependency Injector
     *
     * @var null|\Phalcon\DiInterface
     * @access protected
    */
    protected $_dependencyInjector;

    /**
     * Filter
     *
     * @var null|\Phalcon\FilterInterface
     * @access protected
    */
    protected $_filter;

    /**
     * Name
     *
     * @var null|string
     * @access protected
    */
    protected $_name;

    /**
     * Value
     *
     * @var null|string
     * @access protected
    */
    protected $_value;

    /**
     * Expire
     *
     * @var null|int
     * @access protected
    */
    protected $_expire;

    /**
     * Path
     *
     * @var string
     * @access protected
    */
    protected $_path = '/';

    /**
     * Domain
     *
     * @var null|string
     * @access protected
    */
    protected $_domain;

    /**
     * Secure
     *
     * @var null|boolean
     * @access protected
    */
    protected $_secure;

    /**
     * HTTP Only?
     *
     * @var boolean
     * @access protected
    */
    protected $_httpOnly = true;

    /**
     * \Phalcon\Http\Cookie constructor
     *
     * @param string $name
     * @param string $value
     * @param int|null $expire
     * @param string|null $path
     * @param boolean|null $secure
     * @param string|null $domain
     * @param boolean|null $httpOnly
     * @throws Exception
     */
    public function __construct($name, $value = null, $expire = null, $path = null, $secure = null, $domain = null, $httpOnly = null)
    {
        /* Type check */
        if (is_string($name) === false) {
            throw new Exception('The cookie name must be string');
        }

        if (is_string($value) === true) {
            $this->_value = $value;
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

        if (is_bool($secure) === true) {
            $this->_secure = $secure;
        }

        if (is_string($domain) === true) {
            $this->_domain = $domain;
        }

        if (is_bool($httpOnly) === true) {
            $this->_httpOnly = $httpOnly;
        }

        /* Update property */
        $this->_name = $name;
        $this->_expire = $expire;
    }

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
     * @return \Phalcon\DiInterface|null
     */
    public function getDI()
    {
        return $this->_dependencyInjector;
    }

    /**
     * Sets the cookie's value
     *
     * @param string $value
     * @return \Phalcon\Http\CookieInterface
     * @throws Exception
     */
    public function setValue($value)
    {
        if (is_string($value) === false) {
            throw new Exception('Invalid parameter type.');
        }

        $this->_value = $value;
        $this->_readed = true;
    }

    /**
     * Returns the cookie's value
     *
     * @param string|array|null $filters
     * @param string|null $defaultValue
     * @return mixed
     * @throws Exception
     */
    public function getValue($filters = null, $defaultValue = null)
    {
        if (is_null($filters) === false &&
            is_string($filters) === false &&
            is_array($filters) === false) {
            throw new Exception('Invalid parameter type.');
        }

        if (is_string($defaultValue) === false &&
            is_null($defaultValue) === false) {
            throw new Exception('Invalid parameter type.');
        }

        if ($this->_restored === false) {
            $this->restore();
        }

        if ($this->_readed === false) {
            $name = $this->_name;

            if (isset($_COOKIE[$name]) === true) {
                $value = $_COOKIE[$name];
                if ($this->_useEncryption === true) {
                    $dependencyInjector = $this->_dependencyInjector;
                    //@note no interface validation
                    if (is_object($dependencyInjector) === false) {
                        //@note wrong exception message
                        throw new Exception("A dependency injection object is required to access the 'filter' service");
                    }

                    $crypt = $dependencyInjector->getShared('crypt');

                    if ($crypt instanceof CryptInterface === false) {
                        throw new Exception('Wrong crypt service.');
                    }

                    //Decrypt the value also decoding it with base64
                    $value = $crypt->decryptBase64($value);
                }

                //Update the value
                $this->_value = $value;

                if (is_null($filters) === false) {
                    $filter = $this->_filter;
                    if (is_object($filter) === false) {
                        //Get filter service
                        if (is_null($dependencyInjector) === true) {
                            $dependencyInjector = $this->_dependencyInjector;
                            if ($dependencyInjector instanceof DiInterface === false) {
                                throw new Exception('Invalid dependency injector');
                            }
                        }

                        $filter = $dependencyInjector->getShared('filter');
                        if ($filter instanceof FilterInterface === false) {
                            throw new Exception('Wrong filter service.');
                        }

                        $this->_filter = $filter;
                    }

                    return $filter->sanitize($value, $filters);
                }

                //Return the value without filtering
                return $value;
            }

            return $defaultValue;
        }

        return $this->_value;
    }

    /**
     * Sends the cookie to the HTTP client
     * Stores the cookie definition in session
     *
     * @return \Phalcon\Http\Cookie
     * @throws Exception
     */
    public function send()
    {
        //@note no interface validation
        if (is_object($this->_dependencyInjector) === true) {
            if ($this->_dependencyInjector->has('session') === true) {
                $definition = array();
                if ($this->_expire !== 0) {
                    $definition['expire'] = $this->_expire;
                }

                if (empty($this->_path) === false) {
                    $definition['path'] = $this->_path;
                }

                if (empty($this->_domain) === false) {
                    $definition['domain'] = $this->_domain;
                }

                if (empty($this->_secure) === false) {
                    $definition['secure'] = $this->_secure;
                }

                if (empty($this->_httpOnly) === false) {
                    $definition['httpOnly'] = $this->_httpOnly;
                }

                //The definition is stored in session
                if (count($definition) !== 0) {
                    $session = $this->_dependencyInjector->getShared('session');
                    if (is_null($session) === false) {
                        if ($session instanceof SessionInterface === false) {
                            throw new Exception('Wrong session service.');
                        }

                        $session->set('_PHCOOKIE_'.$this->_name, $definition);
                    }
                }
            }
        }

        /* Encryption */
        if ($this->_useEncryption === true && empty($this->_value) === false) {
            if (is_object($this->_dependencyInjector) === false) {
                //@note wrong exception message
                throw new Exception("A dependency injection object is required to access the 'filter' service");
            }

            $crypt = $this->_dependencyInjector->getShared('crypt');
            if ($crypt instanceof CryptInterface === false) {
                throw new Exception('Wrong crypt service.');
            }

            //Encrypt the value also coding it with base64
            $value = $crypt->encryptBase64($this->_value);
        }

        //Sets the cookie using the standard 'setcookie' function

        //@note use 'bool' as type for the last two parameter
        setcookie(
            (string)$this->_name,
            (string)$value,
            (int)$this->_expire,
            (string)$this->_path,
            (string)$this->_domain,
            (bool)$this->_secure,
            (bool)$this->_httpOnly
        );

        return $this;
    }

    /**
     * Reads the cookie-related info from the SESSION to restore the cookie as it was set
     * This method is automatically called internally so normally you don't need to call it
     *
     * @return \Phalcon\Http\Cookie
     */
    public function restore()
    {
        if ($this->_restored === false) {
            //@note no interface check
            if (is_object($this->_dependencyInjector) === true) {
                $session = $this->_dependencyInjector->getShared('session');

                if ($session instanceof SessionInterface === false) {
                    throw new Exception('Wrong session sevice.');
                }

                //@note no kind of session data validation

                $definition = $session->get('_PHCOOKIE_'.$this->_name);
                if (is_array($definition) === true) {
                    /* Read definition */
                    if (isset($definition['expire']) === true) {
                        $this->_expire = $definition['expire'];
                    }

                    if (isset($definition['domain']) === true) {
                        $this->_domain = $definition['domain'];
                    }

                    if (isset($definition['path']) === true) {
                        $this->_path = $definition['path'];
                    }

                    if (isset($definition['secure']) === true) {
                        $this->_secure = $definition['secure'];
                    }

                    if (isset($definition['httpOnly']) === true) {
                        $this->_httpOnly = $definition['httpOnly'];
                    }
                }
            }

            $this->_restored = true;
        }

        return $this;
    }

    /**
     * Deletes the cookie by setting an expire time in the past
     *
     * @throws Exception
     */
    public function delete()
    {
        if (is_object($this->_dependencyInjector) === true) {
            $session = $this->_dependencyInjector->getShared('session');

            if ($session instanceof SessionInterface === false) {
                throw new Exception('Wrong session service.');
            }

            $session->remove('_PHCOOKIE_'.$this->_name);
        }

        $this->_value = null;

        //@note use the type 'boolean' for the last two parameters
        setcookie(
            (string)$this->_name,
            null,
            time() - 691200,
            (string)$this->_path,
            (string)$this->_domain,
            (bool)$this->_secure,
            (bool)$this->_httpOnly
        );
    }

    /**
     * Sets if the cookie must be encrypted/decrypted automatically
     *
     * @param boolean $useEncryption
     * @return \Phalcon\Http\Cookie
     * @throws Exception
     */
    public function useEncryption($useEncryption)
    {
        if (is_bool($useEncryption) === false) {
            throw new Exception('Invalid parameter type.');
        }

        $this->_useEncryption = $useEncryption;

        return $this;
    }

    /**
     * Check if the cookie is using implicit encryption
     *
     * @return boolean
     */
    public function isUsingEncryption()
    {
        return $this->_useEncryption;
    }

    /**
     * Sets the cookie's expiration time
     *
     * @param int $expire
     * @return \Phalcon\Http\Cookie
     * @throws Exception
     */
    public function setExpiration($expire)
    {
        if (is_int($expire) === false) {
            throw new Exception('Invalid parameter type.');
        }

        if ($this->_restored === false) {
            $this->restore();
        }

        $this->_expire = $expire;

        return $this;
    }

    /**
     * Returns the current expiration time
     *
     * @return string
     */
    public function getExpiration()
    {
        if ($this->_restored === false) {
            $this->restore();
        }

        return (string)$this->_expire;
    }

    /**
     * Sets the cookie's expiration time
     *
     * @param string $path
     * @return \Phalcon\Http\Cookie
     * @throws Exception
     */
    public function setPath($path)
    {
        if (is_string($path) === false) {
            throw new Exception('Invalid parameter type.');
        }

        if ($this->_restored === false) {
            $this->restore();
        }

        $this->_path = $path;

        return $this;
    }

    /**
     * Returns the current cookie's path
     *
     * @return string
     */
    public function getPath()
    {
        if ($this->_restored === false) {
            $this->restore();
        }

        return (string)$this->_path;
    }

    /**
     * Sets the domain that the cookie is available to
     *
     * @param string $domain
     * @return \Phalcon\Http\Cookie
     * @throws Exception
     */
    public function setDomain($domain)
    {
        if (is_string($domain) === false) {
            throw new Exception('Invalid parameter type.');
        }

        if ($this->_restored === false) {
            $this->restore();
        }

        $this->_domain = $domain;

        return $this;
    }

    /**
     * Returns the domain that the cookie is available to
     *
     * @return string
     */
    public function getDomain()
    {
        if ($this->_restored === false) {
            $this->restore();
        }

        return (string)$this->_domain;
    }

    /**
     * Sets if the cookie must only be sent when the connection is secure (HTTPS)
     *
     * @param boolean $secure
     * @return \Phalcon\Http\Cookie
     * @throws Exception
     */
    public function setSecure($secure)
    {
        if (is_bool($secure) === false) {
            throw new Exception('Invalid parameter type.');
        }

        if ($this->_restored === false) {
            $this->restore();
        }

        $this->_secure = $secure;
    }

    /**
     * Returns whether the cookie must only be sent when the connection is secure (HTTPS)
     *
     * @return boolean
     */
    public function getSecure()
    {
        if ($this->_restored === false) {
            $this->restore();
        }

        return $this->_secure;
    }

    /**
     * Sets if the cookie is accessible only through the HTTP protocol
     *
     * @param boolean $httpOnly
     * @return \Phalcon\Http\Cookie
     * @throws Exception
     */
    public function setHttpOnly($httpOnly)
    {
        if (is_bool($httpOnly) === false) {
            throw new Exception('Invalid parameter type.');
        }

        if ($this->_restored === false) {
            $this->restore();
        }

        $this->_httpOnly = $httpOnly;

        return $this;
    }

    /**
     * Returns if the cookie is accessible only through the HTTP protocol
     *
     * @return boolean
     */
    public function getHttpOnly()
    {
        if ($this->_restored === false) {
            $this->restore();
        }

        return $this->_httpOnly;
    }

    /**
     * Magic __toString method converts the cookie's value to string
     *
     * @return mixed
     */
    public function __toString()
    {
        if (is_null($this->_value) === true) {
            try {
                return (string)$this->getValue();
            } catch (\Exception $e) {
                trigger_error((string)$e->getMessage(), \E_USER_ERROR);
            }
        }

        return (string)$this->_value;
    }
}
