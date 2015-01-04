<?php
/**
 * Model
 *
 * @author Andres Gutierrez <andres@phalconphp.com>
 * @author Eduar Carvajal <eduar@phalconphp.com>
 * @author Wenzel Pünter <wenzel@phelix.me>
 * @version 1.2.6
 * @package Phalcon
*/
namespace Phalcon\Mvc;

use \Phalcon\Mvc\ModelInterface;
use \Phalcon\Mvc\Model\ResultInterface;
use \Phalcon\Mvc\Model\ManagerInterface;
use \Phalcon\Mvc\Model\MetaDataInterface;
use \Phalcon\Mvc\Model\TransactionInterface;
use \Phalcon\Mvc\Model\Query\Builder;
use \Phalcon\Mvc\Model\MessageInterface;
use \Phalcon\Mvc\Model\Criteria;
use \Phalcon\Mvc\Model\Message;
use \Phalcon\Mvc\Model\ValidationFailed;
use \Phalcon\Mvc\Model\Exception;
use \Phalcon\Db\AdapterInterface as DbAdapterInterface;
use \Phalcon\Events\ManagerInterface as EventsManagerInterface;
use \Phalcon\DI\InjectionAwareInterface;
use \Phalcon\DiInterface;
use \Phalcon\DI;
use \Phalcon\Text;
use \Serializable;
use \stdClass;

/**
 * Phalcon\Mvc\Model
 *
 * <p>Phalcon\Mvc\Model connects business objects and database tables to create
 * a persistable domain model where logic and data are presented in one wrapping.
 * It‘s an implementation of the object-relational mapping (ORM).</p>
 *
 * <p>A model represents the information (data) of the application and the rules to manipulate that data.
 * Models are primarily used for managing the rules of interaction with a corresponding database table.
 * In most cases, each table in your database will correspond to one model in your application.
 * The bulk of your application’s business logic will be concentrated in the models.</p>
 *
 * <p>Phalcon\Mvc\Model is the first ORM written in C-language for PHP, giving to developers high performance
 * when interacting with databases while is also easy to use.</p>
 *
 * <code>
 *
 * $robot = new Robots();
 * $robot->type = 'mechanical';
 * $robot->name = 'Astro Boy';
 * $robot->year = 1952;
 * if ($robot->save() == false) {
 *  echo "Umh, We can store robots: ";
 *  foreach ($robot->getMessages() as $message) {
 *    echo $message;
 *  }
 * } else {
 *  echo "Great, a new robot was saved successfully!";
 * }
 * </code>
 *
 * @see https://github.com/phalcon/cphalcon/blob/1.2.6/ext/mvc/model.c
 */
abstract class Model implements ModelInterface, ResultInterface, InjectionAwareInterface, Serializable
{
    /**
     * Operation: None
     *
     * @var int
     */
    const OP_NONE = 0;

    /**
     * Operation: Create
     *
     * @var int
     */
    const OP_CREATE = 1;

    /**
     * Operation: Update
     *
     * @var int
     */
    const OP_UPDATE = 2;

    /**
     * Operation: Delete
     *
     * @var int
     */
    const OP_DELETE = 3;

    /**
     * Dirty State: Persistent
     *
     * @var int
     */
    const DIRTY_STATE_PERSISTENT = 0;

    /**
     * Dirty State: Transient
     *
     * @var int
     */
    const DIRTY_STATE_TRANSIENT = 1;

    /**
     * Dirty State: Detached
     *
     * @var int
     */
    const DIRTY_STATE_DETACHED = 2;

    /**
     * Dependency Injector
     *
     * @var null|\Phalcon\DiInterface
     * @access protected
    */
    protected $_dependencyInjector;

    /**
     * Models Manager
     *
     * @var null|\Phalcon\Mvc\Model\ManagerInterface
     * @access protected
     */
    protected $_modelsManager;

    /**
     * Models Metadata
     *
     * @var null|\Phalcon\Mvc\Model\MetaDataInterface
     * @access protected
     */
    protected $_modelsMetaData;

    /**
     * Error Messages
     *
     * @var null|array
     * @access protected
     */
    protected $_errorMessages;

    /**
     * Operation Made
     *
     * @var int
     * @access protected
     */
    protected $_operationMade = 0;

    /**
     * Dirty State
     *
     * @var int
     * @access protected
    */
    protected $_dirtyState = 1;

    /**
     * Transaction
     *
     * @var null|\Phalcon\Mvc\Model\TransactionInterface
     * @access protected
     */
    protected $_transaction;

    /**
     * Unique Key
     *
     * @var null|string
     * @access protected
     */
    protected $_uniqueKey;

    /**
     * Unique Params
     *
     * @var null|array
     * @access protected
     */
    protected $_uniqueParams;

    /**
     * Unique Types
     *
     * @var null|array
     * @access protected
    */
    protected $_uniqueTypes;

    /**
     * Skipped
     *
     * @var null|boolean
     * @access protected
     */
    protected $_skipped;

    /**
     * Related
     *
     * @var null
     * @access protected
     */
    protected $_related;

    /**
     * Snapshot
     *
     * @var null|array
     * @access protected
     */
    protected $_snapshot;

    /**
     * \Phalcon\Mvc\Model constructor
     *
     * @param \Phalcon\DiInterface|null $dependencyInjector
     * @param \Phalcon\Mvc\Model\ManagerInterface|null $modelsManager
     * @throws Exception
     */
    final public function __construct($dependencyInjector = null, $modelsManager = null)
    {
        /* Dependency Injector */
        if (is_null($dependencyInjector) === true) {
            $dependencyInjector = DI::getDefault();

            if (is_object($dependencyInjector) === false) {
                throw new Exception('A dependency injector container is required to obtain the services related to the ORM');
            }
        } elseif (is_object($dependencyInjector) === false ||
            $dependencyInjector instanceof DiInterface === false) {
            throw new Exception('Invalid parameter type.');
        }

        $this->_dependencyInjector = $dependencyInjector;

        /* Models Manager */
        if (is_null($modelsManager) === true) {
            //Inject the manager service from the DI
            $modelsManager = $dependencyInjector->getShared('modelsManager');
            if (is_object($modelsManager) === false) {
                //@note no interface validation
                throw new Exception("The injected service 'modelsManager' is not valid");
            }
        } elseif (is_object($modelsManager) === false ||
            $modelsManager instanceof ManagerInterface === false) {
            throw new Exception('Invalid parameter type.');
        }

        $this->_modelsManager = $modelsManager;

        /* Initialization */
        $this->_modelsManager->initialize($this);
        if (method_exists($this, 'onConstruct') === true) {
            $this->onConstruct();
        }
    }

    /**
     * Sets the dependency injection container
     *
     * @param \Phalcon\DiInterface $dependencyInjector
     * @throws Exception
     */
    public function setDI($dependencyInjector)
    {
        if (is_object($dependencyInjector) === false ||
            $dependencyInjector instanceof DiInterface === false) {
            throw new Exception('Invalid parameter type.');
        }

        $this->_dependencyInjector = $dependencyInjector;
    }

    /**
     * Returns the dependency injection container
     *
     * @return \Phalcon\DiInterface|null
     */
    public function getDI()
    {
        return $this->_dependencyInjector;
    }

    /**
     * Sets a custom events manager
     *
     * @param \Phalcon\Events\ManagerInterface $eventsManager
     * @throws Exception
     */
    protected function setEventsManager($eventsManager)
    {
        if (is_object($eventsManager) === false ||
            $eventsManager instanceof EventsManagerInterface === false) {
            throw new Exception('Invalid parameter type.');
        }

        $this->_modelsManager->setCustomEventsManager($this, $eventsManager);
    }

    /**
     * Returns the custom events manager
     *
     * @return \Phalcon\Events\ManagerInterface
     */
    protected function getEventsManager()
    {
        return $this->_modelsManager->getCustomEventsManager($this);
    }

    /**
     * Returns the models meta-data service related to the entity instance
     *
     * @return \Phalcon\Mvc\Model\MetaDataInterface
     * @throws Exception
     */
    public function getModelsMetaData()
    {
        if (is_object($this->_modelsMetaData) === false) {
            /*
            @see __construct

            if(is_object($this->_dependencyInjector) === false) {
                throw new Exception('A dependency injector container is required to obtain the services related to the ORM');
            }
            */
            $metaData = $this->_dependencyInjector->getShared('modelsMetadata');
            if (is_object($metaData) === false) {
                //@note no interface validation
                throw new Exception("The injected service 'modelsMetadata' is not valid");
            }

            $this->_modelsMetaData = $metaData;
        }

        return $this->_modelsMetaData;
    }

    /**
     * Returns the models manager related to the entity instance
     *
     * @return \Phalcon\Mvc\Model\ManagerInterface
     */
    public function getModelsManager()
    {
        return $this->_modelsManager;
    }

    /**
     * Sets a transaction related to the Model instance
     *
     *<code>
     *use \Phalcon\Mvc\Model\Transaction\Manager as TxManager;
     *use \Phalcon\Mvc\Model\Transaction\Failed as TxFailed;
     *
     *try {
     *
     *  $txManager = new TxManager();
     *
     *  $transaction = $txManager->get();
     *
     *  $robot = new Robots();
     *  $robot->setTransaction($transaction);
     *  $robot->name = 'WALL·E';
     *  $robot->created_at = date('Y-m-d');
     *  if ($robot->save() == false) {
     *    $transaction->rollback("Can't save robot");
     *  }
     *
     *  $robotPart = new RobotParts();
     *  $robotPart->setTransaction($transaction);
     *  $robotPart->type = 'head';
     *  if ($robotPart->save() == false) {
     *    $transaction->rollback("Robot part cannot be saved");
     *  }
     *
     *  $transaction->commit();
     *
     *} catch (TxFailed $e) {
     *  echo 'Failed, reason: ', $e->getMessage();
     *}
     *
     *</code>
     *
     * @param \Phalcon\Mvc\Model\TransactionInterface $transaction
     * @return \Phalcon\Mvc\Model
     * @throws Exception
     */
    public function setTransaction($transaction)
    {
        if (is_object($transaction) === false ||
            $transaction instanceof TransactionInterface === false) {
            throw new Exception('Transaction should be an object');
        }

        $this->_transaction = $transaction;

        return $this;
    }

    /**
     * Sets table name which model should be mapped
     *
     * @param string $source
     * @return \Phalcon\Mvc\Model
     * @throws Exception
     */
    protected function setSource($source)
    {
        if (is_string($source) === false) {
            throw new Exception('Invalid parameter type.');
        }

        $this->_modelsManager->setModelSource($this, $source);
        return $this;
    }

    /**
     * Returns table name mapped in the model
     *
     * @return string
     */
    public function getSource()
    {
        return $this->_modelsManager->getModelSource($this);
    }

    /**
     * Sets schema name where table mapped is located
     *
     * @param string $schema
     * @return \Phalcon\Mvc\Model
     * @throws Exception
     */
    protected function setSchema($schema)
    {
        if (is_string($schema) === false) {
            throw new Exception('Invalid parameter type.');
        }

        $this->_modelsManager->setModelSchema($this, $schema);

        return $this;
    }

    /**
     * Returns schema name where table mapped is located
     *
     * @return string
     */
    public function getSchema()
    {
        return $this->_modelsManager->getModelSchema($this);
    }

    /**
     * Sets the DependencyInjection connection service name
     *
     * @param string $connectionService
     * @return \Phalcon\Mvc\Model
     * @throws Exception
     */
    public function setConnectionService($connectionService)
    {
        if (is_string($connectionService) === false) {
            throw new Exception('Invalid parameter type.');
        }

        $this->_modelsManager->setConnectionService($this, $connectionService);

        return $this;
    }

    /**
     * Sets the DependencyInjection connection service name used to read data
     *
     * @param string $connectionService
     * @return \Phalcon\Mvc\Model
     * @throws Exception
     */
    public function setReadConnectionService($connectionService)
    {
        if (is_string($connectionService) === false) {
            throw new Exception('Invalid parameter type.');
        }

        $this->_modelsManager->setReadConnectionService($this, $connectionService);

        return $this;
    }

    /**
     * Sets the DependencyInjection connection service name used to write data
     *
     * @param string $connectionService
     * @return \Phalcon\Mvc\Model
     * @throws Exception
     */
    public function setWriteConnectionService($connectionService)
    {
        if (is_string($connectionService) === false) {
            throw new Exception('Invalid parameter type.');
        }

        $this->_modelsManager->setWriteConnectionService($this, $connectionService);

        return $this;
    }

    /**
     * Returns the DependencyInjection connection service name used to read data related the model
     *
     * @return string
     */
    public function getReadConnectionService()
    {
        return $this->_modelsManager->getReadConnectionService($this);
    }

    /**
     * Returns the DependencyInjection connection service name used to write data related to the model
     *
     * @return string
     */
    public function getWriteConnectionService()
    {
        return $this->_modelsManager->getWriteConnectionService();
    }

    /**
     * Sets the dirty state of the object using one of the DIRTY_STATE_* constants
     *
     * @param int $dirtyState
     * @return \Phalcon\Mvc\Model
     * @throws Exception
     */
    public function setDirtyState($dirtyState)
    {
        if (is_int($dirtyState) === false) {
            throw new Exception('Invalid parameter type.');
        }

        $this->_dirtyState = $dirtyState;

        return $this;
    }

    /**
     * Returns one of the DIRTY_STATE_* constants telling if the record exists in the database or not
     *
     * @return int
     */
    public function getDirtyState()
    {
        return $this->_dirtyState;
    }

    /**
     * Gets the connection used to read data for the model
     *
     * @return \Phalcon\Db\AdapterInterface
     */
    public function getReadConnection()
    {
        return $this->_modelsManager->getReadConnection($this);
    }

    /**
     * Gets the connection used to write data to the model
     *
     * @return \Phalcon\Db\AdapterInterface
     */
    public function getWriteConnection()
    {
        if (is_object($this->_transaction) === true) {
            return $this->_transaction->getConnection();
        }

        return $this->_modelsManager->getWriteConnection($this);
    }

