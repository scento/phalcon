<?php
/**
 * Annotations
 *
 * @author Andres Gutierrez <andres@phalconphp.com>
 * @author Eduar Carvajal <eduar@phalconphp.com>
 * @author Wenzel Pünter <wenzel@phelix.me>
 * @version 1.2.6
 * @package Phalcon
*/
namespace Phalcon\Mvc\Router;

use \Phalcon\Text,
	\Phalcon\Mvc\Router,
	\Phalcon\Mvc\RouterInterface,
	\Phalcon\Mvc\Router\Exception,
	\Phalcon\DI\InjectionAwareInterface,
	\Phalcon\Annotations\AdapterInterface,
	\Phalcon\Annotations\Annotation;

/**
 * Phalcon\Mvc\Router\Annotations
 *
 * A router that reads routes annotations from classes/resources
 *
 *<code>
 * $di['router'] = function() {
 *
 *		//Use the annotations router
 *		$router = new \Phalcon\Mvc\Router\Annotations(false);
 *
 *		//This will do the same as above but only if the handled uri starts with /robots
 * 		$router->addResource('Robots', '/robots');
 *
 * 		return $router;
 *	};
 *</code>
 * 
 * @see https://github.com/phalcon/cphalcon/blob/1.2.6/ext/mvc/router/annotations.c
 */
class Annotations extends Router implements InjectionAwareInterface, RouterInterface
{
	/**
	 * URI source: _url
	 * 
	 * @var int
	*/
	const URI_SOURCE_GET_URL = 0;

	/**
	 * URI source: REQUEST_URI
	 * 
	 * @var int
	*/
	const URI_SOURCE_SERVER_REQUEST_URI = 1;

	/**
	 * Handlers
	 * 
	 * @var null|array
	 * @access protected
	*/
	protected $_handlers;

	/**
	 * Processed
	 * 
	 * @var boolean
	 * @access protected
	*/
	protected $_processed = false;

	/**
	 * Controller Suffix
	 * 
	 * @var string
	 * @access protected
	*/
	protected $_controllerSuffix = 'Controller';

	/**
	 * Action Suffix
	 * 
	 * @var string
	 * @access protected
	*/
	protected $_actionSuffix = 'Action';

	/**
	 * Route Prefix
	 * 
	 * @var null|string
	 * @access protected
	*/
	protected $_routePrefix;

	/**
	 * Adds a resource to the annotations handler
	 * A resource is a class that contains routing annotations
	 *
	 * @param string $handler
	 * @param string|null $prefix
	 * @return \Phalcon\Mvc\Router\Annotations
	 * @throws Exception
	 */
	public function addResource($handler, $prefix = null)
	{
		if(is_string($handler) === false) {
			throw new Exception('The handler must be a class name');
		}

		if(is_string($prefix) === false &&
		is_null($prefix) === false) {
			throw new Exception('Invalid parameter type.');
		}

		if(is_array($this->_handlers) === false) {
			$this->_handlers = array();
		}

		$this->_handlers[] = array($prefix, $handler);
		$this->_processed = false;

		return $this;
	}

	/**
	 * Adds a resource to the annotations handler
	 * A resource is a class that contains routing annotations
	 * The class is located in a module
	 *
	 * @param string $module
	 * @param string $handler
	 * @param string|null $prefix
	 * @return \Phalcon\Mvc\Router\Annotations
	 * @throws Exception
	 */
	public function addModuleResource($module, $handler, $prefix = null)
	{
		if(is_string($module) === false) {
			throw new Exception('The module is not a valid string');
		}

		if(is_string($handler) === false) {
			throw new Exception('The handler must be a class name');
		}

		if(is_string($prefix) === false &&
			is_null($prefix) === false) {
			throw new Exception('Invalid parameter type.');
		}

		if(is_array($this->_handlers) === false) {
			$this->_handlers = array();
		}

		$this->_handlers[] = array($prefix, $handler, $module);
		$this->_processed = false;

		return $this;
	}

	/**
	 * Produce the routing parameters from the rewrite information
	 *
	 * @param string|null $uri
	 * @throws Exception
	 */
	public function handle($uri = null)
	{
		if(is_null($uri) === true) {
			$uri = $this->getRewriteUri();
		} elseif(is_string($uri) === false) {
			throw new Exception('Invalid parameter type.');
		}

		$annotationsService = null;

		if($this->_processed === false) {
			if(is_array($this->_handlers) === true) {
				foreach($this->_handlers as $scope) {
					if(is_array($scope) === true) {
						//A prefix (if any) must be in position 0
						if(is_string($scope[0]) === true) {
							if(Text::startsWith($uri, $scope[0]) === false) {
								continue;
							}
						}

						if(is_object($annotationsService) === false) {
							if(is_object($this->_dependencyInjector) === false) {
								throw new Exception("A dependency injection container is required to access the 'annotations' service");
							}

							$annotationsService = $this->_dependencyInjector->getShared('annotations');
							//@note no interface validation
						}

						//The controller must be in position 1
						if(strpos($scope[1], '\\') !== false) {
							//Extract the real class name from the namespaced class
							$classWithNamespace = get_class($handler);

							//Extract the real class name from the namespaced class
							//Extract the namespace from the namespaced class
							$pos = strrpos($classWithNamespace, '\\');
							if($pos !== false) {
								$namespaceName = substr($classWithNamespace, 0, $pos);
								$controllerName = substr($classWithNamespace, $pos);
							} else {
								$controllerName = $classWithNamespace;
								$namespaceName = null;
							}

							$this->_routePrefix = null;

							//Check if the scope has a module associated
							if(isset($scope[2]) === true) {
								$moduleName = $scope[2];
							} else {
								$moduleName = null;
							}

							//Get the annotations from the class
							$handlerAnnotations = $annotationsService->get($handler.$this->_controllerSuffix);

							//Process class annotations
							$classAnnotations = $handlerAnnotations->getClassAnnotations();
							if(is_object($classAnnotations) === true) {
								//Process class annotaitons
								$annotations = $classAnnotations->getAnnotations();
								if(is_array($annotations) === true) {
									foreach($annotations as $annotation) {
										$this->processControllerAnnotation($annotation);
									}
								}
							}

							//Process method annotations
							$methodAnnotations = $handlerAnnotations->getMethodsAnnotations();
							if(is_array($methodAnnotations) === true) {
								foreach($methodAnnotations as $method => $collection) {
									if(is_object($collection) === true) {
										$annotations = $collection->getAnnotations();
										foreach($annotations as $annotation) {
											$this->processActionAnnotation($moduleName, $namespaceName, $controllerName, $method, $annotation);
										}
									}
								}
							}
						}
					}
				}
			}

			$this->_processed = true;
		}

		parent::handle($uri);
	}

