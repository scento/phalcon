<?php
/**
 * Form
 *
 * @author Andres Gutierrez <andres@phalconphp.com>
 * @author Eduar Carvajal <eduar@phalconphp.com>
 * @author Wenzel Pünter <wenzel@phelix.me>
 * @version 1.2.6
 * @package Phalcon
*/
namespace Phalcon\Forms;

use \Phalcon\DI\Injectable,
	\Phalcon\Events\EventsAwareInterface,
	\Phalcon\DI\InjectionAwareInterface,
	\Countable,
	\Iterator,
	\Phalcon\Forms\Exception,
	\Phalcon\Forms\ElementInterface,
	\Phalcon\FilterInterface,
	\Phalcon\Validation,
	\Phalcon\Validation\Message\Group;

/**
 * Phalcon\Forms\Form
 *
 * This component allows to build forms using an object-oriented interface
 * 
 * @see https://github.com/phalcon/cphalcon/blob/1.2.6/ext/forms/form.c
 */
class Form extends Injectable implements EventsAwareInterface, InjectionAwareInterface,
	Countable, Iterator
{
	/**
	 * Position
	 * 
	 * @var null|int
	 * @access protected
	*/
	protected $_position;

	/**
	 * Entity
	 * 
	 * @var null|object
	 * @access protected
	*/
	protected $_entity;

	/**
	 * Options
	 * 
	 * @var null|array
	 * @access protected
	*/
	protected $_options;

	/**
	 * Data
	 * 
	 * @var null|array
	 * @access protected
	*/
	protected $_data;

	/**
	 * Elements
	 * 
	 * @var null|array
	 * @access protected
	*/
	protected $_elements;

	/**
	 * Indexed Elements
	 * 
	 * @var null|array
	 * @access protected
	*/
	protected $_elementsIndexed;

	/**
	 * Messages
	 * 
	 * @var null|array
	 * @access protected
	*/
	protected $_messages;

	/**
	 * Action
	 * 
	 * @var null|string
	 * @access protected
	*/
	protected $_action;

	/**
	 * \Phalcon\Forms\Form constructor
	 *
	 * @param object|null $entity
	 * @param array|null $userOptions
	 * @throws Exception
	 */
	public function __construct($entity = null, $userOptions = null)
	{
		if(is_object($entity) === true) {
			$this->_entity = $entity;
		} elseif(is_null($entity) === false) {
			throw new Exception('The base entity is not valid');
		}

		if(is_array($userOptions) === true) {
			$this->_options = $userOptions;
		}

		if(method_exists($this, 'initialize') === true) {
			$this->initialize($entity, $userOptions);
		}
	}

	/**
	 * Sets the form's action
	 *
	 * @param string $action
	 * @return \Phalcon\Forms\Form
	 * @throws Exception
	 */
	public function setAction($action)
	{
		if(is_string($action) === false) {
			throw new Exception('Invalid parameter type.');
		}

		$this->_action = $action;
	}

	/**
	 * Returns the form's action
	 *
	 * @return string|null
	 */
	public function getAction()
	{
		return $this->_action;
	}

	/**
	 * Sets an option for the form
	 *
	 * @param string $option
	 * @param mixed $value
	 * @return \Phalcon\Forms\Form
	 * @throws Exception
	 */
	public function setUserOption($option, $value)
	{
		if(is_string($option) === false) {
			throw new Exception('Invalid parameter type.');
		}

		if(is_array($this->_options) === false) {
			$this->_options = array();
		}

		$this->_options[$option] = $value;

		return $this;
	}

	/**
	 * Returns the value of an option if present
	 *
	 * @param string $option
	 * @param mixed $defaultValue
	 * @return mixed
	 * @throws Exception
	 */
	public function getUserOption($option, $defaultValue = null)
	{
		if(is_string($option) === false) {
			throw new Exception('Invalid parameter type.');
		}

		if(is_array($this->_options) === true &&
			isset($this->_options[$option]) === true) {
			return $this->_options[$option];
		}

		return $defaultValue;
	}

	/**
	 * Sets options for the element
	 *
	 * @param array $options
	 * @return \Phalcon\Forms\ElementInterface
	 * @throws Exception
	 */
	public function setUserOptions($options)
	{
		if(is_array($options) === false) {
			throw new Exception("Parameter 'options' must be an array", 1);
		}

		$this->_options = $options;

		return $this;
	}

	/**
	 * Returns the options for the element
	 *
	 * @return array|null
	 */
	public function getUserOptions()
	{
		return $this->_options;
	}

	/**
	 * Sets the entity related to the model
	 *
	 * @param object $entity
	 * @return \Phalcon\Forms\Form
	 * @throws Exception
	 */
	public function setEntity($entity)
	{
		if(is_object($entity) === false) {
			throw new Exception('Invalid parameter type.');
		}

		$this->_entity = $entity;

		return $this;
	}

	/**
	 * Returns the entity related to the model
	 *
	 * @return object|null
	 */
	public function getEntity()
	{
		return $this->_entity;
	}

	/**
	 * Returns the form elements added to the form
	 *
	 * @return \Phalcon\Forms\ElementInterface[]|null
	 */
	public function getElements()
	{
		return $this->_elements;
	}

	/**
	 * Binds data to the entity
	 *
	 * @param array $data
	 * @param object $entity
	 * @param array|null $whitelist
	 * @return \Phalcon\Forms\Form
	 * @throws Exception
	 */
	public function bind($data, $entity, $whitelist = null)
	{
		$filter = null;

		if(is_array($data) === false) {
			throw new Exception('The data must be an array');
		}

		if(is_object($entity) === false) {
			throw new Exception('Invalid parameter type.');
		}

		if(is_array($whitelist) === false &&
			is_null($whitelist) === false) {
			throw new Exception('Invalid parameter type.');
		}

		if(is_array($this->_elements) === false) {
			throw new Exception('There are no elements in the form');
		}

		foreach($data as $key => $value) {
			if(isset($this->_elements[$key]) === false) {
				continue;
			}

			//Check if the item is in the whitelist
			if(is_array($whitelist) === true &&
				in_array($key, $whitelist) === false) {
				continue;
			}

			//Get the element
			$element = $this->_elements[$key];

			//Apply filters
			$filters = $element->getFilters();
			if(isset($filters) === true) {
				if(is_object($filter) === false) {
					//@note no further validation
					$dependencyInjector = $this->getDi();
					$filter = $dependencyInjector->getShared('filter');
					if(is_object($filter) === false ||
						$filter instanceof FilterInterface === false) {
						throw new Exception('Wrong filter service.');
					}
				}

				$value = $filter->sanitize($value, $filters);
			}

			//Use the setter if available
			$method = 'set'.$key;
			if(method_exists($entity, $method) === true) {
				call_user_func(array($entity, $method), $value);
				continue;
			}

			//Use the public property if it doesn't have a setter
			$entity->$key = $value;
		}

		$this->_data = $data;
	}

	/**
	 * Validates the form
	 *
	 * @param array|null $data
	 * @param object|null $entity
	 * @return boolean
	 * @throws Exception
	 */
	public function isValid($data = null, $entity = null)
	{
		if(is_array($data) === false &&
			is_null($data) === false) {
			throw new Exception('Invalid parameter type.');
		}

		if(is_object($entity) === false && 
			is_null($entity) === false) {
			throw new Exception('Invalid parameter type.');
		}

		if(is_array($this->_elements) === false) {
			return true;
		}

		//If the user doesn't pass an entity we use the one in this_ptr->_entity
		//@note the text does not match the === true but that's how it is
		if(is_object($entity) === true) {
			$this->bind($data, $entity);
		}

		//If the data is not an array use the one passed previously
		if(is_array($data) === false) {
			$data = $this->_data;
		}

		//Check if there is a method 'beforeValidation'
		if(method_exists($this, 'beforeValidation') === true) {
			if($this->beforeValidation($data, $entity) === false) {
				return false;
			}
		}

		$notFailed = true;

		$messages = array();

		foreach($this->_elements as $element) {
			$validators = $element->getValidators();
			if(is_array($validators) === true &&
				empty($validators) === false) {
				$name = $element->getName();
				$preparedValidators = array();
				foreach($validators as $validator) {
					$preparedValidators[] = array($name, $validator);
				}

				//Create an implicit validator
				$validation = new Validation($preparedValidators);

				//Get filters in the element
				$filters = $element->getFilters();

				//Assign the filters to the validation
				if(is_array($filters) === true) {
					$name = $element->getName();
					$validation->setFilters($name, $filters);
				}

				//Perform the validation
				$elementMessages = $validation->validate($data, $entity);
				if(empty($elementMessages) === false) {
					$name = $element->getName();
					$messages[$name] = $elementMessages;
					$notFailed = false;
				}
			}
		}

		//If the validation fails we update the messages
		if($notFailed === false) {
			$this->_messages = $messages;
		}

		//Check if there is a method 'afterValidation'
		if(method_exists($this, 'afterValidation') === true) {
			$this->afterValidation($messages);
		}

		return $notFailed;
	}

	/**
	 * Returns the messages generated in the validation
	 *
	 * @param boolean|null $byItemName
	 * @return array
	 * @throws Exception
	 */
	public function getMessages($byItemName = null)
	{
		if(is_null($byItemName) === true) {
			$byItemName = false;
		} elseif(is_bool($byItemName) === false) {
			throw new Exception('Invalid parameter type.');
		}

		$messages = $this->_messages;

		if($byItemName === true) {
			if(is_array($messages) === false) {
				return new Group();
			}

			return $messages;
		}

		$g = new Group();
		if(is_array($message) === true) {
			foreach($messages as $message) {
				$g->appendMessages($message);
			}
		}

		return $g;
	}

	/**
	 * Returns the messages generated for a specific element
	 *
	 * @param string $name
	 * @return \Phalcon\Validation\Message\Group[]
	 * @throws Exception
	 */
	public function getMessagesFor($name)
	{
		if(is_string($name) === false) {
			throw new Exception('Invalid parameter type.');
		}

		if(is_array($this->_messages) === false) {
			$this->_messages = array();
		}

		if(isset($this->_messages[$name]) === true) {
			return $this->_messages[$name];
		}

		$group = new Group();
		$this->_messages[$name] = $group;

		return $group;
	}

	/**
	 * Check if messages were generated for a specific element
	 *
	 * @param string $name
	 * @return boolean
	 * @throws Exception
	 */
	public function hasMessagesFor($name)
	{
		if(is_string($name) === false) {
			throw new Exception('Invalid parameter type.');
		}

		if(is_array($this->_messages) === true) {
			return isset($this->_messages[$name]);
		}

		return false;
	}

	/**
	 * Adds an element to the form
	 *
	 * @param \Phalcon\Forms\ElementInterface $element
	 * @return \Phalcon\Forms\Form
	 * @throws Exception
	 */
	public function add($element)
	{
		if(is_object($element) === false ||
			$element instanceof ElementInterface === false) {
			throw new Exception('The element is not valid');
		}

		if(is_array($this->_elements) === false) {
			$this->_elements = array();
		}

		$element->setForm($this);

		$this->_elements[$element->getName()] = $element;

		return $this;
	}

	/**
	 * Renders a specific item in the form
	 *
	 * @param string $name
	 * @param array|null $attributes
	 * @return string
	 * @throws Exception
	 */
	public function render($name, $attributes = null)
	{
		if(is_string($name) === false) {
			throw new Exception('The name must be a string');
		}

		if(is_array($this->_elements) === false || 
			isset($this->_elements[$name]) === false) {
			throw new Exception('Element with ID='.$name.' is not part of the form');
		}

		return $this->_elements[$name]->render($attributes);
	}

	/**
	 * Returns an element added to the form by its name
	 *
	 * @param string $name
	 * @return \Phalcon\Forms\ElementInterface
	 * @throws Exception
	 */
	public function get($name)
	{
		if(is_string($name) === false) {
			throw new Exception('Invalid parameter type.');
		}

		if(is_array($this->_elements) === false ||
			isset($this->_elements[$name]) === false) {
			throw new Exception('Element with ID='.$name.' is not part of the form');
		}

		return $this->_elements[$name];
	}

	/**
	 * Generate the label of a element added to the form including HTML
	 *
	 * @param string $name
	 * @return string
	 * @throws Exception
	 */
	public function label($name)
	{
		if(is_string($name) === false) {
			throw new Exception('Invalid parameter type.');
		}

		if(is_array($this->_elements) === false ||
			isset($this->_elements[$name]) === false) {
			throw new Exception('Element with ID='.$name.' is not part of the form');
		}

		return $this->_elements[$name]->label();
	}

	/**
	 * Returns a label for an element
	 *
	 * @param string $name
	 * @return string
	 * @throws Exception
	 */
	public function getLabel($name)
	{
		if(is_string($name) === false) {
			throw new Exception('Invalid parameter type.');
		}

		if(is_array($this->_elements) === false ||
			isset($this->_elements[$name]) === false) {
			throw new Exception('Element with ID='.$name.' is not part of the form');
		}

		$label = $this->_elements[$name]->getLabel();

		//Use the element's name as label if the label is not available
		if(empty($label) === true) {
			return $name;
		}

		return $label;
	}

	/**
	 * Gets a value from the internal related entity or from the default value
	 *
	 * @param string $name
	 * @return mixed
	 * @throws Exception
	 */
	public function getValue($name)
	{
		if(is_string($value) === false) {
			throw new Exception('Invalid parameter type.');
		}

		if(is_object($this->_entity) === true) {
			//Check if the entity has a getter
			$method = 'get'.$name;
			if(method_exists($this->_entity, $method) === true) {
				return call_user_method($method, $this->_entity);
			}

			//Check if the entity has a public property
			if(isset($this->_entity->name) === true) {
				return $this->_entity->name;
			}
		}

		if(is_array($this->_data) === true) {
			//Check if the data is in the data array
			if(isset($this->_data[$name]) === true) {
				return $this->_data[$name];
			}
		}
	}

	/**
	 * Check if the form contains an element
	 *
	 * @param string $name
	 * @return boolean
	 * @throws Exception
	 */
	public function has($name)
	{
		if(is_string($name) === false) {
			throw new Exception('Invalid parameter type.');
		}

		if(is_array($this->_elements) === false) {
			return false;
		}

		//Checks if the element is in the form
		return isset($this->_elements[$name]);
	}

	/**
	 * Removes an element from the form
	 *
	 * @param string $name
	 * @return boolean
	 * @throws Exception
	 */
	public function remove($name)
	{
		if(is_string($name) === false) {
			throw new Exception('Invalid parameter type.');
		}

		//Checks if the element is in the form
		if(is_array($this->_elements) === true &&
			isset($this->_elements[$name]) === true) {
			unset($this->_elements[$name]);
			return true;
		}

		//Clean the iterator index
		$this->_elementsIndexed = null;
		return false;
	}

	/**
	 * Clears every element in the form to its default value
	 *
	 * @param array|null $fields
	 * @return \Phalcon\Forms\Form
	 * @throws Exception
	 */
	public function clear($fields = null)
	{
		if(is_null($fields) === false &&
			is_array($fields) === false) {
			throw new Exception('Invalid parameter type.');
		}

		if(is_array($this->_elements) === true) {
			foreach($this->_elements as $element) {
				//@note slightly inefficient structure
				if(is_array($fields) === false) {
					$element->clear();
				} else {
					if(in_array($element->getName(), $fields) === true) {
						$element->clear();
					}
				}
			}
		}

		return $this;
	}

	/**
	 * Returns the number of elements in the form
	 *
	 * @return int
	 */
	public function count()
	{
		if(is_array($this->_elements) === true) {
			return count($this->_elements);
		}

		return 0;
	}

	/**
	 * Rewinds the internal iterator
	 */
	public function rewind()
	{
		$this->_position = 0;

		$this->_elementsIndexed = array_values($this->_elements);
	}

	/**
	 * Returns the current element in the iterator
	 *
	 * @return \Phalcon\Validation\Message|null
	 */
	public function current()
	{
		if(is_array($this->_elementsIndexed) === true &&
			isset($this->_elementsIndexed[$this->_position]) === true) {
			return $this->_elementsIndexed[$this->_position];
		}
	}

	/**
	 * Returns the current position/key in the iterator
	 *
	 * @return int
	 */
	public function key()
	{
		return $this->_position;
	}

	/**
	 * Moves the internal iteration pointer to the next position
	 */
	public function next()
	{
		++$this->_position;
	}

	/**
	 * Check if the current element in the iterator is valid
	 *
	 * @return boolean
	 */
	public function valid()
	{
		if(is_array($this->_elementsIndexed) === true) {
			return isset($this->_elementsIndexed[$this->_position]);
		}
	}
}