    /**
     * Assigns values to a model from an array
     *
     *<code>
     *$robot->assign(array(
     *  'type' => 'mechanical',
     *  'name' => 'Astro Boy',
     *  'year' => 1952
     *));
     *</code>
     *
     * @param array $data
     * @param array|null $columnMap
     * @return \Phalcon\Mvc\Model
     * @throws Exception
     */
    public function assign($data, $columnMap = null)
    {
        if (is_array($data) === false) {
            throw new  Exception('Data to dump in the object must be an Array');
        }

        if (is_array($columnMap) === false &&
            is_null($columnMap) === false) {
            throw new Exception('Invalid parameter type.');
        }

        //@note this is not perfectly optimized, since the amount of is_array-calls is reducable
        foreach ($data as $key => $value) {
            //@note irritating annotation: Only string keys in the array are valid ?!
            if (is_array($columnMap) === true) {
                //Every field must be part of the column map
                if (isset($columnMap[$key]) === true) {
                    $this->$columnMap[$key] = $value;
                } else {
                    throw new Exception("Column \"".$key."\" doesn't make part of the column map");
                }
            } else {
                $this->$key = $value;
            }
        }
    }

    /**
     * Assigns values to a model from an array returning a new model.
     *
     *<code>
     *$robot = \Phalcon\Mvc\Model::cloneResultMap(new Robots(), array(
     *  'type' => 'mechanical',
     *  'name' => 'Astro Boy',
     *  'year' => 1952
     *));
     *</code>
     *
     * @param \Phalcon\Mvc\ModelInterface $base
     * @param array $data
     * @param array $columnMap
     * @param int|null $dirtyState
     * @param boolean|null $keepSnapshots
     * @return \Phalcon\Mvc\Model
     * @throws Exception
     */
    public static function cloneResultMap($base, $data, $columnMap, $dirtyState = null, $keepSnapshots = null)
    {
        /* Type verification */
        if (is_object($base) === false ||
            $base instanceof ModelInterface === false ||
            is_array($columnMap) === false) {
            throw new Exception('Invalid parameter type.');
        }

        if (is_array($data) === false) {
            throw new Exception('Data to dump in the object must be an Array');
        }

        if (is_null($dirtyState) === true) {
            $dirtyState = 0;
        } elseif (is_int($dirtyState) === false) {
            throw new Exception('Invalid parameter type.');
        }

        if (is_null($keepSnapshots) === false &&
            is_bool($keepSnapshots) === false) {
            throw new Exception('Invalid parameter type.');
        }

        /* Processing */
        $object = clone $base;
        $object->setDirtyState($dirtyState);

        //@note this is not perfectly optimized, since the amount of is_array-calls is reducable
        foreach ($data as $key => $value) {
            if (is_string($key) === true) {
                //Only string keys in the data are valid
                if (is_array($columnMap) === true) {
                    //Every field must be part of the column map
                    if (isset($columnMap[$key]) === true) {
                        $object->$attribute = $value;
                    } else {
                        throw new Exception('Column "'.$key.'" doesn\'t make part of the column map');
                    }
                } else {
                    $object->$key = $value;
                }
            }
        }

        if ($keepSnapshots === true) {
            $object->setSnapshotData($data, $columnMap);
        }

        //Call afterFetch, this allows the developer to execute actions after a record is fetched
        //from the database
        if (method_exists($object, 'afterFetch') === true) {
            $object->afterfetch();
        }

        return $object;
    }

    /**
     * Returns an hydrated result based on the data and the column map
     *
     * @param array $data
     * @param array $columnMap
     * @param int $hydrationMode
     * @return mixed
     * @throws Exception
     */
    public static function cloneResultMapHydrate($data, $columnMap, $hydrationMode)
    {
        if (is_array($data) === false) {
            throw new Exception('Data to hydrate must be an Array'); //@note fixed typo
        }

        if (is_array($columnMap) === false &&
            is_int($hydrationMode) === false) {
            throw new Exception('Invalid parameter type.');
        }

        //If there is no column map and the hydration mode is arrays return the data as it is
        if ($hydrationMode === 1) {
            if (is_array($columnMap) === false) {
                return $data;
            }

            //Create the destimation object according to the hydration mode
            $hydrate = array();
        } else {
            $hydrate = new stdClass();
        }

        //@note this is not perfectly optimized, since the amount of is_array-calls and $hydrationMode comparisons is reducable
        foreach ($data as $key => $value) {
            if (is_string($key) === true) {
                if (is_array($columnMap) === true) {
                    //Every field must be part of the column map
                    if (isset($columnMap[$key]) === false) {
                        throw new Exception('Column "'.$key.'" doesn\'t make part of the column map');
                    }

                    $attribute = $columnMap[$key];
                    if ($hydrationMode === 1) {
                        $hydrate[$attribute] = $value;
                    } else {
                        $hydrate->$attribute = $value;
                    }
                } else {
                    if ($hydrationMode === 1) {
                        $hydrate[$key] = $value;
                    } else {
                        $hydrate->$key = $value;
                    }
                }
            }
        }

        return $hydrate;
    }

    /**
     * Assigns values to a model from an array returning a new model
     *
     *<code>
     *$robot = \Phalcon\Mvc\Model::cloneResult(new Robots(), array(
     *  'type' => 'mechanical',
     *  'name' => 'Astro Boy',
     *  'year' => 1952
     *));
     *</code>
     *
     * @param \Phalcon\Mvc\ModelInterface $base
     * @param array $data
     * @param int|null $dirtyState
     * @return \Phalcon\Mvc\Model
     * @throws Exception
     */
    public static function cloneResult($base, $data, $dirtyState = null)
    {
        if (is_object($base) === false ||
            $base instanceof ModelInterface === false ||
            is_array($data) === false) {
            throw new Exception('Invalid parameter type.');
        }

        if (is_null($dirtyState) === true) {
            $dirtyState = 0;
        } elseif (is_int($dirtyState) === false) {
            throw new Exception('Invalid parameter type.');
        }

        if (is_array($data) === false) {
            throw new Exception('Data to dump in the object must be an Array');
        }

        //Clone the base record
        $object = clone $base;

        //Mark the object as persistent
        $object->setDirtyState($dirtyState);

        foreach ($data as $key => $value) {
            if (is_string($key) === false) {
                throw new Exception("Invalid key in array data provided to cloneResult()"); //@note fixed wrong function name
            }

            $object->$key = $value;
        }

        //Call afterFetch, this allows the developer to execute actions after a record is
        //fetched from the database
        if (method_exists($object, 'afterFetch') === true) {
            $object->afterFetch();
        }

        return $object;
    }

    /**
     * Allows to query a set of records that match the specified conditions
     *
     * <code>
     *
     * //How many robots are there?
     * $robots = Robots::find();
     * echo "There are ", count($robots), "\n";
     *
     * //How many mechanical robots are there?
     * $robots = Robots::find("type='mechanical'");
     * echo "There are ", count($robots), "\n";
     *
     * //Get and print virtual robots ordered by name
     * $robots = Robots::find(array("type='virtual'", "order" => "name"));
     * foreach ($robots as $robot) {
     *     echo $robot->name, "\n";
     * }
     *
     * //Get first 100 virtual robots ordered by name
     * $robots = Robots::find(array("type='virtual'", "order" => "name", "limit" => 100));
     * foreach ($robots as $robot) {
     *     echo $robot->name, "\n";
     * }
     * </code>
     *
     * @param mixed $parameters
     * @return \Phalcon\Mvc\Model\ResultsetInterface
     * @throws Exception
     */
    public static function find($parameters = null)
    {
        if (is_array($parameters) === false) {
            $params = array();
            if (is_null($parameters) === false) {
                $params[] = $parameters;
            }
        } else {
            $params = $parameters;
        }

        //Builds a query with the passed parameters
        $builder = new Builder($params);
        $builder->from(get_called_class());
        $query = $builder->getQuery();

        $bindParams = null;
        $bindTypes = null;

        //Check for bind parameters
        if (isset($params['bind']) === true) {
            $bindParams = $params['bind'];
            if (isset($params['bindTypes']) === true) {
                $bindTypes = $params['bindTypes'];
            }
        }

        //Pass the cache options to the query
        if (isset($params['cache']) === true) {
            $query->cache($params['cache']);
        }

        //Execute the query passing the bind-params and casting types
        $resultset = $query->execute($bindParams, $bindTypes);

        //Define a hydration mode
        if (is_object($resultset) === true && isset($params['hydration']) === true) {
            $resultset->setHydrateMode($params['hydration']);
        }

        return $resultset;
    }

    /**
     * Allows to query the first record that match the specified conditions
     *
     * <code>
     *
     * //What's the first robot in robots table?
     * $robot = Robots::findFirst();
     * echo "The robot name is ", $robot->name;
     *
     * //What's the first mechanical robot in robots table?
     * $robot = Robots::findFirst("type='mechanical'");
     * echo "The first mechanical robot name is ", $robot->name;
     *
     * //Get first virtual robot ordered by name
     * $robot = Robots::findFirst(array("type='virtual'", "order" => "name"));
     * echo "The first virtual robot name is ", $robot->name;
     *
     * </code>
     *
     * @param mixed $parameters
     * @return \Phalcon\Mvc\Model
     * @throws Exception
     */
    public static function findFirst($parameters = null)
    {
        if (is_array($parameters) === false) {
            $params = array();
            if (is_null($parameters) === false) {
                $params[] = $parameters;
            }
        } else {
            $params = $parameters;
        }

        //Builds a query with the passed parameters
        $builder = new Builder($params);
        $builder->from(get_called_class());

        //We only want the first record
        $builder->limit(1);
        $query = $builder->getQuery();

        $bindParams = null;
        $bindTypes = null;

        //Check for bind parameters
        if (isset($params['bind']) === true) {
            $bindParams = $params['bind'];
            if (isset($params['bindTypes']) === true) {
                $bindTypes = $params['bindTypes'];
            }
        }

        //Pass the cache options to the query
        if (isset($params['cache']) === true) {
            $query->cache($params['cache']);
        }

        //Return only the first row
        $query->setUniqueRow(true);

        //Execute the query passing the bind-params and casting-types
        return $query->execute($bindParams, $bindTypes);
    }

    /**
     * Create a criteria for a specific model
     *
     * @param \Phalcon\DiInterface|null $dependencyInjector
     * @return \Phalcon\Mvc\Model\Criteria
     */
    public static function query($dependencyInjector = null)
    {
        if (is_object($dependencyInjector) === false ||
            $dependencyInjector instanceof DiInterface === false) {
            $dependencyInjector = DI::getDefault();
        }

        $criteria = new Criteria();
        $criteria->setDi($dependencyInjector);
        $criteria->setModelName(get_called_class());

        return $criteria;
    }

    /**
     * Checks if the current record already exists or not
     *
     * @param \Phalcon\Mvc\Model\MetadataInterface $metaData
     * @param \Phalcon\Db\AdapterInterface $connection
     * @param null $table
     * @return boolean
     * @throws Exception
     */
    protected function _exists($metaData, $connection, $table = null)
    {
        if (is_object($metaData) === false ||
            $metaData instanceof MetaDataInterface === false ||
            is_object($connection) === false ||
            $connection instanceof DbAdapterInterface === false) {
            throw new Exception('Invalid parameter type.');
        }

        $uniqueParams = null;
        $uniqueTypes = null;
        $numberPrimary = null;

        //Builds an unique primary key condition
        if (is_null($this->_uniqueKey) === true) {
            $primaryKeys = $metaData->getPrimaryKeyAttributes($this);
            $bindDataTypes = $metaData->getBindTypes($this);
            $numberPrimary = count($primaryKeys);

            if ($numberPrimary == false) {
                return false;
            }

            //Check if column renaming is globally activated
            if (isset($GLOBALS['_PHALCON_ORM_COLUMN_RENAMING']) === true &&
                $GLOBALS['_PHALCON_ORM_COLUMN_RENAMING'] === true) {
                $columnMap = $metaData->getColumnMap($this);
            } else {
                $columnMap = null;
            }

            $wherePk = array();
            $uniqueKeys = array();
            $uniqueTypes = array();
            $numberEmpty = 0;

            //We need to create a primary key based on the current data
            foreach ($primaryKeys as $field) {
                if (is_array($columnMap) === true) {
                    if (isset($columnMap[$field]) === true) {
                        $attributeField = $columnMap[$field];
                    } else {
                        throw new Exception("Column '".$field."' isn't part of the column map");
                    }
                } else {
                    $attributeField = $field;
                }

                //If the primary key attribute is set, append it to the conditions
                if (isset($this->$attributeField) === true) {
                    $value = $this->$attributeField;

                    //We count how many fields are empty, if all fields are empty we don't perform an
                    //'exist' check
                    if (empty($value) === true) {
                        $numberEmpty++;
                    }

                    $uniqueParams[] = $value;
                } else {
                    $uniqueParams[] = null;
                    $numberEmpty++;
                }

                if (isset($bindDataTypes[$field]) === false) {
                    throw new Exception("Column '".$field."' isn't part of the table columns");
                }

                $type = $bindDataTypes[$field];
                $uniqueTypes[] = $type;
                $wherePk[] = $connection->escapeIdentifier($field).' = ?';
            }

            //There are no primary key fields defined, assume the record does not eist
            if ($numberPrimary === $numberEmpty) {
                return false;
            }

            $joinWhere = implode(' AND ', $wherePk);

            //The unique key is composed of 3 parts _uniqueKey, _uniqueParams, _uniqueTypes
            $this->_uniqueKey = $joinWhere;
            $this->_uniqueParams = $uniqueParams;
            $this->_uniqueTypes = $uniqueTypes;
            $uniqueKey = $joinWhere;
        }

        //If we already know if the record exists we don't check it
        if ($this->_dirtyState != true) {
            return true;
        }

        if (is_null($uniqueKeys) === true) {
            $uniqueKey = $this->_uniqueKey;
        }

        if (is_null($uniqueParams) === true) {
            $uniqueParams = $this->_uniqueParams;
        }

        if (is_null($uniqueTypes) === true) {
            $uniqueTypes = $this->_uniqueTypes;
        }

        $schema = $this->getSchema();
        $source = $this->getSource();

        if (isset($schema) === true) {
            $table = array($schema, $source);
        } else {
            $table = $source;
        }

        //Here we use a single COUNT(*) without PHQL to make the execution faster
        $select = 'SELECT COUNT(*) "rowcount" FROM '.$connection->escapeIdentifier($table).' WHERE '.$uniqueKey;
        $num = $connection->fetchOne($select, null, $uniqueParams, $uniqueTypes);

        if ($num['rowcount'] != 0) {
            $this->_dirtyState = 0;
            return true;
        } else {
            $this->_dirtyState = 1;
        }

        return false;
    }

