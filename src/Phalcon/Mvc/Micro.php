<?php
/**
 * Micro Model
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
	\Phalcon\DI\FactoryDefault,
	\Phalcon\DiInterface,
	\Phalcon\Mvc\Micro\Exception,
	\Phalcon\Mvc\Micro\LazyLoader,
	\Phalcon\Mvc\Micro\MiddlewareInterface,
	\Phalcon\Mvc\CollectionInterface,
	\ArrayAccess;

/**
 * Phalcon\Mvc\Micro
 *
 * With Phalcon you can create "Micro-Framework like" applications. By doing this, you only need to
 * write a minimal amount of code to create a PHP application. Micro applications are suitable
 * to small applications, APIs and prototypes in a practical way.
 *
 *<code>
 *
 * $app = new Phalcon\Mvc\Micro();
 *
 * $app->get('/say/welcome/{name}', function ($name) {
 *    echo "<h1>Welcome $name!</h1>";
 * });
 *
 * $app->handle();
 *
 *</code>
 * 
 * @see https://github.com/phalcon/cphalcon/blob/1.2.6/ext/mvc/micro.c
 */
class Micro extends Injectable implements EventsAwareInterface, InjectionAwareInterface, ArrayAccess
{
	/**
	 * Dependency Injector
	 * 
	 * @var \Phalcon\DiInterface|null
	 * @access protected
	*/
	protected $_dependencyInjector;

	/**
	 * Handlers
	 * 
	 * @var null|array
	 * @access protected
	*/
	protected $_handlers;

	/**
	 * Router
	 * 
	 * @var null|\Phalcon\Mvc\RouterInterface
	 * @access protected
	*/
	protected $_router;

	/**
	 * Stopped
	 * 
	 * @var null|boolean
	 * @access protected
	*/
	protected $_stopped;

	/**
	 * NotFound-Handler
	 * 
	 * @var null|callable
	 * @access protected
	*/
	protected $_notFoundHandler;

	/**
	 * Active Handler
	 * 
	 * @var null|callable
	 * @access protected
	*/
	protected $_activeHandler;

	/**
	 * Before Handlers
	 * 
	 * @var null|array
	 * @access protected
	*/
	protected $_beforeHandlers;

	/**
	 * After Handlers
	 * 
	 * @var null|array
	 * @access protected
	*/
	protected $_afterHandlers;

	/**
	 * Finish Handlers
	 * 
	 * @var null|array
	 * @access protected
	*/
	protected $_finishHandlers;

	/**
	 * Returned Value
	 * 
	 * @var mixed
	 * @access protected
	*/
	protected $_returnedValue;

	/**
	 * \Phalcon\Mvc\Micro constructor
	 *
	 * @param \Phalcon\DiInterface|null $dependencyInjector
	 * @throws Exception
	 */
	public function __construct($dependencyInjector = null)
	{
		if(is_object($dependencyInjector) === true) {
			$this->setDi($dependencyInjector);
		}
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
			throw new Exception('The dependency injector must be an object');
		}

		//We automatically set ourselves as applications ervice
		if($dependencyInjector->has('application') === false) {
			$dependencyInjector->set('application', $this);
		}

