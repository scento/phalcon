<?php
/**
 * Dependency Injector
 *
 * @author Andres Gutierrez <andres@phalconphp.com>
 * @author Eduar Carvajal <eduar@phalconphp.com>
 * @author Wenzel Pünter <wenzel@phelix.me>
 * @version 1.2.6
 * @package Phalcon
*/
namespace Phalcon;

use \ArrayAccess;
use \Phalcon\DiInterface;
use \Phalcon\DI\ServiceInterface;
use \Phalcon\DI\Service;
use \Phalcon\DI\InjectionAwareInterface;
use \Phalcon\DI\Exception as DiException;

/**
 * Phalcon\DI
 *
 * Phalcon\DI is a component that implements Dependency Injection/Service Location
 * of services and it's itself a container for them.
 *
 * Since Phalcon is highly decoupled, Phalcon\DI is essential to integrate the different
 * components of the framework. The developer can also use this component to inject dependencies
 * and manage global instances of the different classes used in the application.
 *
 * Basically, this component implements the `Inversion of Control` pattern. Applying this,
 * the objects do not receive their dependencies using setters or constructors, but requesting
 * a service dependency injector. This reduces the overall complexity, since there is only one
 * way to get the required dependencies within a component.
 *
 * Additionally, this pattern increases testability in the code, thus making it less prone to errors.
 *
 *<code>
 * $di = new Phalcon\DI();
 *
 * //Using a string definition
 * $di->set('request', 'Phalcon\Http\Request', true);
 *
 * //Using an anonymous function
 * $di->set('request', function(){
 *    return new Phalcon\Http\Request();
 * }, true);
 *
 * $request = $di->getRequest();
 *
 *</code>
 *
 * @see https://github.com/phalcon/cphalcon/1.2.6/master/ext/di.c
 */
class DI implements DiInterface
{
    /**
     * Services
     *
     * @var array
     * @access protected
    */
    protected $_services = array();

    /**
     * Shared Instances
     *
     * @var array
     * @access protected
    */
    protected $_sharedInstances = array();

    /**
     * Fresh Instance
     *
     * @var boolean
     * @access protected
    */
    protected $_freshInstance = false;

    /**
     * Default Instance
     *
     * @var null|\Phalcon\DI
     * @access protected
    */
    protected static $_default = null;

    /**
     * \Phalcon\DI constructor
     */
    public function __construct()
    {
        if (is_null(self::$_default) === true) {
            self::$_default = $this;
        }
    }

    /**
     * Registers a service in the services container
     *
     * @param string $name
     * @param mixed $definition
     * @param boolean $shared
     * @return \Phalcon\DI\ServiceInterface|null
     * @throws DiException
     */
    public function set($name, $definition, $shared = false)
    {
        if (is_string($name) === false) {
            throw new DiException('The service name must be a string');
        }

        if (is_bool($shared) === false) {
            throw new DiException('Invalid parameter type.');
        }

        try {
            $this->_services[$name] = new Service($name, $definition, $shared);
        } catch (\Exception $e) {
            $this->_services[$name] = null;
        }
        return $this->_services[$name];
    }

    /**
     * Registers an "always shared" service in the services container
     *
     * @param string $name
     * @param mixed $definition
     * @return \Phalcon\DI\ServiceInterface|null
     */
    public function setShared($name, $definition)
    {
        return $this->set($name, $definition, true);
    }

    /**
     * Removes a service in the services container
     *
     * @param string $name
     * @throws DiException
     */
    public function remove($name)
    {
        if (is_string($name) === false) {
            throw new DiException('The service name must be a string');
        }

        unset($this->_services[$name]);

        //This is missing is the c++ source but logically required
        unset($this->_sharedInstances[$name]);
    }

    /**
     * Attempts to register a service in the services container
     * Only is successful if a service hasn't been registered previously
     * with the same name
     *
     * @param string $name
     * @param mixed $definition
     * @param boolean $shared
     * @return \Phalcon\DI\ServiceInterface|null
     * @throws DiException
     */
    public function attempt($name, $definition, $shared = false)
    {
        if (is_string($name) === false) {
            throw new DiException('The service name must be a string');
        }

        if (isset($this->_services[$name]) === false) {
            return $this->set($name, $definition, $shared);
        }

        return null;
    }

    /**
     * Sets a service using a raw \Phalcon\DI\Service definition
     *
     * @param string $name
     * @param \Phalcon\DI\ServiceInterface $rawDefinition
     * @return \Phalcon\DI\ServiceInterface
     * @throws DiException
     */
    public function setRaw($name, $rawDefinition)
    {
        if (is_string($name) === false) {
            throw new DiException('The service name must be a string');
        }

        if (is_object($rawDefinition) === false ||
            $rawDefinition instanceof ServiceInterface === false) {
            throw new DiException('The service definition must be an object');
        }

        $this->_services[$name] = $rawDefinition;
        return $rawDefinition;
    }

    /**
     * Returns a service definition without resolving
     *
     * @param string $name
     * @return mixed
     * @throws DiException
     */
    public function getRaw($name)
    {
        if (is_string($name) === false) {
            throw new DiException('The service name must be a string');
        }

        if (isset($this->_services[$name]) === true) {
            return $this->_services[$name]->getDefinition();
        }

        throw new DiException('Service \''.$name.'\' wasn\'t found in the dependency injection container');
    }

    /**
     * Returns a \Phalcon\DI\Service instance
     *
     * @param string $name
     * @return \Phalcon\DI\ServiceInterface
     * @throws DiException
     */
    public function getService($name)
    {
        if (is_string($name) === false) {
            throw new DiException('The service name must be a string');
        }

        if (isset($this->_services[$name]) === true) {
            return $this->_services[$name];
        }

        throw new DiException('Service \''.$name.'\' wasn\'t found in the dependency injection container');
    }