	/**
	 * Checks for annotations in the controller docblock
	 *
	 * @param string $handler
	 * @param \Phalcon\Annotations\AdapterInterface
	 * @throws Exception
	 */
	public function processControllerAnnotation($handler, $annotation)
	{
		if(is_string($handler) === false) {
			throw new Exception('Invalid parameter type.');
		}

		if(is_object($annotation) === false ||
			$annotation instanceof AdapterInterface === false) {
			throw new Exception('Invalid parameter type.');
		}

		$name = $annotation->getName();

		//@RoutePrefix add a prefix for all the routes defined in the model
		if($name === 'RoutePrefix') {
			$this->_routePrefix = $annotation->getArgument(0);
		}
	}

	/**
	 * Checks for annotations in the public methods of the controller
	 *
	 * @param string $module
	 * @param string $namespace
	 * @param string $controller
	 * @param string $action
	 * @param \Phalcon\Annotations\Annotation $annotation
	 * @return null|boolean
	 * @throws Exception
	 */
	public function processActionAnnotation($module, $namespace, $controller, $action, $annotation)
	{
		if(is_string($module) === false ||
			is_string($namespace) === false ||
			is_string($controller) === false ||
			is_string($action) === false ||
			is_object($annotation) === false ||
			$annotation instanceof Annotation === false) {
			throw new Exception('Invalid parameter type.');
		}

		$name = $annotation->getName();
		$methods = null;

		//Find if the route is for adding routes
		if($name === 'Route') {
			$isRoute = true;
		} elseif($name === 'Get') {
			$isRoute = true;
			$methods = 'GET';
		} elseif($name === 'Post') {
			$isRoute = true;
			$methods = 'POST';
		} elseif($name === 'Put') {
			$isRoute = true;
			$methods = 'PUT';
		} elseif($name === 'Options') {
			$isRoute = true;
			$methods = 'OPTIONS';
		}
		//@note no DELETE or HEAD routes?!

		if($isRoute === true) {
			$actionName = strtolower(str_replace($this->_actionSuffix, '', $action));

			//Check for existing paths in the annotation
			$paths = $annotation->getNamedParameter('paths');
			if(is_array($paths) === false) {
				$paths = array();
			}

			//Update the module if any
			if(is_string($module) === true) {
				$paths['module'] = $module;
			}

			//Update the namespace if any
			if(is_string($namespace) === true) {
				$paths['namespace'] = $namespace;
			}

			$paths['controller'] = $controller;
			$paths['action'] = $actionName;
			$paths["\0exact"] = true;

			$value = $annotation->getArgument(0);

			//Create the route using the prefix
			if(is_null($value) === false) {
				if($value !== '/') {
					$uri = $this->_routePrefix.$value;
				} else {
					$uri = $this->_routePrefix;
				}
			} else {
				$uri = $this->_routePrefix.$actionName;
			}

			//Add the route to the router
			$route = $this->add($uri, $paths);
			if(is_null($methods) === true) {
				$methods = $annotation->getNamedParameter('methods');
				if(is_array($methods) === true) {
					$route->via($methods);
				} else {
					if(is_string($methods) === true) {
						$route->via($methods);
					}
				}
			} else {
				$route->via($methods);
			}

			$converts = $annotation->getNamedParameter('converts');
			if(is_array($converts) === true) {
				foreach($converts as $param => $convert) {
					$route->convert($param, $conver);
				}
			}

			$converts = $annotation->getNamedParameter('conversors');
			if(is_array($converts) === true) {
				foreach($converts as $conversorParam => $covert) {
					$route->convert($conversorParam, $convert);
				}
			}

			$routeName = $annotation->getNamedParameter('name');
			if(is_string($routeName) === true) {
				$route->setName($routeName);
			}

			return true;
		}
	}

	/**
	 * Changes the controller class suffix
	 *
	 * @param string $controllerSuffix
	 * @throws Exception
	 */
	public function setControllerSuffix($controllerSuffix)
	{
		if(is_string($controllerSuffix) === false) {
			throw new Exception('Invalid parameter type.');
		}

		$this->_controllerSuffix = $controllerSuffix;
	}

	/**
	 * Changes the action method suffix
	 *
	 * @param string $actionSuffix
	 * @throws Exception
	 */
	public function setActionSuffix($actionSuffix)
	{
		if(is_string($actionSuffix) === false) {
			throw new Exception('Invalid parameter type.');
		}

		$this->_actionSuffix = $actionSuffix;
	}

	/**
	 * Return the registered resources
	 *
	 * @return array|null
	 */
	public function getResources()
	{
		return $this->_handlers;
	}
}