    /**
     * Generate a PHQL SELECT statement for an aggregate
     *
     * @param string $function
     * @param string $alias
     * @param mixed $parameters
     * @return \Phalcon\Mvc\Model\ResultsetInterface
     * @throws Exception
     */
    protected static function _groupResult($function, $alias, $parameters = null)
    {
        if (is_string($function) === false ||
            is_string($alias) === false) {
            throw new Exception('Invalid parameter type.');
        }

        if (is_array($parameters) === false) {
            if (is_null($parameters) === false) {
                $params = array($parameters);
            } else {
                $params = array();
            }
        } else {
            $params = $parameters;
        }

        if (isset($params['column']) === true) {
            $groupColumn = $params['column'];
        } else {
            $groupColumn = '*';
        }

        //Builds the column to query according to the received parameters
        if (isset($params['distinct']) === true) {
            $columns = $function.'(DISTINCT '.$params['distinct'].') AS '.$alias;
        } else {
            if (isset($params['group']) === true) {
                $columns = $params['group'].', '.$function.'('.$params['group'].') AS '.$alias;
            } else {
                $columns = $function.'('.$groupColumn.') AS '.$alias;
            }
        }

        //Builds a query with the passed parameters
        $builder = new Builder($params);
        $builder->columns($columns);
        $builder->from(get_called_class());
        $query = $builder->getQuery();

        $bindParams = null;
        $bindTypes = null;
        //Check for bind parameters
        if (isset($params['bind']) === true) {
            $bindParams = $params['bind'];
            if (isset($params['bindTypes']) === true) {
                $bindTypes = $params['bindTypes'];
            }
        }

        //Execute the query
        $resultset = $query->execute($bindParams, $bindTypes);

        //Pass the cache options to the query
        if (isset($params['cache']) === true) {
            $query->cache($params['cache']);
        }

        //Return the full resultset if the query is grouped
        if (isset($params['group']) === true) {
            return $resultset;
        }

        //Return only the value in the first result
        //$number_rows = count($resultset); @note this variable is not necessary
        $firstRow = $resultset->getFirst();
        return $firstRow->alias;
    }

    /**
     * Allows to count how many records match the specified conditions
     *
     * <code>
     *
     * //How many robots are there?
     * $number = Robots::count();
     * echo "There are ", $number, "\n";
     *
     * //How many mechanical robots are there?
     * $number = Robots::count("type='mechanical'");
     * echo "There are ", $number, " mechanical robots\n";
     *
     * </code>
     *
     * @param array|null $parameters
     * @return int
     */
    public static function count($parameters = null)
    {
        return self::_groupResult('COUNT', 'rowcount', $parameters);
    }

    /**
     * Allows to calculate a summatory on a column that match the specified conditions
     *
     * <code>
     *
     * //How much are all robots?
     * $sum = Robots::sum(array('column' => 'price'));
     * echo "The total price of robots is ", $sum, "\n";
     *
     * //How much are mechanical robots?
     * $sum = Robots::sum(array("type='mechanical'", 'column' => 'price'));
     * echo "The total price of mechanical robots is  ", $sum, "\n";
     *
     * </code>
     *
     * @param array|null $parameters
     * @return double
     */
    public static function sum($parameters = null)
    {
        return self::_groupResult('SUM', 'summatory', $parameters);
    }

    /**
     * Allows to get the maximum value of a column that match the specified conditions
     *
     * <code>
     *
     * //What is the maximum robot id?
     * $id = Robots::maximum(array('column' => 'id'));
     * echo "The maximum robot id is: ", $id, "\n";
     *
     * //What is the maximum id of mechanical robots?
     * $sum = Robots::maximum(array("type='mechanical'", 'column' => 'id'));
     * echo "The maximum robot id of mechanical robots is ", $id, "\n";
     *
     * </code>
     *
     * @param array|null $parameters
     * @return mixed
     */
    public static function maximum($parameters = null)
    {
        return self::_groupResult('MAX', 'maximum', $parameters);
    }

    /**
     * Allows to get the minimum value of a column that match the specified conditions
     *
     * <code>
     *
     * //What is the minimum robot id?
     * $id = Robots::minimum(array('column' => 'id'));
     * echo "The minimum robot id is: ", $id;
     *
     * //What is the minimum id of mechanical robots?
     * $sum = Robots::minimum(array("type='mechanical'", 'column' => 'id'));
     * echo "The minimum robot id of mechanical robots is ", $id;
     *
     * </code>
     *
     * @param array|null $parameters
     * @return mixed
     */
    public static function minimum($parameters = null)
    {
        return self::_groupResult('MIN', 'minimum', $parameters);
    }

    /**
     * Allows to calculate the average value on a column matching the specified conditions
     *
     * <code>
     *
     * //What's the average price of robots?
     * $average = Robots::average(array('column' => 'price'));
     * echo "The average price is ", $average, "\n";
     *
     * //What's the average price of mechanical robots?
     * $average = Robots::average(array("type='mechanical'", 'column' => 'price'));
     * echo "The average price of mechanical robots is ", $average, "\n";
     *
     * </code>
     *
     * @param array|null $parameters
     * @return double
     */
    public static function average($parameters = null)
    {
        return self::_groupResult('AVG', 'average', $parameters);
    }

    /**
     * Fires an event, implicitly calls behaviors and listeners in the events manager are notified
     *
     * @param string $eventName
     * @return boolean
     * @throws Exception
     */
    public function fireEvent($eventName)
    {
        if (is_string($eventName) === false) {
            throw new Exception('Invalid parameter type.');
        }

        //Check if there is a method with the same name of the event
        if (method_exists($this, $eventName) === true) {
            $this->$eventName();
        }

        //Send a notification to the events manager
        return $this->_modelsManager->notifyEvent($eventName, $this);
    }

    /**
     * Fires an event, implicitly calls behaviors and listeners in the events manager are notified
     * This method stops if one of the callbacks/listeners returns boolean false
     *
     * @param string $eventName
     * @return boolean
     * @throws Exception
     */
    public function fireEventCancel($eventName)
    {
        if (is_string($eventName) === false) {
            throw new Exception('Invalid parameter type.');
        }

        //Check if there is a method with the same name of the event
        if (method_exists($this, $eventName) === true) {
            if ($this->$eventName() === false) {
                return false;
            }
        }

        //Send a notification to the events manager
        if ($this->_modelsManager->notifyEvent($eventName, $this) === false) {
            return false;
        }

        return true;
    }

    /**
     * Cancel the current operation
     */
    protected function _cancelOperation()
    {
        if ($this->_operationMade === 3) {
            $this->fireEvent('notDeleted');
        } else {
            $this->fireEvent('notSaved');
        }
    }

    /**
     * Appends a customized message on the validation process
     *
     * <code>
     * use \Phalcon\Mvc\Model\Message as Message;
     *
     * class Robots extends \Phalcon\Mvc\Model
     * {
     *
     *   public function beforeSave()
     *   {
     *     if ($this->name == 'Peter') {
     *        $message = new Message("Sorry, but a robot cannot be named Peter");
     *        $this->appendMessage($message);
     *     }
     *   }
     * }
     * </code>
     *
     * @param \Phalcon\Mvc\Model\MessageInterface $message
     * @return \Phalcon\Mvc\Model
     */
    public function appendMessage($message)
    {
        if (is_object($message) === false ||
            $message instanceof MessageInterface === false) {
            throw new Exception("Invalid message format '".getType($message)."'");
        }

        if (is_array($this->_errorMessages) === false) {
            $this->_errorMessages = array();
        }

        $this->_errorMessages[] = $message;

        return $this;
    }

    /**
     * Executes validators on every validation call
     *
     *<code>
     *use \Phalcon\Mvc\Model\Validator\ExclusionIn as ExclusionIn;
     *
     *class Subscriptors extends \Phalcon\Mvc\Model
     *{
     *
     *  public function validation()
     *  {
     *      $this->validate(new ExclusionIn(array(
     *          'field' => 'status',
     *          'domain' => array('A', 'I')
     *      )));
     *      if ($this->validationHasFailed() == true) {
     *          return false;
     *      }
     *  }
     *
     *}
     *</code>
     *
     * @param object $validator
     * @return \Phalcon\Mvc\Model
     */
    protected function validate($validator)
    {
        //@note no validator interface validation
        if (is_object($validator) === false) {
            throw new Exception('Validator must be an Object');
        }

        if (is_array($this->_errorMessages) === false) {
            $this->_errorMessages = array();
        }

        //Call the validation, if it returns false we apped the messages to the current
        //object
        if ($validator->validate($this) === false) {
            $messages = $validator->getMessages();
            foreach ($messages as $message) {
                $this->_errorMessages[] = $message;
            }
        }

        return $this;
    }

    /**
     * Check whether validation process has generated any messages
     *
     *<code>
     *use \Phalcon\Mvc\Model\Validator\ExclusionIn as ExclusionIn;
     *
     *class Subscriptors extends \Phalcon\Mvc\Model
     *{
     *
     *  public function validation()
     *  {
     *      $this->validate(new ExclusionIn(array(
     *          'field' => 'status',
     *          'domain' => array('A', 'I')
     *      )));
     *      if ($this->validationHasFailed() == true) {
     *          return false;
     *      }
     *  }
     *
     *}
     *</code>
     *
     * @return boolean
     */
    public function validationHasFailed()
    {
        return (is_array($this->_errorMessages) === true && count($this->_errorMessages) > 0 ? true : false);
    }

    /**
     * Returns all the validation messages
     *
     *<code>
     *  $robot = new Robots();
     *  $robot->type = 'mechanical';
     *  $robot->name = 'Astro Boy';
     *  $robot->year = 1952;
     *  if ($robot->save() == false) {
     *      echo "Umh, We can't store robots right now ";
     *      foreach ($robot->getMessages() as $message) {
     *          echo $message;
     *      }
     *  } else {
     *      echo "Great, a new robot was saved successfully!";
     *  }
     * </code>
     *
     * @return \Phalcon\Mvc\Model\MessageInterface[]|null
     */
    public function getMessages()
    {
        return $this->_errorMessages;
    }

    /**
     * Reads "belongs to" relations and check the virtual foreign keys when inserting or updating records
     * to verify that inserted/updated values are present in the related entity
     *
     * @return boolean
     */
    protected function _checkForeignKeysRestrict()
    {
        //Get the models manager
        $manager = $this->_modelsManager;

        //We check if some of the belongsTo relations act as virtual foreign keys
        $belongsTo = $manager->belongsTo($this);

        if (count($belongsTo) > 0) {
            $error = false;

            foreach ($belongsTo as $relation) {
                $foreignKey = $relation->getForeignKey();
                if ($foreignKey !== false) {
                    //By default action is restricted
                    $action = 1;

                    //Try to find a different action in the foreign key's options
                    if (is_array($foreignKey) === true && isset($foreignKey['action']) === true) {
                        $action = $foreignKey['action'];
                    }

                    //Check only if the operation is restricted
                    if ($action === 1) {
                        //Load the referenced model if needed
                        $referencedModel = $manager->load($relation->getReferencedModel());

                        //Since relations can have multiple columns or a single one, we need to build a
                        //condition for each of these cases
                        $conditions = array();
                        $bindParams = array();
                        $fields = $relation->getFields();
                        $referencedFields = $relation->getReferencedFields();

                        if (is_array($fields) === true) {
                            //Create a compound condition
                            foreach ($fields as $position => $field) {
                                if (isset($this->$field) === true) {
                                    $value = $this->$field;
                                } else {
                                    $value = null;
                                }

                                $conditions[] = '['.$referencedFields[$position].'] = ?'.$position;
                                $bindParams[] = $value;
                            }
                        } else {
                            //Create a simple condition
                            if (isset($this->$fields) === true) {
                                $value = $this->$fields;
                            } else {
                                $value = null;
                            }
                            $conditions[] = '['.$referencedFields.'] = ?0';
                            $bindParams[] = $value;
                        }

                        //Check if the virtual foreign key has extra conditions
                        if (isset($foreignKey['conditions']) === true) {
                            $conditions[] = $foreignKey['conditions'];
                        }

                        //We don't trust the actual values in the object and pass the values using bound
                        //parameters
                        $joinConditions = implode(' AND ', $conditions);

                        $parameters[] = $joinConditions;
                        $parameters['bind'] = $bindParams;

                        //Lets make the checking
                        $rowcount = $referencedModel->count($parameters);
                        if ($rowcount == 0) {
                            //Get the message or produce a new one
                            if (isset($foreignKey['message']) === true) {
                                $userMessage = $foreignKey['message'];
                            } else {
                                if (is_array($fields) === true) {
                                    $userMessage = 'Value of fields "'.implode(', ', $fields).'" does not exist on referenced table';
                                } else {
                                    $userMessage = 'Value of field "'.$fields.'" does not exist on referenced table';
                                }
                            }

                            //Create a message
                            $this->appendMessage(new Message($userMessage, $fields, 'ConstraintViolation'));
                            $error = true;
                            break;
                        }
                    }
                }
            }

            //Call 'onValidationFails' if the validation fails
            if ($error === true) {
                if (isset($GLOBALS['_PHALCON_ORM_EVENTS']) === true && $GLOBALS['_PHALCON_ORM_EVENTS'] === true) {
                    $this->fireEvent('onValidationFails');
                    $this->_cancelOperation();
                }

                return false;
            }
        }

        return true;
    }

