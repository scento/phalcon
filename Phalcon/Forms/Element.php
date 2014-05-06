<?php
/**
 * Element
 *
 * @author Andres Gutierrez <andres@phalconphp.com>
 * @author Eduar Carvajal <eduar@phalconphp.com>
 * @author Wenzel Pünter <wenzel@phelix.me>
 * @version 1.2.6
 * @package Phalcon
*/
namespace Phalcon\Forms;

use \Phalcon\Forms\Exception,
	\Phalcon\Forms\Form,
	\Phalcon\Validation\ValidatorInterface,
	\Phalcon\Validation\Message\Group,
	\Phalcon\Validation\Message,
	\Phalcon\Tag;

/**
 * Phalcon\Forms\Element
 *
 * This is a base class for form elements
 * 
 * @see https://github.com/phalcon/cphalcon/blob/1.2.6/ext/forms/element.c
 */
abstract class Element
{
	/**
	 * Form
	 * 
	 * @var null|\Phalcon\Forms\Form
	 * @access protected
	*/
	protected $_form;

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
	 * @var mixed
	 * @access protected
	*/
	protected $_value;

	/**
	 * Label
	 * 
	 * @var null|string
	 * @access protected
	*/
	protected $_label;

	/**
	 * Attributes
	 * 
	 * @var null|array
	 * @access protected
	*/
	protected $_attributes;

	/**
	 * Validators
	 * 
	 * @var null|array
	 * @access protected
	*/
	protected $_validators;

	/**
	 * Filters
	 * 
	 * @var null|string|array
	 * @access protected
	*/
	protected $_filters;

	/**
	 * Options
	 * 
	 * @var null|array
	 * @access protected
	*/
	protected $_options;

	/**
	 * Messages
	 * 
	 * @var null|\Phalcon\Validation\Message\Group
	 * @access protected
	*/
	protected $_messages;

	/**
	 * \Phalcon\Forms\Element constructor
	 *
	 * @param string $name
	 * @param array|null $attributes
	 * @throws Exception
	 */
	public function __construct($name, $attributes = null)
	{
		if(is_string($name) === false) {
			throw new Exception("The element's name must be a string");
		}

		if(is_array($attributes) === true) {
			$this->_attributes = $attributes;
		}
	}

	/**
	 * Sets the parent form to the element
	 *
	 * @param \Phalcon\Forms\Form $form
	 * @return \Phalcon\Forms\ElementInterface
	 * @throws Exception
	 */
	public function setForm($form)
	{
		if(is_object($form) === false ||
			$form instanceof Form === false) {
			throw new Exception('Invalid parameter type.');
		}

		return $this;
	}

	/**
	 * Returns the parent form to the element
	 *
	 * @return \Phalcon\Forms\Form|null
	 */
	public function getForm()
	{
		return $this->_form;
	}

	/**
	 * Sets the element's name
	 *
	 * @param string $name
	 * @return \Phalcon\Forms\ElementInterface
	 * @throws Exception
	 */
	public function setName($name)
	{
		if(is_string($name) === false) {
			throw new Exception('Invalid parameter type.');
		}

		$this->_name = $name;

		return $this;
	}

	/**
	 * Returns the element's name
	 *
	 * @return string
	 */
	public function getName()
	{
		return $this->_name;
	}

	/**
	 * Sets the element's filters
	 *
	 * @param array|string $filters
	 * @return \Phalcon\Forms\ElementInterface
	 * @throws Exception
	 */
	public function setFilters($filters)
	{
		if(is_string($filters) === false ||
			is_array($filters) === false) {
			throw new Exception('Invalid parameter type.');
		}

		$this->_filters = $filters;

		return $this;
	}

	/**
	 * Adds a filter to current list of filters
	 *
	 * @param string $filter
	 * @return \Phalcon\Forms\ElementInterface
	 * @throws Exception
	 */
	public function addFilter($filter)
	{
		if(is_string($filter) === false) {
			throw new Exception('Invalid parameter type.');
		}

		if(is_array($this->_filters) == true) {
			$this->_filters[] = $filter;
		} else {
			if(is_string($this->_filters) === true) {
				$this->_filters = array($this->_filters, $filter);
			} else {
				$this->_filters = $filter;
			}
		}
	}

	/**
	 * Returns the element's filters
	 *
	 * @return null|string|array
	 */
	public function getFilters()
	{
		return $this->_filters;
	}

