<?php
/**
 * Manager
 *
 * @author Andres Gutierrez <andres@phalconphp.com>
 * @author Eduar Carvajal <eduar@phalconphp.com>
 * @author Wenzel Pünter <wenzel@phelix.me>
 * @version 1.2.6
 * @package Phalcon
*/
namespace Phalcon\Mvc\Collection;

use \Phalcon\DI\InjectionAwareInterface,
	\Phalcon\Events\EventsAwareInterface,
	\Phalcon\Mvc\Collection\Exception,
	\Phalcon\DiInterface,
	\Phalcon\Events\ManagerInterface,
	\Phalcon\Mvc\CollectionInterface;

/**
 * Phalcon\Mvc\Collection\Manager
 *
 * This components controls the initialization of models, keeping record of relations
 * between the different models of the application.
 *
 * A CollectionManager is injected to a model via a Dependency Injector Container such as Phalcon\DI.
 *
 * <code>
 * $di = new Phalcon\DI();
 *
 * $di->set('collectionManager', function(){
 *      return new Phalcon\Mvc\Collection\Manager();
 * });
 *
 * $robot = new Robots($di);
 * </code>
 * 
 * @see https://github.com/phalcon/cphalcon/blob/1.2.6/ext/mvc/collection/manager.c
 */
class Manager implements InjectionAwareInterface, EventsAwareInterface
{
	/**
	 * Dependency Injector
	 * 
	 * @var \Phalcon\DiInterface|null
	 * @access protected
	*/
	protected $_dependencyInjector;

	/**
	 * Initialized
	 * 
	 * @var null|array
	 * @access protected
	*/
	protected $_initialized;

	/**
	 * Last Initialized
	 * 
	 * @var null|\Phalcon\Mvc\CollectionInterface
	 * @access protected
	*/
	protected $_lastInitialized;

	/**
	 * Events Manager
	 * 
	 * @var \Phalcon\Events\ManagerInterface|null
	 * @access protected
	*/
	protected $_eventsManager;

	/**
	 * Custom Events Manager
	 * 
	 * @var array|null
	 * @access protected
	*/
	protected $_customEventsManager;

	/**
	 * Connection Services
	 * 
	 * @var null|array
	 * @access protected
	*/
	protected $_connectionServices;