    /**
     * Reads both "hasMany" and "hasOne" relations and checks the virtual foreign keys (restrict) when deleting records
     *
     * @return boolean
     */
    protected function _checkForeignKeysReverseRestrict()
    {
        //Get the models manager
        $manager = $this->_modelsManager;

        //We check if some of the hasOne/hasMany relations are a foreign key
        $relations = $manager->getHasOneAndHasMany($this);
        if (count($relations) > 0) {
            $error = false;

            foreach ($relations as $relation) {
                //Check if the relation has a virtual foregin key
                $foreignKey = $relation->getForeignKey();
                if ($foreignKey != false) {
                    //By default action is restricted
                    $action = 1;

                    //Try to find a different action in the foreign key's options
                    if (is_array($foreignKey) === true) {
                        if (isset($foreignKey['action']) === true) {
                            $action = $foreignKey['action'];
                        }
                    }

                    //Check only if the operation is restricted
                    if ($action === 1) {
                        $relationClass = $relation->getReferencedModel();

                        //Load a plain instance from the models manager
                        $referencedModel = $manager->load($relationClass);
                        $fields = $relation->getFields();
                        $referencedFields = $relation->getReferencedFields();

                        //Create the checking conditions. A relation can have many fields or a single one
                        $conditions = array();
                        $bindParams = array();

                        if (is_array($fields) === true) {
                            foreach ($fields as $position => $field) {
                                if (isset($this->$field) === true) {
                                    $value = $this->$field;
                                } else {
                                    $value = null;
                                }

                                $referencedField = $referencedFields[$position];

                                $conditions[] = '['.$referencedField.'] = ?'.$position;
                                $bindParams[] = $value;
                            }
                        } else {
                            if (isset($this->$fields) === true) {
                                $value = $this->$fields;
                            } else {
                                $value = null;
                            }

                            $conditions[] = '['.$referencedFields.'] = ?0';
                            $bindParams[] = $value;
                        }

                        //Check if the virtual foreign key has extra conditions
                        if (isset($foreignKey['conditions']) === true) {
                            $conditions[] = $foreignKey['conditions'];
                        }

                        //We don't trust the actual values in the object and then we're passing the values
                        //using bound parmeters
                        $joinConditions = implode(' AND ', $conditions);

                        $parameters = array($joinConditions);
                        $parameters['bind'] = $bindParams;

                        //Checking
                        $rowcount = $referencedModel->count($parameters);
                        if ($rowcount != 0) {
                            //Create a new message
                            if (isset($foreignKey['message']) === true) {
                                $userMessage = $foreignKey['message'];
                            } else {
                                $userMessage = 'Record is referenced by model '.$relationClass;
                            }

                            //Create a message
                            $this->appendMessage(new Message($userMessage, $fields, 'ConstraintViolation'));
                            $error = true;
                            break;
                        }
                    }
                }
            }

            //Call validation failed event
            if ($error === true) {
                if (isset($GLOBALS['_PHALCON_ORM_EVENTS']) === true && $GLOBALS['_PHALCON_ORM_EVENTS'] === true) {
                    $this->fireEvent('onValidationFails');
                    $this->_cancelOperation();
                }

                return false;
            }
        }

        return true;
    }

    /**
     * Reads both "hasMany" and "hasOne" relations and checks the virtual foreign keys (cascade) when deleting records
     *
     * @return boolean
     */
    protected function _checkForeignKeysReverseCascade()
    {
        //Get the models manager
        $manager = $this->_modelsManager;

        //We check if some of the hasOne/hasMany relations is a foregin key
        $relations = $manager->getHasOneAndHasMany($this);

        if (count($relations) > 0) {
            foreach ($relations as $relation) {
                //Check if the relation has a virtual foreign key
                $foreignKey = $relation->getForeignKey();
                if ($foreignKey != false) {
                    //By default action is restricted
                    $action = false;

                    //Try to find a different action in the foreign key's options
                    if (is_array($foreignKey) === true &&
                        isset($foreignKey['action']) === true) {
                        $action = $foreignKey['action'];
                    }

                    //Check only if the operation is restricted
                    if ($action === 2) {
                        $relationClass = $relation->getReferencedModel();

                        //Load a plain instance from the models manager
                        $referencedModel = $manager->load($relationClass);
                        $fields = $relation->getFields();
                        $referencedFields = $relation->getReferencedFields();

                        //Create the checking conditions. A relation can have many fields or a single one.
                        $conditions = array();
                        $bindParams = array();

                        if (is_array($fields) === true) {
                            foreach ($fields as $position => $field) {
                                if (isset($this->$field) === true) {
                                    $value = $this->$field;
                                } else {
                                    $value = null;
                                }

                                $conditions[] = '['.$referencedFields[$position].'] = ?'.$position;
                                $bindParams[] = $value;
                            }
                        } else {
                            if (isset($this->$fields) === true) {
                                $value = $this->$fields;
                            } else {
                                $value = null;
                            }

                            $conditions[] = '['.$referencedFields.'] = ?0';
                            $bindParams[] = $value;
                        }

                        //Check if the virtual foreign key has extra conditions
                        if (isset($foreignKey['conditions']) === true) {
                            $conditions[] = $foreignKey['conditions'];
                        }

                        //Pass the values using bound parameters
                        $parameters = array(implode(' AND ', $conditions));
                        $parameters['bind'] = $bindParams;

                        //Let's make the checking
                        $resultset = $referencedModel->find($parameters);

                        //Delete the resultset
                        if ($resultset->delete() === false) {
                            return false;
                        }
                    }
                }
            }
        }

        return true;
    }

    /**
     * Executes internal hooks before save a record
     *
     * @param \Phalcon\Mvc\Model\MetaDataInterface $metaData
     * @param boolean $exists
     * @param string $identityField
     * @return boolean
     * @throws Exception
     */
    protected function _preSave($metaData, $exists, $identityField)
    {
        if (is_object($metaData) === false ||
            $metaData instanceof MetaDataInterface  === false ||
            is_bool($exists) === false ||
            is_string($identityField) === false) {
            throw new Exception('Invalid parameter type.');
        }

        //Run Validation Callbacks "Before"
        if (isset($GLOBALS['_PHALCON_ORM_EVENTS']) === true &&
            $GLOBALS['_PHALCON_ORM_EVENTS'] === true) {
            //Call the beforeValidation
            if ($this->fireEventCancel('beforeValidation') === false) {
                return false;
            }

            //Call the specific beforeValidation event for the current action
            if ($exists === true) {
                if ($this->fireEventCancel('beforeValidationOnCreate') === false) {
                    return false;
                }
            } else {
                if ($this->fireEventCancel('beforeValidationOnUpdate') === false) {
                    return false;
                }
            }
        }

        //Check for Virtual foreign keys
        if (isset($GLOBALS['_PHALCON_ORM_VIRTUAL_FOREIGN_KEYS']) === true &&
            $GLOBALS['_PHALCON_ORM_VIRTUAL_FOREIGN_KEYS'] === true) {
            if ($this->_checkForeignKeysRestrict() === false) {
                return false;
            }
        }

        //Columns marked as not null are automatically validated by the ORM
        if (isset($GLOBALS['_PHALCON_ORM_NOT_NULL_VALIDATIONS']) === true) {
            $notNull = $metaData->getNotNullAttributes($this);
            if (is_array($notNull) === true) {
                //Get the fields which are numeric, these are validated in a different way
                $dataTypeNumeric = $metaData->getDataTypesNumeric($this);
                if (isset($GLOBALS['_PHALCON_ORM_COLUMN_RENAMING']) === true &&
                    $GLOBALS['_PHALCON_ORM_COLUMN_RENAMING'] === true) {
                    $columnMap = $metaData->getColumnMap($this);
                } else {
                    $columnMap = null;
                }

                //Get fields which must be omitted from the SQL generation
                if ($exists === true) {
                    $automaticAttributes = $metaData->getAutomaticUpdateAttributes($this);
                } else {
                    $automaticAttributes = $metaData->getAutomaticCreateAttributes($this);
                }

                $error = false;

                foreach ($notNull as $field) {
                    //We don't check fields which must be omitted
                    if (isset($automaticAttributes[$field]) === false) {
                        $isNull = false;

                        if (is_array($columnMap) === true) {
                            if (isset($columnMap[$field]) === true) {
                                $attributeField = $columnMap[$field];
                            } else {
                                throw new Exception("Column '".$field."' isn't part of the column map");
                            }
                        } else {
                            $attributeField = $field;
                        }

                        //Field is null when: 1) is not set, 2) is numeric but its value is not numeric,
                        //3) is null or 4) is empty string
                        if (isset($this->$attributeField) === true) {
                            //Read the attribute from $this using the real or renamed name
                            $value = $this->$attributeField;

                            //Objects are never treated as null, numeric fields must be numeric to be accepted
                            //as not null
                            if (is_object($value) === false) {
                                if (isset($dataTypeNumeric[$field]) === false) {
                                    if (empty($value) === true) {
                                        $isNull = true;
                                    }
                                } else {
                                    if (is_numeric($value) === false) {
                                        $isNull = true;
                                    }
                                }
                            }
                        } else {
                            $isNull = true;
                        }

                        if ($isNull === true) {
                            if ($exists === false) {
                                //The identity field can be null
                                if ($field === $identityField) {
                                    continue;
                                }
                            }

                            //A implicit PresenceOf message is created
                            $this->_errorMessages[] = new Message($attributeField.' is required', $attributeField, 'PresenceOf');
                            $error = true;
                        }
                    }
                }

                if ($error === true) {
                    if (isset($GLOBALS['_PHALCON_ORM_EVENTS']) === true &&
                        $GLOBALS['_PHALCON_ORM_EVENTS'] === true) {
                        $this->fireEvent('onValidationFails');
                        $this->_cancelOperation();
                    }

                    return false;
                }
            }
        }

        //Call the main validation event
        if ($this->fireEventCancel('validation') === false) {
            if (isset($GLOBALS['_PHALCON_ORM_EVENTS']) === true &&
                $GLOBALS['_PHALCON_ORM_EVENTS'] === true) {
                $this->fireEvent('onValidationFails');
            }

            return false;
        }

        //Run validation
        if (isset($GLOBALS['_PHALCON_ORM_EVENTS']) === true &&
            $GLOBALS['_PHALCON_ORM_EVENTS'] === true) {
            //Run Validation Callbacks "After"
            if ($exists === false) {
                if ($this->fireEventCancel('afterValidationOnCreate') === false) {
                    return false;
                }
            } else {
                if ($this->fireEventCancel('afterValidationOnUpdate') === false) {
                    return false;
                }
            }

            if ($this->fireEventCancel('afterValidation') === false) {
                return false;
            }

            //Run "Before" Callbacks
            if ($this->fireEventCancel('beforeSave') === false) {
                return false;
            }

            //The operation can be skipped here
            $this->_skipped = false;
            if ($exists === true) {
                if ($this->fireEventCancel('beforeUpdate') === false) {
                    return false;
                }
            } else {
                if ($this->fireEventCancel('beforeCreate') === false) {
                    return false;
                }
            }

            //Always return true if the operation is skipped
            if ($this->_skipped === true) {
                return true;
            }
        }

        return true;
    }

    /**
     * Executes internal events after save a record
     *
     * @param boolean $success
     * @param boolean $exists
     * @return boolean
     * @throws Exception
     */
    protected function _postSave($success, $exists)
    {
        if (is_bool($success) === false ||
            is_bool($exists) === false) {
            throw new Exception('Invalid parameter type.');
        }

        if ($success === true) {
            if ($exists === true) {
                $this->fireEvent('afterUpdate');
            } else {
                $this->fireEvent('afterCreate');
            }

            $this->fireEvent('afterSave');
            return true;
        }

        $this->fireEvent('notSave');
        $this->_cancelOperation();
        return false;
    }

    /**
     * Sends a pre-build INSERT SQL statement to the relational database system
     *
     * @param \Phalcon\Mvc\Model\MetaDataInterface $metaData
     * @param \Phalcon\Db\AdapterInterface $connection
     * @param string $table
     * @param string|boolean $identityField
     * @return boolean
     * @throws Exception
     */
    protected function _doLowInsert($metaData, $connection, $table, $identityField)
    {
        if (is_object($metaData) === false ||
            is_object($connection) === false ||
            $metaData instanceof MetaDataInterface === false ||
            $connection instanceof DbAdapterInterface === false ||
            is_string($table) === false) {
            throw new Exception('Invalid parameter type.');
        }

        if (is_string($identityField) === false &&
            is_bool($identityField) === false) {
            throw new Exception('Invalid parameter type.');
        }

        $bindSkip = 1024;
        $fields = array();
        $values = array();
        $bindTypes = array();
        $columnMap = null;

        $attributes = $metaData->getAttributes($this);
        $bindDataTypes = $metaData->getBindTypes($this);
        $automaticAttributes = $metaData->getAutomaticCreateAttributes($this);

        if (isset($GLOBALS['_PHALCON_ORM_COLUMN_RENAMING']) === true &&
            $GLOBALS['_PHALCON_ORM_COLUMN_RENAMING'] === true) {
            $columnMap = $metaData->getColumnMap($this);
        }

        //All fields in the model are part of the INSERT statement
        foreach ($attributes as $field) {
            //Check if the model has a column map
            if (is_array($columnMap) === true) {
                if (isset($columnMap[$field]) === true) {
                    $attributeField = $columnMap[$field];
                } else {
                    throw new Exception("Column '".$field."' isn't part of the column map");
                }
            } else {
                $attributeField = $field;
            }

            //Check every attribute in the model except identity field
            if ($field !== $identityField) {
                $fields[] = $field;

                //This isset check that the property is defined in the model
                if (isset($this->$attributeField) === true) {
                    //Every column must have a bind data type defined
                    if (isset($bindDataTypes[$field]) === false) {
                        throw new Exception("Column '".$field."' has not defined a bind data type");
                    }

                    $value = $this->$attributeField;
                    $values[] = $value;
                    $bindTypes[] = $bindDataTypes[$field];
                } else {
                    $values[] = null;
                    $bindTypes = $bindSkip;
                }
            }
        }

        //If there is an identity field, we add it using "null" or "default"
        $identityFieldIsNotFalse = ($identityField !== false ? true : false);
        if ($identityFieldIsNotFalse === true) {
            $defaultValue = $connection->getDefaultIdValue();

            //Not all database systems require an explicit value for identity columns
            $useExplicitIdentity = $connection->useExplicitIdValue();
            if ($useExplicitIdentity === true) {
                $fields[] = $identityField;
            }

            //Check if the model has a column map
            if (is_array($columnMap) === true) {
                if (isset($columnMap[$identityField]) === true) {
                    $attributeField = $columnMap[$identityField];
                } else {
                    throw new Exception("Identity column '".$identityField."' isn't part of the column map");
                }
            } else {
                $attributeField = $identityField;
            }

            //Check if the developer set an explicit value for the column
            if (isset($this->$attributeField) === true) {
                $value = $this->$attributeField;
                if (empty($value) === true) {
                    if ($useExplicitIdentity === true) {
                        $values[] = $defaultValue;
                        $bindTypes[] = $bindSkip;
                    }
                } else {
                    //Add the explicit value to the field list if the user defined a value for it
                    if ($useExplicitIdentity === false) {
                        $fields[] = $identityField;
                    }

                    //The field is valid - we look for a bind value (normally int)
                    if (isset($bindDataTypes[$identityField]) === false) {
                        throw new Exception("Identity column '".$identityField."' isn't part of the table columns");
                    }

                    $values[] = $value;
                    $bindTypes[] = $bindDataTypes[$identityField];
                }
            } else {
                if ($useExplicitIdentity === true) {
                    $values[] = $defaultValue;
                    $bindTypes[] = $bindSkip;
                }
            }
        }

        //The low-level insert
        $success = $connection->insert($table, $values, $fields, $bindTypes);
        if ($identityFieldIsNotFalse === true) {
            //We check if the model has sequences
            $sequenceName = null;
            $supportSequences = $connection->supportSequences();
            if ($supportSequences == true) {
                if (method_exists($this, 'getSequenceName') === true) {
                    $sequenceName = $this->getSequenceName();
                } else {
                    $source = $this->getSource();
                    $sequenceName = $source.'_'.$identityField.'_seq';
                }
            }

            //Recover the last "insert id" and assign it to the object
            $lastInsertId = $connection->lastInsertId($sequenceName);
            $this->$attributeField = $lastInsertId;

            //Since the primary key was modified, we delete the _uniqueParams to force any
            //future update to rebuild the primary key
            $this->_uniqueParams = null;
        }

        return true;
    }

