<?php
/**
 * CLI Console
 *
 * @author Andres Gutierrez <andres@phalconphp.com>
 * @author Eduar Carvajal <eduar@phalconphp.com>
 * @author Wenzel Pünter <wenzel@phelix.me>
 * @version 1.2.6
 * @package Phalcon
*/
namespace Phalcon\CLI;

use \Phalcon\DI\InjectionAwareInterface,
	\Phalcon\Events\EventsAwareInterface,
	\Phalcon\DiInterface,
	\Phalcon\Events\ManagerInterface,
	\Phalcon\CLI\Console\Exception,
	\Phalcon\DispatcherInterface;

/**
 * Phalcon\CLI\Console
 *
 * This component allows to create CLI applications using Phalcon
 * 
 * @see https://github.com/phalcon/cphalcon/blob/1.2.6/ext/cli/console.c
 */
class Console implements InjectionAwareInterface, EventsAwareInterface
{
	/**
	 * Dependency Injector
	 * 
	 * @var null|\Phalcon\DiInterface
	 * @access protected
	*/
	protected $_dependencyInjector = null;

	/**
	 * Events Manager
	 * 
	 * @var null|\Phalcon\Events\ManagerInterface
	 * @access protected
	*/
	protected $_eventsManager = null;

	/**
	 * Modules
	 * 
	 * @var null|array
	 * @access protected
	*/
	protected $_modules = null;

	/**
	 * Module Object
	 * 
	 * @var null
	 * @access protected
	*/
	protected $_moduleObject = null;

	/**
	 * \Phalcon\CLI\Console constructor
	 * 
	 * @param \Phalcon\DiInterface|null $dependencyInjector
	 */
	public function __construct($dependencyInjector = null)
	{
		if(is_object($dependencyInjector) === true && 
			$dependencyInjector instanceof DiInterface === true) {
			$this->_dependencyInjector = $dependencyInjector;
		}
	}

