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

use \ArrayAccess,
	\Countable,
	\Phalcon\Config\Exception as ConfigException;

/**
 * Phalcon\Config
 *
 * Phalcon\Config is designed to simplify the access to, and the use of, configuration data within applications.
 * It provides a nested object property based user interface for accessing this configuration data within
 * application code.
 *
 *<code>
 *	$config = new Phalcon\Config(array(
 *		"database" => array(
 *			"adapter" => "Mysql",
 *			"host" => "localhost",
 *			"username" => "scott",
 *			"password" => "cheetah",
 *			"dbname" => "test_db"
 *		),
 *		"phalcon" => array(
 *			"controllersDir" => "../app/controllers/",
 *			"modelsDir" => "../app/models/",
 *			"viewsDir" => "../app/views/"
 *		)
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
	private $storage = array();

	/**
	 * \Phalcon\Config constructor
	 *
	 * @param array $arrayConfig
	 * @throws ConfigException
	 */
	public function __construct($arrayConfig = null)
	{
		if(is_array($arrayConfig) === false)
		{
			throw new ConfigException('The configuration must be an Array');
		}

		$this->storage = $arrayConfig;
	}


	/**
	 * Allows to check whether an attribute is defined using the array-syntax
	 *
	 *<code>
	 * var_dump(isset($config['database']));
	 *</code>
	 *
	 * @param string $index
	 * @return boolean
	 * @throws ConfigException
	 */
	public function offsetExists($index)
	{
		if(is_string($index) === false)
		{
			throw new ConfigException('Invalid parameter type.');
		}

		return isset($this->storage[$index]);
	}


	/**
	 * Gets an attribute from the configuration, if the attribute isn't defined returns null
	 * If the value is exactly null or is not defined the default value will be used instead
	 *
	 *<code>
	 * echo $config->get('controllersDir', '../app/controllers/');
	 *</code>
	 *
	 * @param string $index
	 * @param mixed $defaultValue
	 * @return mixed
	 * @throws ConfigException
	 */
	public function get($index, $defaultValue = null)
	{
		if(is_string($index) === false)
		{
			throw new ConfigException('Invalid parameter type.');
		}

		return (isset($this->storage[$index]) === true ? $this->storage[$index] : $defaultValue);
	}


	/**
	 * Gets an attribute using the array-syntax
	 *
	 *<code>
	 * print_r($config['database']);
	 *</code>
	 *
	 * @param string $index
	 * @return string|null
	 * @throws ConfigException
	 */
	public function offsetGet($index)
	{
		if(is_string($index) === false)
		{
			throw new ConfigException('Invalid parameter type.');
		}

		return (isset($this->storage[$index]) === true ? $this->storage[$index] : null);
	}


	/**
	 * Sets an attribute using the array-syntax
	 *
	 *<code>
	 * $config['database'] = array('type' => 'Sqlite');
	 *</code>
	 *
	 * @param string $index
	 * @param mixed $value
	 * @throws ConfigException
	 */
	public function offsetSet($index, $value)
	{
		if(is_string($index) === false)
		{
			throw new ConfigException('Invalid parameter type.');
		}

		$this->storage[$index] = $value;
	}


	/**
	 * Unsets an attribute using the array-syntax
	 *
	 *<code>
	 * unset($config['database']);
	 *</code>
	 *
	 * @param string $index
	 * @throws ConfigException
	 */
	public function offsetUnset($index)
	{
		if(is_string($index) === false)
		{
			throw new ConfigException('Invalid parameter type.');
		}

		unset($this->storage[$index]);
	}


	/**
	 * Merges a configuration into the current one
	 *
	 * @brief void \Phalcon\Config::merge(array|object $with)
	 *
	 *<code>
	 *	$appConfig = new \Phalcon\Config(array('database' => array('host' => 'localhost')));
	 *	$globalConfig->merge($config2);
	 *</code>
	 *
	 * @param \Phalcon\Config|array $config
	 * @todo Object should be more specific
	 * @throws Exception ConfigException
	 */
	public function merge($config)
	{
		if(is_array($config) === true)
		{
			$this->storage = array_merge($this->storage, $config);
		} elseif(is_object($config) === true)
		{
			$this->storage = array_merge($this->storage, $config->toArray());
		} else {
			throw new ConfigException('Invalid parameter type.');
		}
	}


	/**
	 * Converts recursively the object to an array
	 *
	 * @brief array \Phalcon\Config::toArray(bool $recursive = true);
	 *
	 *<code>
	 *	print_r($config->toArray());
	 *</code>
	 *
	 * @param boolean $recursive
	 * @return array
	 */
	public function toArray($recursive = true)
	{
		if($recursive === true)
		{
			return $this->storage;
		} else {
			$d = array();
			foreach($this->storage as $key => $value)
			{
				$d[$key] = new Config($value);
			}
			return $d;
		}
	}

	/**
	 * Counts configuration elements
	 * 
	 * @return int
	*/
	public function count()
	{
		return count($this->storage);
	}

	/**
	 * Restore data after unserialize()
	*/
	public function __wakeup(){}

	/**
	 * Restores the state of a \Phalcon\Config object
	 *
	 * @param array $data
	 * @return \Phalcon\Config
	 */
	public static function __set_state($data)
	{
		return new Config($data);
	}

	/**
	 * Get element
	 * 
	 * @param string $index
	 * @return mixed
	 * @throws ConfigException
	*/
	public function __get($index)
	{
		if(is_string($index) === false)
		{
			throw new ConfigException('Invalid parameter type.');
		}

		if(isset($this->storage[$index]) === true)
		{
			return $this->storage[$index];
		} else {
			return null;
		}
	}

	/**
	 * Set element
	 * 
	 * @param string $index
	 * @param mixed $value
	 * @throws ConfigException
	*/
	public function __set($index, $value)
	{
		if(is_string($index) === false)
		{
			throw new ConfigException('Invalid parameter type.');
		}

		$this->storage[$index] = $value;
	}

	/**
	 * Isset element?
	 * 
	 * @param string $index
	 * @return boolean
	 * @throws ConfigException
	*/
	public function __isset($index)
	{
		if(is_string($index) === false)
		{
			throw new ConfigException('Invalid parameter type.');
		}

		return isset($this->storage[$index]);
	}

	/**
	 * Unset element
	 * 
	 * @WARNING This function is not implemented in the original
	 * Phalcon API.
	 * 
	 * @param string $index
	 * @throws ConfigException
	*/
	public function __unset($index)
	{
		if(is_string($index) === false)
		{
			throw new ConfigException('Invalid parameter type.');
		}

		unset($this->storage[$index]);
	}
}