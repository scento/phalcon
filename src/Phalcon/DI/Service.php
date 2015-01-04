<?php
/**
 * Service
 *
 * @author Andres Gutierrez <andres@phalconphp.com>
 * @author Eduar Carvajal <eduar@phalconphp.com>
 * @author Wenzel Pünter <wenzel@phelix.me>
 * @version 1.2.6
 * @package Phalcon
*/
namespace Phalcon\DI;

use \Phalcon\DI\ServiceInterface;
use \Phalcon\DI\Exception;
use \Phalcon\DiInterface;
use \Phalcon\DI\Service\Builder;
use \ReflectionClass;
use \Closure;

/**
 * Phalcon\DI\Service
 *
 * Represents individually a service in the services container
 *
 *<code>
 * $service = new Phalcon\DI\Service('request', 'Phalcon\Http\Request');
 * $request = $service->resolve();
 *<code>
 *
 * @see https://github.com/phalcon/cphalcon/blob/1.2.6/ext/di/service.c
 */
class Service implements ServiceInterface
{
    /**
     * Name
     *
     * @var null|string
     * @access protected
    */
    protected $_name;

    /**
     * Definiton
     *
     * @var mixed
     * @access protected
    */
    protected $_definition;

    /**
     * Shared
     *
     * @var null|boolean
     * @access protected
    */
    protected $_shared;

    /**
     * Shared Instance
     *
     * @var null|object
     * @access protected
    */
    protected $_sharedInstance;

    /**
     * \Phalcon\DI\Service
     *
     * @param string $name
     * @param mixed $definition
     * @param boolean|null $shared
     * @throws Exception
     */
    public function __construct($name, $definition, $shared = null)
    {
        /* Type check */
        if (is_string($name) === false) {
            throw new Exception('Invalid parameter type.');
        }

        if (is_null($shared) === true) {
            $shared = false;
        } elseif (is_bool($shared) === false) {
            throw new Exception('Invalid parameter type.');
        }

        /* Update member variables */
        $this->_name = $name;
        $this->_definition = $definition;
        $this->_shared = $shared;
    }

    /**
     * Returns the service's name
     *
     * @return string
     */
    public function getName()
    {
        return $this->_name;
    }

    /**
     * Sets if the service is shared or not
     *
     * @param boolean $shared
     * @throws Exception
     */
    public function setShared($shared)
    {
        if (is_bool($shared) === false) {
            throw new Exception('Invalid parameter type.');
        }

        $this->_shared = $shared;
    }

    /**
     * Check whether the service is shared or not
     *
     * @return boolean
     */
    public function isShared()
    {
        return $this->_shared;
    }

    /**
     * Sets/Resets the shared instance related to the service
     *
     * @param object $sharedInstance
     * @throws Exception
     */
    public function setSharedInstance($sharedInstance)
    {
        if (is_object($sharedInstance) === false) {
            throw new Exception('Invalid parameter type.');
        }

        $this->_sharedInstance = $sharedInstance;
    }

    /**
     * Set the service definition
     *
     * @param mixed $definition
     */
    public function setDefinition($definition)
    {
        $this->_definition = $definition;
    }

    /**
     * Returns the service definition
     *
     * @return mixed
     */
    public function getDefinition()
    {
        return $this->_definition;
    }

