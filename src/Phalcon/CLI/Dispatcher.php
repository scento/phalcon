<?php
/**
 * CLI Dispatcher
 *
 * @author Andres Gutierrez <andres@phalconphp.com>
 * @author Eduar Carvajal <eduar@phalconphp.com>
 * @author Wenzel Pünter <wenzel@phelix.me>
 * @version 1.2.6
 * @package Phalcon
*/
namespace Phalcon\CLI;

use \Phalcon\Events\EventsAwareInterface,
	\Phalcon\DI\InjectionAwareInterface,
	\Phalcon\DispatcherInterface,
	\Phalcon\CLI\Dispatcher\Exception;

/**
 * Phalcon\CLI\Dispatcher
 *
 * Dispatching is the process of taking the command-line arguments, extracting the module name,
 * task name, action name, and optional parameters contained in it, and then
 * instantiating a task and calling an action on it.
 *
 *<code>
 *
 *	$di = new Phalcon\DI();
 *
 *	$dispatcher = new Phalcon\CLI\Dispatcher();
 *
 *  $dispatcher->setDI($di);
 *
 *	$dispatcher->setTaskName('posts');
 *	$dispatcher->setActionName('index');
 *	$dispatcher->setParams(array());
 *
 *	$handle = $dispatcher->dispatch();
 *
 *</code>
 * 
 * @see https://github.com/phalcon/cphalcon/blob/1.2.6/ext/cli/dispatcher.c
 */
class Dispatcher extends \Phalcon\Dispatcher implements EventsAwareInterface, InjectionAwareInterface, DispatcherInterface
{
	/**
	 * Exception: No Dependency Injector
	 * 
	 * @var int
	*/
	const EXCEPTION_NO_DI = 0;

	/**
	 * Exception: Cyclic Routing
	 * 
	 * @var int
	*/
	const EXCEPTION_CYCLIC_ROUTING = 1;

	/**
	 * Exception: Handler Not Found
	 * 
	 * @var int
	*/
	const EXCEPTION_HANDLER_NOT_FOUND = 2;

	/**
	 * Exception: Invalid Handler
	 * 
	 * @var int
	*/
	const EXCEPTION_INVALID_HANDLER = 3;

	/**
	 * Exception: Invalid Params
	 * 
	 * @var int
	*/
	const EXCEPTION_INVALID_PARAMS = 4;

	/**
	 * Exception: Action Not Found
	 * 
	 * @var int
	*/
	const EXCEPTION_ACTION_NOT_FOUND = 5;

	/**
	 * Handler Suffix
	 * 
	 * @var string
	 * @access protected
	*/
	protected $_handlerSuffix = 'Task';

	/**
	 * Default Handler
	 * 
	 * @var string
	 * @access protected
	*/
	protected $_defaultHandler = 'main';

	/**
	 * Default Action
	 * 
	 * @var string
	 * @access protected
	*/
	protected $_defaultAction = 'main';

	/**
	 * Sets the default task suffix
	 *
	 * @param string $taskSuffix
	 * @throws Exception
	 */
	public function setTaskSuffix($taskSuffix)
	{
		if(is_string($taskSuffix) === false) {
			throw new Exception('Invalid parameter type.');
		}

		$this->_handlerSuffix = $taskSuffix;
	}

	/**
	 * Sets the default task name
	 *
	 * @param string $taskName
	 * @throws Exception
	 */
	public function setDefaultTask($taskName)
	{
		if(is_string($taskName) === false) {
			throw new Exception('Invalid parameter type.');
		}

		$this->_defaultHandler = $taskName;
	}

	/**
	 * Sets the task name to be dispatched
	 *
	 * @param string $taskName
	 * @throws Exception
	 */
	public function setTaskName($taskName)
	{
		if(is_string($taskName) === false) {
			throw new Exception('Invalid parameter type.');
		}

		//@see \Phalcon\Dispatcher::_handlerName
		$this->_handlerName = $taskName;
	}

	/**
	 * Gets last dispatched task name
	 *
	 * @return string
	 */
	public function getTaskName()
	{
		return $this->_handlerName;
	}

	/**
	 * Throws an internal exception
	 *
	 * @param string $message
	 * @param int $exceptionCode
	 * @throws Exception
	 * @return boolean|null
	 */
	protected function _throwDispatchException($message, $exceptionCode = 0)
	{
		if(is_string($message) === false || is_int($exceptionCode) === false) {
			throw new Exception('Invalid parameter type.');
		}

		$exception = new Exception($message, $exceptionCode);

		if(is_object($this->_eventsManager) === true) {
			if($this->_eventsManager->fire('dispatch:beforeException', $this, $exception) === false) {
				return false;
			}
		}

		//Throw the exception if it wasn't handled
		throw $exception;
	}

	/**
	 * Handles a user exception
	 *
	 * @param \Exception $exception
	 * @return boolean|null
	 */
	protected function _handleException($exception)
	{
		if(is_object($this->_eventsManager) === true) {
			if($this->_eventsManager->fire('dispatch:beforeException', $this, $exception) === false) {
				return false;
			}
		}
	}

	/**
	 * Possible task class name that will be located to dispatch the request
	 *
	 * @return string
	 */
	public function getTaskClass()
	{
		return $this->getHandlerName();
	}

	/**
	 * Returns the lastest dispatched controller
	 *
	 * @return null|object
	 */
	public function getLastTask()
	{
		return $this->_lastHandler;
	}

	/**
	 * Returns the active task in the dispatcher
	 *
	 * @return null|object
	 */
	public function getActiveTask()
	{
		return $this->_activeHandler;
	}
}