	/**
	 * Implicit Object Ids
	 * 
	 * @var null|array
	 * @access protected
	*/
	protected $_implicitObjectsIds;

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
			throw new Exception('The dependency injector is invalid');
		}

		$this->_dependencyInjector = $dependencyInjector;
	}

	/**
	 * Returns the DependencyInjector container
	 *
	 * @return \Phalcon\DiInterface|null
	 */
	public function getDI()
	{
		return $this->_dependencyInjector;
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
	 * Sets a custom events manager for a specific model
	 *
	 * @param \Phalcon\Mvc\CollectionInterface $model
	 * @param \Phalcon\Events\ManagerInterface $eventsManager
	 * @throws Exception
	 */
	public function setCustomEventsManager($model, $eventsManager)
	{
		if(is_object($model) === false ||
			$model instanceof CollectionInterface === false ||
			is_object($eventsManager) === false ||
			$eventsManager instanceof ManagerInterface === false) {
			throw new Exception('Invalid parameter type.');
		}

		if(is_array($this->_customEventsManager) === false) {
			$this->_customEventsManager = array();
		}

		$this->_customEventsManager[get_class($model)] = $eventsManager;
	}

	/**
	 * Returns a custom events manager related to a model
	 *
	 * @param \Phalcon\Mvc\CollectionInterface $model
	 * @return \Phalcon\Events\ManagerInterface|null
	 * @throws Exception
	 */
	public function getCustomEventsManager($model)
	{
		if(is_object($model) === false ||
			$model instanceof CollectionInterface === false) {
			throw new Exception('Invalid parameter type.');
		}

		
		if(is_array($this->_customEventsManager) === true) {
			$c = get_class($model);
			if(isset($this->_customEventsManager[$c]) === true) {
				return $this->_customEventsManager[$c];
			}
		}
	}

	/**
	 * Initializes a model in the models manager
	 *
	 * @param \Phalcon\Mvc\CollectionInterface $model
	 * @throws Exception
	 */
	public function initialize($model)
	{
		if(is_object($model) === false ||
			$model instanceof CollectionInterface === false) {
			throw new Exception('Invalid parameter type.');
		}

		if(is_array($this->_initialized) === false) {
			$this->_initialized = array();
		}

		$class_name = get_class($model);

		//Models are just initialized once per request
		if(isset($this->_initialized[$class_name]) === false) {
			//Call the 'initialize' method if it's implemented
			if(method_exists($model, 'initialize') === true) {
				$model->initialize();
			}

			//If an EventsManager is available we pass to it every initialized model
			if(is_object($this->_eventsManager) === true) {
				$this->_eventsManager->fire('collectionManager:afterInitialize', $this);
			}

			$this->_initialized[$class_name] = $model;
			$this->_lastInitialized = $model;
		}
	}

	/**
	 * Check whether a model is already initialized
	 *
	 * @param string $modelName
	 * @return bool
	 * @throws Exception
	 */
	public function isInitialized($modelName)
	{
		if(is_string($modelName) === false) {
			throw new Exception('Invalid parameter type.');
		}

		return isset($this->_initialized[strtolower($modelName)]);
	}

	/**
	 * Get the latest initialized model
	 *
	 * @return \Phalcon\Mvc\CollectionInterface|null
	 */
	public function getLastInitialized()
	{
		return $this->_lastInitialized;
	}

	/**
	 * Sets a connection service for a specific model
	 *
	 * @param \Phalcon\Mvc\CollectionInterface $model
	 * @param string $connectionService
	 * @throws Exception
	 */
	public function setConnectionService($model, $connectionService)
	{
		if(is_object($model) === false ||
			$model instanceof CollectionInterface === false) {
			throw new Exception('A valid collection instance is required');
		}

		if(is_string($connectionService) === false) {
			throw new Exception('Invalid parameter type.');
		}

		if(is_array($this->_connectionServices) === false) {
			$this->_connectionServices = array();
		}

		$this->_connectionServices[get_class($model)] = $connectionService;
	}

	/**
	 * Sets if a model must use implicit objects ids
	 *
	 * @param \Phalcon\Mvc\CollectionInterface $model
	 * @param boolean $useImplicitObjectIds
	 * @throws Exception
	 */
	public function useImplicitObjectIds($model, $useImplicitObjectIds)
	{
		if(is_object($model) === false ||
			$model instanceof CollectionInterface === false) {
			throw new Exception('A valid collection instance is required');
		}

		if(is_bool($useImplicitObjectIds) === false) {
			throw new Exception('Invalid parameter type.');
		}

		$this->_implicitObjectsIds[get_class($model)] = $useImplicitObjectIds;
	}

	/**
	 * Checks if a model is using implicit object ids
	 *
	 * @param \Phalcon\Mvc\CollectionInterface $model
	 * @return boolean
	 * @throws Exception
	 */
	public function isUsingImplicitObjectIds($model)
	{
		if(is_object($model) === false ||
			$model instanceof CollectionInterface === false) {
			throw new Exception('Invalid parameter type.');
		}

		if(is_array($this->_implicitObjectsIds) === false) {
			$this->_implicitObjectsIds = array();
		}

		//All collections use by default implicit object ids
		return (isset($this->_implicitObjectsIds[$entity_name]) === true ?
			$this->_implicitObjectsIds[$entity_name] : true);
	}

	/**
	 * Returns the connection related to a model
	 *
	 * @param \Phalcon\Mvc\CollectionInterface $model
	 * @return \Phalcon\Db\AdapterInterface
	 * @throws Exception
	 */
	public function getConnection($model)
	{
		if(is_object($model) === false ||
			$model instanceof CollectionInterface === false) {
			throw new Exception('A valid collection instance is required');
		}

		$service = 'mongo';

		if(is_array($this->_connectionServices) === true) {
			$entity_name = get_class($model);

			//Check if the model has a custom connection service
			if(isset($this->_connectionServices[$entity_name]) === true) {
				$service = $this->_connectionServices[$entity_name];
			}
		}

		if(is_object($this->_dependencyInjector) === false) {
			throw new Exception('A dependency injector container is required to obtain the services related to the ORM');
		}

		//Request the connection service from the DI
		$connection = $this->_dependencyInjector->getShared($service);
		if(is_object($connection) === false) {
			throw new Exception('Invalid injected connection service');
		}

		return $connection;
	}

	/**
	 * Receives events generated in the models and dispatches them to a events-manager if available
	 * Notify the behaviors that are listening in the model
	 *
	 * @param string $eventName
	 * @param \Phalcon\Mvc\CollectionInterface $model
	 * @return mixed
	 * @throws Exception
	 */
	public function notifyEvent($eventName, $model)
	{
		if(is_string($eventName) === false ||
			is_object($model) === false ||
			$model instanceof CollectionInterface === false) {
			throw new Exception('Invalid parameter type.');
		}

		//Dispatch events to the global events manager
		if(is_object($this->_eventsManager) === true) {
			$status = $this->_eventsManager->fire('collection:'.$eventName, $model);
			if($status === false) {
				return false;
			}
		}

		//A model can have a specific events manager
		if(is_array($this->_customEventsManager) === true) {
			$entity_name = get_class($model);
			if(isset($this->_customEventsManager[$entity_name]) === true) {
				$status = $this->_customEventsManager[$entity_name]->fire('collection:'.$eventName, $model);
			}
		}

		return $status;
	}
}