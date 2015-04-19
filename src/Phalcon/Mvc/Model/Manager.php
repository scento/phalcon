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
namespace Phalcon\Mvc\Model;

use \Phalcon\Mvc\Model\ManagerInterface;
use \Phalcon\Mvc\Model\BehaviorInterface;
use \Phalcon\Mvc\Model\Relation;
use \Phalcon\Mvc\Model\Exception;
use \Phalcon\Mvc\Model\Query;
use \Phalcon\Mvc\Model\Query\Builder;
use \Phalcon\Mvc\ModelInterface;
use \Phalcon\DI\InjectionAwareInterface;
use \Phalcon\Events\EventsAwareInterface;
use \Phalcon\Events\ManagerInterface as EventsManagerInterface;
use \Phalcon\DiInterface;
use \Phalcon\Text;

/**
 * Phalcon\Mvc\Model\Manager
 *
 * This components controls the initialization of models, keeping record of relations
 * between the different models of the application.
 *
 * A ModelsManager is injected to a model via a Dependency Injector/Services Container such as Phalcon\DI.
 *
 * <code>
 * $di = new Phalcon\DI();
 *
 * $di->set('modelsManager', function() {
 *      return new Phalcon\Mvc\Model\Manager();
 * });
 *
 * $robot = new Robots($di);
 * </code>
 *
 * @see https://github.com/phalcon/cphalcon/blob/1.2.6/ext/mvc/model/manager.c
 */
class Manager implements ManagerInterface, InjectionAwareInterface, EventsAwareInterface
{
    /**
     * Dependency Injector
     *
     * @var null|\Phalcon\DiInterface
     * @access protected
    */
    protected $_dependencyInjector;

    /**
     * Events Manager
     *
     * @var null|\Phalcon\Events\ManagerInterface
     * @access protected
    */
    protected $_eventsManager;

    /**
     * Custom Events Manager
     *
     * @var null|array
     * @access protected
    */
    protected $_customEventsManager;

    /**
     * Read Connection Services
     *
     * @var null|array
     * @access protected
    */
    protected $_readConnectionServices;

    /**
     * Write Connection Services
     *
     * @var null|array
     * @access protected
    */
    protected $_writeConnectionServices;

    /**
     * Aliases
     *
     * @var null|array
     * @access protected
    */
    protected $_aliases;

    /**
     * Has Many
     *
     * @var null|array
     * @access protected
    */
    protected $_hasMany;

    /**
     * Has Many Single
     *
     * @var null|array
     * @access protected
    */
    protected $_hasManySingle;

    /**
     * Has One
     *
     * @var null|array
     * @access protected
    */
    protected $_hasOne;

    /**
     * Has One Single
     *
     * @var null|array
     * @access protected
    */
    protected $_hasOneSingle;

    /**
     * Belongs To
     *
     * @var null|array
     * @access protected
    */
    protected $_belongsTo;

    /**
     * Belongs To Single
     *
     * @var null|array
     * @access protected
    */
    protected $_belongsToSingle;

    /**
     * Has Many-To-Many
     *
     * @var null|array
     * @access protected
    */
    protected $_hasManyToMany;

    /**
     * Has Many-To-Many Single
     *
     * @var null|array
     * @access protected
    */
    protected $_hasManyToManySingle;

    /**
     * Initialized
     *
     * @var null|array
     * @access protected
    */
    protected $_initialized;

    /**
     * Sources
     *
     * @var null|array
     * @access protected
    */
    protected $_sources;

    /**
     * Schemas
     *
     * @var null|array
     * @access protected
    */
    protected $_schemas;

    /**
     * Behaviors
     *
     * @var null|array
     * @access protected
    */
    protected $_behaviors;

    /**
     * Last Initialized
     *
     * @var null|\Phalcon\Mvc\ModelInterface
     * @access protected
    */
    protected $_lastInitialized;

    /**
     * Last Query
     *
     * @var null|\Phalcon\Mvc\Model\QueryInterface
     * @access protected
    */
    protected $_lastQuery;

    /**
     * Reusable
     *
     * @var null|array
     * @access protected
    */
    protected $_reusable;

    /**
     * Keep Snapshots
     *
     * @var null|array
     * @access protected
    */
    protected $_keepSnapshots;

    /**
     * Dynamic Update
     *
     * @var null|array
     * @access protected
    */
    protected $_dynamicUpdate;

    /**
     * Namespace Aliases
     *
     * @var null|array
     * @access protected
    */
    protected $_namespaceAliases;