    /**
     * Sends a pre-build UPDATE SQL statement to the relational database system
     *
     * @param \Phalcon\Mvc\Model\MetaDataInterface $metaData
     * @param \Phalcon\Db\AdapterInterface $connection
     * @param string|array $table
     * @return boolean
     * @throws Exception
     */
    protected function _doLowUpdate($metaData, $connection, $table)
    {
        if (is_object($metaData) === false ||
            is_object($connection) === false ||
            $metaData instanceof MetaDataInterface === false ||
            $connection instanceof DbAdapterInterface === false) {
            throw new Exception('Invalid parameter type.');
        }

        if (is_array($table) === false &&
            is_string($table) === false) {
            throw new Exception('Invalid parameter type.');
        }

        $bindSkip = 1024;
        $fields = array();
        $values = array();
        $bindTypes = array();

        $manager = $this->_modelsManager;
        $isDynamicUpdate = $manager->isDynamicUpdate($this);
        if ($isDynamicUpdate === true) {
            if (is_array($this->_snapshot) === false) {
                $isDynamicUpdate = false;
            }
        }

        $bindDataTypes = $metaData->getBindTypes($this);
        $nonPrimary = $metaData->getNonPrimaryKeysAttributes($this);
        $automaticAttributes = $metaData->getAutomaticUpdateAttributes($this);

        if (isset($GLOBALS['_PHALCON_ORM_COLUMN_RENAMING']) === true &&
            $GLOBALS['_PHALCON_ORM_COLUMN_RENAMING'] === true) {
            $columnMap = $metaData->getColumnMap($this);
        } else {
            $columnMap = null;
        }

        //Update based on the non-primary attributes. Values of the
        //primary key attributes will be ignored.
        foreach ($nonPrimary as $field) {
            if (isset($automaticAttributes[$field]) === false) {
                //Check the bind type for field
                if (isset($bindDataTypes[$field]) === false) {
                    throw new Exception("Column '".$field."' have not defined a bind data type");
                }

                //Check if the model has a column map
                if (is_array($columnMap) === true) {
                    if (isset($columnMap[$field]) === true) {
                        $attributeField = $columnMap[$field];
                    } else {
                        throw new Exception("Column '".$field."' isn't part of the column map");
                    }
                } else {
                    $attributeField = $field;
                }

                //If a field isn't set we pass a null value
                if (isset($this->$attributeField) === true) {
                    //Get the fields value
                    $value = $this->$attributeField;

                    //When dynamic update is not used we pass every field to the update
                    if ($isDynamicUpdate === false) {
                        $fields[] = $field;
                        $values[] = $value;
                        $bindTypes[] = $bindDataTypes[$field];
                    } else {
                        //If the field is not part of the snapshot we add them as changed
                        if (isset($this->_snapshot[$attributeField]) === false) {
                            $changed = true;
                        } else {
                            if ($value !== $this->_snapshot[$attributeField]) {
                                $changed = true;
                            } else {
                                $changed = false;
                            }
                        }

                        //Only changed values are added to the SQL update
                        if ($changed === true) {
                            $fields[] = $field;
                            $values[] = $value;
                            $bindTypes[] = $bindDataTypes[$field];
                        }
                    }
                } else {
                    $fields[] = $field;
                    $values[] = null;
                    $bindTypes[] = $bindSkip;
                }
            }
        }

        //If there is no field to update we return true
        if (count($fields) === 0) {
            return true;
        }

        $uniqueKey = $this->_uniqueKey;
        $uniqueParams = $this->_uniqueParams;
        $uniqueTypes = $this->_uniqueTypes;

        //When unique params is null we need to rebuild the bind params
        if (is_array($this->_uniqueParams) === false) {
            $uniqueParams = array();
            $primaryKeys = $metaData->getPrimaryKeyAttributes($this);

            //We can't create dynamic SQL without a primary key
            if (empty($primaryKeys) === true) {
                throw new Exception('A primary key must be defined in the model in order to perform the operation');
            }

            foreach ($primaryKeys as $field) {
                //Check if the model has a column map
                if (is_array($columnMap) === true) {
                    if (isset($columnMap[$field]) === true) {
                        $attributeField = $columnMap[$field];
                    } else {
                        throw new Exception("Column '".$field."' isn't part of the column map");
                    }
                } else {
                    $attributeField = $field;
                }

                if (isset($this->$attributeField) === true) {
                    $value = $this->$attributeField;
                    $unqiueParams[] = $value;
                } else {
                    $unqiueParams[] = null;
                }
            }
        }

        //We build the conditions as an array
        $conditions = array('conditions' => $uniqueKey, 'bind' => $unqiueParams, 'bindtypes' => $uniqueTypes);

        //Perform the low-level update
        return $connection->update($table, $fields, $values, $conditions, $bindTypes);
    }

    /**
     * Get messages from model
     *
     * @param object $model
     * @param object $target
     * @return boolean
    */
    private static function getMessagesFromModel($pointer, $model, $target)
    {
        try {
            $messages = $model->getMessages();

            if (is_array($messages) === false) {
                return false;
            }

            foreach ($messages as $message) {
                if (is_object($message) === true) {
                    $message->setModel($target);
                }

                $pointer->appendMessage($message);
            }
        } catch (\Exception $e) {
            return false;
        }

        return true;
    }

    /**
     * Saves related records that must be stored prior to save the master record
     *
     * @param \Phalcon\Db\AdapterInterface $connection
     * @param \Phalcon\Mvc\ModelInterface[] $related
     * @return boolean
     * @throws Exception
     */
    protected function _preSaveRelatedRecords($connection, $related)
    {
        if (is_object($connection) === false ||
            $connection instanceof DbAdapterInterface === false ||
            is_array($related) === false) {
            throw new Exception('Invalid parameter type.');
        }

        //Start an implicit transaction
        $connection->begin(false);
        $manager = $this->getModelsManager();

        foreach ($related as $name => $record) {
            //Try to get a relation with the same name
            $relation = $manager->getRelationByAlias(__CLASS__, $name);
            if (is_object($relation) === true) {
                //Get the relation type
                $type = $relation->getType();

                //Only belongsTo are stored before saving the master record
                if ($type === 0) {
                    if (is_object($record) === false) {
                        $connection->rollback(false);
                        throw new Exception('Only objects can be stored as part of belongs-to relations');
                    }

                    $columns = $relation->getFields();
                    $referencedModel = $relation->getReferencedModel();
                    $referencedFields = $relation->getReferencedFields();
                    if (is_array($columns) === true) {
                        $connection->rollback(false);
                        throw new Exception('Not implemented');
                    }

                    //If dynamic update is enabled, saving the record must not take some action
                    if ($record->save() === false) {
                        //Get the validation messages generated by the referenced model
                        if (self::getMessagesFromModel($this, $record, $record) === false) {
                            return;
                        }

                        //Rollback the implicit transaction
                        $connection->rollback(false);
                        return false;
                    }

                    //Read the attribute from the referenced model and assign it to the current model
                    $this->$columns = $record->readAttribute($referencedFields);
                }
            }
        }

        return true;
    }

    /**
     * Save the related records assigned in the has-one/has-many relations
     *
     * @param \Phalcon\Db\AdapterInterface $connection
     * @param \Phalcon\Mvc\ModelInterface[] $related
     * @return boolean|null
     * @throws Exception
     */
    protected function _postSaveRelatedRecords($connection, $related)
    {
        if (is_object($connection) === false ||
            $connection instanceof DbAdapterInterface === false ||
            is_array($related) === false) {
            throw new Exception('Invalid parameter type.');
        }

        $nesting = false;
        $newInstance = true;
        $manager = $this->getModelsManager();

        foreach ($related as $name => $record) {
            //Try to get a relation with the same name
            $relation = $manager->getRelationByAlias(__CLASS__, $name);
            if (is_object($relation) === true) {
                $type = $relation->getType();

                //Discard belongsTo relation
                if ($type === 0) {
                    continue;
                }

                if (is_object($record) === false && is_array($record) === false) {
                    $connection->rollback($nesting);
                    throw new Exception("Only objects/arrays can be stored as part of has-many/has-one/has-many-to-many relations");
                }

                $columns = $relation->getFields();
                $referencedModel = $relation->getReferencedModel();
                $referencedFields = $relation->getReferencedFields();

                if (is_array($columns) === true) {
                    $connection->rollback($nesting);
                    throw new Exception('Not implemented');
                }

                //Create an implicit array for has-many/has-one records
                if (is_object($record) === true) {
                    $relatedRecord = array($record);
                } else {
                    $relatedRecord = $record;
                }

                if (isset($this->$columns) === false) {
                    $connection->rollback($nesting);
                    throw new Exception("The column '".$columns."' needs to be present in the model");
                }

                //Get the value of the field from the current model
                $value = $this->$columns;

                //Check if the relation is has-many-to-amy
                $isThrough = $relation->isThrough();

                //Get the rest of intermediate model info
                if ($isThrough === true) {
                    $intermediateModelName = $relation->getIntermediateModel();
                    $intermediateFields = $relation->getIntermediateFields();
                    $intermediateReferencedFields = $relation->getIntermediateReferencedFields();
                }

                foreach ($relatedRecord as $recordAfter) {
                    //For non has-many-to-many relations just assign the local value in the referenced
                    //model
                    if ($isThrough === false) {
                        //Assign the value
                        $recordAfter->writeAttribute($referencedFields, $value);
                    }

                    //Save the record and get messages
                    if ($recordAfter->save() === false) {
                        //Get the validation messages generated by the referenced model
                        if (self::getMessagesFromModel($this, $recordAfter, $record) === false) {
                            return;
                        }

                        //Rollback the implicit transaction
                        $connection->rollback($nesting);
                        return false;
                    }

                    if ($isThrough === true) {
                        //Create a new instance of the intermediate model
                        $intermediateModel = $manager->load($intermediateModelName, $newInstance);

                        //Write values in the intermediate model
                        $intermediateModel->writeAttribute($intermediateFields, $value);

                        //Get the value from the referenced model
                        $intermediateValue = $recordAfter->$referencedFields;

                        //Write the intermediate value in the intermediate model
                        $intermediateModel->writeAttribute($intermediateReferencedFields, $intermediateValue);

                        //Save the record and get messages
                        if ($intermediateModel->save() === false) {
                            //Get the validation messages generated by the referenced model
                            if (self::getMessagesFromModel($this, $intermediateModel, $record) === false) {
                                return;
                            }

                            //Rollback the implicit transaction
                            $connection->rollback($nesting);
                            return false;
                        }
                    }
                }
            } else {
                if (is_array($record) === false) {
                    $connection->rollback($nesting);
                    throw new Exception('There are no defined relations for the model "'.__CLASS__.'" using alias "'.$name.'"');
                }
            }
        }

        //Commit the implicit transaction
        $connection->commit($nesting);
        return true;
    }