    /**
     * Resolves the service
     *
     * @param array|null $parameters
     * @param \Phalcon\DiInterface|null $dependencyInjector
     * @return mixed
     * @throws Exception
     */
    public function resolve($parameters = null, $dependencyInjector = null)
    {
        /* Type check */
        if (is_array($parameters) === false &&
            is_null($parameters) === false) {
            throw new Exception('Invalid parameter type.');
        }

        if (is_object($dependencyInjector) === false ||
            $dependencyInjector instanceof DiInterface === false) {
            if (is_null($dependencyInjector) === false) {
                throw new Exception('Invalid parameter type.');
            }
        }

        /* Shared instance */
        if ($this->_shared === true) {
            if (is_null($this->_sharedInstance) === false) {
                return $this->_sharedInstance;
            }
        }

        $found = true;

        if (is_string($this->_definition) === true) {
            //String definitions can be class names without implicit parameters
            if (class_exists($this->_definition) === true) {
                if (is_array($parameters) === true && count($parameters) > 0) {
                    //Create new instance
                    try {
                        $mirror = new ReflectionClass($this->_definition);
                        $instance = $mirror->newInstanceArgs($parameters);
                    } catch (\Exception $e) {
                        return null;
                    }
                } else {
                    try {
                        $instance = new $this->_definition();
                    } catch (\Exception $e) {
                        return null;
                    }
                }
            } else {
                $found = false;
            }
        } else {
            //Object definitons can be a Closure or an already resolved instance
            if (is_object($this->_definition) === true) {
                if ($this->_definition instanceof Closure) {
                    if (is_array($parameters) === true) {
                        $instance = call_user_func_array($this->_definition, $parameters);
                    } else {
                        $instance = call_user_func($this->_definition);
                    }
                } else {
                    $instance = $this->_definition;
                }
            } else {
                //Array definitions require a 'className' parameter
                if (is_array($this->_definition) === true) {
                    $builder = new Builder();
                    $instance = $builder->build(
                        $dependencyInjector,
                        $this->_definition,
                        $parameters
                    );
                } else {
                    $found = false;
                }
            }
        }

        //If the service can't be built, we must throw an exception
        if ($found === false) {
            throw new Exception("Service '".$this->_name."' cannot be resolved");
        }

        //Update the shared instance if the service is shared
        if ($this->_shared === true) {
            $this->_sharedInstance = $instance;
        }

        return $instance;
    }

    /**
     * Changes a parameter in the definition without resolve the service
     *
     * @param int $position
     * @param array $parameter
     * @return \Phalcon\DI\Service
     * @throws Exception
     */
    public function setParameter($position, $parameter)
    {
        /* Type check */
        if (is_array($this->_definition) === false) {
            throw new Exception('Defintion must be an array to update its parameters');
        }

        if (is_int($position) === false) {
            throw new Exception('Position must be integer');
        }

        if (is_array($parameter) === false) {
            throw new Exception('The parameter must be an array');
        }

        /* Update the parameter */
        if (isset($this->_definition['arguments']) === true) {
            $arguments = $this->_definition['arguments'];
        } else {
            $arguments = array();
        }

        $arguments[$position] = $parameter;

        /* Re-update the definition */
        $this->_definition['arguments'] = $arguments;

        return $this;
    }

    /**
     * Returns a parameter in a specific position
     *
     * @param int $position
     * @return array|null
     * @throws Exception
     */
    public function getParameter($position)
    {
        /* Type check */
        if (is_array($this->_definition) === false) {
            throw new Exception('Definition must be an array to obtain its parameters');
        }

        if (is_int($position) === false) {
            throw new Exception('Position must be integer');
        }

        /* Get the parameter */
        if (isset($this->_definition['arguments']) === true &&
            isset($this->_definition['arguments'][$position]) === true) {
            return $this->_definition['arguments'][$position];
        }

        return;
    }

    /**
     * Restore the internal state of a service
     *
     * @param array $attributes
     * @return \Phalcon\DI\Service
     * @throws Exception
     */
    public static function __set_state($attributes)
    {
        if (is_array($attributes) === false) {
            throw new Exception('Invalid parameter type.');
        }

        if (isset($attributes['_name']) === false) {
            throw new Exception("The attribute '_name' is required");
        }

        if (isset($attributes['_definition']) === false) {
            //@note adapted exception message
            throw new Exception("The attribute '_shared' is required");
        }

        if (isset($attributes['_shared']) === false) {
            throw new Exception("The attribute '_shared' is required");
        }

        return new Service(
            $attributes['_name'],
            $attributes['_definition'],
            $attributes['_shared']
        );
    }
}