	/**
	 * Adds a group of validators
	 *
	 * @param \Phalcon\Validation\ValidatorInterface[] $validators
	 * @param boolean|null $merge
	 * @return \Phalcon\Forms\ElementInterface
	 * @throws Exception
	 */
	public function addValidators($validators, $merge = null)
	{
		if(is_null($merge) === true) {
			$merge = true;
		} elseif(is_bool($merge) === false) {
			throw new Exception('Invalid parameter type.');
		}

		if(is_array($validators) === false) {
			throw new Exception("The validators parameter must be an array");
		}

		if(is_array($this->_validators) === false) {
			$this->_validators = array();
		}

		//@note nothing happens when $merge === false
		if($merge === true) {
			if(is_array($this->_validators) === true) {
				$this->_validators = array_merge($this->_validators, $validators);
			} else {
				$this->_validators = $validators;
			}
		}

		return $this;
	}

	/**
	 * Adds a validator to the element
	 *
	 * @param \Phalcon\Validation\ValidatorInterface $validator
	 * @return \Phalcon\Forms\ElementInterface
	 * @throws Exception
	 */
	public function addValidator($validator)
	{
		if(is_object($validator) === false ||
			$validator instanceof ValidatorInterface === false) {
			throw new Exception('The validators parameter must be an object');
		}

		if(is_array($this->_validators) === false) {
			$this->_validators = array();
		}

		$this->_validators[] = $validators;
	}

	/**
	 * Returns the validators registered for the element
	 *
	 * @return \Phalcon\Validation\ValidatorInterface[]|null
	 */
	public function getValidators()
	{
		return $this->_validators;
	}

	/**
	 * Returns an array of prepared attributes for \Phalcon\Tag helpers
	 * according to the element's parameters
	 *
	 * @param array|null $attributes
	 * @param boolean|null $useChecked
	 * @return array
	 * @throws Exception
	 */
	public function prepareAttributes($attributes = null, $useChecked = null)
	{
		/* Type check */
		if(is_array($attributes) === false) {
			$attributes = array();
		}

		if(is_null($useChecked) === true) {
			$useChecked = false;
		} elseif(is_bool($useChecked) === false) {
			throw new Exception('Invalid parameter type.');
		}

		//Create an array of parameters
		$attributes[0] = $this->_name;

		//Merge passed parameters with default ones
		if(is_array($this->_attributes) === true) {
			//@note we are potentially overriding data from $attributes
			$attributes = array_merge($this->_attributes, $attributes);
		}

		//Get the current element's value
		$value = $this->getValue();

		//If the widget has a value set it as default value
		if(is_null($value) === false) {
			if($useChecked === true) {
				/*
				 * Check if the element already has a default value, compare it with the one in the
				 * attributes, if they are the same mark the element as checked
				 */
				if(isset($attributes['value']) === true) {
					if($attributes['value'] === $value) {
						$attributes['checked'] = 'checked';
					}
				} else {
					//Evaluate the current value and mark the check as checked
					if($value == true) {
						$attributes['checked'] = 'checked';
					}

					$attributes['value'] = $value;
				}
			} else {
				$attributes['value'] = $value;
			}
		}

		return $attributes;
	}

	/**
	 * Sets a default attribute for the element
	 *
	 * @param string $attribute
	 * @param mixed $value
	 * @return \Phalcon\Forms\ElementInterface
	 * @throws Exception
	 */
	public function setAttribute($attribute, $value)
	{
		if(is_string($attribute) === false) {
			throw new Exception('Invalid parameter type.');
		}

		if(is_array($this->_attributes) === false) {
			$this->_attributes = array();
		}

		$this->_attributes[$attribute] = $value;

		return $this;
	}

	/**
	 * Returns the value of an attribute if present
	 *
	 * @param string $attribute
	 * @param mixed $defaultValue
	 * @return mixed
	 * @throws Exception
	 */
	public function getAttribute($attribute, $defaultValue = null)
	{
		if(is_string($attribute) === false) {
			throw new Exception('Invalid parameter type.');
		}

		if(is_array($this->_attributes) === true &&
			isset($this->_attributes[$attribute]) === true) {
			return $this->_attributes[$attribute];
		}

		return $defaultValue;
	}

	/**
	 * Sets default attributes for the element
	 *
	 * @param array $attributes
	 * @return \Phalcon\Forms\ElementInterface
	 * @throws Exception
	 */
	public function setAttributes($attributes)
	{
		if(is_array($attributes) === false) {
			throw new Exception("Parameter 'attributes' must be an array");
		}

		$this->_attributes = $attributes;

		return $this;
	}