    /**
     * Inserts or updates a model instance. Returning true on success or false otherwise.
     *
     *<code>
     *  //Creating a new robot
     *  $robot = new Robots();
     *  $robot->type = 'mechanical';
     *  $robot->name = 'Astro Boy';
     *  $robot->year = 1952;
     *  $robot->save();
     *
     *  //Updating a robot name
     *  $robot = Robots::findFirst("id=100");
     *  $robot->name = "Biomass";
     *  $robot->save();
     *</code>
     *
     * @param array|null $data
     * @param array|null $whiteList
     * @return boolean
     * @throws Exception
     */
    public function save($data = null, $whiteList = null)
    {
        if (is_array($whiteList) === false &&
            is_null($whiteList) === false) {
            throw new Exception('Invalid parameter type.');
        }

        $metaData = $this->getModelsMetaData();

        if (is_array($data) === true) {
            //Get the reversed column map for future remainings
            $attributes = $metaData->getColumnMap($this);

            if (is_array($attributes) === false) {
                //Use the standard column map if there are no renamings
                $attributes = $metaData->getAttributes($this);
            }

            foreach ($attributes as $attribute) {
                if (isset($data[$attribute]) === true) {
                    //If the whitelist is an array, check if the attribute is on that list
                    if (is_array($whiteList) === true && in_array($attribute, $whiteList) === false) {
                        continue;
                    }

                    //We check if the field has a setter
                    $value = $data[$attribute];
                    $possibleSetter = 'set'.$attribute;

                    if (method_exists($this, $possibleSetter) === true) {
                        $this->$possibleSetter($value);
                    } else {
                        //Otherwise we assign the attribute directly
                        $this->$attribute = $value;
                    }
                }
            }
        } elseif (is_null($data) === false) {
            throw new Exception('Data passed to save() must be an array');
        }

        //Create/Get the current database connection
        $writeConnection = $this->getWriteConnection();

        //Save related records in belongsTo relationships
        if (is_array($this->_related) === true) {
            if ($this->_preSaveRelatedRecords($writeConnection, $this->_related) === false) {
                return false;
            }
        }

        $schema = $this->getSchema();
        $source = $this->getSource();

        if ($schema == true) {
            $table = array($schema, $source);
        } else {
            $table = $source;
        }

        //Create/Get the current database connection
        $readConnection = $this->getReadConnection();

        //We need to check if the records exists
        $exists = $this->_exists($metaData, $readConnection, $table);
        if ($exists === true) {
            $this->_operationMade = 2;
        } else {
            $this->_operationMade = 1;
        }

        //Clean the messages
        $this->_errorMessages = array();

        //Query the identity field
        $identityField = $metaData->getIdentityField($this);

        //_preSave() makes all the validations
        if ($this->_preSave($metaData, $exists, $identityField) === false) {
            //Rollback the current transaction if there was validation error
            if (is_array($this->_related) === true) {
                $writeConnection->rollback(false);
            }

            //Throw exceptions on failed saves
            if (isset($GLOBALS['_PHALCON_ORM_EXCEPTION_ON_FAILED_SAVE']) === true &&
                $GLOBALS['_PHALCON_ORM_EXCEPTION_ON_FAILED_SAVE'] === true) {
                //Launch a Phalcon\Mvc\Model\ValidationFailed to notify that the save failed
                throw new ValidationFailed($this, $this->_errorMessages);
            }

            return false;
        }

        //Depending if the record exists we do an update or an insert operation
        if ($exists === true) {
            $success = $this->_doLowUpdate($metaData, $writeConnection, $table);
        } else {
            $success = $this->_doLowInsert($metaData, $writeConnection, $table, $identityField);
        }

        //Change the dirty state to persistent
        if ($success === true) {
            $this->_dirtyState = 0;
        }

        //_postSave() makes all the validations
        if (isset($GLOBALS['_PHALCON_ORM_EVENTS']) === true &&
            $GLOBALS['_PHALCON_ORM_EVENTS'] === true) {
            $success = $this->_postSave($success, $exists);
        }

        if (is_array($this->_related) === true) {
            //Rollbacks the implicit transaction if the master save has failed
            if ($success === false) {
                $writeConnection->rollback(false);
                return false;
            }

            //Save the post-related records
            if ($this->_postSaveRelatedRecords($writeConnection, $this->_related) === false) {
                return false;
            }
        }

        return $success;
    }

    /**
     * Inserts a model instance. If the instance already exists in the persistance it will throw an exception
     * Returning true on success or false otherwise.
     *
     *<code>
     *  //Creating a new robot
     *  $robot = new Robots();
     *  $robot->type = 'mechanical';
     *  $robot->name = 'Astro Boy';
     *  $robot->year = 1952;
     *  $robot->create();
     *
     *  //Passing an array to create
     *  $robot = new Robots();
     *  $robot->create(array(
     *      'type' => 'mechanical',
     *      'name' => 'Astroy Boy',
     *      'year' => 1952
     *  ));
     *</code>
     *
     * @param array|null $data
     * @param array|null $whiteList
     * @return boolean
     * @throws Exception
     */
    public function create($data = null, $whiteList = null)
    {
        if (is_array($whiteList) === false &&
            is_null($whiteList) === false) {
            throw new Exception('Invalid parameter type.');
        }

        $metaData = $this->getModelsMetaData();

        //Assign the values passed
        if (is_null($data) === false) {
            if (is_array($data) === false) {
                throw new Exception('Data passed to create() must be an array');
            }

            if (isset($GLOBALS['_PHALCON_ORM_COLUMN_RENAMING']) === true &&
                $GLOBALS['_PHALCON_ORM_COLUMN_RENAMING'] === true) {
                $columnMap = $metaData->getColumnMap($this);
            } else {
                $columnMap = null;
            }

            //We assign the fields starting from the current attributes in the model
            $attributes = $metaData->getAttributes($this);
            foreach ($attributes as $attribute) {
                //Check if we need to rename the field
                if (is_array($columnMap) === true) {
                    if (isset($columnMap[$attribute]) === true) {
                        $attributeField = $columnMap[$attribute];
                    } else {
                        throw new Exception("Column '".$attribute."' isn't part of the column map");
                    }
                } else {
                    $attributeField = $attribute;
                }

                //Check if there is data for the field
                if (isset($data[$attributeField]) === true) {
                    //If the whiteliste is an array check if the attribute is on that
                    if (is_array($whiteList) === true &&
                        in_array($attributeField, $whiteList) === false) {
                        continue;
                    }

                    //The value in the array passed
                    $value = $data[$attributeField];

                    //Check if the field has a possible setter
                    $possibleSetter = 'set'.$attributeField;

                    if (method_exists($this, $possibleSetter) === true) {
                        $this->$possibleSetter($value);
                    } else {
                        $this->$attributeField = $value;
                    }
                }
            }
        }

        //Get the current connection
        $readConnection = $this->getReadConnection();

        //A 'exists' confirmation is performed first
        $exists = $this->_exists($metaData, $readConnection);

        //If the record already exists we must throw an exception
        if ($exists === true) {
            $modelMessage = new Message('Record cannot be created because it already exists', null, 'InvalidCreateAttempt');
            $this->_errorMessages = array($modelMessage);
            return false;
        }

        //Using save() anyways
        return $this->save();
    }

    /**
     * Updates a model instance. If the instance doesn't exist in the persistance it will throw an exception
     * Returning true on success or false otherwise.
     *
     *<code>
     *  //Updating a robot name
     *  $robot = Robots::findFirst("id=100");
     *  $robot->name = "Biomass";
     *  $robot->update();
     *</code>
     *
     * @param array|null $data
     * @param array|null $whiteList
     * @return boolean
     * @throws Exception
     */
    public function update($data = null, $whiteList = null)
    {
        if (is_array($whiteList) === false &&
            is_null($whiteList) === false) {
            throw new Exception('Invalid parameter type.');
        }

        $metaData = null;

        //Assign the values based on the passed
        if (is_null($data) === false) {
            if (is_array($data) === false) {
                throw new Exception('Data passed to update() must be an array');
            }

            $metaData = $this->getModelsMetaData();
            if (isset($GLOBALS['_PHALCON_ORM_COLUMN_RENAMING']) === true &&
                $GLOBALS['_PHALCON_ORM_COLUMN_RENAMING'] === true) {
                $columnMap = $metaData->getColumnMap($this);
            } else {
                $columnMap = null;
            }

            //We assign the fields starting form the current attributes in the model
            $attributes = $metaData->getAttributes($this);
            foreach ($attributes as $attribute) {
                //Check if we need to rename the field
                if (is_array($columnMap) === true) {
                    if (isset($columnMap[$attribute]) === true) {
                        $attributeField = $columnMap[$attribute];
                    } else {
                        throw new Exception("Column '".$attribute."' isn't part of the column map");
                    }
                } else {
                    $attributeField = $attribute;
                }

                //Check if there is data for the field
                if (isset($data[$attributeField]) === true) {
                    //If the whitelist is an array check if the attribute is on that list
                    if (is_array($whiteList) === true &&
                        in_array($attributeField, $whiteList) === false) {
                            continue;
                    }

                    //Read the attribute from the data
                    $value = $data[$attributeField];

                    //Try to find a possible setter
                    $possibleSetter = 'set'.$attributeField;
                    if (method_exists($this, $possibleSetter) === true) {
                        $this->$possibleSetter($value);
                    } else {
                        $this->$attributeField = $value;
                    }
                }
            }
        }

        //We don't check if the record exists, if the record is already checked
        if ($this->_dirtyState == true) {
            if (is_null($metaData) === true) {
                $metaData = $this->getModelsMetaData();
            }

            $readConnection = $this->getReadConnection();
            $exists = $this->_exists($metaData, $readConnection);
            if ($exists === false) {
                $this->_errorMessages = array(new Message('Record cannot be updated because it does not exist', null, 'InvalidUpdateAttempt'));
                return false;
            }
        }

        //Call save() anyways
        return $this->save();
    }

    /**
     * Deletes a model instance. Returning true on success or false otherwise.
     *
     * <code>
     *$robot = Robots::findFirst("id=100");
     *$robot->delete();
     *
     *foreach (Robots::find("type = 'mechanical'") as $robot) {
     *   $robot->delete();
     *}
     * </code>
     *
     * @return boolean
     * @throws Exception
     */
    public function delete()
    {
        $metaData = $this->getModelsMetaData();
        $writeConnection = $this->getWriteConnection();
        $this->_errorMessages = array();

        //Operation made is OP_DELETE
        $this->_operationMade = 3;

        //Check if deleting the record violates a virtual foreign key
        if (isset($GLOBALS['_PHALCON_ORM_VIRTUAL_FOREIGN_KEYS']) === true &&
            $GLOBALS['_PHALCON_ORM_VIRTUAL_FOREIGN_KEYS'] === true &&
            $this->_checkForeignKeysReverseRestrict() === false) {
            return false;
        }

        $value = array();
        $bindTypes = array();
        $conditions = array();
        $primaryKeys = $metaData->getPrimaryKeyAttributes($this);
        $bindDataTypes = $metaData->getBindTypes($this);

        if (isset($GLOBALS['_PHALCON_ORM_COLUMN_RENAMING']) === true &&
            $GLOBALS['_PHALCON_ORM_COLUMN_RENAMING'] === true) {
            $columnMap = $metaData->getColumnMap($this);
        } else {
            $columnMap = null;
        }

        //We can't create dynamic SQL without a primary key
        if (count($primaryKeys) === 0) {
            throw new Exception('A primary key must be defined in the model in order to perform the operation');
        }

        //Create a condition from the primary keys
        foreach ($primaryKeys as $primaryKey) {
            //Every column part of the primary key must be in the bind_data_types
            if (isset($bindDataTypes[$primaryKey]) === false) {
                throw new Exception("Column '".$primaryKey."' have not defined a bind data type");
            }

            //Take the column values based on the column map if any
            if (is_array($columnMap) === true) {
                if (isset($columnMap[$primaryKey]) === true) {
                    $attributeField = $columnMap[$primaryKey];
                } else {
                    throw new Exception("Column '".$primaryKey."' isn't part of the column map");
                }
            } else {
                $attributeField = $primaryKey;
            }

            //If the attribute is currently set in the object add it to the conditions
            if (isset($this->$attributeField) === false) {
                throw new Exception("Cannot delete the record because the priamry key attribute: '".$attributeField."' wasn't set");
            }

            $values[] = $this->$attributeField;

            //Escape the column identifier
            $conditions[] = $writeConnection->escapeIdentifier($primaryKey).' = ?';
            $bindTypes[] = $bindDataTypes[$primaryKey];
        }

        //Join the conditions in the array using an AND operator
        $deleteConditions = implode(' AND ', $conditions);

        if (isset($GLOBALS['_PHALCON_ORM_EVENTS']) === true &&
            $GLOBALS['_PHALCON_ORM_EVENTS'] === true) {
            $this->_skipped = false;

            //Fire the beforeDelete event
            if ($this->fireEventCancel('beforeDelete') === false) {
                return false;
            } else {
                //The operation can be skipped
                if ($this->_skipped === true) {
                    return true;
                }
            }
        }

        $schema = $this->getSchema();
        $source = $this->getSource();

        if ($schema == true) {
            $table = array($schema, $source);
        } else {
            $table = $source;
        }

        //Do the deletion
        $success = $writeConnection->delete($table, $deleteConditions, $values, $bindTypes);

        //Check if there is a virtual foreign key with cascade action
        if (isset($GLOBALS['_PHALCON_ORM_VIRTUAL_FOREIGN_KEYS']) === true &&
            $GLOBALS['_PHALCON_ORM_VIRTUAL_FOREIGN_KEYS'] === true) {
            if ($this->_checkForeignKeysReverseCascade() === false) {
                return false;
            }
        }

        if (isset($GLOBALS['_PHALCON_ORM_EVENTS']) === true &&
            $GLOBALS['_PHALCON_ORM_EVENTS'] === true &&
            $success === true) {
                $this->fireEvent('afterDelete');
        }

        //Force perform the record existence check again
        $this->_dirtyState = 2;

        return $success;
    }

    /**
     * Returns the type of the latest operation performed by the ORM
     * Returns one of the OP_* class constants
     *
     * @return int
     */
    public function getOperationMade()
    {
        return $this->_operationMade;
    }

    /**
     * Refreshes the model attributes re-querying the record from the database
     *
     * @throws Exception
     */
    public function refresh()
    {
        if ($this->_dirtyState !== 0) {
            throw new Exception('The record cannot be refreshed because it does not exist or is deleted');
        }

        $metaData = $this->getModelsMetaData();
        $readConnection = $this->getReadConnection();
        $schema = $this->getSchema();
        $source = $this->getSource();

        if ($schema == true) {
            $table = array($schema, $source);
        } else {
            $table = $source;
        }

        $uniqueKey = $this->_uniqueKey;
        if (isset($uniqueKey) === false) {
            //We need to check if the record exists
            $exists = $this->_exists($metaData, $readConnection, $table);
            if ($exists !== true) {
                throw new Exception('The record cannot be refreshed because it does not exist or is deleted');
            }

            $uniqueKey = $this->_uniqueKey;
        }

        $uniqueParams = $this->_uniqueParams;
        if (is_array($uniqueParams) === false) {
            throw new Exception('The record cannot be refreshed because it does not exist or is deleted');
        }

        $uniqueTypes = $this->_uniqueTypes;

        //We only refresh the attributes in the model's metadata
        $attributes = $metaData->getAttributes($this);
        $fields = array();

        foreach ($attributes as $attribute) {
            $fields[] = array($attribute);
        }

        //We directly build the SELECT to save resources
        $dialect = $readConnection->getDialect();
        $sql = $dialect->select(array('columns' => $fields, 'tables' => $readConnection->escapeIdentifier($table),
         'where' => $uniqueKey));
        $row = $readConnection->fetchOne($sql, 1, $uniqueParams, $uniqueTypes);

        //Get a column map if any
        $columnMap = $metaData->getColumnMap($this);

        //Assign the resulting array to the $this object
        if (is_array($row) === true) {
            $this->assign($row, $columnMap);
        }
    }

