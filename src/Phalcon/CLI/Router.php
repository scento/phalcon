<?php
/**
 * CLI Router
 *
 * @author Andres Gutierrez <andres@phalconphp.com>
 * @author Eduar Carvajal <eduar@phalconphp.com>
 * @author Wenzel Pünter <wenzel@phelix.me>
 * @version 1.2.6
 * @package Phalcon
*/
namespace Phalcon\CLI;

use \Phalcon\DI\InjectionAwareInterface,
	\Phalcon\CLI\Router\Exception,
	\Phalcon\DiInterface;

/**
 * Phalcon\CLI\Router
 *
 * <p>Phalcon\CLI\Router is the standard framework router. Routing is the
 * process of taking a command-line arguments and
 * decomposing it into parameters to determine which module, task, and
 * action of that task should receive the request</p>
 *
 *<code>
 *	$router = new Phalcon\CLI\Router();
 *	$router->handle(array(
 *		'module' => 'main',
 *		'task' => 'videos',
 *		'action' => 'process'
 *	));
 *	echo $router->getTaskName();
 *</code>
 *
 * @see https://github.com/phalcon/cphalcon/blob/1.2.6/ext/cli/router.c
 */
class Router implements InjectionAwareInterface
{
	/**
	 * Dependency Injector
	 * 
	 * @var null|\Phalcon\DiInterface
	 * @access protected
	*/
	protected $_dependencyInjector = null;

	/**
	 * Module
	 * 
	 * @var null|string
	 * @access protected
	*/
	protected $_module = null;

	/**
	 * Task
	 * 
	 * @var null|string
	 * @access protected
	*/
	protected $_task = null;

	/**
	 * Action
	 * 
	 * @var null|string
	 * @access protected
	*/
	protected $_action = null;

	/**
	 * Params
	 * 
	 * @var null|array
	 * @access protected
	*/
	protected $_params = null;

	/**
	 * Default Module
	 * 
	 * @var null|string
	 * @access protected
	*/
	protected $_defaultModule = null;

	/**
	 * Default Task
	 * 
	 * @var null|string
	 * @access protected
	*/
	protected $_defaultTask = null;

	/**
	 * Default Action
	 * 
	 * @var null|string
	 * @access protected
	*/
	protected $_defaultAction = null;

	/**
	 * Default Params
	 * 
	 * @var null|array
	 * @access protected
	*/
	protected $_defaultParams = null;

	/**
	 * \Phalcon\CLI\Router constructor
	 */
	public function __construct()
	{
		$this->_params = array();
		$this->_defaultParams = array();
	}

	/**
	 * Sets the dependency injector
	 *
	 * @param \Phalcon\DiInterface $dependencyInjector
	 * @throws Exception
	 */
	public function setDI($dependencyInjector)
	{
		if(is_object($dependencyInjector) === false ||
			$dependencyInjector instanceof DiInterface === false) {
			throw new Exception('Invalid parameter type.');
		}

		$this->_dependencyInjector = $dependencyInjector;
	}

	/**
	 * Returns the internal dependency injector
	 *
	 * @return \Phalcon\DiInterface|null
	 */
	public function getDI()
	{
		return $this->_dependencyInjector;
	}

	/**
	 * Sets the name of the default module
	 *
	 * @param string $moduleName
	 * @throws Exception
	 */
	public function setDefaultModule($moduleName)
	{
		if(is_string($moduleName) === false) {
			throw new Exception('Invalid parameter type.');
		}

		$this->_defaultModule = $moduleName;
	}

	/**
	 * Sets the default controller name
	 *
	 * @param string $taskName
	 * @throws Exception
	 */
	public function setDefaultTask($taskName)
	{
		if(is_string($taskName) === false) {
			throw new Exception('Invalid parameter type.');
		}

		$this->_defaultTask = $taskName;
	}

	/**
	 * Sets the default action name
	 *
	 * @param string $actionName
	 * @throws Exception
	 */
	public function setDefaultAction($actionName)
	{
		if(is_string($actionName) === false) {
			throw new Exception('Invalid parameter type.');
		}

		$this->_defaultAction = $actionName;
	}

	/**
	 * Handles routing information received from command-line arguments
	 *
	 * @param array|null $arguments
	 * @throws Exception
	 */
	public function handle($arguments = null)
	{
		/* Type check */
		if(is_null($arguments) === true) {
			$arguments = array();
		} elseif(is_array($arguments) === false) {
			throw new Exception('Arguments must be an Array');
		}

		//Check for a module
		if(isset($arguments['module']) === true) {
			$module_name = $arguments['module'];
			unset($arguments['module']);
		} else {
			$module_name = null;
		}

		//Check for a task
		if(isset($arguments['task']) === true) {
			$task_name = $arguments['task'];
			unset($arguments['task']);
		} else {
			$task_name = null;
		}

		//Check for an action
		if(isset($arguments['action']) === true) {
			$action_name = $arguments['action'];
			unset($arguments['task']);
		} else {
			$action_name = null;
		}

		$this->_module = $module_name;
		$this->_task = $task_name;
		$this->_action = $action_name;
		$this->_params = $arguments;
	}

	/**
	 * Returns proccesed module name
	 *
	 * @return string|null
	 */
	public function getModuleName()
	{
		return $this->_module;
	}

	/**
	 * Returns proccesed task name
	 *
	 * @return string|null
	 */
	public function getTaskName()
	{
		return $this->_task;
	}

	/**
	 * Returns proccesed action name
	 *
	 * @return string|null
	 */
	public function getActionName()
	{
		return $this->_action;
	}

	/**
	 * Returns proccesed extra params
	 *
	 * @return array|null
	 */
	public function getParams()
	{
		return $this->_params;
	}
}