<?php
/**
 * Application
 *
 * @author Andres Gutierrez <andres@phalconphp.com>
 * @author Eduar Carvajal <eduar@phalconphp.com>
 * @author Wenzel Pünter <wenzel@phelix.me>
 * @version 1.2.6
 * @package Phalcon
*/
namespace Phalcon\Mvc;

use \Phalcon\DI\Injectable,
	\Phalcon\Events\EventsAwareInterface,
	\Phalcon\DI\InjectionAwareInterface,
	\Phalcon\DiInterface,
	\Phalcon\Mvc\Application\Exception,
	\Phalcon\Http\ResponseInterface,
	\Closure;

/**
 * Phalcon\Mvc\Application
 *
 * This component encapsulates all the complex operations behind instantiating every component
 * needed and integrating it with the rest to allow the MVC pattern to operate as desired.
 *
 *<code>
 *
 * class Application extends \Phalcon\Mvc\Application
 * {
 *
 *		/\**
 *		 * Register the services here to make them general or register
 *		 * in the ModuleDefinition to make them module-specific
 *		 *\/
 *		protected function _registerServices()
 *		{
 *
 *		}
 *
 *		/\**
 *		 * This method registers all the modules in the application
 *		 *\/
 *		public function main()
 *		{
 *			$this->registerModules(array(
 *				'frontend' => array(
 *					'className' => 'Multiple\Frontend\Module',
 *					'path' => '../apps/frontend/Module.php'
 *				),
 *				'backend' => array(
 *					'className' => 'Multiple\Backend\Module',
 *					'path' => '../apps/backend/Module.php'
 *				)
 *			));
 *		}
 *	}
 *
 *	$application = new Application();
 *	$application->main();
 *
 *</code>
 * 
 * @see https://github.com/phalcon/cphalcon/blob/1.2.6/ext/mvc/application.c
 */
class Application extends Injectable implements EventsAwareInterface, InjectionAwareInterface
{
	/**
	 * Default Module
	 * 
	 * @var null|string
	 * @access protected
	*/
	protected $_defaultModule;

	/**
	 * Modules
	 * 
	 * @var null|array
	 * @access protected
	*/
	protected $_modules;

	/**
	 * Module Object
	 * 
	 * @var null
	 * @access protected
	*/
	protected $_moduleObject;

	/**
	 * Implicit View?
	 * 
	 * @var bool
	 * @access protected
	*/
	protected $_implicitView = true;

	/**
	 * \Phalcon\Mvc\Application
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
	 * By default. The view is implicitly buffering all the output
	 * You can full disable the view component using this method
	 *
	 * @param boolean $implicitView
	 * @return \Phalcon\Mvc\Application
	 * @throws Exception
	 */
	public function useImplicitView($implicitView)
	{
		if(is_bool($implicitView) === false) {
			throw new Exception('Invalid parameter type.');
		}

		$this->_implicitView = $implicitView;
		return $this;
	}

	/**
	 * Register an array of modules present in the application
	 *
	 *<code>
	 *	$this->registerModules(array(
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
	 * @param boolean|null $merge
	 * @param \Phalcon\Mvc\Application
	 * @throws Exception
	 */
	public function registerModules($modules, $merge = null)
	{
		if(is_null($merge) === true) {
			$merge = false;
		} elseif(is_bool($merge) === false) {
			throw new Exception('Invalid parameter type.');
		}

		if(is_array($modules) === false) {
			throw new Exception('Modules must be an Array');
		}

		if($merge === false) {
			$this->_modules = $modules;
		} else {
			if(is_array($this->_modules) === true) {
				$this->_modules = array_merge($this->_modules, $modules);
			} else {
				$this->_modules = $modules;
			}
		}

		return $this;
	}

	/**
	 * Return the modules registered in the application
	 *
	 * @return array|null
	 */
	public function getModules()
	{
		return $this->_modules;
	}

	/**
	 * Sets the module name to be used if the router doesn't return a valid module
	 *
	 * @param string $defaultModule
	 * @return \Phalcon\Mvc\Application
	 * @throws Exception
	 */
	public function setDefaultModule($defaultModule)
	{
		if(is_string($defaultModule) === false) {
			throw new Exception('Invalid parameter type.');
		}

		$this->_defaultModule = $defaultModule;

		return $this;
	}

	/**
	 * Returns the default module name
	 *
	 * @return string|null
	 */
	public function getDefaultModule()
	{
		return $this->_defaultModule;
	}

