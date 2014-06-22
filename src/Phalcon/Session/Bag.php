<?php
/**
 * Bag
 *
 * @author Andres Gutierrez <andres@phalconphp.com>
 * @author Eduar Carvajal <eduar@phalconphp.com>
 * @author Wenzel Pünter <wenzel@phelix.me>
 * @version 1.2.6
 * @package Phalcon
*/
namespace Phalcon\Session;

use \Phalcon\DI\InjectionAwareInterface,
	\Phalcon\Session\BagInterface,
	\Phalcon\DiInterface,
	\Phalcon\DI,
	\Phalcon\Session\Exception,
	\Phalcon\Session\AdapterInterface;

/**
 * Phalcon\Session\Bag
 *
 * This component helps to separate session data into "namespaces". Working by this way
 * you can easily create groups of session variables into the application
 *
 *<code>
 *	$user = new \Phalcon\Session\Bag('user');
 *	$user->name = "Kimbra Johnson";
 *	$user->age = 22;
 *</code>
 * 
 * @see https://github.com/phalcon/cphalcon/blob/1.2.6/ext/session/bag.c
 */
class Bag implements InjectionAwareInterface, BagInterface
{
	/**
	 * Dependency Injector
	 * 
	 * @var null|\Phalcon\DiInterface
	 * @access protected
	*/
	protected $_dependencyInjector;

	/**
	 * Name
	 * 
	 * @var null|string
	 * @access protected
	*/
	protected $_name;

	/**
	 * Data
	 * 
	 * @var null|array
	 * @access protected
	*/
	protected $_data;

	/**
	 * Initialized
	 * 
	 * @var boolean
	 * @access protected
	*/
	protected $_initialized = false;

	/**
	 * Session
	 * 
	 * @var null|\Phalcon\Session\Adapter
	 * @access protected
	*/
	protected $_session;

	/**
	 * \Phalcon\Session\Bag constructor
	 *
	 * @param string $name
	 * @throws Exception
	 */
	public function __construct($name)
	{
		if(is_string($name) === false) {
			throw new Exception('Invalid parameter type.');
		}

		$this->_name = $name;
	}

	/**
	 * Sets the DependencyInjector container
	 *
	 * @param \Phalcon\DiInterface $dependencyInjector
	 * @throws Exception
	 */
	public function setDI($dependencyInjector)
	{
		if(is_object($dependencyInjector) === false ||
			$dependencyInjector instanceof DiInterface === false) {
			throw new Exception('The dependency injector must be an Object');
		}

		$this->_dependencyInjector = $dependencyInjector;
	}

	/**
	 * Returns the DependencyInjector container
	 *
	 * @return \Phalcon\DiInterface|null
	 */
	public function getDI()
	{
		return $this->_dependencyInjector;
	}

	/**
	 * Initializes the session bag. This method must not be called directly, the class calls it when its internal data is accesed
	 * 
	 * @throws Exception
	 */
	public function initialize()
	{
		/* Ensure session object is present */
		if(is_object($this->_session) === false) {
			if(is_object($this->_dependencyInjector) === false) {
				$dependencyInjector = DI::getDefault();
				if(is_object($dependencyInjector) === false) {
					throw new Exception('A dependency injection object is required to access the \'session\' service');
				}
			} else {
				$dependencyInjector = $this->_dependencyInjector;
			}

			$session = $dependencyInjector->getShared('session');

			if(is_object($session) === false ||
				$session instanceof AdapterInterface === false) {
				throw new Exception('Invalid session service.');
			}

			$this->_session = $session;
		}

		/* Obtain data */
		$data = $session->get($this->_name);
		if(is_array($data) === false) {
			$data = array();
		}

		$this->_data = $data;
		$this->_initialized = true;
	}

	/**
	 * Destroyes the session bag
	 *
	 *<code>
	 * $user->destroy();
	 *</code>
	 */
	public function destroy()
	{
		if($this->_initialized === false) {
			$this->initialize();
		}

		$this->_session->remove($this->_name);
	}

	/**
	 * Sets a value in the session bag
	 *
	 *<code>
	 * $user->set('name', 'Kimbra');
	 *</code>
	 *
	 * @param string $property
	 * @param mixed $value
	 * @throws Exception
	 */
	public function set($property, $value)
	{
		if(is_string($property) === false) {
			throw new Exception('Invalid parameter type.');
		}

		if($this->_initialized === false) {
			$this->initialize();
		}

		$this->_data[$property] = $value;
		$this->_session->set($this->_name, $this->_data);
	}

	/**
	 * Magic setter to assign values to the session bag.
	 * Alias for \Phalcon\Session\Bag::set()
	 *
	 *<code>
	 * $user->name = "Kimbra";
	 *</code>
	 *
	 * @param string $property
	 * @param mixed $value
	 */
	public function __set($property, $value)
	{
		return $this->set($property, $value);
	}

	/**
	 * Obtains a value from the session bag optionally setting a default value
	 *
	 *<code>
	 * echo $user->get('name', 'Kimbra');
	 *</code>
	 *
	 * @param string $property
	 * @param mixed $defaultValue
	 * @return mixed
	 * @throws Exception
	 */
	public function get($property, $defaultValue = null)
	{
		if(is_string($property) === false) {
			throw new Exception('Invalid parameter type.');
		}

		/* Initialize bag if required */
		if($this->_initialized === false) {
			$this->initialize();
		}

		/* Retrieve the data */
		if(isset($this->_data[$property]) === true) {
			$value = $this->_data[$property];
			if(empty($value) === false) {
				return $value;
			}
		}

		return $defaultValue;
	}

	/**
	 * Magic getter to obtain values from the session bag.
	 * Alias for \Phalcon\Session\Bag::get()
	 *
	 *<code>
	 * echo $user->name;
	 *</code>
	 *
	 * @param string $property
	 * @return mixed
	 */
	public function __get($property)
	{
		return $this->get($property, null);
	}

	/**
	 * Check whether a property is defined in the internal bag
	 *
	 *<code>
	 * var_dump($user->has('name'));
	 *</code>
	 *
	 * @param string $property
	 * @return boolean
	 * @throws Exception
	 */
	public function has($property)
	{
		if(is_string($property) === false) {
			throw new Exception('Invalid parameter type.');
		}

		if($this->_initialized === false) {
			$this->initialize();
		}

		return isset($this->data[$property]);
	}

	/**
	 * Magic isset to check whether a property is defined in the bag.
	 * Alias for \Phalcon\Session\Bag::has()
	 *
	 *<code>
	 * var_dump(isset($user['name']));
	 *</code>
	 *
	 * @param string $property
	 * @return boolean
	 */
	public function __isset($property)
	{
		return $this->has($property);
	}

	/**
	 * Removes a property from the internal bag
	 *
	 *<code>
	 * $user->remove('name');
	 *</code>
	 *
	 * @param string $property
	 * @return boolean
	 * @throws Exception
	 */
	public function remove($property)
	{
		if(is_string($property) === false) {
			throw new Exception('Invalid parameter type.');
		}

		if($this->_initialized === false) {
			$this->initialize();
		}

		if(isset($this->_data[$property]) === true) {
			unset($this->_data[$property]);
			$this->_session->set($this->_name, $this->_data);

			return true;
		}

		return false;
	}

	/**
	 * Magic unset to remove items using the property syntax.
	 * Alias for \Phalcon\Session\Bag::remove()
	 *
	 *<code>
	 * unset($user['name']);
	 *</code>
	 *
	 * @param string $property
	 * @return boolean
	 */
	public function __unset($property)
	{
		return $this->remove($property);
	}
}