	/**
	 * Sets the DependencyInjector container
	 *
	 * @param \Phalcon\DiInterface $dependencyInjector
	 */
	public function setDI($dependencyInjector)
	{
		if(is_object($dependencyInjector) === true &&
			$dependencyInjector instanceof \Phalcon\DiInterface === true) {
			$this->_dependencyInjector = $dependencyInjector;
		}
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
	 * Sets the events manager
	 *
	 * @param \Phalcon\Events\ManagerInterface $eventsManager
	 */
	public function setEventsManager($eventsManager)
	{
		if(is_object($eventsManager) === true &&
			$eventsManager instanceof ManagerInterface === true) {
			$this->_eventsManager = $eventsManager;
		}
	}

	/**
	 * Returns the internal event manager
	 *
	 * @return \Phalcon\Events\ManagerInterface|null
	 */
	public function getEventsManager()
	{
		return $this->_eventsManager;
	}

	/**
	 * Register an array of modules present in the console
	 *
	 *<code>
	 *	$application->registerModules(array(
	 *		'frontend' => array(
	 *			'className' => 'Multiple\Frontend\Module',
	 *			'path' => '../apps/frontend/Module.php'
	 *		),
	 *		'backend' => array(
	 *			'className' => 'Multiple\Backend\Module',
	 *			'path' => '../apps/backend/Module.php'
	 *		)
	 *	));
	 *</code>
	 *
	 * @param array $modules
	 * @throws Exception
	 */
	public function registerModules($modules)
	{
		if(is_array($modules) === false) {
			throw new Exception('Modules must be an array');
		}

		$this->_modules = $modules;
	}

	/**
	 * Merge modules with the existing ones
	 *
	 *<code>
	 *	$application->addModules(array(
	 *		'admin' => array(
	 *			'className' => 'Multiple\Admin\Module',
	 *			'path' => '../apps/admin/Module.php'
	 *		)
	 *	));
	 *</code>
	 *
	 * @param array $modules
	 * @throws Exception
	 */
	public function addModules($modules)
	{
		if(is_array($modules) === false) {
			throw new Exception('Modules must be an Array');
		}

		if(is_array($this->_modules) === false) {
			$this->_modules = array();
		}

		$this->_modules = array_merge($modules, $this->_modules);
	}

	/**
	 * Return the modules registered in the console
	 *
	 * @return array|null
	 */
	public function getModules()
	{
		return $this->_modules;
	}

	/**
	 * Handle the command-line arguments.
	 *  
	 * 
	 * <code>
	 * 	$arguments = array(
	 * 		'task' => 'taskname',
	 * 		'action' => 'action',
	 * 		'params' => array('parameter1', 'parameter2')
	 * 	);
	 * 	$console->handle($arguments);
	 * </code>
	 *
	 * @param array $arguments
	 * @return mixed
	 * @throws Exception
	 */
	public function handle($arguments = null)
	{
		/* Type check */
		if(is_null($arguments) === true) {
			$arguments = array();
		} elseif(is_array($arguments) === false) {
			throw new Exception('Invalid parameter type.');
		}

		if(is_object($this->_dependencyInjector) === false) {
			throw new Exception('A dependency injection object is required to access internal services');
		}

		$router = $this->_dependencyInjector->getShared('router');
		$router->handle($arguments);
		$moduleName = $router->getModuleName();
		
		if(isset($moduleName) === true) {

			//Event: console:beforeStartModule
			if(is_object($this->_eventsManager) === true) {
				if($this->_eventsManager->fire('console:beforeStartModule', $this, $moduleName) === false) {
					return false;
				}
			}

			//Validate module structure
			if(is_array($this->_modules) === false ||
				isset($this->_modules[$moduleName]) === false) {
				throw new Exception('Module \''.$moduleName.'\' isn\'t registered in the console container');
			}

			if(is_array($this->_modules[$moduleName]) === false) {
				throw new Exception('Invalid module definition path');
			}

			//Require ['path']
			if(isset($this->_modules[$moduleName]['path']) === true) {
				if(file_exists($this->_modules[$moduleName]['path']) === true) {
					require($this->_modules[$moduleName]['path']);
				} else {
					throw new Exception('Module definiton path \''.$this->_modules[$moduleName]['path'].'" doesn\'t exist');
				}
			}

			//Get class name
			if(isset($this->_modules[$moduleName]['className']) === true) {
				$className = $this->_modules[$moduleName]['className'];
			} else {
				$className = 'Module';
			}

			//Prepare $moduleObject
			$moduleObject = $this->_dependencyInjector->get($className);
			$moduleObject->registerAutoloaders();
			$moduleObject->registerServices($dependencyInjector);

			//Event: console:afterStartModule
			if(is_object($this->_eventsManager) === true) {
				$this->_moduleObject = $moduleObject;

				if($this->_eventsManager->fire('console:afterStartModule', $this, $moduleName) === false) {
					return false;
				}
			}
		}

		//Get route
		$taskName = $router->getTaskName();
		$actionName = $router->getActionName();
		$params = $router->getParams();

		//Get dispatcher
		$dispatcher = $this->_dependencyInjector->getShared('dispatcher');
		if(is_object($dispatcher) === false || ($dispatcher instanceof DispatcherInterface === false)) {
			throw new Exception('Dispatcher service is not available.');
		}

		//Set route
		$dispatcher->setTaskName($taskName);
		$dispatcher->setActionName($actionName);
		$dispatcher->setParams($params);

		//Event: console:beforeHandleTask
		if(is_object($this->_eventsManager) === true) {
			if($this->_eventsManager->fire('console:beforeHandleTask', $this, $dispatcher) === false) {
				return false;
			}
		}

		//Dispatch
		$task = $dispatcher->dispatch();

		if(is_object($this->_eventsManager) === true) {
			$this->_eventsManager->fire('console:afterHandleTask', $this, $task);
		}

		return $task;
	}
}