    /**
     * Create Instance
     *
     * @param string $className
     * @param array|null $params
     * @return object
     * @throws DiException
    */
    private static function createInstance($className, $params = null)
    {
        if (is_string($className) === false) {
            throw new DiException('Invalid class name');
        }

        if (is_array($params) === false || empty($params) === true) {
            return new $className;
        } else {
            $reflection = new ReflectionClass($className);
            return $reflection->newInstanceArgs($params);
        }
    }

    /**
     * Resolves the service based on its configuration
     *
     * @param string $name
     * @param array|null $parameters
     * @return mixed
     * @throws DiException
     */
    public function get($name, $parameters = null)
    {
        if (is_string($name) === false) {
            throw new DiException('The service name must be a string');
        }

        if (isset($this->_services[$name]) === true) {
            //Service is registered in the DI
            $instance = $this->_services[$name]->resolve($parameters, $this);
        } else {
            //Act as builder for any class
            if (class_exists($name) === true) {
                if (is_array($parameters) === true) {
                    if (empty($parameters) === false) {
                        $instance = self::createInstance($name, $params);
                    } else {
                        $instance = self::createInstance($name);
                    }
                } else {
                    $instance = self::createInstance($name);
                }
            } else {
                throw new DiException('Service \''.$name.'\' wasn\'t found in the dependency injection container');
            }
        }

        if (is_object($instance) === true &&
            $instance instanceof InjectionAwareInterface) {
            $instance->setDI($this);
        }

        return $instance;
    }

    /**
     * Resolves a service, the resolved service is stored in the DI, subsequent requests for this service will return the same instance
     *
     * @param string $name
     * @param array|null $parameters
     * @return mixed
     * @throws DiException
     */
    public function getShared($name, $parameters = null)
    {
        if (is_string($name) === false) {
            throw new DiException('The service alias must be a string');
        }

        if (isset($this->_sharedInstances[$name]) === true) {
            $instance = $this->_sharedInstances[$name];
            $this->_freshInstance = 0;
        } else {
            //Resolve
            $instance = $this->get($name, $parameters);

            //Save
            $this->_sharedInstances[$name] = $instance;
            $this->_freshInstance = true;
        }

        return $instance;
    }

    /**
     * Check whether the DI contains a service by a name
     *
     * @param string $name
     * @return boolean
     * @throws DiException
     */
    public function has($name)
    {
        if (is_string($name) === false) {
            throw new DiException('The service alias must be a string');
        }

        return isset($this->_services[$name]);
    }

    /**
     * Check whether the last service obtained via getShared produced a fresh instance or an existing one
     *
     * @return boolean
     */
    public function wasFreshInstance()
    {
        return $this->_freshInstance;
    }

    /**
     * Return the services registered in the DI
     *
     * @return \Phalcon\DI\Service[]
     */
    public function getServices()
    {
        return $this->_services;
    }

    /**
     * Check if a service is registered using the array syntax.
     * Alias for \Phalcon\Di::has()
     *
     * @param string $name
     * @return boolean
     */
    public function offsetExists($name)
    {
        return $this->has($name);
    }

    /**
     * Allows to register a shared service using the array syntax.
     * Alias for \Phalcon\Di::setShared()
     *
     *<code>
     *  $di['request'] = new \Phalcon\Http\Request();
     *</code>
     *
     * @param string $name
     * @param mixed $definition
     */
    public function offsetSet($name, $definition)
    {
        $this->setShared($name, $definition);
    }

    /**
     * Allows to obtain a shared service using the array syntax.
     * Alias for \Phalcon\Di::getShared()
     *
     *<code>
     *  var_dump($di['request']);
     *</code>
     *
     * @param string $name
     * @return mixed
     */
    public function offsetGet($name)
    {
        return $this->getShared($name, null);
    }

    /**
     * Removes a service from the services container using the array syntax.
     * Alias for \Phalcon\Di::remove()
     *
     * @param string $name
     */
    public function offsetUnset($name)
    {
        $this->remove($name);
    }

    /**
     * Magic method to get or set services using setters/getters
     *
     * @param string $method
     * @param array|null $arguments
     * @return mixed
     * @throws DiException
     */
    public function __call($method, $arguments = null)
    {
        if (strpos($method, 'get') === 0) {
            $serviceName = substr($method, 3);

            $possibleService = lcfirst($serviceName);
            if (isset($this->_services[$possibleService]) === true) {
                if (empty($arguments) === false) {
                    return $this->get($possibleService, $arguments);
                }
                return $this->get($possibleService);
            }
        }

        if (strpos($method, 'set') === 0) {
            if (isset($arguments[0]) === true) {
                $serviceName = substr($method, 3);

                $this->set(lcfirst($serviceName), $arguments[0]);
                return null;
            }
        }

        throw new DiException('Call to undefined method or service \''.$method."'");
    }

    /**
     * Set a default dependency injection container to be obtained into static methods
     *
     * @param \Phalcon\DiInterface $dependencyInjector
     */
    public static function setDefault($dependencyInjector)
    {
        if ($dependencyInjector instanceof DiInterface) {
            self::$_default = $dependencyInjector;
        }
    }

    /**
     * Return the lastest DI created
     *
     * @return \Phalcon\DiInterface
     */
    public static function getDefault()
    {
        return self::$_default;
    }

    /**
     * Resets the internal default DI
     */
    public static function reset()
    {
        self::$_default = null;
    }
}
