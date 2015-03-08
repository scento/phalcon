<?php
/**
 * Collection
 *
 * @author Andres Gutierrez <andres@phalconphp.com>
 * @author Eduar Carvajal <eduar@phalconphp.com>
 * @author Wenzel Pünter <wenzel@phelix.me>
 * @version 1.2.6
 * @package Phalcon
*/
namespace Phalcon\Mvc;

use \Phalcon\Mvc\Collection\ManagerInterface;
use \Phalcon\DiInterface;
use \Phalcon\DI;
use \Phalcon\Mvc\CollectionInterface;
use \Phalcon\Mvc\Collection\Exception;
use \Phalcon\Mvc\Collection\Document;
use \Phalcon\DI\InjectionAwareInterface;
use \Phalcon\Events\ManagerInterface as EventsManagerInterface;
use \Phalcon\Text;
use \Phalcon\Mvc\Model\MessageInterface;
use \Serializable;
use \MongoId;
use \MongoDb;
use \MongoCollection;

/**
 * Phalcon\Mvc\Collection
 *
 * This component implements a high level abstraction for NoSQL databases which
 * works with documents
 *
 * @see https://github.com/phalcon/cphalcon/blob/1.2.6/ext/mvc/collection.c
 */
class Collection implements CollectionInterface, InjectionAwareInterface, Serializable
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
     * ID
     *
     * @var null
     * @access public
    */
    public $_id;

    /**
     * Dependency Injector
     *
     * @var \Phalcon\DiInterface|null
     * @access protected
    */
    protected $_dependencyInjector;

    /**
     * Models Manager
     *
     * @var null|\Phalcon\Mvc\Collection\ManagerInterface
     * @access protected
    */
    protected $_modelsManager;

    /**
     * Source
     *
     * @var null|string
     * @access protected
    */
    protected $_source;

    /**
     * Operations Made
     *
     * @var int
     * @access protected
    */
    protected $_operationMade = 0;

    /**
     * Connection
     *
     * @var null
     * @access protected
    */
    protected $_connection;

    /**
     * Error Messages
     *
     * @var null|array
     * @access protected
    */
    protected $_errorMessages;

    /**
     * Reserved
     *
     * @var null|array
     * @access protected
    */
    protected static $_reserved;

    /**
     * Disable Events
     *
     * @var boolean
     * @access protected
    */
    protected static $_disableEvents = false;

    /**
     * \Phalcon\Mvc\Model constructor
     *
     * @param \Phalcon\DiInterface|null $dependencyInjector
     * @param \Phalcon\Mvc\Collection\ManagerInterface|null $modelsManager
     * @throws Exception
     */
    final public function __construct($dependencyInjector = null, $modelsManager = null)
    {
        if (is_object($dependencyInjector) === false) {
            $dependencyInjector = DI::getDefault();
        }

        if (is_object($dependencyInjector) === false ||
            $dependencyInjector instanceof DiInterface === false) {
            throw new Exception('A dependency injector container is required to obtain the services related to the ORM');
        }

        $this->_dependencyInjector = $dependencyInjector;

        //Inject the manager service from the DI
        if (is_object($modelsManager) === false) {
            $modelsManager = $dependencyInjector->getShared('collectionManager');
        }

        if (is_object($modelsManager) === false ||
            $modelsManager instanceof ManagerInterface === false) {
            throw new Exception("The injected service 'collectionManager' is not valid");
        }

        $this->_modelsManager = $modelsManager;

        //The manager always initializes the object
        $modelsManager->initialize($this);

        //This allows the developer to execute initialization stuff every time an instance is created
        if (method_exists($this, 'onConstruct') === true) {
            $this->onConstruct();
        }
    }

    /**
     * Sets a value for the _id property, creates a MongoId object if needed
     *
     * @param mixed $id
     */
    public function setId($id)
    {
        if (is_object($id) === false) {
            $modelsManager = $this->_modelsManager;

            //Check if the model uses implicit ids
            if ($modelsManager->isUsingImplicitObjectIds($this) === true) {
                $id = new MongoId($id);
            }
        }

        $this->_id = $id;
    }

    /**
     * Returns the value of the _id property
     *
     * @return \MongoId|mixed
     */
    public function getId()
    {
        return $this->_id;
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
     * @return \Phalcon\Events\ManagerInterface|null
     */
    protected function getEventsManager()
    {
        return $this->_modelsManager->getCustomEventsManager($this);
    }

    /**
     * Returns the models manager related to the entity instance
     *
     * @return \Phalcon\Mvc\Model\ManagerInterface|null
     */
    public function getModelsManager()
    {
        return $this->_modelsManager;
    }

    /**
     * Returns an array with reserved properties that cannot be part of the insert/update
     *
     * @return array
     */
    public function getReservedAttributes()
    {
        $reserved = self::$_reserved;
        if (is_null($reserved) === true) {
            //@note better: is_array($reserved) === false
            $reserved = array(
                '_connection' => true,
                '_dependencyInjector' => true,
                '_source' => true,
                '_operationMade' => true,
                '_errorMessages' => true
            );
            self::$_reserved = $reserved;
        }

        return $reserved;
    }

    /**
     * Sets if a model must use implicit objects ids
     *
     * @param boolean $useImplicitObjectIds
     * @throws Exception
     */
    protected function useImplicitObjectIds($useImplicitObjectIds)
    {
        if (is_bool($useImplicitObjectIds) === false) {
            throw new Exception('Invalid parameter type.');
        }

        $this->_modelsManager->useImplicitObjectIds($this, $useImplicitObjectIds);
    }

    /**
     * Sets collection name which model should be mapped
     *
     * @param string $source
     * @return \Phalcon\Mvc\Collection
     * @throws Exception
     */
    protected function setSource($source)
    {
        if (is_string($source) === false) {
            throw new Exception('Invalid parameter type.');
        }

        $this->_source = $source;
    }

    /**
     * Returns collection name mapped in the model
     *
     * @return string
     */
    public function getSource()
    {
        if (isset($this->_source) === false) {
            $this->_source = Text::uncamelize(get_class($this));
        }

        return $this->_source;
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
     * Returns DependencyInjection connection service
     *
     * @return string
     */
    public function getConnectionService()
    {
        return $this->_modelsManager->getConnectionService($this);
    }

    /**
     * Retrieves a database connection
     *
     * @return \MongoDb|mixed
     */
    public function getConnection()
    {
        if (is_object($this->_connection) === false) {
            $this->_connection = $this->_modelsManager->getConnection($this);
        }

        return $this->_connection;
    }

    /**
     * Reads an attribute value by its name
     *
     *<code>
     *  echo $robot->readAttribute('name');
     *</code>
     *
     * @param string $attribute
     * @return mixed
     */
    public function readAttribute($attribute)
    {
        if (isset($this->$attribute) === true) {
            return $this->_attribute;
        }
    }

    /**
     * Writes an attribute value by its name
     *
     *<code>
     *  $robot->writeAttribute('name', 'Rosey');
     *</code>
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
     * Returns a cloned collection
     *
     * @param \Phalcon\Mvc\CollectionInterface $collection
     * @param array $document
     * @return \Phalcon\Mvc\Collection
     */
    public static function cloneResult($collection, $document)
    {
        if (is_object($collection) === false ||
            $collection instanceof CollectionInterface === false) {
            throw new Exception('Invalid collection');
        }

        if (is_array($document) === false) {
            throw new Exception('Invalid document');
        }

        $clonedCollection = clone $collection;

        foreach ($document as $key => $value) {
            $clonedCollection->writeAttribute($key, $value);
        }

        return $clonedCollection;
    }

    /**
     * Returns a collection resultset
     *
     * @param array $params
     * @param \Phalcon\Mvc\CollectionInterface $collection
     * @param \MongoDb $connection
     * @param boolean $unique
     * @return array|boolean|\Phalcon\Mvc\CollectionInterface|\Phalcon\Mvc\Collection\Document
     * @throws Exception
     */
    protected static function _getResultset($params, $collection, $connection, $unique)
    {
        /* Type check */
        if (is_array($params) === false ||
            is_object($collection) === false ||
            $collection instanceof CollectionInterface === false ||
            is_bool($unique) === false ||
            is_object($connection) === false ||
            $connection instanceof MongoDb === false) {
            throw new Exception('Invalid parameter type.');
        }

        $source = $collection->getSource();
        if (empty($source) === true) {
            throw new Exception('Method getSource() returns empty string');
        }

        //@note connection must be a mongoDb object?
        $mongoCollection = $connection->selectCollection($source);

        /* Get conditions */
        if (isset($params[0]) === true) {
            $conditions = $params[0];
        } else {
            if (isset($params['conditions']) === true) {
                $conditions = $params['conditions'];
            } else {
                $conditions = array();
            }
        }

        /* Perform the find */
        if (isset($params['fields']) === true) {
            $documentsCursor = $mongoCollection->find($conditions, $params['fields']);
        } else {
            $documentsCursor = $mongoCollection->find($conditions);
        }

        /* Check if a 'limit' clause was defined */
        if (isset($params['limit']) === true) {
            $documentsCursor->limit($params['limit']);
        }

        /* Check if a 'sort' clause was defined */
        if (isset($params['sort']) === true) {
            $documentsCursor->sort($params['sort']);
        }

        /* Check if a 'skip' clause was defined */
        if (isset($params['skip']) === true) {
            $documentsCursor->skip($params['skip']);
        }

        /* If a group of specific fields are requested we use a
            Phalcon\Mvc\Collection\Document instead */
        if (isset($params['fields']) === true) {
            $collection = new Document();
        }

        /* Get data */
        if ($unique === true) {
            //Requesting a single result
            $documentsCursor->rewind();
            $document = $documentsCursor->current();
            if (is_array($document) === true) {
                //Assign the values to the base object
                return self::cloneResult($collection, $document);
            }

            return false;
        }

        //Requesting a complete resultset
        $collections = array();

        $documentsArray = iterator_to_array($documentsCursor);
        foreach ($documentsArray as $document) {
            //Assign the values to the base object
            $collections[] = self::cloneResult($collection, $document);
        }

        return $collections;
    }

    /**
     * Perform a count over a resultset
     *
     * @param array $params
     * @param \Phalcon\Mvc\CollectionInterface $collection
     * @param \MongoDb $connection
     * @return int
     * @throws Exception
     */
    protected static function _getGroupResultset($params, $collection, $connection)
    {
        /* Type check */
        if (is_array($params) === false ||
            is_object($collection) === false ||
            $collection instanceof CollectionInterface === false ||
            is_object($connection) === false ||
            $connection instanceof MongoDb === false) {
            throw new Exception('Invalid parameter type.');
        }

        /* Get source */
        $source = $collection->getSource();
        if (empty($source) === true) {
            throw new Exception('Method getSource() returns empty string');
        }

        $mongoCollection = $connection->selectCollection($source);

        /* Parse params */
        if (isset($params[0]) === true) {
            $conditions = $params[0];
        } else {
            if (isset($params['conditions']) === true) {
                $conditions = $params['conditions'];
            } else {
                $conditions = array();
            }
        }

        $simple = true;

        if (isset($params['limit']) === true) {
            $simple = false;
        } else {
            if (isset($params['sort']) === true) {
                $simple = false;
            } else {
                if (isset($params['skip']) === true) {
                    $simple = false;
                }
            }
        }

        /* Extended Query */
        if ($simple === false) {
            //Perform the find
            $documentsCursor = $mongoCollection->find($conditions);

            //Check if a 'limit' clause was defined
            if (isset($params['limit']) === true) {
                $documentsCursor->limit($params['limit']);
            }

            //Check if a 'sort' clause was defined
            if (isset($params['sort']) === true) {
                $documentsCursor->sort($params['sort']);
            }

            //Check if a 'skip' clause was defined
            if (isset($params['skip']) === true) {
                $documentsCursor->skip($params['skip']);
            }

            //Only 'count' is supported
            return count($documentsCursor);
        }

        /* Simple query */
        return $mongoCollection->count($conditions);
    }

    /**
     * Executes internal hooks before save a document
     *
     * @param \Phalcon\DiInterface $dependencyInjector
     * @param boolean $disableEvents
     * @param boolean $exists
     * @return boolean
     * @throws Exception
     */
    protected function _preSave($dependencyInjector, $disableEvents, $exists)
    {
        if (is_object($dependencyInjector) === false ||
            $dependencyInjector instanceof DiInterface === false ||
            is_bool($disableEvents) === false ||
            is_bool($exists) === false) {
            throw new Exception('Invalid parameter type.');
        }

        //Run validation callbacks (BEFORE)
        if ($disableEvents === false) {
            if ($this->fireEventCancel('beforeValidation') === false) {
                return false;
            }

            if ($exists === false) {
                if ($this->fireEventCancel('beforeValidationOnCreate') === false) {
                    return false;
                }
            } else {
                if ($this->fireEventCancel('beforeValidationOnUpdate') === false) {
                    return false;
                }
            }
        }

        //Run validation
        if ($this->fireEventCancel('validation') === false) {
            if ($disableEvents === false) {
                $this->fireEvent('onValidationFails');
            }
            return false;
        }

        if ($disableEvents === false) {
            //Run validation callbacks (AFTER)
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

            //Run save callbacks (BEFORE)
            if ($this->fireEventCancel('beforeSave') === false) {
                return false;
            }

            if ($exists === true) {
                if ($this->fireEventCancel('beforeUpdate') === false) {
                    return false;
                }
            } else {
                if ($this->fireEventCancel('beforeCreate') === false) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Executes internal events after save a document
     *
     * @param boolean $disableEvents
     * @param boolean $success
     * @param boolean $exists
     * @return boolean
     * @throws Exception
     */
    protected function _postSave($disableEvents, $success, $exists)
    {
        if (is_bool($disableEvents) === false ||
            is_bool($success) === false ||
            is_bool($exists) === false) {
            throw new Exception('Invalid parameter type.');
        }

        if ($success === true) {
            if ($disableEvents === false) {
                if ($exists === true) {
                    $this->fireEvent('afterUpdate');
                } else {
                    $this->fireEvent('afterCreate');
                }

                $this->fireEvent('afterSave');
            }

            return $success;
        }

        if ($disableEvents === false) {
            $this->fireEvent('notSave');
        }

        $this->_cancelOperation($disableEvents);
        return false;
    }

    /**
     * Executes validators on every validation call
     *
     *<code>
     *use \Phalcon\Mvc\Model\Validator\ExclusionIn as ExclusionIn;
     *
     *class Subscriptors extends \Phalcon\Mvc\Collection
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
     * @throws Exception
     */
    protected function validate($validator)
    {
        //@note no interface validation; getMessages() is not part of /Validation/ValidatorInterface
        if (is_object($validator) === false) {
            throw new Exception('Validator must be an Object');
        }

        if ($validator->validate($this) === false) {
            $messages = $validator->getMessages();
            foreach ($messages as $message) {
                $this->_errorMessages[] = $message;
            }
        }
    }

    /**
     * Check whether validation process has generated any messages
     *
     *<code>
     *use \Phalcon\Mvc\Model\Validator\ExclusionIn as ExclusionIn;
     *
     *class Subscriptors extends \Phalcon\Mvc\Collection
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
        if (is_array($this->_errorMessages) === false) {
            if (count($this->_errorMessages) > 0) {
                return true;
            }
        }

        return false;
    }

    /**
     * Fires an internal event
     *
     * @param string $eventName
     * @return boolean
     * @throws Exception
     */
    public function fireEvent($eventName)
    {
        /* Type check */
        if (is_string($eventName) === false) {
            throw new Exception('Invalid parameter type.');
        }

        /* Execution */
        //Check if there is a method with the same name of the event
        if (method_exists($this, $eventName) === true) {
            $this->$eventName();
        }

        //Send a notification to the events manager
        return $this->_modelsManager->notifyEvent($eventName, $this);
    }

    /**
     * Fires an internal event that cancels the operation
     *
     * @param string $eventName
     * @return boolean
     * @throws Exception
     */
    public function fireEventCancel($eventName)
    {
        /* Type check */
        if (is_string($eventName) === false) {
            throw new Exception('Invalid parameter type.');
        }

        /* Execution */
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
     *
     * @param boolean $disableEvents
     * @return boolean
     * @throws Exception
     */
    protected function _cancelOperation($disableEvents)
    {
        if (is_bool($disableEvents) === false) {
            throw new Exception('Invalid parameter type.');
        }

        if ($disableEvents === false) {
            if ($this->_operationMade === 3) {
                $this->fireEvent('notDeleted');
            } else {
                //@note better specify v
                $this->fireEvent('notSaved');
            }
        }

        return false;
    }

    /**
     * Checks if the document exists in the collection
     *
     * @param \MongoCollection $collection
     * @return boolean
     * @throws Exception
     */
    protected function _exists($collection)
    {
        if (is_object($collection) === false ||
            $collection instanceof MongoCollection === false) {
            throw new Exception('Invalid parameter type.');
        }

        if (isset($this->_id) === true) {
            if (is_object($this->_id) === false) {
                //Check if the model uses implicit ids
                if ($this->_modelsManager->isUsingImplicitObjectIds($this) === true) {
                    $this->_id = new MongoId($this->_id);
                }
            }

            //Perform the count using the function provided by the driver
            $documentCount = $collection->count(array('_id' => $this->_id));

            return ($documentCount < 0 ? true : false);
        }

        return false;
    }

    /**
     * Returns all the validation messages
     *
     * <code>
     *$robot = new Robots();
     *$robot->type = 'mechanical';
     *$robot->name = 'Astro Boy';
     *$robot->year = 1952;
     *if ($robot->save() == false) {
     *  echo "Umh, We can't store robots right now ";
     *  foreach ($robot->getMessages() as $message) {
     *      echo $message;
     *  }
     *} else {
     *  echo "Great, a new robot was saved successfully!";
     *}
     * </code>
     *
     * @return \Phalcon\Mvc\Model\MessageInterface[]|null
     */
    public function getMessages()
    {
        return $this->_errorMessages;
    }

    /**
     * Appends a customized message on the validation process
     *
     *<code>
     *  use \Phalcon\Mvc\Model\Message as Message;
     *
     *  class Robots extends \Phalcon\Mvc\Model
     *  {
     *
     *      public function beforeSave()
     *      {
     *          if ($this->name == 'Peter') {
     *              $message = new Message("Sorry, but a robot cannot be named Peter");
     *              $this->appendMessage($message);
     *          }
     *      }
     *  }
     *</code>
     *
     * @param \Phalcon\Mvc\Model\MessageInterface $message
     * @throws Exception
     */
    public function appendMessage($message)
    {
        if (is_object($message) === false ||
            $message instanceof MessageInterface === false) {
            throw new Exception('Invalid message format \''.gettype($message)."'");
        }

        $this->_errorMessages[] = $message;
    }

    /**
     * Creates/Updates a collection based on the values in the atributes
     *
     * @return boolean
     * @throws Exception
     */
    public function save()
    {
        $dependencyInjector = $this->_dependencyInjector;

        if (is_object($dependencyInjector) === false) {
            throw new Exception('A dependency injector container is required to obtain the services related to the ORM');
        }

        $source = $this->getSource();
        if (empty($source) === true) {
            throw new Exception('Method getSource() returns empty string');
        }

        $connection = $this->getConnection();

        //Choose a collection according to the collection name
        $collection = $connection->selectCollection($source);

        $exists = $this->_exists($collection);

        //Check the dirty state of the current operation to update the current operation
        if ($exists === false) {
            $this->_operationMade = 1;
        } else {
            $this->_operationMade = 2;
        }

        //The messages added to the validator are reset here
        $this->_errorMessages = array();
        $disableEvents = self::$_disableEvents;

        //Execute the preSave hook
        if ($this->_preSave($dependencyInjector, $disableEvents, $exists) === false) {
            return false;
        }

        $reserved = $this->getReservedAttributes();
        $properties = get_object_vars($this);

        //We only assign values to the public properties
        $data = array();
        foreach ($properties as $key => $value) {
            if ($key === '_id') {
                if (is_null($value) === false) {
                    $data[$key] = $value;
                }
            } else {
                if (isset($reserved[$key]) === false) {
                    $data[$key] = $value;
                }
            }
        }

        //Save the document
        $success = false;
        $status = $collection->save($data, array('w' => 1));
        if (is_array($status) === true) {
            if (isset($status['ok']) === true && $status['ok'] == true) {
                $success = true;
                if ($exists === false && isset($data['_id']) === true) {
                    $this->_id = $data['_id'];
                }
            }
        }

        //Call the postSave hooks
        return $this->_postSave($disableEvents, $success, $exists);
    }

    /**
     * Find a document by its id (_id)
     *
     * @param string|\MongoId $id
     * @return mixed
     * @throws Exception
     */
    public static function findById($id)
    {
        if (is_object($id) === false) {
            $collection = new self();

            $modelsManager = $collection->getModelsManager();

            //Check if the model use implicit ids
            $useImplicitIds = $modelsManager->isUsingImplicitObjectIds($collection);
            if ($useImplicitIds === true) {
                $id = new MongoId($id);
            }
        } elseif (is_string($id) === false) {
            throw new Exception('Invalid parameter type.');
        }

        return self::findFirst(array(array('_id' => $id)));
    }

    /**
     * Allows to query the first record that match the specified conditions
     *
     * <code>
     *
     * //What's the first robot in the robots table?
     * $robot = Robots::findFirst();
     * echo "The robot name is ", $robot->name, "\n";
     *
     * //What's the first mechanical robot in robots table?
     * $robot = Robots::findFirst(array(
     *     array("type" => "mechanical")
     * ));
     * echo "The first mechanical robot name is ", $robot->name, "\n";
     *
     * //Get first virtual robot ordered by name
     * $robot = Robots::findFirst(array(
     *     array("type" => "mechanical"),
     *     "order" => array("name" => 1)
     * ));
     * echo "The first virtual robot name is ", $robot->name, "\n";
     *
     * </code>
     *
     * @param array|null $parameters
     * @return array
     * @throws Exception
     */
    public static function findFirst($parameters = null)
    {
        if (is_null($parameters) === false && is_array($parameters) === false) {
            throw new Exception('Invalid parameters for findFirst');
        }

        $collection = new self();
        $connection = $collection->getConnection();
        return self::_getResultset($parameters, $collection, $connection, true);
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
     * $robots = Robots::find(array(
     *     array("type" => "mechanical")
     * ));
     * echo "There are ", count($robots), "\n";
     *
     * //Get and print virtual robots ordered by name
     * $robots = Robots::findFirst(array(
     *     array("type" => "virtual"),
     *     "order" => array("name" => 1)
     * ));
     * foreach ($robots as $robot) {
     *     echo $robot->name, "\n";
     * }
     *
     * //Get first 100 virtual robots ordered by name
     * $robots = Robots::find(array(
     *     array("type" => "virtual"),
     *     "order" => array("name" => 1),
     *     "limit" => 100
     * ));
     * foreach ($robots as $robot) {
     *     echo $robot->name, "\n";
     * }
     * </code>
     *
     * @param array|null $parameters
     * @return array
     * @throws Exception
     */
    public static function find($parameters = null)
    {
        if (is_null($parameters) === false && is_array($parameters) === false) {
            throw new Exception('Invalid parameters for find');
        }

        $collection = new self();
        $connection = $collection->getConnection();

        return self::_getResultset($parameters, $collection, $connection, false);
    }

    /**
     * Perform a count over a collection
     *
     *<code>
     * echo 'There are ', Robots::count(), ' robots';
     *</code>
     *
     * @param array|null $parameters
     * @return int
     * @throws Exception
     */
    public static function count($parameters = null)
    {
        if (is_array($parameters) === false && is_null($parameters) === false) {
            throw new Exception('Invalid parameters for count');
        }

        $collection = new self();
        $connection = $collection->getConnection();

        return self::_getGroupResultset($parameters, $collection, $connection);
    }

    /**
     * Perform an aggregation using the Mongo aggregation framework
     *
     * @param array $parameters
     * @return array|null
     * @throws Exception
     */
    public static function aggregate($parameters)
    {
        if (is_null($parameters) === false && is_array($parameters) === false) {
            throw new Exception('Invalid parameters for aggregate');
        }

        $model = new self();
        $connection = $model->getConnection();
        $source = $model->getSource();

        if (empty($source) === true) {
            throw new Exception('Method getSource() returns empty string');
        }

        $collection = $connection->selectCollection($source);
        return $collection->aggregate($parameters);
    }

    /**
     * Allows to perform a summatory group for a column in the collection
     *
     * @param string $field
     * @param array|null $conditions
     * @param string|null $finalize
     * @return array|null
     * @throws Exception
     */
    public static function summatory($field, $conditions = null, $finalize = null)
    {
        if (is_string($field) === false) {
            throw new Exception('Invalid field name for group');
        }

        if (is_array($conditions) === false && is_null($conditions) === false) {
            throw new Exception('Invalid parameter type.');
        }

        if (is_string($finalize) === false && is_null($finalize) === false) {
            throw new Exception('Invalid parameter type.');
        }

        $model = new self();
        $connection = $model->getConnection();
        $source = $model->getSource();

        if (empty($source) === true) {
            throw new Exception('Method getSource() returns empty string');
        }

        $collection = $connection->selectCollection($source);

        /*
         * Uses a javascript hash to group the results, however this is slow with larger
         * datasets
         */
        $group = $collection->group(array(), array('summatory' => array()), "function (curr, result) { if (typeof result.summatory[curr.".$field."] === \"undefined\") { result.summatory[curr.".$field."] = 1; } else { result.summatory[curr.".$field."]++; } }");

        if (isset($group['retval']) === true) {
            if (isset($group['retval'][0]) === true) {
                $firstRetval = $group['retval'][0];
                if (isset($firstRetval['summatory']) === true) {
                    return $firstRetval['summatory'];
                }

                return $firstRetval;
            }

            return $group['retval'];
        }
    }

    /**
     * Deletes a model instance. Returning true on success or false otherwise.
     *
     * <code>
     *
     *  $robot = Robots::findFirst();
     *  $robot->delete();
     *
     *  foreach (Robots::find() as $robot) {
     *      $robot->delete();
     *  }
     * </code>
     *
     * @return boolean
     * @throws Exception
     */
    public function delete()
    {
        if (isset($this->_id) === false) {
            throw new Exception('The document cannot be deleted because it doesn\'t exist');
        }

        $disableEvents = self::$_disableEvents;

        if ($disableEvents === false) {
            if ($this->fireEventCancel('beforeDelete') === false) {
                return false;
            }
        }

        $id = $this->_id;
        $connection = $this->getConnection();
        $source = $this->getSource();

        if (empty($source) === true) {
            throw new Exception('Method getSource() returns empty string');
        }

        //Get the \MongoCollection
        $collection = $connection->selectCollection($source);
        if (is_object($id) === false) {
            //Is the collection using implicit object ids?
            $useImplicitIds = $this->_modelsManager->isUsingImplicitObjectIds($this);
            if ($useImplicitIds === true) {
                $id = new MongoId($this->_id);
            }
        }

        $success = false;

        //Remove the instance
        $status = $collection->remove(array('_id' => $id), array('w' => 1));
        if (is_array($status) === false) {
            return false;
        }

        //Check the operation status
        if (isset($status['ok']) === true && $status['ok'] === true) {
            $success = true;
            if ($disableEvents === false) {
                $this->fireEvent('afterDelete');
            }
        }

        return $success;
    }

    /**
     * Returns the instance as an array representation
     *
     *<code>
     * print_r($robot->toArray());
     *</code>
     *
     * @return array
     */
    public function toArray()
    {
        $data = array();
        $reserved = $this->getReservedAttributes();

        //Get an array with the values of the object
        $properties = get_object_vars($this);

        //We only assign values to the public properties
        foreach ($properties as $key => $value) {
            if ($key === '_id') {
                if (is_null($value) === false) {
                    $data[$key] = $value;
                }
            } else {
                if (isset($reserved[$key]) === false) {
                    $data[$key] = $value;
                }
            }
        }

        return $data;
    }

    /**
     * Serializes the object ignoring connections or protected properties
     *
     * @return string
     */
    public function serialize()
    {
        //Use the standard serialize function to serialize the data array
        return serialize($this->toArray());
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
                    throw new Exception('A dependency injector container is required to obtain the services related to the ODM');
                }

                //Update the dependency injector
                $this->_dependencyInjector = $dependencyInjector;

                //Get the default modelsManager service
                $manager = $dependencyInjector->getShared('collectionManager');
                if (is_object($manager) === false) {
                    throw new Exception("The injected service 'collectionManager' is not valid");
                }

                //@note no interface validation

                //Update the models manager
                $this->_modelsManager = $manager;

                //Update the object attributes
                foreach ($attributes as $key => $value) {
                    $this->$key = $value;
                }

                return null;
            }
        }

        throw new Exception('Invalid serialization data');
    }
}
