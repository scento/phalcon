<?php
/**
 * Behavior
 *
 * @author Andres Gutierrez <andres@phalconphp.com>
 * @author Eduar Carvajal <eduar@phalconphp.com>
 * @author Wenzel Pünter <wenzel@phelix.me>
 * @version 1.2.6
 * @package Phalcon
*/
namespace Phalcon\Mvc\Model;

use \Phalcon\Mvc\Model\Exception;

/**
 * Phalcon\Mvc\Model\Behavior
 *
 * This is an optional base class for ORM behaviors
 * 
 * @see https://github.com/phalcon/cphalcon/blob/1.2.6/ext/mvc/model/behavior.c
 */
abstract class Behavior
{
	/**
	 * Options
	 * 
	 * @var null|array
	 * @access protected
	*/
	protected $_options;

	/**
	 * \Phalcon\Mvc\Model\Behavior
	 *
	 * @param array|null $options
	 * @throws Exception
	 */
	public function __construct($options = null)
	{
		if(is_null($options) === false &&
			is_array($options) === false) {
			throw new Exception('Invalid parameter type.');
		}

		$this->_options = $options;
	}

	/**
	 * Checks whether the behavior must take action on certain event
	 *
	 * @param string $eventName
	 * @return boolean
	 * @throws Exception
	 */
	protected function mustTakeAction($eventName)
	{
		if(is_string($eventName) === false) {
			throw new Exception('Invalid parameter type.');
		}

		if(is_array($this->_options) === false) {
			return false;
		}

		return isset($this->_options[$eventName]);
	}

	/**
	 * Returns the behavior options related to an event
	 *
	 * @param string|null $eventName
	 * @return array
	 * @throws Exception
	 */
	protected function getOptions($eventName = null)
	{
		if(is_string($eventName) === false &&
			is_null($eventName) === false) {
			throw new Exception('Invalid parameter type.');
		}

		if(is_null($eventName) === false) {
			if(isset($this->_options[$eventName]) === true) {
				return $this->_options[$eventName];
			}

			return null;
		}

		return $this->_options;
	}

	/**
	 * This method receives the notifications from the EventsManager
	 *
	 * @param string $type
	 * @param \Phalcon\Mvc\ModelInterface $model
	 */
	public function notify($type, $model)
	{

	}

	/**
	 * Acts as fallbacks when a missing method is called on the model
	 *
	 * @param \Phalcon\Mvc\ModelInterface $model
	 * @param string $method
	 * @param array|null $arguments
	 * @throws Exception
	 */
	public function missingMethod($model, $method, $arguments = null)
	{
		
	}
}