		$this->_dependencyInjector = $dependencyInjector;
	}

	/**
	 * Maps a route to a handler without any HTTP method constraint
	 *
	 * @param string $routePattern
	 * @param callable $handler
	 * @return \Phalcon\Mvc\Router\RouteInterface
	 * @throws Exception
	 */
	public function map($routePattern, $handler)
	{
		if(is_string($routePattern) === false ||
			is_callable($handler) === false) {
			throw new Exception('Invlaid parameter type.');
		}

		//We create a router even if there is no one in the DI
		$router = $this->getRouter();

		//Routes are added to the router
		$route = $router->add($routePattern);

		//Using the id produced by the router we store the handler
		$this->_handlers[$route->getRouteId()] = $handler;

		//The route is returned the developer can add more things on it
		return $route;
	}

	/**
	 * Maps a route to a handler that only matches if the HTTP method is GET
	 *
	 * @param string $routePattern
	 * @param callable $handler
	 * @return \Phalcon\Mvc\Router\RouteInterface
	 * @throws Exception
	 */
	public function get($routePattern, $handler)
	{
		if(is_string($routePattern) === false ||
			is_callable($handler) === false) {
			throw new Exception('Invalid  parameter type.');
		}

		//We create a router even if there is no one in the DI
		$router = $this->getRouter();

		//Routes are added to the router restricting to GET
		$route = $router->addGet($routePattern);

		//Using the id produced we store the handler
		$this->_handlers[$route->getRouteId()] = $handler;

		//The route is returned, the developer can add more things on it
		return $route;
	}

	/**
	 * Maps a route to a handler that only matches if the HTTP method is POST
	 *
	 * @param string $routePattern
	 * @param callable $handler
	 * @return \Phalcon\Mvc\Router\RouteInterface
	 * @throws Exception
	 */
	public function post($routePattern, $handler)
	{
		if(is_string($routePattern) === false ||
			is_callable($handler) === false) {
			throw new Exception('Invalid  parameter type.');
		}

		//We create a router even if there is no one in the DI
		$router = $this->getRouter();

		//Routes are added to the router restricting to POST
		$route = $router->addPost($routePattern);

		//Using the id produced we store the handler
		$this->_handlers[$route->getRouteId()] = $handler;

		//The route is returned, the developer can add more things on it
		return $route;
	}

	/**
	 * Maps a route to a handler that only matches if the HTTP method is PUT
	 *
	 * @param string $routePattern
	 * @param callable $handler
	 * @return \Phalcon\Mvc\Router\RouteInterface
	 * @throws Exception
	 */
	public function put($routePattern, $handler)
	{
		if(is_string($routePattern) === false ||
			is_callable($handler) === false) {
			throw new Exception('Invalid  parameter type.');
		}

		//We create a router even if there is no one in the DI
		$router = $this->getRouter();

		//Routes are added to the router restricting to PUT
		$route = $router->addPut($routePattern);

		//Using the id produced we store the handler
		$this->_handlers[$route->getRouteId()] = $handler;

		//The route is returned, the developer can add more things on it
		return $route;
	}

	/**
	 * Maps a route to a handler that only matches if the HTTP method is PATCH
	 *
	 * @param string $routePattern
	 * @param callable $handler
	 * @return \Phalcon\Mvc\Router\RouteInterface
	 * @throws Exception
	 */
	public function patch($routePattern, $handler)
	{
		if(is_string($routePattern) === false ||
			is_callable($handler) === false) {
			throw new Exception('Invalid  parameter type.');
		}

		//We create a router even if there is no one in the DI
		$router = $this->getRouter();

		//Routes are added to the router restricting to PATCH
		$route = $router->addPatch($routePattern);

		//Using the id produced we store the handler
		$this->_handlers[$route->getRouteId()] = $handler;

		//The route is returned, the developer can add more things on it
		return $route;
	}

	/**
	 * Maps a route to a handler that only matches if the HTTP method is HEAD
	 *
	 * @param string $routePattern
	 * @param callable $handler
	 * @return \Phalcon\Mvc\Router\RouteInterface
	 * @throws Exception
	 */
	public function head($routePattern, $handler)
	{
		if(is_string($routePattern) === false ||
			is_callable($handler) === false) {
			throw new Exception('Invalid  parameter type.');
		}

		//We create a router even if there is no one in the DI
		$router = $this->getRouter();

		//Routes are added to the router restricting to HEAD
		$route = $router->addHead($routePattern);

		//Using the id produced we store the handler
		$this->_handlers[$route->getRouteId()] = $handler;

		//The route is returned, the developer can add more things on it
		return $route;
	}

	/**
	 * Maps a route to a handler that only matches if the HTTP method is DELETE
	 *
	 * @param string $routePattern
	 * @param callable $handler
	 * @return \Phalcon\Mvc\Router\RouteInterface
	 * @throws Exception
	 */
	public function delete($routePattern, $handler)
	{
		if(is_string($routePattern) === false ||
			is_callable($handler) === false) {
			throw new Exception('Invalid  parameter type.');
		}

		//We create a router even if there is no one in the DI
		$router = $this->getRouter();

		//Routes are added to the router restricting to DELETE
		$route = $router->addDelete($routePattern);

		//Using the id produced we store the handler
		$this->_handlers[$route->getRouteId()] = $handler;

		//The route is returned, the developer can add more things on it
		return $route;
	}

	/**
	 * Maps a route to a handler that only matches if the HTTP method is OPTIONS
	 *
	 * @param string $routePattern
	 * @param callable $handler
	 * @return \Phalcon\Mvc\Router\RouteInterface
	 * @throws Exception
	 */
	public function options($routePattern, $handler)
	{
		if(is_string($routePattern) === false ||
			is_callable($handler) === false) {
			throw new Exception('Invalid  parameter type.');
		}

		//We create a router even if there is no one in the DI
		$router = $this->getRouter();

		//Routes are added to the router restricting to OPTIONS
		$route = $router->addOptions($routePattern);

		//Using the id produced we store the handler
		$this->_handlers[$route->getRouteId()] = $handler;

		//The route is returned, the developer can add more things on it
		return $route;
	}

	/**
	 * Mounts a collection of handlers
	 *
	 * @param \Phalcon\Mvc\CollectionInterface $collection
	 * @return \Phalcon\Mvc\Micro
	 * @throws Exception
	 */
	public function mount($collection)
	{
		if(is_object($collection) === false ||
			$collection instanceof CollectionInterface === false) {
			throw new Exception('The collection is not valid');
		}

		//Get the main handler
		$main_handler = $collection->getHandler();
		if(empty($main_handler) === true) {
			throw new Exception('The collection requires a main handler');
		}

		$handlers = $collection->getHandlers();
		if(count($handlers) === 0) {
			throw new Exception('There are no handlers to mount');
		}

		if(is_array($handlers) === true) {
			//Check if hander is lazy
			if($collection->isLazy() === true) {
				$main_handler = new LazyLoader($main_handler);
			}

			//Get the main prefix for the collection
			$prefix = $collection->getPrefix();
			foreach($handlers as $handler) {
				if(is_array($handler) === false) {
					throw new Exception('One of the registered handlers is invalid');
				}

				$methods = $handler[0];
				$pattern = $handler[1];
				$sub_handler = $handler[2];

				//Create a real handler
				if(empty($prefix) === false) {
					if($pattern !== '/') {
						$prefixed_pattern = $prefix.$pattern;
					} else {
						$prefixed_pattern = $prefix;
					}
				} else {
					$prefixed_pattern = $pattern;
				}

				//Map the route manually
				$route = $this->map($prefixed_pattern, array($main_handler, $sub_handler));
				if(isset($methods) === true) {
					$route->via($methods);
				}
			}
		}

		return $this;
	}

	/**
	 * Sets a handler that will be called when the router doesn't match any of the defined routes
	 *
	 * @param callable $handler
	 * @return \Phalcon\Mvc\Micro
	 * @throws Exception
	 */
	public function notFound($handler)
	{
		if(is_callable($handler) === false) {
			throw new Exception('Invalid parameter type.');
		}

		$this->_notFoundHandler = $handler;

		return $this;
	}

	/**
	 * Returns the internal router used by the application
	 *
	 * @return \Phalcon\Mvc\RouterInterface
	 */
	public function getRouter()
	{
		if(is_object($this->_router) === false) {
			$router = $this->getSharedService('router');

			//Clear the set routes if any
			$router->clear();

			//Automatically remove extra slashes
			$router->removeExtraSlashes(true);

			//Update the internal router
			$this->_router = $router;
		}

		return $this->_router;
	}

	/**
	 * Sets a service from the DI
	 *
	 * @param string $serviceName
	 * @param mixed $definition
	 * @param boolean|null $shared
	 * @return \Phalcon\DI\ServiceInterface
	 * @throws Exception
	 */
	public function setService($serviceName, $definition, $shared = null)
	{
		if(is_null($shared) === true) {
			$shared = false;
		} elseif(is_bool($shared) === false) {
			throw new Exception('Invalid parameter type.');
		}

		if(is_string($serviceName) === false) {
			throw new Exception('Invalid parameter type.');
		}

		if(is_object($this->_dependencyInjector) === false) {
			$this->_dependencyInjector = new FactoryDefault();
		}

		return $this->_dependencyInjector->set($serviceName, $definition, $shared);
	}

	/**
	 * Checks if a service is registered in the DI
	 *
	 * @param string $serviceName
	 * @return boolean
	 * @throws Exception
	 */
	public function hasService($serviceName)
	{
		if(is_string($serviceName) === false) {
			throw new Exception('Invalid parameter type.');
		}

		if(is_object($this->_dependencyInjector) === false) {
			$this->_dependencyInjector = new FactoryDefault();
		}

		return $this->_dependencyInjector->has($serviceName);
	}

	/**
	 * Obtains a service from the DI
	 *
	 * @param string $serviceName
	 * @return object
	 * @throws Exception
	 */
	public function getService($serviceName)
	{
		if(is_string($serviceName) === false) {
			throw new Exception('Invalid parameter type.');
		}

		if(is_object($this->_dependencyInjector) === false) {
			$this->_dependencyInjector = new FactoryDefault();
		}

		return $this->_dependencyInjector->get($serviceName);
	}

	/**
	 * Obtains a shared service from the DI
	 *
	 * @param string $serviceName
	 * @return mixed
	 * @throws Exception
	 */
	public function getSharedService($serviceName)
	{
		if(is_string($serviceName) === false) {
			throw new Exception('Invalid parameter type.');
		}

		if(is_object($this->_dependencyInjector) === false) {
			$this->_dependencyInjector = new FactoryDefault();
		}

		return $this->_dependencyInjector->getShared($serviceName);
	}

	/**
	 * Handle the whole request
	 *
	 * @param string|null $uri
	 * @return mixed
	 * @throws Exception
	 */
	public function handle($uri = null)
	{
		if(is_string($uri) === false &&
			is_null($uri) === false) {
			throw new Exception('Invalid parameter type.');
		}

		if(is_object($this->_dependencyInjector) === false) {
			throw new Exception('A dependency injection container is required to access related dispatching services');
		}

		//Calling beforeHandle routing
		if(is_object($this->_eventsManager) === true) {
			if($this->_eventsManager->fire('micro:beforeHandleRoute', $this) === false) {
				return false;
			}
		}

		//Handling route information
		$router = $this->_dependencyInjector->getShared('router');

		//Handle the URI as normal
		$router->handle($uri);

		//Check if one route was matched
		$matched_route = $router->getMatchedRoute();
		if(is_object($matched_route) === true) {
			$route_id = $matched_route->getRouteId();
			if(isset($this->_handlers[$route_id]) === false) {
				throw new Exception("Matched route doesn't have an associate handler");
			}

			//Updating active handler
			$handler = $this->_handlers[$route_id];
			$this->_activeHandler = $handler;

			//Calling beforeExecuteRoute event
			if(is_object($this->_eventsManager) === true) {
				if($this->_eventsManager->fire('micro:beforeExecuteRoute', $this) === false) {
					return false;
				} else {
					$handler = $this->_activeHandler;
				}
			}

			if(is_array($this->_beforeHandlers) === true) {
				$this->_stopped = false;

				//Call the before handlers
				foreach($this->_beforeHandlers as $before) {
					if(is_object($before) === true) {
						if($before instanceof MiddlewareInterface === true) {
							//Call the middleware
							$status = $before->call($this);

							//break the execution if the middleware was stopped
							if($this->_stopped === true) {
								break;
							}

							continue;
						}
					}

					if(is_callable($before) === false) {
						throw new Exception('The before handler is not callable');
					}

					//Call the before handler, if it return false exit
					$status = call_user_func($before);
					if($status === false) {
						return false;
					}

					//Reload the stopped status
					if($this->_stopped === true) {
						return $status;
					}
				}
			}

			//Update the returned value
			$params = $router->getParams();
			$this->_returnedValue = call_user_func_array($handler, $params);

			//Calling afterExecuteRoute event
			if(is_object($this->_eventsManager) === true) {
				$this->_eventsManager->fire('micro:afterExecuteRoute', $this);
			}

			if(is_array($this->_afterHandlers) === true) {
				$this->_stopped = false;

				//Call the after handlers
				foreach($this->_afterHandlers as $after) {
					if(is_object($after) === true &&
						$after instanceof MiddlewareInterface === true) {
						//Call the middleware
						$status = $after->call($this);

						//Reload the status
						if($this->_stopped === true) {
							break;
						}
						continue;
					}

					if(is_callable($after) === false) {
						throw new Exception("One of the 'after' handlers is not callable");
					}

					$status = call_user_func($after);
				}
			}
		} else {
			//Calling beforeNotFound event
			if(is_object($this->_eventsManager) === true) {
				if($this->_eventsManager->fire('micro:beforeNotFound', $this) === false) {
					return false;
				}
			}

			//Check if a notfound handler is defined and is callable
			if(is_callable($this->_notFoundHandler) === false) {
				throw new Exception('The Not-Found handler is not callable or is not defined');
			}

			//Call the notfound handler
			$this->_returnedValue = call_user_func($this->_notFoundHandler);

			return $this->_returnedValue;
		}

		//Calling afterHandleRoute event
		if(is_object($this->_eventsManager) === true) {
			$this->_eventsManager->fire('micro:afterHandleRoute', $this);
		}

		if(is_array($this->_finishHandlers) === true) {
			$this->_stopped = false;
			$params = null;

			foreach($this->_finishHandlers as $finish) {
				//Try to execute middleware as plugins
				if(is_object($finish) === true &&
					$finish instanceof MiddlewareInterface === true) {
					//Call the middleware
					$status = $finish->call($this);

					//Reload the status
					if($this->_stopped === true) {
						break;
					}

					continue;
				}

				if(is_callable($finish) === false) {
					throw new Exception('One of finish handlers is not callable');
				}

				if(is_null($params) === true) { //@note see #719
					$params = array($this);
				}

				//Call the 'finish' middleware
				$status = $finish($params);

				//Reload the status
				if($this->_stopped === true) {
					break;
				}
			}
		}

		//Check if the returned object is already a response
		if(is_object($this->_returnedValue) === true &&
			$this->_returnedValue instanceof ResponseInterface === true) {
			//Automatically send the responses
			$this->_returnedValue->send();
		}

		return $this->_returnedValue;
	}

	/**
	 * Stops the middleware execution avoiding than other middlewares be executed
	 */
	public function stop()
	{
		$this->_stopped = true;
	}

	/**
	 * Sets externally the handler that must be called by the matched route
	 *
	 * @param callable $activeHandler
	 * @throws Exception
	 */
	public function setActiveHandler($activeHandler)
	{
		if(is_callable($activeHandler) === false) {
			throw new Exception('Invalid parameter type.');
		}

		$this->_activeHandler = $activeHandler;
	}

	/**
	 * Return the handler that will be called for the matched route
	 *
	 * @return callable|null
	 */
	public function getActiveHandler()
	{
		return $this->_activeHandler;
	}

	/**
	 * Returns the value returned by the executed handler
	 *
	 * @return mixed
	 */
	public function getReturnedValue()
	{
		return $this->_returnedValue;
	}

	/**
	 * Check if a service is registered in the internal services container using the array syntax.
	 * Alias for \Phalcon\Mvc\Micro::hasService()
	 *
	 * @param string $serviceName
	 * @return boolean
	 */
	public function offsetExists($serviceName)
	{
		return $this->hasService($serviceName);
	}

	/**
	 * Allows to register a shared service in the internal services container using the array syntax.
	 * Alias for \Phalcon\Mvc\Micro::setService()
	 *
	 *<code>
	 *	$app['request'] = new \Phalcon\Http\Request();
	 *</code>
	 *
	 * @param string $alias
	 * @param mixed $definition
	 */
	public function offsetSet($serviceName, $definition)
	{
		return $this->setService($serviceName, $definition);
	}

	/**
	 * Allows to obtain a shared service in the internal services container using the array syntax.
	 * Alias for \Phalcon\Mvc\Micro::getService()
	 *
	 *<code>
	 *	var_dump($di['request']);
	 *</code>
	 *
	 * @param string $alias
	 * @return mixed
	 */
	public function offsetGet($serviceName)
	{
		return $this->getService($serviceName);
	}

	/**
	 * Removes a service from the internal services container using the array syntax
	 *
	 * @param string $alias
	 * @todo Not implemented
	 */
	public function offsetUnset($alias)
	{
		if(is_string($alias) === false) {
			throw new Exception('Invalid parameter type.');
		}

		return $alias;
	}

	/**
	 * Appends a before middleware to be called before execute the route
	 *
	 * @param callable $handler
	 * @return \Phalcon\Mvc\Micro
	 * @throws Exception
	 */
	public function before($handler)
	{
		if(is_callable($handler) === false) {
			throw new Exception('Invalid parameter type.');
		}

		if(is_array($this->_beforeHandlers) === false) {
			$this->_beforeHandlers = array();
		}

		$this->_beforeHandlers[] = $handler;

		return $this;
	}

	/**
	 * Appends an 'after' middleware to be called after execute the route
	 *
	 * @param callable $handler
	 * @return \Phalcon\Mvc\Micro
	 * @throws Exception
	 */
	public function after($handler)
	{
		if(is_callable($handler) === false) {
			throw new Exception('Invalid parameter type.');
		}

		if(is_array($this->_afterHandlers) === false) {
			$this->_afterHandlers = array();
		}

		$this->_afterHandlers[] = $handler;

		return $this;
	}

	/**
	 * Appends a 'finish' middleware to be called when the request is finished
	 *
	 * @param callable $handler
	 * @return \Phalcon\Mvc\Micro
	 * @throws Exception
	 */
	public function finish($handler)
	{
		if(is_callable($handler) === false) {
			throw new Exception('Invalid parameter type.');
		}

		if(is_array($this->_finishHandlers) === false) {
			$this->_finishHandlers = array();
		}

		$this->_finishHandlers[] = $handler;

		return $this;
	}

	/**
	 * Returns the internal handlers attached to the application
	 *
	 * @return array|null
	 */
	public function getHandlers()
	{
		return $this->_handlers;
	}
}