<?php
/**
 * Injectable
 *
 * @author Andres Gutierrez <andres@phalconphp.com>
 * @author Eduar Carvajal <eduar@phalconphp.com>
 * @author Wenzel Pünter <wenzel@phelix.me>
 * @version 1.2.6
 * @package Phalcon
*/
namespace Phalcon\DI;

use \Phalcon\DI\InjectionAwareInterface,
	\Phalcon\Events\EventsAwareInterface,
	\Phalcon\DiInterface,
	\Phalcon\DI\Exception,
	\Phalcon\DI,
	\Phalcon\Events\ManagerInterface;

/**
 * Phalcon\DI\Injectable
 *
 * This class allows to access services in the services container by just only accessing a public property
 * with the same name of a registered service
 * 
 * @see https://github.com/phalcon/cphalcon/blob/1.2.6/ext/di/injectable.c
 */
abstract class Injectable implements InjectionAwareInterface, EventsAwareInterface
{
	/**
	 * Dependency Injector
	 * 
	 * @var null|Phalcon\DiInterface
	 * @access protected
	*/
	protected $_dependencyInjector;

	/**
	 * Events Manager
	 * 
	 * @var null|Phalcon\Events\ManagerInterface
	 * @access protected
	*/
	protected $_eventsManager;

	/**
	 * View
	 * 
	 * @var \Phalcon\Mvc\ViewInterface|null
	 * @access public
	*/
	public $view;

	/**
	 * Router
	 * 
	 * @var \Phalcon\Mvc\RouterInterface|null
	 * @access public
 	 */
	public $router;

	/**
	 * Dispatcher
	 * 
	 * @var \Phalcon\Mvc\DispatcherInterface|null
	 * @access public
 	 */
	public $dispatcher;

	/**
	 * URL
	 * 
	 * @var \Phalcon\Mvc\UrlInterface|null
	 * @access public
 	 */
	public $url;

	/**
	 * DI
	 * 
	 * @var \Phalcon\DiInterface|null
	 * @access public
 	 */
	public $di;

	/**
	 * Request
	 * 
	 * @var \Phalcon\HTTP\RequestInterface|null
	 * @access public
 	 */
	public $request;

	/**
	 * Response
	 * 
	 * @var \Phalcon\HTTP\ResponseInterface|null
	 * @access public
 	 */
	public $response;

	/**
	 * Flash
	 * 
	 * @var \Phalcon\Flash\Direct|null
	 * @access public
 	 */
	public $flash;

	/**
	 * Flash Session
	 * 
	 * @var \Phalcon\Flash\Session|null
	 * @access public
 	 */
	public $flashSession;

	/**
	 * Session
	 * 
	 * @var \Phalcon\Session\AdapterInterface|null
	 * @access public
 	 */
	public $session;

	/**
	 * Persistent
	 * 
	 * @var \Phalcon\Session\Bag|null
	 * @access public
 	 */
	public $persistent;

	/**
	 * Models Manager
	 * 
	 * @var \Phalcon\Mvc\Model\ManagerInterface|null
	 * @access public
 	 */
	public $modelsManager;

	/**
	 * Models Metadata
	 * 
	 * @var \Phalcon\Mvc\Model\MetadataInterface|null
	 * @access public
 	 */
	public $modelsMetadata;

	/**
	 * Transaction Manager
	 * 
	 * @var \Phalcon\Mvc\Model\Transaction\Manager|null
	 * @access public
 	 */
	public $transactionManager;

	/**
	 * Filter
	 * 
	 * @var \Phalcon\FilterInterface|null
	 * @access public
 	 */
	public $filter;

	/**
	 * Security
	 * 
	 * @var \Phalcon\Security|null
	 * @access public
 	 */
	public $security;

	/**
	 * Annotations
	 * 
	 * @var \Phalcon\Annotations\Adapter\Memory|null
	 * @access public
 	 */
	public $annotations;

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
			throw new Exception('Dependency Injector is invalid');
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
		if(is_object($this->_dependencyInjector) === true) {
			return $this->_dependencyInjector;
		} else {
			return DI::getDefault();
		}
	}

	/**
	 * Sets the event manager
	 *
	 * @param \Phalcon\Events\ManagerInterface $eventsManager
	 * @throws Exception
	 */
	public function setEventsManager($eventsManager)
	{
		if(is_object($eventsManager) === false ||
			$eventsManager instanceof ManagerInterface === false) {
			throw new Exception('Invalid parameter type.');
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
	 * Magic method __get
	 *
	 * @param string $propertyName
	 * @return mixed
	 */
	public function __get($propertyName)
	{
		if(is_string($propertyName) === false) {
			throw new Exception('Invalid parameter type.');
		}

		$dependency_injector = $this->_dependencyInjector;
		if(is_object($dependency_injector) === false) {
			$dependency_injector = DI::getDefault();

			if(is_object($dependency_injector) === false) {
				throw new Exception('A dependency injector object is required to access the application services');
			}
		}

		//Fallback to the PHP userland if the cache is not available
		if($dependency_injector->has($propertyName) === true) {
			$service = $dependency_injector->getShared($propertyName);
			$this->$propertyName = $service;
			return $service;
		}

		//Dependency Injector
		if($propertyName === 'di') {
			$this->di = $dependency_injector;
			return $dependency_injector;
		}

		//Accessing the persistent property will create a session bag in any class
		if($propertyName === 'persistent') {
			$persistent = $dependency_injector->get('sessionBag', array(get_class($this)));
			$this->persistent = $persistent;
			return $persistent;
		}

		//A notice is shown if the property is not defined and isn't a valid service
		trigger_error('Access to undefined property '.$propertyName, \E_USER_WARNING);
	}
}