	/**
	 * Returns the default attributes for the element
	 *
	 * @return array
	 */
	public function getAttributes()
	{
		if(is_array($this->_attributes) === false) {
			return array();
		}

		return $this->_attributes;
	}

	/**
	 * Sets an option for the element
	 *
	 * @param string $option
	 * @param mixed $value
	 * @return \Phalcon\Forms\ElementInterface
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
			throw new Exception("Parameter 'options' must be an array");
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
	 * Sets the element label
	 *
	 * @param string $label
	 * @return \Phalcon\Forms\ElementInterface
	 * @throws Exception
	 */
	public function setLabel($label)
	{
		if(is_string($label) === false) {
			throw new Exception('Invalid parameter type.');
		}

		$this->_label = $label;

		return $this;
	}

	/**
	 * Returns the element's label
	 *
	 * @return string|null
	 */
	public function getLabel()
	{
		return $this->_label;
	}

	/**
	 * Generate the HTML to label the element
	 *
	 * @return string
	 */
	public function label()
	{
		//Check if there is an 'id' attribute defined
		if(is_array($this->_attributes) === true &&
			isset($this->_attributes['id']) === true) {
			$name = $this->_attributes['id'];
		} else {
			$name = $this->_name;
		}

		//Use the default value or leave the same name as label
		if(is_string($this->_label) === true) {
			return '<label for="'.htmlspecialchars($name).'">'.$this->_label.'</label>';
		} else {
			return '<label for="'.htmlspecialchars($name).'">'.$name.'</label>'; 
		}
	}

	/**
	 * Sets a default value in case the form does not use an entity
	 * or there is no value available for the element in $_POST
	 *
	 * @param mixed $value
	 * @return \Phalcon\Forms\ElementInterface
	 */
	public function setDefault($value)
	{
		$this->_value = $value;

		return $this;
	}

	/**
	 * Returns the default value assigned to the element
	 *
	 * @return mixed
	 */
	public function getDefault()
	{
		return $this->_value;
	}

	/**
	 * Returns the element's value
	 *
	 * @return mixed
	 */
	public function getValue()
	{
		$value = null;

		//Get the related form
		if(is_object($this->_form) === true) {
			$has_default_value = Tag::hasValue($this->_name);
			if($has_default_value === false) {
				//Get the possible value for the widget
				$value = $this->_form->getValue($name);
			}
		}

		//Assign the default value if there is no form available
		if(is_null($value) === true) {
			$value = $this->_value;
		}

		return $value;
	}

	/**
	 * Returns the messages that belongs to the element
	 * The element needs to be attached to a form
	 *
	 * @return \Phalcon\Validation\Message\Group
	 */
	public function getMessages()
	{
		if(is_object($this->_messages) === true) {
			return $this->_messages;
		}

		$messages = new Group();
		$this->_messages = $messages;
		return $messages;
	}

	/**
	 * Checks whether there are messages attached to the element
	 *
	 * @return boolean
	 */
	public function hasMessages()
	{
		if(is_object($this->_messages) === true) {
			return (count($this->_messages) > 0 ? true : false);
		}

		return false;
	}

	/**
	 * Sets the validation messages related to the element
	 *
	 * @param \Phalcon\Validation\Message\Group $group
	 * @return \Phalcon\Forms\ElementInterface
	 * @throws Exception
	 */
	public function setMessages($group)
	{
		if(is_object($group) === false ||
			$group instanceof Group === false) {
			throw new Exception("The message group is not valid");
		}

		$this->_messages = $group;

		return $this;
	}

	/**
	 * Appends a message to the internal message list
	 *
	 * @param \Phalcon\Validation\Message $message
	 * @return \Phalcon\Forms\ElementInterface
	 * @throws Exception
	 */
	public function appendMessage($message)
	{
		if(is_object($message) === false ||
			$message instanceof Message === false) {
			throw new Exception('Invalid parameter type.');
		}

		if(is_object($this->_messages) === false) {
			$this->_messages = new Group($messages);
		}

		$this->_messages->appendMessage($message);

		return $this;
	}

	/**
	 * Clears every element in the form to its default value
	 *
	 * @return \Phalcon\Forms\Element
	 */
	public function clear()
	{
		Tag::setDefault($this->_name, null);

		return $this;
	}

	/**
	 * Magic method __toString renders the widget without atttributes
	 *
	 * @return string
	 */
	public function __toString()
	{
		try {
			return $this->render();
		} catch(\Exception $e) {
			trigger_error((string)$e->getMessage(), \E_USER_ERROR)
		}
	}
}