    /**
     * Skips the current operation forcing a success state
     *
     * @param boolean $skip
     * @throws Exception
     */
    public function skipOperation($skip)
    {
        if (is_bool($skip) === false) {
            throw new Exception('Invalid parameter type.');
        }

        $this->_skipped = $skip;
    }

    /**
     * Reads an attribute value by its name
     *
     * <code>
     * echo $robot->readAttribute('name');
     * </code>
     *
     * @param string $attribute
     * @return mixed
     * @throws Exception
     */
    public function readAttribute($attribute)
    {
        if (is_string($attribute) === false) {
            throw new Exception('Invalid parameter type.');
        }

        if (isset($this->$attribute) === true) {
            return $this->$attribute;
        }

        return null;
    }

    /**
     * Writes an attribute value by its name
     *
     * <code>
     *  $robot->writeAttribute('name', 'Rosey');
     * </code>
     *
     * @param string $attribute
     * @param mixed $value
     * @throws Exception
     */
    public function writeAttribute($attribute, $value)
    {
        if (is_string($attribute) === false) {
            throw new Exception('Invalid parameter type.');
        }

        $this->$attribute = $value;
    }

    /**
     * Sets a list of attributes that must be skipped from the
     * generated INSERT/UPDATE statement
     *
     *<code>
     *
     *class Robots extends \Phalcon\Mvc\Model
     *{
     *
     *   public function initialize()
     *   {
     *       $this->skipAttributes(array('price'));
     *   }
     *
     *}
     *</code>
     *
     * @param array $attributes
     * @param boolean|null $replace
     * @throws Exception
     */
    protected function skipAttributes($attributes, $replace = null)
    {
        if (is_array($attributes) === false) {
            throw new Exception('Attributes must be an array');
        }

        if (is_null($replace) === true) {
            $replace = false;
        } elseif (is_bool($replace) === false) {
            throw new Exception('Invalid parameter type.');
        }

        $keysAttributes = array();

        foreach ($attributes as $attribute) {
            $keysAttributes[$attribute] = null;
        }

        $metaData = $this->getModelsMetaData();
        $metaData->setAutomaticCreateAttributes($this, $keysAttributes, $replace);
        $metaData->setAutomaticUpdateAttributes($this, $keysAttributes, $replace);
    }

    /**
     * Sets a list of attributes that must be skipped from the
     * generated INSERT statement
     *
     *<code>
     *
     *class Robots extends \Phalcon\Mvc\Model
     *{
     *
     *   public function initialize()
     *   {
     *       $this->skipAttributesOnCreate(array('created_at'));
     *   }
     *
     *}
     *</code>
     *
     * @param array $attributes
     * @param null|boolean $replace
     * @throws Exception
     */
    protected function skipAttributesOnCreate($attributes, $replace = null)
    {
        if (is_array($attributes) === false) {
            throw new Exception('Attributes must be an array');
        }

        if (is_null($replace) === true) {
            $replace = false;
        } elseif (is_bool($replace) === false) {
            throw new Exception('Invalid parameter type.');
        }

        $keysAttributes = array();

        foreach ($attributes as $attribute) {
            $keysAttributes[$attribute] = null;
        }

        $metaData = $this->getModelsMetaData();
        $metaData->setAutomaticCreateAttributes($this, $keysAttributes, $replace);
    }

    /**
     * Sets a list of attributes that must be skipped from the
     * generated UPDATE statement
     *
     *<code>
     *
     *class Robots extends \Phalcon\Mvc\Model
     *{
     *
     *   public function initialize()
     *   {
     *       $this->skipAttributesOnUpdate(array('modified_in'));
     *   }
     *
     *}
     *</code>
     *
     * @param array $attributes
     * @param null|boolean $replace
     * @throws Exception
     */
    protected function skipAttributesOnUpdate($attributes, $replace = null)
    {
        if (is_array($attributes) === false) {
            throw new Exception('Attributes must be an array');
        }

        if (is_null($replace) === true) {
            $replace = false;
        } elseif (is_bool($replace) === false) {
            throw new Exception('Invalid parameter type.');
        }

        $keysAttributes = array();

        foreach ($attributes as $attribute) {
            $keysAttributes[$attribute] = null;
        }

        $metaData = $this->getModelsMetaData();
        $metaData->setAutomaticUpdateAttributes($this, $keysAttributes, $replace);
    }

    /**
     * Setup a 1-1 relation between two models
     *
     *<code>
     *
     *class Robots extends \Phalcon\Mvc\Model
     *{
     *
     *   public function initialize()
     *   {
     *       $this->hasOne('id', 'RobotsDescription', 'robots_id');
     *   }
     *
     *}
     *</code>
     *
     * @param mixed $fields
     * @param string $referenceModel
     * @param mixed $referencedFields
     * @param array $options
     * @return \Phalcon\Mvc\Model\Relation
     */
    public function hasOne($fields, $referenceModel, $referencedFields, $options)
    {
        return $this->_modelsManager->addHasOne($this, $fields, $referenceModel, $referencedFields, $options);
    }

    /**
     * Setup a relation reverse 1-1  between two models
     *
     *<code>
     *
     *class RobotsParts extends \Phalcon\Mvc\Model
     *{
     *
     *   public function initialize()
     *   {
     *       $this->belongsTo('robots_id', 'Robots', 'id');
     *   }
     *
     *}
     *</code>
     *
     * @param mixed $fields
     * @param string $referenceModel
     * @param mixed $referencedFields
     * @param array|null $options
     * @return \Phalcon\Mvc\Model\Relation
     */
    public function belongsTo($fields, $referenceModel, $referencedFields, $options = null)
    {
        return $this->_modelsManager->addBelongsTo($this, $fields, $referenceModel, $referencedFields, $options);
    }

    /**
     * Setup a relation 1-n between two models
     *
     *<code>
     *
     *class Robots extends \Phalcon\Mvc\Model
     *{
     *
     *   public function initialize()
     *   {
     *       $this->hasMany('id', 'RobotsParts', 'robots_id');
     *   }
     *
     *}
     *</code>
     *
     * @param mixed $fields
     * @param string $referenceModel
     * @param mixed $referencedFields
     * @param array|null $options
     * @return \Phalcon\Mvc\Model\Relation
     */
    public function hasMany($fields, $referenceModel, $referencedFields, $options = null)
    {
        return $this->_modelsManager->addHasMany($this, $fields, $referenceModel, $referencedFields, $options);
    }

    /**
     * Setup a relation n-n between two models through an intermediate relation
     *
     *<code>
     *
     *class Robots extends \Phalcon\Mvc\Model
     *{
     *
     *   public function initialize()
     *   {
     *       //Setup a many-to-many relation to Parts through RobotsParts
     *       $this->hasManyToMany(
     *          'id',
     *          'RobotsParts',
     *          'robots_id',
     *          'parts_id',
     *          'Parts',
     *          'id'
     *      );
     *   }
     *
     *}
     *</code>
     *
     * @param string $fields
     * @param string $intermediateModel
     * @param string $intermediateFields
     * @param string $intermediateReferencedFields
     * @param string $referenceModel
     * @param string $referencedFields
     * @param array|null $options
     * @return \Phalcon\Mvc\Model\Relation
     */
    public function hasManyToMany($fields, $intermediateModel, $intermediateFields, $intermediateReferecedFields, $referenceModel, $referencedFields, $options = null)
    {
        return $this->_modelsManager->addHasManyToMany(
            $this,
            $fields,
            $intermediateModel,
            $intermediateFields,
            $intermediateReferecedFields,
            $referenceModel,
            $referencedFields,
            $options
        );
    }

    /**
     * Setups a behavior in a model
     *
     *<code>
     *
     *use \Phalcon\Mvc\Model\Behavior\Timestampable;
     *
     *class Robots extends \Phalcon\Mvc\Model
     *{
     *
     *   public function initialize()
     *   {
     *      $this->addBehavior(new Timestampable(array(
     *          'onCreate' => array(
     *              'field' => 'created_at',
     *              'format' => 'Y-m-d'
     *          )
     *      )));
     *   }
     *
     *}
     *</code>
     *
     * @param \Phalcon\Mvc\Model\BehaviorInterface $behavior
     */
    public function addBehavior($behavior)
    {
        $this->_modelsManager->addBehavior($this, $behavior);
    }

    /**
     * Sets if the model must keep the original record snapshot in memory
     *
     *<code>
     *
     *class Robots extends \Phalcon\Mvc\Model
     *{
     *
     *   public function initialize()
     *   {
     *      $this->keepSnapshots(true);
     *   }
     *
     *}
     *</code>
     *
     * @param boolean $keepSnapshots
     */
    protected function keepSnapshots($keepSnapshots)
    {
        $this->_modelsManager->keepSnapshots($keepSnapshots);
    }

    /**
     * Sets the record's snapshot data.
     * This method is used internally to set snapshot data when the model was set up to keep snapshot data
     *
     * @param array $data
     * @param array|null $columnMap
     * @throws Exception
     */
    public function setSnapshotData($data, $columnMap = null)
    {
        if (is_array($data) === false) {
            throw new Exception('The snapshot data must be an array');
        }

        //Build the snapshot based on a column map
        if (is_array($columnMap) === true) {
            $snapshot = array();
            foreach ($data as $key => $value) {
                //Use only strings
                if (is_string($key) === false) {
                    continue;
                }

                //Every field must be part of the column map
                if (isset($columnMap[$key]) === false) {
                    throw new Exception('Column "'.$key.'" doesn\'t make part of the column map');
                }

                $snapshot[$columnMap[$key]] = $value;
            }

            $this->_snapshot = $snapshot;
            return;
        } elseif (is_null($columnMap) === false) {
            throw new Exception('Invalid parameter type.');
        }

        $this->_snapshot = $data;
    }

    /**
     * Checks if the object has internal snapshot data
     *
     * @return boolean
     */
    public function hasSnapshotData()
    {
        return (is_array($this->_snapshot) === true ? true : false);
    }

    /**
     * Returns the internal snapshot data
     *
     * @return array|null
     */
    public function getSnapshotData()
    {
        return $this->_snapshot;
    }

    /**
     * Check if a specific attribute has changed
     * This only works if the model is keeping data snapshots
     *
     * @param string|null $fieldName
     * @throws Exception
     */
    public function hasChanged($fieldName = null)
    {
        if (is_array($this->_snapshot) === false) {
            throw new Exception("The record doesn't have a valid data snapshot");
        }

        if (is_string($fieldName) === false &&
            is_null($fieldName) === false) {
            throw new Exception('The field name must be string');
        }

        //Dirty state must be DIRTY_PERSISTENT to make the checking
        if ($this->_dirtyState !== 0) {
            throw new Exception('Change checking cannot be performed because the object has not been persisted or is deleted');
        }

        //Return the models metadata
        $metaData = $this->getModelsMetaData();

        //The reversed column map is an array if the model has a column map
        $columnMap = $metaData->getReverseColumnMap($this);

        //Data types are field indexes
        if (is_array($columnMap) === false) {
            $attributes = $metaData->getDataTypes($this);
            $allAttributes = $attributes;
        } else {
            $allAttributes = $columnMap;
        }

        //If a field was specified we only check it
        if (is_string($fieldName) === true) {
            //We only make this validation over valid fields
            if (is_array($columnMap) === true) {
                if (isset($columnMap[$fieldName]) === false) {
                    throw new Exception("The field '".$fieldName."' is not part of the model");
                }
            } else {
                if (isset($attributes[$fieldName]) === false) {
                    throw new Exception("The field '".$fieldName."' is not part of the model");
                }
            }

            //The field is not part of the model - throw exception
            if (isset($this->$fieldName) === false) {
                throw new Exception("The field '".$fieldName."' is not defined on the model");
            }

            //The field is not part of the data snapshot, throw exception
            if (isset($this->_snapshot[$fieldName]) === false) {
                throw new Exception("The field '".$fieldName."' was not found in the snapshot");
            }

            $value = $this->$fieldName;
            $originalValue = $this->_snapshot[$fieldName];

            //Check if the field has changed
            return ($value == $originalValue ? false : true);
        }

        //Check every attribute in the model
        foreach ($allAttributes as $name => $type) {
            //If an attribute is not present in the snapshot, we assume the record as changed
            if (isset($snapshot[$name]) === false) {
                return true;
            }

            //If some attribute is not present in the model, we assume the record as changed
            if (isset($this->$name) === false) {
                return true;
            }

            $value = $this->$name;
            $originalValue = $this->_snapshot[$name];

            if ($value !== $originalValue) {
                return true;
            }
        }

        return false;
    }

    /**
     * Returns a list of changed values
     *
     * @return array
     * @throws Exception
     */
    public function getChangedFields()
    {
        if (is_array($this->_snapshot) === false) {
            throw new Exception("The record doesn't have a valid data snapshot");
        }

        //Dirty state must be DIRTY_PERSISTENT to make the checking
        if ($this->_dirtyState !== 0) {
            throw new Exception('Change checking cannot be performed because the object has not been persisted or is deleted');
        }

        //return the models metadata
        $metaData = $this->getModelsMetaData();

        //The reversed column map is an array if the model has a column map
        $columnMap = $metaData->getReverseColumnMap($this);

        //Data types are field indexed
        if (is_array($columnMap) === false) {
            $allAttributes = $metaData->getDataTypes($this);
        } else {
            $allAttributes = $columnMap;
        }

        $changed = array();

        //Check every attribute in the model
        foreach ($allAttributes as $name => $type) {
            //If some attribute is not present in the snapshot, we assume the record as
            //changed
            if (isset($this->_snapshot[$name]) === false) {
                $changed[] = $name;
                continue;
            }

            //If some attribute is not present in the model, we assume the record as changed
            if (isset($this->$name) === false) {
                $changed[] = $name;
                continue;
            }

            if ($this->$name !== $this->_snapshot[$name]) {
                $changed[] = $name;
                continue;
            }
        }

        return $changed;
    }

