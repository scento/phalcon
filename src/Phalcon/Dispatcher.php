<?php
/**
 * Dispatcher
 *
 * @author Andres Gutierrez <andres@phalconphp.com>
 * @author Eduar Carvajal <eduar@phalconphp.com>
 * @author Wenzel Pünter <wenzel@phelix.me>
 * @version 1.2.6
 * @package Phalcon
*/
namespace Phalcon;

use \ReflectionMethod,
	\Phalcon\DispatcherInterface,
	\Phalcon\DI\InjectionAwareInterface,
	\Phalcon\Events\EventsAwareInterface,
	\Phalcon\DiInterface,
	\Phalcon\Text,
	\Phalcon\Exception,
	\Phalcon\FilterInterface,
	\Phalcon\Events\ManagerInterface;

/**
 * Phalcon\Dispatcher
 *
 * This is the base class for Phalcon\Mvc\Dispatcher and Phalcon\CLI\Dispatcher.
 * This class can't be instantiated directly, you can use it to create your own dispatchers
 * 
 * @see https://github.com/phalcon/cphalcon/blob/1.2.6/ext/dispatcher.c
 */
abstract class Dispatcher implements DispatcherInterface, InjectionAwareInterface, EventsAwareInterface
{
	/**
	 * Exception: No DI
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
	 * Exception: Handler not found
	 * 
	 * @var int
	*/
	const EXCEPTION_HANDLER_NOT_FOUND = 2;

	/**
	 * Exception: Invalid handler
	 * 
	 * @var int
	*/
	const EXCEPTION_INVALID_HANDLER = 3;

	/**
	 * Exception: Invalid params
	 * 
	 * @var int
	*/
	const EXCEPTION_INVALID_PARAMS = 4;

	/**
	 * Exception: Action not found
	 * 
	 * @var int
	*/
	const EXCEPTION_ACTION_NOT_FOUND = 5;

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
	 * Active Handler
	 * 
	 * @var null|object
	 * @access protected
	*/
	protected $_activeHandler = null;

	/**
	 * Finished
	 * 
	 * @var null|boolean
	 * @access protected
	*/
	protected $_finished = null;

	/**
	 * Forwarded
	 * 
	 * @var boolean
	 * @access protected
	*/
	protected $_forwarded = false;

	/**
	 * Module Name
	 * 
	 * @var null|string
	 * @access protected
	*/
	protected $_moduleName = null;

	/**
	 * Namespace Name
	 * 
	 * @var null|string
	 * @access protected
	*/
	protected $_namespaceName = null;

	/**
	 * Handler Name
	 * 
	 * @var null|string
	 * @access protected
	*/
	protected $_handlerName = null;

	/**
	 * Action Name
	 * 
	 * @var null|string
	 * @access protected
	*/
	protected $_actionName = null;

	/**
	 * Params
	 * 
	 * @var null|array
	 * @access protected
	*/
	protected $_params = null;

	/**
	 * Returned Value
	 * 
	 * @var mixed
	 * @access protected
	*/
	protected $_returnedValue = null;

	/**
	 * Last Handler
	 * 
	 * @var null|object
	 * @access protected
	*/
	protected $_lastHandler = null;

	/**
	 * Default Namespace
	 * 
	 * @var null|string
	 * @access protected
	*/
	protected $_defaultNamespace = null;

	/**
	 * Default Handler
	 * 
	 * @var null|object
	 * @access protected
	*/
	protected $_defaultHandler = null;

	/**
	 * Default Action
	 * 
	 * @var string
	 * @access protected
	*/
	protected $_defaultAction = '';

	/**
	 * Handler Suffix
	 * 
	 * @var string
	 * @access protected
	*/
	protected $_handlerSuffix = '';

	/**
	 * Action Suffix
	 * 
	 * @var string
	 * @access protected
	*/
	protected $_actionSuffix = 'Action';

	/**
	 * Is Exact Handler
	 * 
	 * @var boolean
	 * @access protected
	*/
	protected $_isExactHandler = false;

