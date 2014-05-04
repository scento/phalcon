<?php
/**
 * Forms Manager
 *
 * @author Andres Gutierrez <andres@phalconphp.com>
 * @author Eduar Carvajal <eduar@phalconphp.com>
 * @author Wenzel Pünter <wenzel@phelix.me>
 * @version 1.2.6
 * @package Phalcon
*/
namespace Phalcon\Forms;

use \Phalcon\Forms\Exception,
	\Phalcon\Forms\Form;

/**
 * Phalcon\Forms\Manager
 *
 * Manages forms within the application. Allowing the developer to access them from
 * any part of the application
 * 
 * @see https://github.com/phalcon/cphalcon/blob/1.2.6/ext/forms/manager.c
 */
class Manager
{
	/**
	 * Forms
	 * 
	 * @var null|array
	 * @access protected
	*/
	protected $_forms;

	/**
	 * Creates a form registering it in the forms manager
	 *
	 * @param string $name
	 * @param object|null $entity
	 * @return \Phalcon\Forms\Form
	 * @throws Exception
	 */
	public function create($name, $entity = null)
	{
		if(is_string($name) === false) {
			throw new Exception('The form name must be string');
		}

		if(is_array($this->_forms) === false) {
			$this->_forms = array();
		}

		$form = new Form($entity);
		$this->_forms[$name] = $form;
		return $form;
	}

	/**
	 * Returns a form by its name
	 *
	 * @param string $name
	 * @return \Phalcon\Forms\Form
	 * @throws Exception
	 */
	public function get($name)
	{
		if(is_string($name) === false) {
			throw new Exception('Invalid parameter type.');
		}

		if(isset($this->_forms[$name]) === false) {
			throw new Exception("There is no form with name='".$name."'");
		}

		return $this->_forms[$name];
	}

	/**
	 * Checks if a form is registered in the forms manager
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

		return isset($this->_forms[$name]);
	}

	/**
	 * Registers a form in the Forms Manager
	 *
	 * @param string $name
	 * @param \Phalcon\Forms\Form $form
	 * @return \Phalcon\Forms\Form
	 * @throws Exception
	 */
	public function set($name, $form)
	{
		if(is_string($name) === false) {
			throw new Exception('The form name must be string');
		}

		if(is_object($form) === false ||
			$form instanceof Form === false) {
			throw new Exception('The form is not valid');
		}

		$this->_forms[$name] = $form;

		return $this;
	}
}