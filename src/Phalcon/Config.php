<?php
/**
 * Config
 *
 * @author Andres Gutierrez <andres@phalconphp.com>
 * @author Eduar Carvajal <eduar@phalconphp.com>
 * @author Wenzel Pünter <wenzel@phelix.me>
 * @version 1.2.6
 * @package Phalcon
 */
namespace Phalcon;

use \ArrayAccess;
use \Countable;
use \Phalcon\Config\Exception as ConfigException;

/**
 * Phalcon\Config
 *
 * Phalcon\Config is designed to simplify the access to, and the use of, configuration data within applications.
 * It provides a nested object property based user interface for accessing this configuration data within
 * application code.
 *
 *<code>
 *  $config = new Phalcon\Config(array(
 *      "database" => array(
 *          "adapter" => "Mysql",
 *          "host" => "localhost",
 *          "username" => "scott",
 *          "password" => "cheetah",
 *          "dbname" => "test_db"
 *      ),
 *      "phalcon" => array(
 *          "controllersDir" => "../app/controllers/",
 *          "modelsDir" => "../app/models/",
 *          "viewsDir" => "../app/views/"
 *      )
 * ));
 *</code>
 *
 * @see https://github.com/phalcon/cphalcon/blob/1.2.6/ext/config.c
 */
class Config implements ArrayAccess, Countable
{
    /**
     * Storage
     *
     * @var array
     * @access private
    */
    private $_storage = array();

    /**
     * \Phalcon\Config constructor
     *
     * @param array $arrayConfig
     * @throws ConfigException
     */
    public function __construct($arrayConfig)
    {
        if (is_array($arrayConfig) === false) {
            throw new ConfigException('The configuration must be an Array');
        }

        foreach ($arrayConfig as $key => $value) {
            if (is_array($value) === true) {
                $this->_storage[$key] = new self($value);
            } else {
                $this->_storage[$key] = $value;
            }
        }
    }

    /**
     * Allows to check whether an attribute is defined using the array-syntax
     *
     *<code>
     * var_dump(isset($config['database']));
     *</code>
     *
     * @param scalar $index
     * @return boolean
     * @throws ConfigException
     */
    public function offsetExists($index)
    {
        if (is_scalar($index) === false) {
            throw new ConfigException('Invalid parameter type.');
        }
        return isset($this->_storage[$index]);
    }

    /**
     * Gets an attribute from the configuration, if the attribute isn't defined returns null
     * If the value is exactly null or is not defined the default value will be used instead
     *
     *<code>
     * echo $config->get('controllersDir', '../app/controllers/');
     *</code>
     *
     * @param scalar $index
     * @param mixed $defaultValue
     * @return mixed
     * @throws ConfigException
     */
    public function get($index, $defaultValue = null)
    {
        if (is_scalar($index) === false) {
            throw new ConfigException('Invalid parameter type.');
        }
        return (isset($this->_storage[$index]) === true ? $this->_storage[$index] : $defaultValue);
    }

    /**
     * Gets an attribute using the array-syntax
     *
     *<code>
     * print_r($config['database']);
     *</code>
     *
     * @param scalar $index
     * @return mixed
     * @throws ConfigException
     */
    public function offsetGet($index)
    {
        return $this->get($index);
    }

    /**
     * Sets an attribute using the array-syntax
     *
     *<code>
     * $config['database'] = array('type' => 'Sqlite');
     *</code>
     *
     * @param scalar $index
     * @param mixed $value
     * @throws ConfigException
     */
    public function offsetSet($index, $value)
    {
        if (is_scalar($index) === false) {
            throw new ConfigException('Invalid parameter type.');
        }

        $this->_storage[$index] = $value;
    }

    /**
     * Unsets an attribute using the array-syntax
     *
     *<code>
     * unset($config['database']);
     *</code>
     *
     * @param scalar $index
     * @throws ConfigException
     */
    public function offsetUnset($index)
    {
        if (is_scalar($index) === false) {
            throw new ConfigException('Invalid parameter type.');
        }

        unset($this->_storage[$index]);
    }

    /**
     * Merges a configuration into the current one
     *
     * @brief void \Phalcon\Config::merge(array|object $with)
     *
     *<code>
     *  $appConfig = new \Phalcon\Config(array('database' => array('host' => 'localhost')));
     *  $globalConfig->merge($config2);
     *</code>
     *
     * @param \Phalcon\Config|array $config
     * @throws Exception ConfigException
     */
    public function merge($config)
    {
        if (is_object($config) === true && $config instanceof Config === true) {
            $config = $config->toArray(false);
        } elseif (is_array($config) === false) {
            throw new ConfigException('Configuration must be an object or array');
        }

        foreach ($config as $key => $value) {
            //The key is already defined in the object, we have to merge it
            if (isset($this->_storage[$key]) === true) {
                if ($this->$key instanceof Config === true &&
                    $value instanceof Config === true) {
                    $this->$key->merge($value);
                } else {
                    $this->$key = $value;
                }
            } else {
                if ($value instanceof Config === true) {
                    $this->$key = new self($value->toArray());
                } else {
                    $this->$key = $value;
                }
            }
        }
    }

    /**
     * Converts recursively the object to an array
     *
     * @brief array \Phalcon\Config::toArray(bool $recursive = true);
     *
     *<code>
     *  print_r($config->toArray());
     *</code>
     *
     * @param boolean $recursive
     * @return array
     */
    public function toArray($recursive = true)
    {
        $array = $this->_storage;

        if ($recursive === true) {
            foreach ($this->_storage as $key => $value) {
                if ($value instanceof Config === true) {
                    $array[$key] = $value->toArray($recursive);
                } else {
                    $array[$key] = $value;
                }
            }
        }

        return $array;
    }

    /**
     * Counts configuration elements
     *
     * @return int
    */
    public function count()
    {
        return count($this->_storage);
    }

    /**
     * Restore data after unserialize()
    */
    public function __wakeup()
    {
    }

    /**
     * Restores the state of a \Phalcon\Config object
     *
     * @param array $data
     * @return \Phalcon\Config
     */
    public static function __set_state($data)
    {
        //@warning this function is not compatible with a direct var_export
        return new Config($data);
    }

    /**
     * Get element
     *
     * @param scalar $index
     * @return mixed
     * @throws ConfigException
    */
    public function __get($index)
    {
        return $this->get($index);
    }

    /**
     * Set element
     *
     * @param scalar $index
     * @param mixed $value
     * @throws ConfigException
    */
    public function __set($index, $value)
    {
        $this->offsetSet($index, $value);
    }

    /**
     * Isset element?
     *
     * @param scalar $index
     * @return boolean
     * @throws ConfigException
    */
    public function __isset($index)
    {
        return $this->offsetExists($index);
    }

    /**
     * Unset element
     *
     * @WARNING This function is not implemented in the original
     * Phalcon API.
     *
     * @param scalar $index
     * @throws ConfigException
    */
    public function __unset($index)
    {
        $this->offsetUnset($index);
    }
}