	/**
	 * \Phalcon\Dispatcher constructor
	 */
	public function __construct()
	{
		$this->_params = array();
	}

	/**
	 * Sets the dependency injector
	 *
	 * @param \Phalcon\DiInterface $dependencyInjector
	 */
	public function setDI($dependencyInjector)
	{
		if($dependencyInjector instanceof DiInterface === false) {
			$this->_throwDispatchException('Invalid parameter type.');
			return null;
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
	 * Sets the events manager
	 *
	 * @param \Phalcon\Events\ManagerInterface $eventsManager
	 */
	public function setEventsManager($eventsManager)
	{
		if($eventsManager instanceof ManagerInterface === false) {
			$this->_throwDispatchException('Invalid parameter type.');
			return null;
		}

		$this->_eventsManager = $eventsManager;
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
	 * Sets the default action suffix
	 *
	 * @param string $actionSuffix
	 */
	public function setActionSuffix($actionSuffix)
	{
		if(is_string($actionSuffix) === false) {
			$this->_throwDispatchException('Invalid parameter type.');
			return null;
		}

		$this->_actionSuffix = $actionSuffix;
	}

	/**
	 * Sets the module where the controller is (only informative)
	 *
	 * @param string|null $moduleName
	 */
	public function setModuleName($moduleName)
	{
		if(is_string($moduleName) === false &&
			is_null($moduleName) === false) {
			$this->_throwDispatchException('Invalid parameter type.');
			return;
		}

		$this->_moduleName = $moduleName;
	}

	/**
	 * Gets the module where the controller class is
	 *
	 * @return string|null
	 */
	public function getModuleName()
	{
		return $this->_moduleName;
	}

	/**
	 * Sets the namespace where the controller class is
	 *
	 * @param string|null $namespaceName
	 */
	public function setNamespaceName($namespaceName)
	{
		if(is_string($namespaceName) === false &&
			is_null($namespaceName) === false) {
			$this->_throwDispatchException('Invalid parameter type.');
			return;
		}

		$this->_namespaceName = $namespaceName;
	}

	/**
	 * Gets a namespace to be prepended to the current handler name
	 *
	 * @return string|null
	 */
	public function getNamespaceName()
	{
		return $this->_namespaceName;
	}

	/**
	 * Sets the default namespace
	 *
	 * @param string $namespace
	 */
	public function setDefaultNamespace($namespace)
	{
		if(is_string($namespace) === false) {
			$this->_throwDispatchException('Invalid parameter type.');
			return null;
		}

		$this->_defaultNamespace = $namespace;
	}

	/**
	 * Returns the default namespace
	 *
	 * @return string|null
	 */
	public function getDefaultNamespace()
	{
		return $this->_defaultNamespace;
	}

	/**
	 * Sets the default action name
	 *
	 * @param string $actionName
	 */
	public function setDefaultAction($actionName)
	{
		if(is_string($actionName) === false) {
			$this->_throwDispatchException('Invalid parameter type.');
			return null;
		}

		$this->_defaultAction = $actionName;
	}

	/**
	 * Sets the action name to be dispatched
	 *
	 * @param string|null $actionName
	 */
	public function setActionName($actionName)
	{
		if(is_string($actionName) === false &&
			is_null($actionName) === false) {
			$this->_throwDispatchException('Invalid parameter type.');
			return;
		}

		$this->_actionName = $actionName;
	}

	/**
	 * Gets the lastest dispatched action name
	 *
	 * @return string|null
	 */
	public function getActionName()
	{
		return $this->_actionName;
	}

	/**
	 * Sets action params to be dispatched
	 *
	 * @param array $params
	 */
	public function setParams($params)
	{
		if(is_array($params) === false) {
			$this->_throwDispatchException('Parameter must be an Array');
			return null;
		}

		$this->_params = $params;
	}

	/**
	 * Gets action params
	 *
	 * @return array|null
	 */
	public function getParams()
	{
		return $this->_params;
	}

	/**
	 * Set a param by its name or numeric index
	 *
	 * @param scalar $param
	 * @param mixed $value
	 */
	public function setParam($param, $value)
	{
		if(is_array($this->_params) === false) {
			$this->_params = array();
		}

		$this->_params[$param] = $value;
	}

	/**
	 * Gets a param by its name or numeric index
	 *
	 * @param scalar $param
	 * @param string|array|null $filters
	 * @param mixed $defaultValue
	 * @return mixed
	 */
	public function getParam($param, $filters = null, $defaultValue = null)
	{
		if(isset($this->_params[$param]) === true) {
			if(is_null($filters) === false) {

				if(is_object($this->_dependencyInjector) === false) {
					$this->_throwDispatchException('A dependency injection object is required to access the \'filter\' service', self::EXCEPTION_NO_DI);
					return null;
				}

				$filter = $this->_dependencyInjector->getShared('filter');
				if($filter instanceof FilterInterface === false) {
					$this->_throwDispatchException('Filter service is not available.');
					return null;
				}

				return $filter->sanitize($this->_params[$param], $filters);
			}

			return $this->_params[$param];
		}

		return $defaultValue;
	}

	/**
	 * Returns the current method to be/executed in the dispatcher
	 *
	 * @return string
	 */
	public function getActiveMethod()
	{
		return $this->_actionName.$this->_actionSuffix;
	}

	/**
	 * Checks if the dispatch loop is finished or has more pendent controllers/tasks to disptach
	 *
	 * @return boolean|null
	 */
	public function isFinished()
	{
		return $this->_finished;
	}

	/**
	 * Sets the latest returned value by an action manually
	 *
	 * @param mixed $value
	 */
	public function setReturnedValue($value)
	{
		$this->_returnedValue = $value;
	}

	/**
	 * Returns value returned by the lastest dispatched action
	 *
	 * @return mixed
	 */
	public function getReturnedValue()
	{
		return $this->_returnedValue;
	}

	/**
	 * Dispatches a handle action taking into account the routing parameters
	 *
	 * @return object|boolean
	 */
	public function dispatch()
	{
		if(is_object($this->_dependencyInjector) === false) {
			$this->_throwDispatchException('A dependency injection container is required to access related dispatching services', self::EXCEPTION_NO_DI);
			return false;
		}

		if(is_object($this->_eventsManager) === true) {
			if($this->_eventsManager->fire('dispatch:beforeDispatchLoop', $this) === false) {
				return false;
			}
		}

		$numberDispatches = 0;
		$this->_finished = false;

		while(true) {
			//Loop until finished is false
			if($this->_finished === true) {
				break;
			}
			++$numberDispatches;

			//Throw an exception after 256 consecutive forwards
			if($numberDispatches >= 256) {
				$this->_throwDispatchException('Dispatcher has detected a cyclic routing causing stability problems', self::EXCEPTION_CYCLIC_ROUTING);
				break;
			}

			$this->_finished = true;

			//If the current namespace is null we use the set in $this->_defaultNamespace
			if(is_null($this->_namespaceName) === true) {
				$this->_namespaceName = $this->_defaultNamespace;
			}

			//If the handler is null we use the set in $this->_defaultHandler
			if(is_null($this->_handlerName) === true) {
				$this->_handlerName = $this->_defaultHandler;
			}

			//If the action is null we use the set in $this->_defaultAction
			if(is_null($this->_actionName) === true) {
				$this->_actionName = $this->_defaultAction;
			}

			//Calling beforeDispatch
			if(is_object($this->_eventsManager) === true) {
				if($this->_eventsManager->fire('dispatch:beforeDispatch', $this) === false) {
					continue;
				}

				//Check if the user made a forward in the listener
				if($this->_finished === false) {
					continue;
				}
			}

			//We don't camelize the classes if they are in namespaces
			$p = strpos($this->_handlerName, '\\');
			if($p === false) {
				$camelizedClass = Text::camelize($this->_handlerName);
			} elseif($p === 0) {
				//@note this only handles one leading slash
				$camelizedClass = substr($this->_handlerName, strlen($this->_handlerName)+1);
			} else {
				$camelizedClass = $this->_handlerName;
			}

			//Create the complete controller class name prepending the namespace
			if(is_null($this->_namespaceName) === false) {
				if(strrpos($this->_namespaceName, '\\') === (strlen($this->_namespaceName)-1)) {
					$handlerClass = $this->_namespaceName.$camelizedClass.$this->_handlerSuffix;
				} else {
					$handlerClass = $this->_namespaceName.'\\'.$camelizedClass.$this->_handlerSuffix;
				}
			} else {
				$handlerClass = $camelizedClass.$this->_handlerSuffix;
			}

			//Handlers are retrieved as shared instances from the Service Container
			if($this->_dependencyInjector->has($handlerClass) === false) {
				//Check using autoloading
				if(class_exists($handlerClass) === false) {
					if($this->_throwDispatchException($handlerClass.' handler class cannot be loaded', self::EXCEPTION_HANDLER_NOT_FOUND) === false) {
						if($this->_finished === false) {
							continue;
						}
					}

					break;
				}
			}

			//Handlers must be only objects
			$handler = $this->_dependencyInjector->getShared($handlerClass);
			if(is_object($handler) === false) {
				if($this->_throwDispatchException('Invalid handler returned from the services container', self::EXCEPTION_INVALID_HANDLER) === false) {
					if($this->_finished === false) {
						continue;
					}
				}

				break;
			}

			//If the object was recently created in the DI we initialize it
			$wasFresh = $this->_dependencyInjector->wasFreshInstance();

			$this->_activeHandler = $handler;

			//Check if the method exists in the handler
			$actionMethod = $this->_actionName.$this->_actionSuffix;

			if(method_exists($handler, $actionMethod) === false) {
				//Call beforeNotFoundAction
				if(is_object($this->_eventsManager) === true) {
					if($this->_eventsManager->fire('dispatch:beforeNotFoundAction', $this) === false) {
						continue;
					}

					if($this->_finished === false) {
						continue;
					}
				}

				if($this->_throwDispatchException('Action \''.$this->_actionName.'\' was not found on handler \''.$this->_handlerName.'\'', self::EXCEPTION_ACTION_NOT_FOUND) === false) {
					if($this->_finished === false) {
						continue;
					}
				}

				break;
			}

			//Calling beforeExecuteRoute
			if(is_object($this->_eventsManager) === true) {
				if($this->_eventsManager->fire('dispatch:beforeExecuteRoute', $this) === false) {
					continue;
				}

				//Check if the user made a forward in the listener
				if($this->_finished === false) {
					continue;
				}
			}

			//Calling beforeExecuteRoute as callback and event
			if(method_exists($handler, 'beforeExecuteRoute') === true) {
				if($handler->beforeExecuteRoute($this) === false) {
					continue;
				}

				//Check if the user made a forward in the listener
				if($this->_finished === false) {
					continue;
				}
			}

			//Check if params is an array
			if(is_array($this->_params) === false) {
				if($this->_throwDispatchException('Action parameters must be an Array', self::EXCEPTION_INVALID_PARAMS) === false) {
					if($this->_finished === false) {
						continue;
					}
				}

				break;
			}

			//Call the 'initialize' method just once per request
			if($wasFresh === true) {
				if(method_exists($handler, 'initialize') === true) {
					$handler->initialize();
				}
			}


			//Call the method with/without exceptions if an events manager is present
			if(is_object($this->_eventsManager) === true) {
				try {
					//Call the method allowing exceptions
					$m = new ReflectionMethod($handler, $actionMethod);
					$value = $m->invokeArgs($handler, $this->_params);
				} catch(\Exception $e) {
					//Copy the exception to rethrow it later if needed

					//Try to handle the exception
					if($this->_handleException($exception) === false) {
						if($this->_finished === false) {
							continue;
						}
					} else {
						//Exception wasn't handled, rethrow it
						throw new Exception($e);
					}
				}
			
				//Update the latest value produced by the latest handler
				$this->_returnedValue = $value;
			} else {
				//Call the method handling exceptions as normal
				$this->_returnedValue = call_user_func_array(array($handler, $actionMethod), $this->_params);
			}

			$this->_lastHandler = $handler;

			//Calling afterExecuteRoute
			if(is_object($this->_eventsManager) === true) {
				if($this->_eventsManager->fire('dispatch:afterExecuteRoute', $this) === false) {
					continue;
				}

				if($this->_finished === false) {
					continue;
				}

				//Calling afetDispatch
				$this->_eventsManager->fire('dispatch:afterDispatch', $this);
			}

			//Calling afterExecuteRoute as callback and event
			if(method_exists($handler, 'afterExecuteRoute') === true) {
				if($handler->afterExecuteRoute($this, $this->_returnedValue) === false) {
					continue;
				}

				if($this->_finished === false) {
					continue;
				}
			}
		}

		//Call afterDispatchLoop
		if(is_object($this->_eventsManager) === true) {
			$this->_eventsManager->fire('dispatch:afterDispatchLoop', $this);
		}

		return $handler;
	}

	/**
	 * Forwards the execution flow to another controller/action
	 * Dispatchers are unique per module. Forwarding between modules is not allowed
	 *
	 *<code>
	 *  $this->dispatcher->forward(array('controller' => 'posts', 'action' => 'index'));
	 *</code>
	 *
	 * @param array $forward
	 */
	public function forward($forward)
	{
		if(is_array($forward) === false) {
			$this->_throwDispatchException('Forward parameter must be an Array');
			return null;
		}

		//Check if we need to forward to another namespace
		if(isset($forward['namespace']) === true &&
			is_string($forward['namespace']) === true) {
			$this->_namespaceName = $forward['namespace'];
		}

		//Check if we need to forward to another controller
		if(isset($forward['controller']) === true && 
			is_string($forward['controller']) === true) {
			$this->_handlerName = $forward['controller'];
		} else {
			if(isset($forward['task']) === true &&
				is_string($forward['task']) === true) {
				$this->_handlerName = $forward['task'];
			}
		}

		//Check if we need to forward to another action
		if(isset($forward['action']) === true && 
			is_string($forward['action']) === true) {
			$this->_actionName = $forward['action'];
		}

		//Check if we need to forward changing the current parameters

		if(isset($forward['params']) === true 
			&& is_array($forward['params']) === true) {
			//@note Changed "fetch_string" to "fetch_array", since the parameters are passed
			//as an array
			$this->_params = $params;
		}

		$this->_isExactHandler = false;
		$this->_finished = false;
		$tihs->_forwarded = true;
	}

	/**
	 * Check if the current executed action was forwarded by another one
	 *
	 * @return boolean
	 */
	public function wasForwarded()
	{
		return $this->_forwarded;
	}

	/**
	 * Possible class name that will be located to dispatch the request
	 *
	 * @return string
	 */
	public function getHandlerClass()
	{
		//If the current namespace is null we use the one set in $this->_defaultNamespace
		if(is_null($this->_namespaceName) === true) {
			$this->_namespaceName = $this->_defaultNamespace;
		}

		//If the handler is null we use the one set in $this->_defaultHandler
		if(is_null($this->_handlerName) === true) {
			$this->_handlerName = $this->_defaultHandler;
		}

		//We don't camelize the classes if they are in namespaces
		$p = strpos($this->_handlerName, '\\');
		if($p === false) {
			$camelizedClass = Text::camelize($this->_handlerName);
		} elseif($p === 0) {
			//@note this only handles one leading slash
			$camelizedClass = substr($this->_handlerName, strlen($this->_handlerName)+1);
		} else {
			$camelizedClass = $this->_handlerName;
		}

		//Create the complete controller class name prepending the namespace
		if(is_null($this->_namespaceName) === false) {
			if(strrpos($this->_namespaceName, '\\') === (strlen($this->_namespaceName)-1)) {
				return $this->_namespaceName.$camelizedClass.$this->_handlerSuffix;
			} else {
				return $this->_namespaceName.'\\'.$camelizedClass.$this->_handlerSuffix;
			}
		} else {
			return $camelizedClass.$this->_handlerSuffix;
		}
	}
}