    /**
     * Sets if a model must use dynamic update instead of the all-field update
     *
     *<code>
     *
     *class Robots extends \Phalcon\Mvc\Model
     *{
     *
     *   public function initialize()
     *   {
     *      $this->useDynamicUpdate(true);
     *   }
     *
     *}
     *</code>
     *
     * @param boolean $dynamicUpdate
     */
    protected function useDynamicUpdate($dynamicUpdate)
    {
        $this->_modelsManager->useDynamicUpdate($this, $dynamicUpdate);
    }

    /**
     * Returns related records based on defined relations
     *
     * @param string $alias
     * @param array|null $arguments
     * @return \Phalcon\Mvc\Model\ResultsetInterface
     * @throws Exception
     */
    public function getRelated($alias, $arguments = null)
    {
        if (is_string($alias) === false) {
            throw new Exception('Invalid parameter type.');
        }

        if (is_array($arguments) === false &&
            is_null($arguments) === false) {
            throw new Exception('Invalid parameter type.');
        }

        //Query the relation by alias
        $relation = $this->_modelsManager->getRelationByAlias(__CLASS__, $alias);
        if (is_object($relation) === false) {
            throw new Exception('There is no defined relations for the model "'.__CLASS__.'" using alias "'.$alias.'"'); //@note sic
        }

        //Call the 'getRelationRecords' in the model manager
        return call_user_func_array(
            array(
                $this->_modelsManager,
                'getRelationRecords'
            ),
            array(
                $relation,
                null,
                $this,
                $arguments)
        );
    }

    /**
     * Returns related records defined relations depending on the method name
     *
     * @param string $modelName
     * @param string $method
     * @param array $arguments
     * @return mixed
     * @throws Exception
     */
    protected function _getRelatedRecords($modelName, $method, $arguments)
    {
        if (is_string($modelName) === false &&
            is_string($method) === false &&
            is_array($arguments) === false) {
            throw new Exception('Invalid parameter type.');
        }

        $relation = null;
        $queryMethod = null;

        //Calling find/findFirst if the method starts with "get"
        if (strpos($method, 'get') === 0) {
            $relation = $this->_modelsManager->getRelationByAlias($modelName, substr($method, 3));
        }

        //Calling count if the method starts with "count"
        if (is_object($relation) === false && strpos($method, 'count') === 0) {
            $queryMethod = 'count';
            $relation = $this->_modelsManager->getRelationByAlias($modelName, substr($method, 5));
        }

        //If the relation was found perform the query via the models manager
        if (is_object($relation) === true) {
            if (isset($arguments[0]) === true) {
                $extraArgs = $arguments[0];
            } else {
                $extraArgs = null;
            }

            return call_user_func_array(
                array(
                    $this->_modelsManager,
                    'getRelationRecords'
                    ),
                array(
                    $relation,
                    $queryMethod,
                    $this,
                    $extraArgs
                )
            );
        }

        return null;
    }

    /**
     * Handles method calls when a method is not implemented
     *
     * @param string $method
     * @param array|null $arguments
     * @return mixed
     * @throws Exception
     */
    public function __call($method, $arguments = null)
    {
        if (is_string($method) === false) {
            throw new Exception('Invalid parameter type.');
        }

        if (is_null($arguments) === true) {
            $arguments = array();
        } elseif (is_array($arguments) === false) {
            throw new Exception('Invalid parameter type.');
        }

        //Check if there is a default action using the magic getter
        $records = $this->_getRelatedRecords(__CLASS__, $method, $arguments);
        if (is_null($records) === false) {
            return $records;
        }

        //Try to find a replacement for the missing method in a behavior/listener
        $status = $this->_modelsManager->missingMethod($this, $method, $arguments);
        if (is_null($status) === false) {
            return $status;
        }

        //The method doesn't exist - throw an exception
        throw new Exception('The method "'.$method.'" doesn\'t exist on model "'.__CLASS__.'"');
    }

    /**
     * Handles method calls when a static method is not implemented
     *
     * @param string $method
     * @param array|null $arguments
     * @return mixed
     * @throws Exception
     */
    public static function __callStatic($method, $arguments = null)
    {
        if (is_string($method) === false) {
            throw new Exception('Invalid parameter type.');
        }

        if (is_array($arguments) === false &&
            is_null($arguments) === false) {
            throw new Exception('Invalid parameter type.');
        }

        $extraMethod = null;
        $type = null;

        if (strpos($method, 'findFirstBy') === 0) {
            $type = 'findFirst';
            $extraMethod = substr($method, 11);
        } elseif (strpos($method, 'findBy') === 0) {
            $type = 'find';
            $extraMethod = substr($method, 6);
        } elseif (strpos($method, 'countBy') === 0) {
            $type = 'count';
            $extraMethod = substr($method, 7);
        }

        $modelName = get_called_class();

        if (is_null($extraMethod) === true) {
            //The method doesn't exist - throw an exception
            throw new Exception('The static method "'.$method.'" doesn\'t exist on model"'.$modelName.'"');
        }

        if (isset($arguments[0]) === false) {
            throw new Exception('The static method "'.$method.'" requires one argument');
        }

        $value = $arguments[0];
        $model = new $modelName();

        //Get the model's metadata
        $metaData = $model->getModelsMetaData();

        //Get the attributes
        $attributes = $metaData->getReverseColumnMap($model);
        if (is_array($attributes) === false) {
            //Use the standard attributes if there is no column map available
            $attributes = $metaData->getDataTypes($model);
        }

        //Check if the extra-method is an attribute
        if (isset($attributes[$extraMethod]) === true) {
            $field = $extraMethod;
        } else {
            //Lowercase the first letter of the extra-method
            $extraMethodFirst = lcfirst($extraMethod);
            if (isset($attributes[$extraMethodFirst]) === true) {
                $field = $extraMethodFirst;
            } else {
                //Get the possible real method name
                $field = Text::uncamelize($extraMethod);
                if (isset($attributes[$field]) === false) {
                    throw new Exception('Cannot resolve attribute "'.$extraMethod.'" in the model');
                }
            }
        }

        //Execute the query (static call)
        return call_user_func_array(
            array($modelName, $type),
            array('conditions' => $field.' = ?0', 'bind' => array($value))
        );
    }

    /**
     * Magic method to assign values to the the model
     *
     * @param string $property
     * @param mixed $value
     * @throws Exception
     */
    public function __set($property, $value)
    {
        if (is_string($property) === false) {
            throw new Exception('Invalid parameter type.');
        }

        //Values are probably relationships if they are objects
        if (is_object($value) === true && $value instanceof ModelInterface === true) {
            $lowerProperty = strtolower($property);
            $this->$lowerProperty = $value;
            $this->_related[$lowerProperty] = $value;
            $this->_dirtyState = 1;
            return $value;
        }

        //Check if the value is an array
        if (is_array($value) === true) {
            $lowerProperty = strtolower($property);
            $this->_related[$lowerProperty] = $value; //@note ???
            $this->_dirtyState = 1;
            return $value;
        }

        $this->$property = $value;

        return $value;
    }

    /**
     * Magic method to get related records using the relation alias as a property
     *
     * @param string $property
     * @return \Phalcon\Mvc\Model\Resultset
     * @throws Exception
     */
    public function __get($property)
    {
        if (is_string($property) === false) {
            throw new Exception('Invalid parameter type.');
        }

        $modelName = get_class($this);
        $lowerProperty = strtolower($property);
        $manager = $this->getModelsManager();

        //Check if the property is a relationship
        $relation = $manager->getRelationByAlias($modelName, $lowerProperty);
        if (is_object($relation) === true) {
            $callArgs = array($relation, null, $this, null);
            $callObject = array($manager, 'getRelationRecords');

            //Get the related records
            $result = call_user_func_array($callObject, $callArgs);

            //Assign the result to the object
            if (is_object($result) === true) {
                //We assign the result to the instance avoiding future queries
                $this->$lowerProperty = $result;

                //For belongs-to-relations we store the object in the related bag
                if ($result instanceof ModelInterface === true) {
                    $this->_related[$lowerProperty] = $result;
                }
            }

            return $result;
        }

        //A notice is shown if the property is not defined an isn't a relationship
        trigger_error('Access to undefined property '.$modelName.'::'.$property);
    }

    /**
     * Magic method to check if a property is a valid relation
     *
     * @param string $property
     * @throws Exception
     */
    public function __isset($property)
    {
        if (is_string($property) === false) {
            throw new Exception('Invalid parameter type.');
        }

        $manager = $this->getModelsManager();

        //Check if the property is a relationship
        $relation = $manager->getRelationByAlias(get_called_class(), $property);

        return (is_object($relation) === true ? true : false);
    }

    /**
     * Serializes the object ignoring connections, services, related objects or static properties
     *
     * @return string
     */
    public function serialize()
    {
        $metaData = $this->getModelsMetaData();

        //We get the model's attributes to only serialize them
        $attributes = $metaData->getAttributes($this);

        $data = array();

        foreach ($attributes as $attribute) {
            if (isset($this->$attribute) === true) {
                $data[$attribute] = $this->$attribute;
            } else {
                $data[$attribute] = null;
            }
        }

        //Use the standard serialize function to serialize the array data
        return serialize($data);
    }

    /**
     * Unserializes the object from a serialized string
     *
     * @param string $data
     * @throws Exception
     */
    public function unserialize($data)
    {
        if (is_string($data) === true) {
            $attributes = unserialize($data);
            if (is_array($attributes) === true) {
                //Obtain the default DI
                $dependencyInjector = DI::getDefault();
                if (is_object($dependencyInjector) === false) {
                    throw new Exception('A dependency injector container is required to obtain the services related to the ORM');
                }

                //Update the dependency injector
                $this->_dependencyInjector = $dependencyInjector;

                //Get the default modelsManager service
                $manager = $dependencyInjector->getShared('modelsManager');

                if (is_object($manager) === false) {
                    //@note no interface validation
                    throw new Exception("The injected service 'modelsManager' is not valid");
                }

                //Update the models manager
                $this->_modelsManager = $manager;

                //Try to initialize the model
                $manager->initialize($this);

                //Update the objects attributes
                foreach ($attributes as $key => $value) {
                    $this->$key = $value;
                }

                return;
            }
        }

        throw new Exception('Invalid serialization data');
    }

    /**
     * Returns a simple representation of the object that can be used with var_dump
     *
     *<code>
     * var_dump($robot->dump());
     *</code>
     *
     * @return array
     */
    public function dump()
    {
        return get_object_vars($this);
    }

    /**
     * Returns the instance as an array representation
     *
     *<code>
     * print_r($robot->toArray());
     *</code>
     *
     * @return array
     * @throws Exception
     */
    public function toArray()
    {
        $metaData = $this->getModelsMetaData();
        $data = array();

        //Original attributes
        $attributes = $metaData->getAttributes($this);

        //Reverse column map
        $columnMap = $metaData->getColumnMap($this);

        foreach ($attributes as $attribute) {
            //Check if the columns must be renamed
            if (is_array($columnMap) === true) {
                if (isset($columnMap[$attribute]) === false) {
                    throw new Exception('Column "'.$attribute.'" doesn\'t make part of the column map');
                }
                $attributeField = $columnMap[$attribute];
            } else {
                $attributeField = $attribute;
            }

            if (isset($this->$attributeField) === true) {
                $data[$attributeField] = $this->$attributeField;
            } else {
                $data[$attributeField] = null;
            }
        }

        return $data;
    }

    /**
     * Enables/disables options in the ORM
     * Available options:
     * events                — Enables/Disables globally the internal events
     * virtualForeignKeys    — Enables/Disables virtual foreign keys
     * columnRenaming        — Enables/Disables column renaming
     * notNullValidations    — Enables/Disables automatic not null validation
     * exceptionOnFailedSave — Enables/Disables throws an exception if the saving process fails
     * phqlLiterals          — Enables/Disables literals in PHQL this improves the security of applications
     *
     * @param array $options
     * @throws Exception
     */
    public static function setup($options)
    {
        if (is_array($options) === false) {
            throw new Exception('Options must be an array');
        }

        //Enable/Disable internal events
        if (isset($options['events']) === true) {
            $GLOBALS['_PHALCON_ORM_EVENTS'] = ($options['events'] == true ? true : false);
        }

        //Enable/Disable virtual foreign keys
        if (isset($options['virtualForeignKeys']) === true) {
            $GLOBALS['_PHALCON_ORM_VIRTUAL_FOREIGN_KEYS'] = ($options['virtualForeignKeys'] == true ? true : false);
        }

        //Enable/Disable column renaming
        if (isset($options['columnRenaming']) === true) {
            $GLOBALS['_PHALCON_ORM_COLUMN_RENAMING'] = ($options['columnRenaming'] == true ? true : false);
        }

        //Enable/Disable automatic not null validation
        if (isset($options['notNullValidations']) === true) {
            $GLOBALS['_PHALCON_ORM_NOT_NULL_VALIDATIONS'] = ($options['notNullValidations'] == true ? true : false);
        }

        //Enable/Disable of throwing exceptions if the saving process fails
        if (isset($options['exceptionOnFailedSave']) === true) {
            $GLOBALS['_PHALCON_ORM_EXCEPTION_ON_FAILED_SAVE'] = ($options['exceptionOnFailedSave'] == true ? true : false);
        }

        //Enable/Disable literals in PHQL - this improves the security of applications
        if (isset($options['phqlLiterals']) === true) {
            $GLOBALS['_PHALCON_ORM_ENABLE_LITERALS'] = ($options['phqlLiterals'] == true ? true : false);
        }
    }
}