    /**
     * Sets the DependencyInjector container
     *
     * @param \Phalcon\DiInterface $dependencyInjector
     * @throws Exception
     */
    public function setDI($dependencyInjector)
    {
        if (is_object($dependencyInjector) === false ||
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
     * Sets a global events manager
     *
     * @param \Phalcon\Events\ManagerInterface $eventsManager
     * @throws Exception
     */
    public function setEventsManager($eventsManager)
    {
        if (is_object($eventsManager) === false ||
            $eventsManager instanceof EventsManagerInterface === false) {
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
     * @param \Phalcon\Mvc\ModelInterface $model
     * @param \Phalcon\Events\ManagerInterface $eventsManager
     * @throws Exception
     */
    public function setCustomEventsManager($model, $eventsManager)
    {
        if (is_object($model) === false ||
            $model instanceof ModelInterface === false ||
            is_object($eventsManager) === false ||
            $eventsManager instanceof EventsManagerInterface === false) {
            throw new Exception('Invalid parameter type.');
        }

        if (is_array($this->_customEventsManager) === false) {
            $this->_customEventsManager = array();
        }

        $this->_customEventsManager[strtolower(get_class($model))] = $eventsManager;
    }

    /**
     * Returns a custom events manager related to a model
     *
     * @param \Phalcon\Mvc\ModelInterface $model
     * @return \Phalcon\Events\ManagerInterface|null
     * @throws Exception
     */
    public function getCustomEventsManager($model)
    {
        if (is_object($model) === false ||
            $model instanceof ModelInterface === false) {
            throw new Exception('Invalid parameter type.');
        }

        if (is_array($this->_customEventsManager) === true) {
            $className = strtolower(get_class($model));
            if (isset($this->_customEventsManager[$className]) === true) {
                return $this->_customEventsManager[$className];
            }
        }

        return null;
    }

    /**
     * Initializes a model in the model manager
     *
     * @param \Phalcon\Mvc\ModelInterface $model
     * @return boolean
     * @throws Exception
     */
    public function initialize($model)
    {
        if (is_object($model) === false ||
            $model instanceof ModelInterface === false) {
            throw new Exception('Invalid parameter type.');
        }

        $className = strtolower(get_class($model));

        //Models are just initialized once per request
        if (isset($this->_initialized[$className]) === true) {
            return false;
        }

        //Update the model as initialized, this avoids cyclic initializations
        $this->_initialized[$className] = $model;

        //Call the 'initialize' method if it's implemented
        if (method_exists($model, 'initialize') === true) {
            $model->initialize();
        }

        //Update the last initialized model, so it can be used in modelsManager::afterInitialize
        $this->_lastInitialized = $model;
        if (is_object($this->_eventsManager) === true) {
            $this->_eventsManager->fire('modelsManager:afterInitialize', $this, $model);
        }

        return true;
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
        if (is_string($modelName) === false) {
            throw new Exception('Invalid parameter type.');
        }

        return isset($this->_initialized[strtolower($modelName)]);
    }

    /**
     * Get last initialized model
     *
     * @return \Phalcon\Mvc\ModelInterface|null
     */
    public function getLastInitialized()
    {
        return $this->_lastInitialized;
    }

    /**
     * Loads a model throwing an exception if it doesn't exist
     *
     * @param string $modelName
     * @param boolean|null $newInstance
     * @return \Phalcon\Mvc\ModelInterface
     * @throws Exception
     */
    public function load($modelName, $newInstance = null)
    {
        if (is_string($modelName) === false) {
            throw new Exception('Invalid parameter type.');
        }

        if (is_null($newInstance) === true) {
            $newInstance = false;
        } elseif (is_bool($newInstance) === false) {
            throw new Exception('Invalid parameter type.');
        }

        $lowercased = strtolower($modelName);
        
        //Check if a model with the same name is already loaded
        if (isset($this->_initialized[$lowercased]) === true) {
            $model = $this->_initialized[$lowercased];
            if ($newInstance === true) {
                return new $model($this->_dependencyInjector, $this);
            }

            return $model;
        }

        //Load it using an autoloader
        if (class_exists($modelName) === true) {
            return new $modelName($this->_dependencyInjector, $this);
        }

        //The model doesn't exist throw an exception
        throw new Exception("Model '".$modelName."' could not be loaded");
    }

    /**
     * Sets the mapped source for a model
     *
     * @param \Phalcon\Mvc\ModelInterface $model
     * @param string $source
     * @return string
     * @throws Exception
     */
    public function setModelSource($model, $source)
    {
        if (is_object($model) === false ||
            $model instanceof ModelInterface === false) {
            throw new Exception('Model is not an object');
        }

        if (is_string($source) === false) {
            throw new Exception('Source must be a string');
        }

        if (is_array($this->_sources) === false) {
            $this->_sources = array();
        }

        $this->_sources[strtolower(get_class($model))] = $source;
    }

    /**
     * Returns the mapped source for a model
     *
     * @param \Phalcon\Mvc\ModelInterface $model
     * @return string
     * @throws Exception
     */
    public function getModelSource($model)
    {
        if (is_object($model) === false ||
            $model instanceof ModelInterface === false) {
            throw new Exception('Model is not an object');
        }

        $entity = strtolower(get_class($model));

        if (is_array($this->_sources) === true) {
            if (isset($this->_sources[$entity]) === true) {
                return $this->_sources[$entity];
            }
        } else {
            $this->_sources = array();
        }

        $source = Text::uncamelize($entity);
        $this->_sources[$entity] = $source;
        return $source;
    }

    /**
     * Sets the mapped schema for a model
     *
     * @param \Phalcon\Mvc\ModelInterface $model
     * @param string $schema
     * @return string
     * @throws Exception
     */
    public function setModelSchema($model, $schema)
    {
        if (is_object($model) === false ||
            $model instanceof ModelInterface === false) {
            throw new Exception('Model is not an object');
        }

        if (is_string($schema) === false) {
            throw new Exception('Schema must be a string');
        }

        if (is_array($this->_schemas) === false) {
            $this->_schemas = array();
        }

        $this->_schemas[strtolower(get_class($model))] = $schema;
    }

    /**
     * Returns the mapped schema for a model
     *
     * @param \Phalcon\Mvc\ModelInterface $model
     * @return string|null
     * @throws Exception
     */
    public function getModelSchema($model)
    {
        if (is_object($model) === false ||
            $model instanceof ModelInterface === false) {
            throw new Exception('Model is not an object');
        }

        $entityName = strtolower(get_class($model));

        if (is_array($this->_schemas) === true) {
            if (isset($this->_schemas[$entityName]) === true) {
                return $this->_schemas[$entityName];
            }
        }

        return null;
    }

    /**
     * Sets both write and read connection service for a model
     *
     * @param \Phalcon\Mvc\ModelInterface $model
     * @param string $connectionService
     * @throws Exception
     */
    public function setConnectionService($model, $connectionService)
    {
        if (is_object($model) === false ||
            $model instanceof ModelInterface === false) {
            throw new Exception('Invalid parameter type.');
        }

        if (is_string($connectionService) === false) {
            throw new Exception('The connection service must be a string');
        }

        if (is_array($this->_readConnectionServices) === false) {
            $this->_readConnectionServices = array();
        }

        if (is_array($this->_writeConnectionServices) === false) {
            $this->_writeConnectionServices = array();
        }

        $entityName = strtolower(get_class($model));

        $this->_readConnectionServices[$entityName] = $connectionService;
        $this->_writeConnectionServices[$entityName] = $connectionService;
    }

    /**
     * Sets write connection service for a model
     *
     * @param \Phalcon\Mvc\ModelInterface $model
     * @param string $connectionService
     * @throws Exception
     */
    public function setWriteConnectionService($model, $connectionService)
    {
        if (is_object($model) === false ||
            $model instanceof ModelInterface === false) {
            throw new Exception('Invalid parameter type.');
        }

        if (is_string($connectionService) === false) {
            throw new Exception('The connection service must be a string');
        }

        if (is_array($this->_writeConnectionServices) === false) {
            $this->_writeConnectionServices = array();
        }

        $this->_writeConnectionServices[strtolower(get_class($model))] = $connectionService;
    }

    /**
     * Sets read connection service for a model
     *
     * @param \Phalcon\Mvc\ModelInterface $model
     * @param string $connectionService
     * @throws Exception
     */
    public function setReadConnectionService($model, $connectionService)
    {
        if (is_object($model) === false ||
            $model instanceof ModelInterface === false) {
            throw new Exception('Invalid parameter type.');
        }

        if (is_string($connectionService) === false) {
            throw new Exception('The connection service must be a string');
        }

        if (is_array($this->_readConnectionServices) === false) {
            $this->_readConnectionServices = array();
        }

        $this->_readConnectionServices[strtolower(get_class($model))] = $connectionService;
    }

    /**
     * Returns the connection to write data related to a model
     *
     * @param \Phalcon\Mvc\ModelInterface $model
     * @return \Phalcon\Db\AdapterInterface
     * @throws Exception
     */
    public function getWriteConnection($model)
    {
        if (is_object($model) === false ||
            $model instanceof ModelInterface === false) {
            throw new Exception('Invalid parameter type.');
        }

        $service = 'db';

        if (is_array($this->_writeConnectionServices) === true) {
            $entityName = strtolower(get_class($model));

            //Check if the model has a custom connection service
            if (isset($this->_writeConnectionServices[$entityName]) === true) {
                $service = $this->_writeConnectionServices[$entityName];
            }
        }

        if (is_object($this->_dependencyInjector) === false) {
            throw new Exception('A dependency injector container is required to obtain the services related to the ORM');
        }

        //Request the connection service from the DI
        $connection = $this->_dependencyInjector->getShared($service);
        if (is_object($connection) === false) {
            throw new Exception('Invalid injected connection service');
        }

        return $connection;
    }

    /**
     * Returns the connection to read data related to a model
     *
     * @param \Phalcon\Mvc\ModelInterface $model
     * @return \Phalcon\Db\AdapterInterface
     * @throws Exception
     */
    public function getReadConnection($model)
    {
        if (is_object($model) === false ||
            $model instanceof ModelInterface === false) {
            throw new Exception('Invalid parameter type.');
        }

        $service = 'db';

        if (is_array($this->_readConnectionServices) === true) {
            $entityName = strtolower(get_class($model));

            //Check if the model has a custom connection service
            if (isset($this->_readConnectionServices[$entityName]) === true) {
                $service = $this->_readConnectionServices[$entityName];
            }
        }

        if (is_object($this->_dependencyInjector) === false) {
            throw new Exception('A dependency injector container is required to obtain the services related to the ORM');
        }

        //Request the connection service from the DI
        $connection = $this->_dependencyInjector->getShared($service);
        if (is_object($connection) === false) {
            throw new Exception('Invalid injected connection service');
        }

        return $connection;
    }

    /**
     * Returns the connection service name used to read data related to a model
     *
     * @param \Phalcon\Mvc\ModelInterface $model
     * @param string
     * @throws Exception
     */
    public function getReadConnectionService($model)
    {
        if (is_object($model) === false ||
            $model instanceof ModelInterface === false) {
            throw new Exception('Invalid parameter type.');
        }

        if (is_array($this->_readConnectionServices) === true) {
            $entityName = strtolower(get_class($model));

            //Check if there is a custom service connection
            if (isset($this->_readConnectionServices[$entityName]) === true) {
                return $this->_readConnectionServices[$entityName];
            }
        }

        return 'db';
    }

    /**
     * Returns the connection service name used to write data related to a model
     *
     * @param \Phalcon\Mvc\ModelInterface $model
     * @param string
     * @throws Exception
     */
    public function getWriteConnectionService($model)
    {
        if (is_object($model) === false ||
            $model instanceof ModelInterface === false) {
            throw new Exception('Invalid parameter type.');
        }

        if (is_array($this->_writeConnectionServices) === true) {
            $entityName = strtolower(get_class($model));

            //Check if there is a custom service connection
            if (isset($this->_writeConnectionServices[$entityName]) === true) {
                return $this->_writeConnectionServices[$entityName];
            }
        }

        return 'db';
    }

    /**
     * Receives events generated in the models and dispatches them to a events-manager if available
     * Notify the behaviors that are listening in the model
     *
     * @param string $eventName
     * @param \Phalcon\Mvc\ModelInterface $model
     * @return null|false
     * @throws Exception
     */
    public function notifyEvent($eventName, $model)
    {
        if (is_string($eventName) === false ||
            is_object($model) === false ||
            $model instanceof ModelInterface === false) {
            throw new Exception('Invalid parameter type.');
        }
        
        $entityName = strtolower(get_class($model));

        if (is_array($this->_behaviors) === true &&
            isset($this->_behaviors[$entityName]) === true) {
            //Notify all the events on the behavior
            foreach ($this->_behaviors[$entityName] as $behavior) {
                if ($behavior->notify($eventName, $model) === false) {
                    return false;
                }
            }
        }

        //Dispatch events to the global events manager
        if (is_object($this->_eventsManager) === true) {
            if ($this->_eventsManager->fire('model:'.$eventName, $model) === false) {
                return false;
            }
        }

        //A model can have a specific events manager
        if (is_array($this->_customEventsManager) === true &&
            isset($this->_customEventsManager[$entityName]) === true) {
            if ($this->_customEventsManager[$entityName]->fire('model:'.$eventName, $model) === false) {
                return false;
            }
        }

        return null;
    }

    /**
     * Dispatch a event to the listeners and behaviors
     * This method expects that the endpoint listeners/behaviors returns true
     * meaning that a least one is implemented
     *
     * @param \Phalcon\Mvc\ModelInterface $model
     * @param string $eventName
     * @param array $data
     * @return mixed
     * @throws Exception
     */
    public function missingMethod($model, $eventName, $data)
    {
        if (is_object($model) === false ||
            $model instanceof ModelInterface === false ||
            is_string($eventName) === false ||
            is_array($data) === false) {
            throw new Exception('Invalid parameter type.');
        }

        if (is_array($this->_behaviors) === true) {
            $entityName = strtolower(get_class($model));
            if (isset($this->_behaviors[$entityName]) === true) {
                //Notify all the events on the behavior
                foreach ($this->_behaviors[$entityName] as $behavior) {
                    $result = $behavior->missingMethod($model, $eventName, $data);
                    if (is_null($result) === false) {
                        return $result;
                    }
                }
            }
        }

        //Dispatch events to the global events manager
        if (is_object($this->_eventsManager) === true) {
            return $this->_eventsManager->fire('model:'.$eventName, $model, $data);
        }

        return null;
    }

    /**
     * Binds a behavior to a model
     *
     * @param \Phalcon\Mvc\ModelInterface $model
     * @param \Phalcon\Mvc\Model\BehaviorInterface $behavior
     * @throws Exception
     */
    public function addBehavior($model, $behavior)
    {
        if (is_object($model) === false ||
            $model instanceof ModelInterface === false) {
            throw new Exception('Invalid parameter type.');
        }

        if (is_object($behavior) === false ||
            $behavior instanceof BehaviorInterface === false) {
            throw new Exception('The behavior is invalid');
        }

        $entityName = strtolower(get_class($model));

        //Get the current behaviors
        if (isset($this->_behaviors[$entityName]) === true) {
            $modelsBehaviors = $this->_behaviors[$entityName];
        } else {
            $modelsBehaviors = array();
        }

        //Append the behavior to the list of behaviors
        $modelsBehaviors[] = $behavior;

        //Update the behavior list
        $this->_behaviors[$entityName] = $modelsBehaviors;
    }

    /**
     * Sets if a model must keep snapshots
     *
     * @param \Phalcon\Mvc\ModelInterface $model
     * @param boolean $keepSnapshots
     * @throws Exception
     */
    public function keepSnapshots($model, $keepSnapshots)
    {
        if (is_object($model) === false ||
            $model instanceof ModelInterface === false ||
            is_bool($keepSnapshots) === false) {
            throw new Exception('Invalid parameter type.');
        }

        if (is_array($this->_keepSnapshots) === false) {
            $this->_keepSnapshots = array();
        }

        $this->_keepSnapshots[strtolower(get_class($model))] = $keepSnapshots;
    }

    /**
     * Checks if a model is keeping snapshots for the queried records
     *
     * @param \Phalcon\Mvc\ModelInterface $model
     * @return boolean
     * @throws Exception
     */
    public function isKeepingSnapshots($model)
    {
        if (is_object($model) === false ||
            $model instanceof ModelInterface === false) {
            throw new Exception('Invalid parameter type.');
        }

        if (is_array($this->_keepSnapshots) === true) {
            $entityName = strtolower(get_class($model));
            if (isset($this->_keepSnapshots[$entityName]) === true) {
                return (bool)$this->_keepSnapshots[$entityName];
            }
        }

        return false;
    }

    /**
     * Sets if a model must use dynamic update instead of the all-field update
     *
     * @param \Phalcon\Mvc\ModelInterface $model
     * @param boolean $dynamicUpdate
     * @throws Exception
     */
    public function useDynamicUpdate($model, $dynamicUpdate)
    {
        if (is_object($model) === false ||
            $model instanceof ModelInterface === false ||
            is_bool($dynamicUpdate) === false) {
            throw new Exception('Invalid parameter type.');
        }

        if (is_array($this->_dynamicUpdate) === false) {
            $this->_dynamicUpdate = array();
        }

        if (is_array($this->_keepSnapshots) === false) {
            $this->_keepSnapshots = array();
        }
 
        $entityName = strtolower(get_class($model));
        $this->_dynamicUpdate[$entityName] = $dynamicUpdate;
        $this->_keepSnapshots[$entityName] = $dynamicUpdate;
    }

    /**
     * Checks if a model is using dynamic update instead of all-field update
     *
     * @param \Phalcon\Mvc\ModelInterface $model
     * @return boolean
     * @throws Exception
     */
    public function isUsingDynamicUpdate($model)
    {
        if (is_object($model) === false ||
            $model instanceof ModelInterface === false) {
            throw new Exception('Invalid parameter type.');
        }

        if (is_array($this->_dynamicUpdate) === true) {
            $entityName = strtolower(get_class($model));
            if (isset($this->_dynamicUpdate[$entityName]) === true) {
                return (bool)$this->_dynamicUpdate[$entityName];
            }
        }

        return false;
    }

    /**
     * Setup a 1-1 relation between two models
     *
     * @param \Phalcon\Mvc\ModelInterface $model
     * @param mixed $fields
     * @param string $referencedModel
     * @param mixed $referencedFields
     * @param array|null $options
     * @return \Phalcon\Mvc\Model\Relation
     * @throws Exception
     */
    public function addHasOne($model, $fields, $referencedModel, $referencedFields, $options = null)
    {
        if (is_object($model) === false ||
            $model instanceof ModelInterface === false ||
            is_string($referencedModel) === false ||
            (is_array($options) === false &&
                is_null($options) === false)) {
            throw new Exception('Invalid parameter type.');
        }

        $entityName = strtolower(get_class($model));
        $referencedEntity = strtolower($referencedModel);
        $keyRelation = $entityName.'$'.$referencedEntity;

        if (isset($this->_hasOne[$keyRelation]) === false) {
            $relations = array();
        } else {
            $relations = $this->_hasOne[$keyRelation];
        }

        //Check if the number of fields is the same
        if (is_array($referencedFields) === true &&
            count($fields) !== count($referencedFields)) {
            throw new Exception('Number of referenced fields are not the same'); //@note (sic!)
        }

        //Create a relationship instance
        $relation = new Relation(1, $referencedModel, $fields, $referencedFields, $options);

        if (isset($options['alias']) === true) {
            $lowerAlias = strtolower($options['alias']);
        } else {
            $lowerAlias = $referencedEntity;
        }

        //Append a new relationship
        $relations[] = $relation;

        //Update the global alias
        $this->_aliases[$entityName.'$'.$lowerAlias] = $relation;

        //Update the relations
        $this->_hasOne[$keyRelation] = $relations;

        //Get existing relations by model
        if (isset($this->_hasOneSingle[$entityName]) === false) {
            $singleRelations = array();
        } else {
            $singleRelations = $this->_hasOneSingle[$entityName];
        }

        //Append a new relationship
        $singleRelations[] = $relation;

        //Update relations by model
        $this->_hasOneSingle[$entityName] = $singleRelations;

        return $relation;
    }

    /**
     * Setup a relation reverse many to one between two models
     *
     * @param \Phalcon\Mvc\Model $model
     * @param mixed $fields
     * @param string $referencedModel
     * @param mixed $referencedFields
     * @param array|null $options
     * @return \Phalcon\Mvc\Model\Relation
     * @throws Exception
     */
    public function addBelongsTo($model, $fields, $referencedModel, $referencedFields, $options = null)
    {
        if (is_object($model) === false ||
            $model instanceof ModelInterface === false ||
            is_string($referencedModel) === false ||
            (is_array($options) === false &&
                is_null($options) === false)) {
            throw new Exception('Invalid parameter type.');
        }

        $entityName = strtolower(get_class($model));
        $referencedEntity = strtolower($referencedModel);
        $keyRelation = $entityName.'$'.$referencedEntity;

        if (isset($this->_belongsTo[$keyRelation]) === false) {
            $relations = array();
        } else {
            $relations = $this->_belongsTo[$keyRelation];
        }

        //Check if the number of fields is the same
        if (is_array($referencedFields) === true &&
            count($fields) !== count($referencedFields)) {
            throw new Exception('Number of referenced fields are not the same'); //@note (sic!)
        }

        //Create a relationship instance
        $relation = new Relation(0, $referencedModel, $fields, $referencedFields, $options);

        if (isset($options['alias']) === true) {
            $lowerAlias = strtolower($options['alias']);
        } else {
            $lowerAlias = $referencedEntity;
        }

        //Append a new relationship
        $relations[] = $relation;

        //Update the global alias
        $this->_aliases[$entityName.'$'.$lowerAlias] = $relation;

        //Update the relations
        $this->_belongsTo[$keyRelation] = $relations;

        //Get existing relations by model
        if (isset($this->_belongsToSingle[$entityName]) === false) {
            $singleRelations = array();
        } else {
            $singleRelations = $this->_belongsToSingle[$entityName];
        }

        //Append a new relationship
        $singleRelations[] = $relation;

        //Update relations by model
        $this->_belongsToSingle[$entityName] = $singleRelations;

        return $relation;
    }

    /**
     * Setup a relation 1-n between two models
     *
     * @param Phalcon\Mvc\ModelInterface $model
     * @param mixed $fields
     * @param string $referencedModel
     * @param array|null $options
     * @param mixed $referencedFields
     * @return \Phalcon\Mvc\Model\Relation
     * @throws Exception
     */
    public function addHasMany($model, $fields, $referencedModel, $referencedFields, $options = null)
    {
        if (is_object($model) === false ||
            $model instanceof ModelInterface === false ||
            is_string($referencedModel) === false ||
            (is_array($options) === false &&
                is_null($options) === false)) {
            throw new Exception('Invalid parameter type.');
        }

        $entityName = strtolower(get_class($model));
        $referencedEntity = strtolower($referencedModel);
        $keyRelation = $entityName.'$'.$referencedEntity;

        if (isset($this->_hasMany[$keyRelation]) === false) {
            $relations = array();
        } else {
            $relations = $this->_hasMany[$keyRelation];
        }

        //Check if the number of fields is the same
        if (is_array($referencedFields) === true &&
            count($fields) !== count($referencedFields)) {
            throw new Exception('Number of referenced fields are not the same'); //@note (sic!)
        }

        //Create a relationship instance
        $relation = new Relation(2, $referencedModel, $fields, $referencedFields, $options);

        if (isset($options['alias']) === true) {
            $lowerAlias = strtolower($options['alias']);
        } else {
            $lowerAlias = $referencedEntity;
        }

        //Append a new relationship
        $relations[] = $relation;

        //Update the global alias
        $this->_aliases[$entityName.'$'.$lowerAlias] = $relation;

        //Update the relations
        $this->_hasMany[$keyRelation] = $relations;

        //Get existing relations by model
        if (isset($this->_hasManySingle[$entityName]) === false) {
            $singleRelations = array();
        } else {
            $singleRelations = $this->_hasManySingle[$entityName];
        }

        //Append a new relationship
        $singleRelations[] = $relation;

        //Update relations by model
        $this->_hasManySingle[$entityName] = $singleRelations;

        return $relation;
    }

    /**
     * Setups a relation n-m between two models
     *
     * @param \Phalcon\Mvc\ModelInterface $model
     * @param string|array $fields
     * @param string $intermediateModel
     * @param string|array $intermediateFields
     * @param string|array $intermediateReferencedFields
     * @param string $referencedModel
     * @param string|array $referencedFields
     * @param array|null $options
     * @return \Phalcon\Mvc\Model\Relation
     * @throws Exception
     */
    public function addHasManyToMany($model, $fields, $intermediateModel, $intermediateFields, $intermediateReferencedFields, $referencedModel, $referencedFields, $options = null)
    {
        if (is_object($model) === false ||
            $model instanceof ModelInterface === false ||
            is_string($intermediateModel) === false ||
            is_string($referencedModel) === false ||
            (is_array($options) === false &&
                is_null($options) === false)) {
            throw new Exception('Invalid parameter type.');
        }

        $entityName = strtolower(get_class($model));
        $intermediateEntity = strtolower($intermediateModel);
        $referencedEntity = strtolower($referencedModel);
        $keyRelation = $entityName.'$'.$referencedEntity;

        if (isset($this->_hasManyToMany[$keyRelation]) === false) {
            $relations = array();
        } else {
            $relations = $this->_hasManyToMany[$keyRelation];
        }

        //Check if the number of fields is the same from the model to the intermediate model
        if (is_array($intermediateFields) === true &&
            count($fields) !== count($intermediateFields)) {
            throw new Exception('Number of referenced fields are not the same'); //@note sic!
        }

        //@note this check is doing the same as the check before:
        //Check if the number of fields is the same from the intermediate model to the
        //referenced model

        //Create a relationship instance
        $relation = new Relation(4, $referencedModel, $fields, $referencedFields, $options);

        //Set extended intermediate relation data
        $relation->setIntermediateRelation($intermediateFields, $intermediateModel, $intermediateFields);

        if (isset($options['alias']) === true) {
            $lowerAlias = strtolower($options['alias']);
        } else {
            $lowerAlias = $referencedEntity;
        }

        //Append a new relationship
        $relations[] = $relation;

        //Update the global alias
        $this->_aliases[$entityName.'$'.$lowerAlias] = $relation;

        //Update the relations
        $this->_hasManyToMany[$keyRelation] = $relations;

        //Get existing relations by model
        if (isset($this->_hasManyToManySingle[$entityName]) === false) {
            $singleRelations = array();
        } else {
            $singleRelations = $this->_hasManyToManySingle[$entityName];
        }

        //Append a new relationship
        $singleRelations[] = $relation;

        //Update relations by model
        $this->_hasManyToManySingle[$entityName] = $singleRelations;

        return $relation;
    }

    /**
     * Checks whether a model has a belongsTo relation with another model
     *
     * @param string $modelName
     * @param string $modelRelation
     * @return boolean
     * @throws Exception
     */
    public function existsBelongsTo($modelName, $modelRelation)
    {
        if (is_string($modelName) === false ||
            is_string($modelRelation) === false) {
            throw new Exception('Invalid parameter type.');
        }

        $entityName = strtolower($modelName);

        //Initialize the model first
        if (isset($this->_initialized[$entityName]) === false) {
            $this->load($modelName);
        }

        return isset($this->_belongsTo[$entityName.'$'.strtolower($modelRelation)]);
    }

    /**
     * Checks whether a model has a hasMany relation with another model
     *
     * @param string $modelName
     * @param string $modelRelation
     * @return boolean
     * @throws Exception
     */
    public function existsHasMany($modelName, $modelRelation)
    {
        if (is_string($modelName) === false ||
            is_string($modelRelation) === false) {
            throw new Exception('Invalid parameter type.');
        }

        $entityName = strtolower($modelName);

        //Initialize the model first
        if (isset($this->_initialized[$entityName]) === false) {
            $this->load($modelName);
        }

        return isset($this->_hasMany[$entityName.'$'.strtolower($modelRelation)]);
    }

    /**
     * Checks whether a model has a hasOne relation with another model
     *
     * @param string $modelName
     * @param string $modelRelation
     * @return boolean
     * @throws Exception
     */
    public function existsHasOne($modelName, $modelRelation)
    {
        if (is_string($modelName) === false ||
            is_string($modelRelation) === false) {
            throw new Exception('Invalid parameter type.');
        }

        $entityName = strtolower($modelName);

        //Initialize the model first
        if (isset($this->_initialized[$entityName]) === false) {
            $this->load($modelName);
        }

        return isset($this->_hasOne[$entityName.'$'.strtolower($modelRelation)]);
    }

    /**
     * Checks whether a model has a hasManyToMany relation with another model
     *
     * @param string $modelName
     * @param string $modelRelation
     * @return boolean
     * @throws Exception
     */
    public function existsHasManyToMany($modelName, $modelRelation)
    {
        if (is_string($modelName) === false ||
            is_string($modelRelation) === false) {
            throw new Exception('Invalid parameter type.');
        }

        $entityName = strtolower($modelName);

        //Initialize the model first
        if (isset($this->_initialized[$entityName]) === false) {
            $this->load($modelName);
        }

        return isset($this->_hasManyToMany[$entityName.'$'.strtolower($modelRelation)]);
    }

    /**
     * Returns a relation by its alias
     *
     * @param string $modelName
     * @param string $alias
     * @return \Phalcon\Mvc\Model\Relation|boolean
     * @throws Exception
     */
    public function getRelationByAlias($modelName, $alias)
    {
        if (is_string($modelName) === false ||
            is_string($alias) === false) {
            throw new Exception('Invalid parameter type.');
        }

        if (is_array($this->_aliases) === true) {
            $keyLower = strtolower($modelName.'$'.$alias);
            if (isset($this->_aliases[$keyLower]) === true) {
                return $this->_aliases[$keyLower];
            }
        }

        return false;
    }

    /**
     * Returns a string representation of the value $input
     *
     * @param mixed $input
     * @return string
    */
    private static function appendPrintableVal($input)
    {
        switch (getType($input)) {
            case 'string':
            case 'integer':
            case 'double':
                return $input;
            case 'boolean':
                return ($input === true ? '1' : '');
            case 'NULL':
                return '';
            default:
                return (string)$input;
                break;
        }
    }

    /**
     * Returns a string representation of the array $input
     *
     * @param array $input
     * @return string
    */
    private static function appendPrintableArray($input)
    {
        $str = '[';

        $end = count($input);
        $pos = 0;
        foreach ($input as $tmp) {
            if (is_object($tmp) === true) {
                $str .= '0';
            } elseif (is_array($tmp) === true) {
                $str .= self::appendPrintableArray($tmp);
            } else {
                $str .= self::appendPrintableVal($tmp);
            }

            if ($pos !== $end) {
                $str .= ',';
            }
            ++$pos;
        }

        return $str.']';
    }

    /**
     * Create a unique key to be used as index in a hash
     *
     * @param string $prefix
     * @param array|string $value
     * @return string|null
    */
    private static function uniqueKey($prefix, $value)
    {
        $implstr = '';
        if (is_string($prefix) === true) {
            $implstr .= $prefix;
        }

        if (is_array($value) === true) {
        }
    }

    /**
     * Helper method to query records based on a relation definition
     *
     * @param \Phalcon\Mvc\Model\RelationInterface $relation
     * @param string $method
     * @param \Phalcon\Mvc\ModelInterface $record
     * @param array|null $parameters
     * @return \Phalcon\Mvc\Model\Resultset\Simple
     * @throws Exception
     */
    public function getRelationRecords($relation, $method, $record, $parameters = null)
    {
        if (is_object($relation) === false ||
            $relation instanceof RelationInterface === false ||
            is_string($method) === false ||
            is_object($record) === false ||
            $record instanceof ModelInterface === false ||
            (is_array($parameters) === false &&
                is_null($parameters) === false)) {
            throw new Exception('Invalid parameter type.');
        }

        //Re-use conditions
        if (is_array($parameters) === true) {
            if (isset($parameters[0]) === true) {
                $preConditions = $parameters[0];
                unset($parameters[0]);
            } elseif (isset($parameters['conditions']) === true) {
                $preConditions = $parameters['conditions'];
                unset($parameters['conditions']);
            }
        } else {
            if (is_string($parameters) === true) {
                $preConditions = $parameters;
            }
        }

        //Re-use bound parameters
        if (is_array($parameters) === true) {
            if (isset($parameters['bind']) === true) {
                $placeholders = $parameters['bind'];
                unset($parameters['bind']);
            } else {
                $placeholders = array();
            }
        } else {
            $placeholders = array();
        }

        //Perform the query on the referenced model
        $referencedModel = $relation->getReferencedModel();

        //Check if the relation is through an intermediate model
        if ($relation->isThrough() === true) {
            $conditions = array();
            $intermediateModel = $relation->getIntermediateModel();
            $intermediateFields = $relation->getIntermediateFields();

            //Appends conditions created from the fields defined in the relation
            $fields = $relation->getFields();
            if (is_array($fields) === false) {
                $value = $record->readAttribute($fields);
                $conditions[] = '['.$intermediateModel.'].['.$intermediateFields.'] = ?0';
                $placeholders[] = $value;
            } else {
                throw new Exception('Not supported');
            }

            $joinConditions = array();

            //Create the join conditions
            $intermediateFields = $relation->getIntermediateReferencedFields();
            if (is_array($intermediateFields) === false) {
                $referencedFields = $relation->getReferencedFields();
                $condition = '['.$intermediateModel.'].['.$intermediateFields.'] = ['.$referencedModel.'].['.$referencedFields.']';
            } else {
                throw new Exception('Not supported');
            }

            //We don't trust the user or the database so we use bound parameters
            $joinedJoinConditions = implode(' AND ', $joinConditions);

            //Add extra conditions passed by the programmer
            if (empty($preConditions) === false) {
                $conditions[] = $preConditions;
            }

            //We don't trust the user or the database so we use bound parameters
            $joinedConditions = implode(' AND ', $conditions);

            //Create a query builder
            $builder = $this->createBuilder($parameters);
            $builder->from($referencedModel);
            $builder->innerJoin($intermediateModel, $joinedJoinConditions);
            $builder->andWhere($joinedConditions, $placeholders);

            //Get the query
            $query = $builder->getQuery();

            //Execute the query
            return $query->execute();
        }

        if (is_null($preConditions) === false) {
            $conditions = array($preConditions);
        } else {
            $conditions = array();
        }

        //Append conditions create from the fields defined in the relation
        $fields = $relation->getFields();
        if (is_array($fields) === false) {
            $value = $record->readAttribute($fields);
            $referencedField = $relation->getReferencedFields();
            $conditions[] = '['.$referencedField.'] = ?0';
            $placeholders[] = $value;
        } else {
            //Compound relation
            $referencedFields = $relation->getReferencedFields();
            foreach ($fields as $refPosition => $field) {
                $value = $record->readAttribute($field);
                $referencedField = $referencedFields[$refPosition];
                $conditions[] = '['.$referencedField.'] = ?'.$refPosition;
                $placeholders[] = $value;
            }
        }

        //We don't trust the user or the database so we use bound parameters
        $findParams = array(implode(' AND ', $conditions), 'bind' => $placeholders, 'di' => $record->getDi());

        if (is_array($parameters) === true) {
            $findArguments = array_merge($findParams, $parameters);
        } else {
            $findArguments = $findParams;
        }

        $arguments = array($findArguments);

        //Check the right method to get the data
        if (is_null($method) ===true) {
            switch ((int)$relation->getType()) {
                case Relation::BELONGS_TO:
                case Relation::HAS_ONE:
                    $method = 'findFirst';
                    break;
                case Relation::HAS_MANY:
                    $method = 'find';
                    break;
            }
        }

        //Find first results could be reusable
        $iReusable = $relation->isReusable();
        if ($iReusable === true) {
            $uniqueKey = self::uniqueKey($referencedModel, $arguments);
            $records = $this->getReusableRecords($referencedModel, $uniqueKey);
            if (is_array($records) === true ||
                is_object($records) === true) {
                return $records;
            }
        }

        //Load the referenced model
        $referencedEntity = $this->load($referencedModel);

        //Call the function in the model
        $records = call_user_func_array(array($referencedEntity, $method), $arguments);

        //Store the result in the cache if it's reusable
        if ($iReusable === true) {
            $this->setReusableRecords($referencedModel, $uniqueKey, $records);
        }

        return $records;
    }

    /**
     * Returns a reusable object from the internal list
     *
     * @param string $modelName
     * @param string $key
     * @return object|null
     * @throws Exception
     */
    public function getReusableRecords($modelName, $key)
    {
        if (is_string($key) === false) {
            throw new Exception('Invalid parameter type.');
        }

        if (isset($this->_reusable[$key]) === true) {
            return $this->_reusable[$key];
        }

        return null;
    }

    /**
     * Stores a reusable record in the internal list
     *
     * @param string $modelName
     * @param string $key
     * @param mixed $records
     * @throws Exception
     */
    public function setReusableRecords($modelName, $key, $records)
    {
        if (is_string($key) === false) {
            throw new Exception('Invalid parameter type');
        }

        if (is_array($this->_reusable) === false) {
            $this->_reusable = array();
        }

        $this->_reusable[$key] = $records;
    }

    /**
     * Clears the internal reusable list
     *
     */
    public function clearReusableObjects()
    {
        $this->_reusable = null;
    }

    /**
     * Gets belongsTo related records from a model
     *
     * @param string $method
     * @param string $modelName
     * @param string $modelRelation
     * @param \Phalcon\Mvc\ModelInterface $record
     * @param array|null $parameters
     * @return \Phalcon\Mvc\Model\ResultsetInterface|boolean
     * @throws Exception
     */
    public function getBelongsToRecords($method, $modelName, $modelRelation, $record, $parameters = null)
    {
        if (is_string($modelName) === false ||
            is_string($modelRelation) === false) {
            throw new Exception('Invalid parameter type.');
        }

        if (is_array($this->_belongsTo) === true) {
            //Check if there is a relation between them
            $keyRelation = strtolower($modelName).'$'.strtolower($modelRelation);
            if (isset($this->_belongsTo[$keyRelation]) === false) {
                return false;
            }

            //Perform the query
            return $this->getRelationRecords($this->_belongsTo[$keyRelation][0], $method, $record, $parameters);
        }

        return false;
    }

    /**
     * Gets hasMany related records from a model
     *
     * @param string $method
     * @param string $modelName
     * @param string $modelRelation
     * @param \Phalcon\Mvc\ModelInterface $record
     * @param array|null $parameters
     * @return \Phalcon\Mvc\Model\ResultsetInterface
     * @throws Exception
     */
    public function getHasManyRecords($method, $modelName, $modelRelation, $record, $parameters = null)
    {
        if (is_string($modelName) === false ||
            is_string($modelRelation) === false) {
            throw new Exception('Invalid parameter type.');
        }

        if (is_array($this->_hasMany) === true) {
            //Check if there is a relation between them
            $keyRelation = strtolower($modelName).'$'.strtolower($modelRelation);
            if (isset($this->_hasMany[$keyRelation]) === false) {
                return false;
            }

            //Perform the query
            return $this->getRelationRecords($this->_hasMany[$keyRelation][0], $method, $record, $parameters);
        }

        return false;
    }

    /**
     * Gets belongsTo related records from a model
     *
     * @param string $method
     * @param string $modelName
     * @param string $modelRelation
     * @param \Phalcon\Mvc\ModelInterface $record
     * @param array|null $parameters
     * @return \Phalcon\Mvc\Model\ResultsetInterface
     * @throws Exception
     */
    public function getHasOneRecords($method, $modelName, $modelRelation, $record, $parameters = null)
    {
        if (is_string($modelName) === false ||
            is_string($modelRelation) === false) {
            throw new Exception('Invalid parameter type.');
        }

        if (is_array($this->_hasOne) === true) {
            //Check if there is a relation between them
            $keyRelation = strtolower($modelName).'$'.strtolower($modelRelation);
            if (isset($this->_hasOne[$keyRelation]) === false) {
                return false;
            }

            //Perform the query
            return $this->getRelationRecords($this->_hasOne[$keyRelation][0], $method, $record, $parameters);
        }

        return false;
    }

    /**
     * Gets all the belongsTo relations defined in a model
     *
     *<code>
     *  $relations = $modelsManager->getBelongsTo(new Robots());
     *</code>
     *
     * @param \Phalcon\Mvc\ModelInterface $model
     * @return \Phalcon\Mvc\Model\RelationInterface[]
     * @throws Exception
     */
    public function getBelongsTo($model)
    {
        if (is_object($model) === false ||
            $model instanceof ModelInterface === false) {
            throw new Exception('Invalid parameter type.');
        }

        if (is_array($this->_belongsToSingle) === true) {
            $lowerName = strtolower(get_class($model));
            if (isset($this->_belongsToSingle[$lowerName]) === true) {
                return $this->_belongsToSingle[$lowerName];
            }
        }

        return array();
    }

    /**
     * Gets hasMany relations defined on a model
     *
     * @param \Phalcon\Mvc\ModelInterface $model
     * @return \Phalcon\Mvc\Model\RelationInterface[]
     * @throws Exception
     */
    public function getHasMany($model)
    {
        if (is_object($model) === false ||
            $model instanceof ModelInterface === false) {
            throw new Exception('Invalid parameter type.');
        }

        if (is_array($this->_hasManySingle) === true) {
            $lowerName = strtolower(get_class($model));
            if (isset($this->_hasManySingle[$lowerName]) === true) {
                return $this->_hasManySingle[$lowerName];
            }
        }

        return array();
    }

    /**
     * Gets hasOne relations defined on a model
     *
     * @param \Phalcon\Mvc\ModelInterface $model
     * @return array
     * @throws Exception
     */
    public function getHasOne($model)
    {
        if (is_object($model) === false ||
            $model instanceof ModelInterface === false) {
            throw new Exception('Invalid parameter type.');
        }

        if (is_array($this->_hasOneSingle) === true) {
            $lowerName = strtolower(get_class($model));
            if (isset($this->_hasOneSingle[$lowerName]) === true) {
                return $this->_hasOneSingle[$lowerName];
            }
        }

        return array();
    }

    /**
     * Gets hasManyToMany relations defined on a model
     *
     * @param \Phalcon\Mvc\ModelInterface $model
     * @return \Phalcon\Mvc\Model\RelationInterface[]
     * @throws Exception
     */
    public function getHasManyToMany($model)
    {
        if (is_object($model) === false ||
            $model instanceof ModelInterface === false) {
            throw new Exception('Invalid parameter type.');
        }

        if (is_array($this->_hasManyToManySingle) === true) {
            $lowerName = strtolower(get_class($model));
            if (isset($this->_hasManyToManySingle[$lowerName]) === true) {
                return $this->_hasManyToManySingle[$lowerName];
            }
        }

        return array();
    }

    /**
     * Gets hasOne relations defined on a model
     *
     * @param \Phalcon\Mvc\ModelInterface $model
     * @return array
     * @throws Exception
     */
    public function getHasOneAndHasMany($model)
    {
        return array_merge($this->getHasOne($model), $this->getHasMany($model));
    }

    /**
     * Query all the relationships defined on a model
     *
     * @param string $modelName
     * @return \Phalcon\Mvc\Model\RelationInterface[]
     * @throws Exception
     */
    public function getRelations($modelName)
    {
        if (is_string($modelName) === false) {
            throw new Exception('Invalid parameter type.');
        }

        $entityName = strtolower($modelName);
        $allRelations = array();

        //Get belongs-to relations
        if (is_array($this->_belongsToSingle) === true) {
            if (isset($this->_belongsToSingle[$entityName]) === true) {
                foreach ($this->_belongsToSingle as $relation) {
                    $allRelations[] = $relation;
                }
            }
        }

        //Get has-many relations
        if (is_array($this->_hasManySingle) === true) {
            if (isset($this->_hasManySingle[$entityName]) === true) {
                foreach ($this->_hasManySingle as $relation) {
                    $allRelations[] = $relation;
                }
            }
        }

        //Get has-one relations
        if (is_array($this->_hasOneSingle) === true) {
            if (isset($this->_hasOneSingle[$entityName]) === true) {
                foreach ($this->_hasOneSingle as $relation) {
                    $allRelations[] = $relation;
                }
            }
        }

        return $allRelations;
    }

    /**
     * Query the first relationship defined between two models
     *
     * @param string $first
     * @param string $second
     * @return \Phalcon\Mvc\Model\RelationInterface|boolean
     * @throws Exception
     */
    public function getRelationsBetween($first, $second)
    {
        if (is_string($first) === false ||
            is_string($second) === false) {
            throw new Exception('Invalid parameter type.');
        }

        $keyRelation = strtolower($first).'$'.strtolower($second);

        //Check if it's a belongs-to relationship
        if (is_array($this->_belongsTo) === true &&
            isset($this->_belongsTo[$keyRelation]) === true) {
            return $this->_belongsTo[$keyRelation];
        }

        //Check if it's a has-many relationship
        if (is_array($this->_hasMany) === true &&
            isset($this->_hasMany[$keyRelation]) === true) {
            return $this->_hasMany[$keyRelation];
        }

        //Check if it's a has-one relationship
        if (is_array($this->_hasOne) === true &&
            isset($this->_hasOne[$keyRelation]) === true) {
            return $this->_hasOne[$keyRelation];
        }

        return false;
    }

    /**
     * Creates a \Phalcon\Mvc\Model\Query without execute it
     *
     * @param string $phql
     * @return \Phalcon\Mvc\Model\QueryInterface
     * @throws Exception
     */
    public function createQuery($phql)
    {
        if (is_string($phql) === false) {
            throw new Exception('Invalid parameter type.');
        }

        if (is_object($this->_dependencyInjector) === false) {
            throw new Exception('A dependency injection object is required to access ORM services');
        }

        //Create a query
        $query = new Query($phql);
        $query->setDi($this->_dependencyInjector);
        $this->_lastQuery = $query;

        return $query;
    }

    /**
     * Creates a \Phalcon\Mvc\Model\Query and execute it
     *
     * @param string $phql
     * @param array|null $placeholders
     * @return \Phalcon\Mvc\Model\QueryInterface
     * @throws Exception
     */
    public function executeQuery($phql, $placeholders = null)
    {
        if (is_string($phql) === false ||
            (is_array($placeholders) === false &&
                is_null($placeholders) === false)) {
            throw new Exception('Invalid parameter type.');
        }

        if (is_object($this->_dependencyInjector) === false) {
            throw new Exception('A dependency injection object is required to access ORM services');
        }

        //Create a query
        $query = new Query($phql);
        $query->setDi($this->_dependencyInjector);
        $this->_lastQuery = $query;

        //Execute the query
        return $query->execute($placeholders);
    }

    /**
     * Creates a \Phalcon\Mvc\Model\Query\Builder
     *
     * @param string|null $params
     * @return \Phalcon\Mvc\Model\Query\BuilderInterface
     * @throws Exception
     */
    public function createBuilder($params = null)
    {
        if (is_string($params) === false &&
            is_null($params) === false) {
            throw new Exception('Invalid parameter type.');
        }

        if (is_object($this->_dependencyInjector) === false) {
            throw new Exception('A dependency injection object is required to access ORM services');
        }

        //Create a query builder
        return new Builder($params, $this->_dependencyInjector);
    }

    /**
     * Returns the lastest query created or executed in the models manager
     *
     * @return \Phalcon\Mvc\Model\QueryInterface|null
     */
    public function getLastQuery()
    {
        return $this->_lastQuery;
    }

    /**
     * Registers shorter aliases for namespaces in PHQL statements
     *
     * @param string $alias
     * @param string $namespace
     * @throws Exception
     */
    public function registerNamespaceAlias($alias, $namespace)
    {
        if (is_string($alias) === false) {
            throw new Exception('The namespace alias must be a string');
        }

        if (is_string($namespace) === false) {
            throw new Exception('The namespace must be a string');
        }

        if (is_array($this->_namespaceAliases) === false) {
            $this->_namespaceAliases = array();
        }

        $this->_namespaceAliases[$alias] = $namespace;
    }

    /**
     * Returns a real namespace from its alias
     *
     * @param string $alias
     * @return string
     * @throws Exception
     */
    public function getNamespaceAlias($alias)
    {
        if (is_string($alias) === false) {
            throw new Exception('Invalid parameter type.');
        }

        if (isset($this->_namespaceAliases[$alias]) === true) {
            return $this->_namespaceAliases[$alias];
        }

        throw new Exception("Namespace alias '".$alias."' is not registered");
    }

    /**
     * Returns all the registered namespace aliases
     *
     * @return array|null
     */
    public function getNamespaceAliases()
    {
        return $this->_namespaceAliases;
    }

    /**
     * Destroys the PHQL cache
     */
    public function __destruct()
    {
    }
}