	/**
	 * Handles a MVC request
	 *
	 * @param string|null $uri
	 * @return \Phalcon\Http\ResponseInterface|boolean
	 * @throws Exception
	 */
	public function handle($uri = null)
	{
		/* Checks */
		if(is_null($uri) === false &&
			is_string($uri) === false) {
			throw new Exception('Invalid parameter type.');
		}

		if(is_object($this->_dependencyInjector) === false) {
			throw new Exception('A dependency injection object is required to access internal services');
		}

		/* Initialization */
		//Call boot event, this allows the developer to perform initialization actions
		if(is_object($this->_eventsManager) === true) {
			if($this->_eventsManager->fire('application:boot', $this) === false) {
				return false;
			}
		}

		$router = $this->_dependencyInjector->getShared('router');

		//Handle the URI pattern (if any) and get the module config
		$router->handle($uri);
		$module_name = $router->getModuleName();

		//If the router doesn't return a valid module we use the default module
		if(isset($module_name) === false) {
			$module_name = $this->_defaultModule;
		} else {
			//Process the module definition
			if(is_object($this->_eventsManager) === true) {
				if($this->_eventsManager->fire('application:beforeStartModule', $this, $module_name) === false) {
					return false;
				}
			}

			//Check if the module passed by the router is registered in the modules container
			if(isset($this->_modules[$module_name]) === false) {
				throw new Exception("Module '".$module_name."' isn't registered in the application container");
			}

			//A module definition must be an array or an object
			$module = $this->_modules[$module_name];
			if(is_array($module) === false && is_object($module) === false) {
				throw new Exception('Invalid module definition');
			}

			//An array module definition contains a path to a module definition class
			if(is_array($module) === true) {
				//Class name used to load the module definition
				$class_name = (isset($module['className']) === true ? $module['className'] : 'Module');

				//If developer specifies a path try to include the file
				if(isset($module['path']) === true) {
					if(class_exists($class_name) === false) {
						if(file_exists($module['path']) === true) {
							require_once($module['path']);
						} else {
							throw new Exception("Module definition path '".$path."' doesn't exist");
						}
					}
				}

				$module_object = $this->_dependencyInjector->get($class_name);

				// 'registerAutoloaders' and 'registerServices' are automatically called
				$module_object->registerAutoloaders($this->_dependencyInjector);
				$module_object->registerServices($this->_dependencyInjector);
			} else {
				//A module definition object can be a Closure instance
				if($module instanceof Closure === true) {
					//@note $status is not used later
					call_user_func($module, $this->_dependencyInjector);
				} else {
					throw new Exception('Invalid module definition');
				}
			}

			//Calling afterStartModule event
			if(is_object($this->_eventsManager) === true) {
				$this->_moduleObject = $module_object;

				if($this->_eventsManager->fire('application:afterStartModule', $this, $module_name) === false) {
					return false;
				}
			}
		}

		//Check whether use implicit views or not
		if($this->_implicitView === true) {
			$view = $this->_dependencyInjector->getShared('view');
		}

		//We get the parameters from the router and assign them to the dispatcher
		$controller_name = $router->getControllerName();
		$action_name = $router->getActionName();
		$params = $router->getParams();

		$dispatcher = $this->_dependencyInjector->getShared('dispatcher');
		$dispatcher->setModuleName($router->getModuleName());
		$dispatcher->setNamespaceName($router->getNamespaceName());
		$dispatcher->setControllerName($controller_name);
		$dispatcher->setActionName($action_name);
		$dispatcher->setParams($params);

		$exact = $router->isExactControllerName();

		//Start the view component (start output buffering)
		if(isset($view) === true) {
			$view->start();
		}

		//Calling beforeHandleRequest
		if(is_object($this->_eventsManager) === true) {
			if($this->_eventsManager->fire('application:beforeHandleRequest', $this, $dispatcher) === false) {
				return false;
			}
		}

		//The dispatcher must return an object
		$controller = $dispatcher->dispatch();

		$returned_response = false;

		//Get the latest value returned by an action
		$possible_response = $dispatcher->getReturnedValue();
		if(is_object($possible_response) === true) {
			//Check if the returned object is already a response
			$returned_response = $possible_response instanceof ResponseInterface;
		}

		//Calling afterHandleRequest
		if(is_object($this->_eventsManager) === true) {
			$this->_eventsManager->fire('application:afterHandleRequest', $this, $controller);
		}

		//If the dispatcher returns an object we try to render the view in auto-rendering mode
		if($returned_response === false) {
			if(isset($view) === true && is_object($controller) === true) {
				$render_status = true;

				//This allows to make a custom view
				if(is_object($this->_eventsManager) === true) {
					$render_status = $this->_eventsManager->fire('application:viewRender', $this, $view);
				}

				//Check if the view progress has been treated by the developer
				if($render_status !== false) {
					$controller_name = $dispatcher->getControllerName();
					$action_name = $dispatcher->getActionName();
					$params = $dispatcher->getParams();

					//Automatic render based on the latest controller executed
					$view->render($controller_name, $action_name, $params);
				}
			}
		}

		//Finish the view component (stop output buffering)
		if(isset($view) === true) {
			$view->finish();
		}

		if($returned_response === false) {
			$response = $this->_dependencyInjector->getShared('response');
			if(isset($view) === true) {
				//The content returned by the view is passed to the response service
				$response->setContent($view->getContent());
			}
		} else {
			//We don't need to create a response because there is one already
			$response = $possible_response;
		}

		//Headers are automatically send
		$response->sendHeaders();

		//Cookies are automatically send
		$response->sendCookies();

		//Return the response
		return $response;
	}
}