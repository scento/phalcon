<?php
/**
 * Metadata
 *
 * @author Andres Gutierrez <andres@phalconphp.com>
 * @author Eduar Carvajal <eduar@phalconphp.com>
 * @author Wenzel Pünter <wenzel@phelix.me>
 * @version 1.2.6
 * @package Phalcon
*/
namespace Phalcon\Mvc\Model;

use \Phalcon\DI\InjectionAwareInterface,
	\Phalcon\DiInterface,
	\Phalcon\Mvc\Model\MetaData\Strategy\Introspection,
	\Phalcon\Mvc\Model\Exception,
	\Phalcon\Mvc\ModelInterface;

/**
 * Phalcon\Mvc\Model\MetaData
 *
 * <p>Because Phalcon\Mvc\Model requires meta-data like field names, data types, primary keys, etc.
 * this component collect them and store for further querying by Phalcon\Mvc\Model.
 * Phalcon\Mvc\Model\MetaData can also use adapters to store temporarily or permanently the meta-data.</p>
 *
 * <p>A standard Phalcon\Mvc\Model\MetaData can be used to query model attributes:</p>
 *
 * <code>
 *	$metaData = new Phalcon\Mvc\Model\MetaData\Memory();
 *	$attributes = $metaData->getAttributes(new Robots());
 *	print_r($attributes);
 * </code>
 *
 * @see https://github.com/phalcon/cphalcon/blob/1.2.6/ext/mvc/model/metadata.c
 */
abstract class MetaData implements InjectionAwareInterface
{
	/**
	 * Models: Attributes
	 * 
	 * @var int
	*/
	const MODELS_ATTRIBUTES = 0;

	/**
	 * Models: Primary Key
	 * 
	 * @var int
	*/
	const MODELS_PRIMARY_KEY = 1;

	/**
	 * Models: Non Primary Key
	 * 
	 * @var int
	*/
	const MODELS_NON_PRIMARY_KEY = 2;

	/**
	 * Models: Not Null
	 * 
	 * @var int
	*/
	const MODELS_NOT_NULL = 3;

	/**
	 * Models: Data Types
	 * 
	 * @var int
	*/
	const MODELS_DATA_TYPES = 4;

	/**
	 * Models: Data Types Numeric
	 * 
	 * @var int
	*/
	const MODELS_DATA_TYPES_NUMERIC = 5;

	/**
	 * Models: Date At
	 * 
	 * @var int
	*/
	const MODELS_DATE_AT = 6;

	/**
	 * Models: Date In
	 * 
	 * @var int
	*/
	const MODELS_DATE_IN = 7;

	/**
	 * Models: Identity Column
	 * 
	 * @var int
	*/
	const MODELS_IDENTITY_COLUMN = 8;

	/**
	 * Models: Data Types Bind
	 * 
	 * @var int
	*/
	const MODELS_DATA_TYPES_BIND = 9;

	/**
	 * Models: Automatic Default Insert
	 * 
	 * @var int
	*/
	const MODELS_AUTOMATIC_DEFAULT_INSERT = 10;

	/**
	 * Models: AUtomatic Default Update
	 * 
	 * @var int
	*/
	const MODELS_AUTOMATIC_DEFAULT_UPDATE = 11;

	/**
	 * Models: Column Map
	 * 
	 * @var int
	*/
	const MODELS_COLUMN_MAP = 0;

	/**
	 * Models: Reverse Column Map
	 * 
	 * @var int
	*/
	const MODELS_REVERSE_COLUMN_MAP = 1;

	/**
	 * Dependency Injector
	 * 
	 * @var null|\Phalcon\DiInterface
	 * @access protected
	*/
	protected $_dependencyInjector;

	/**
	 * Strategy
	 * 
	 * @var null|\Phalcon\Mvc\Model\MetaData\Strategy\Introspection
	 * @access protected
	*/
	protected $_strategy;

	/**
	 * Metadata
	 * 
	 * @var null|array
	 * @access protected
	*/
	protected $_metaData;

	/**
	 * Column Map
	 * 
	 * @var null|array
	 * @access protected
	*/
	protected $_columnMap;

	/**
	 * Initialize the metadata for certain table
	 *
	 * @param \Phalcon\Mvc\ModelInterface $model
	 * @param string|null $key
	 * @param string $table
	 * @param string $schema
	 * @throws Exception
	 */
	protected function _initialize(ModelInterface $model, $key = null, $table, $schema)
	{
		if(is_string($table) === false ||
			is_string($schema) === false ||
			(is_string($key) === false &&
				is_null($key) === false)) {
			throw new Exception('Invalid parameter type.');
		}

		$strategy = null;
		$className = get_class($model);

		if(is_null($key) === false) {
			//Check for $key in local metadata db
			$metaData = $this->_metaData;
			if(isset($metaData[$key]) === false) {
				//The meta-data is read from the adapter always
				$prefixKey = 'meta-'.$key;
				$data = $this->read($prefixKey);

				if(is_null($data) === false) {
					//Store the adapters metadata locally
					if(is_array($metaData) === false) {
						$metaData = array();
					}

					$metaData[$key] = $data;
					$this->_metaData = $metaData;
				} else {
					//Check if there is a method 'metaData' in the model to retrieve meta-data form it
					if(method_exists($model, 'metaData') === true) {
						$modelMetadata = $model->metaData();
						if(is_array($modelMetadata) === false) {
							throw new Exception('Invalid meta-data for model '.$className);
						}
					} else {
						//Get the meta-data extraction strategy
						$strategy = $this->getStrategy();

						//Get the meta-data
						$modelMetadata = $strategy->getMetaData($model, $this->_dependencyInjector);
					}

					//Store the meta-data locally
					$this->_metaData[$key] = $modelMetadata;

					//Store the meta-data in the adapter
					$this->write($prefixKey, $modelMetadata);
				}
			}
		}

		//Check for a column map, store in _columnMap in order and reversed order
		if(isset($GLOBALS['_PHALCON_ORM_COLUMN_RENAMING']) === false ||
			$GLOBALS['_PHALCON_ORM_COLUMN_RENAMING'] === false) {
			return;
		}

		$keyName = strtolower($className);

		if(is_array($this->_columnMap) === false) {
			$this->_columnMap = array();
		}

		if(isset($this->_columnMap[$keyName]) === true) {
			return;
		}

		//Create the map key name
		$prefixKey = 'map-'.$keyName;

		//Check if the meta-data is already in the adapter
		$data = $this->read($prefixKey);
		if(is_null($data) === false) {
			$this->_columnMap[$keyName] = $data;
			return;
		}

		//Get the meta-data extraction strategy
		if(is_object($strategy) === false) {
			$strategy = $this->_dependencyInjector->getStrategy();
		}

		//Get the meta-data
		$modelColumnMap = $strategy->getColumnMaps($model, $this->_dependencyInjector);

		//Update the column map locally
		$this->_columnMap[$keyName] = $modelColumnMap;

		//Write the data to the adapter
		$this->write($prefixKey, $modelColumnMap);
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
	 * Set the meta-data extraction strategy
	 *
	 * @param \Phalcon\Mvc\Model\MetaData\Strategy\Introspection $strategy
	 * @throws Exception
	 */
	public function setStrategy($strategy)
	{
		if(is_object($strategy) === false) {
			throw new Exception('The meta-data extraction strategy is not valid');
		}

		$this->_strategy = $strategy;
	}

	/**
	 * Return the strategy to obtain the meta-data
	 *
	 * @return \Phalcon\Mvc\Model\MetaData\Strategy\Introspection
	 */
	public function getStrategy()
	{
		if(is_null($this->_strategy) === true) {
			$this->_strategy = new Introspection();
		}

		return $this->_strategy;
	}

	/**
	 * Reads the complete meta-data for certain model
	 *
	 *<code>
	 *	print_r($metaData->readMetaData(new Robots()));
	 *</code>
	 *
	 * @param \Phalcon\Mvc\ModelInterface $model
	 * @return array
	 * @throws Exception
	 */
	public function readMetaData($model)
	{
		if(is_object($model) === false ||
			$model instanceof ModelInterface === false) {
			throw new Exception('A model instance is required to retrieve the meta-data');
		}

		$table = $model->getSource();
		$schema = $model->getSchema();

		//Unique key for meta-data is created using class-name-schema-table
		$key = strtolower(get_class($model)).'-'.$schema.$table;
		if(isset($this->_metaData[$key]) === false) {
			$this->_initialize($model, $key, $table, $schema);
		}

		return $this->_metaData[$key];
	}

	/**
	 * Reads meta-data for certain model using a MODEL_* constant
	 *
	 *<code>
	 *	print_r($metaData->writeColumnMapIndex(new Robots(), MetaData::MODELS_REVERSE_COLUMN_MAP, array('leName' => 'name')));
	 *</code>
	 *
	 * @param \Phalcon\Mvc\ModelInterface $model
	 * @param int $index
	 * @return array
	 * @throws Exception
	 */
	public function readMetaDataIndex($model, $index)
	{
		if(is_object($model) === false ||
			$model instanceof ModelInterface === false) {
			throw new Exception('A model instance is required to retrieve the meta-data');
		}

		if(is_int($index) === false) {
			throw new Exception('Index must be a valid integer constant');
		}

		$table = $model->getSource();
		$schema = $model->getSchema();

		//Unique key for meta-data is created using class-name-schema-table
		$key = strtolower(get_class($model)).'-'.$schema.$table;
		if(isset($this->_metaData[$key]) === false) {
			$this->_initialize($model, $key, $table, $schema);
		}

		return $this->_metaData[$key][$index];
	}

	/**
	 * Writes meta-data for certain model using a MODEL_* constant
	 *
	 *<code>
	 *	print_r($metaData->writeColumnMapIndex(new Robots(), MetaData::MODELS_REVERSE_COLUMN_MAP, array('leName' => 'name')));
	 *</code>
	 *
	 * @param \Phalcon\Mvc\ModelInterface $model
	 * @param int $index
	 * @param array|string|boolean $data
	 * @param boolean $replace
	 * @throws Exception
	 */
	public function writeMetaDataIndex($model, $index, $data, $replace)
	{
		if(is_object($model) === false ||
			$model instanceof ModelInterface === false) {
			throw new Exception('A model instance is required to retrieve the meta-data');
		}

		if(is_int($index) === false) {
			throw new Exception('Index must be a valid integer constant');
		}

		if(is_array($data) === false && is_string($data) === false &&
			is_bool($data) === false) {
			throw new Exception('Invalid data for index');
		}

		if(is_bool($replace) === false) {
			throw new Exception('Invalid parameter type.');
		}

		$table = $model->getSource();
		$schema = $model->getSchema();

		//Unique key for meta-data is created using class-name-schema-table
		$key = strtolower(get_class($model)).'-'.$schema.$table;

		if(isset($this->_metaData[$key]) === false) {
			$this->_initialize($model, $key, $table, $schema);
		} elseif($replace == false) {
			$value = $this->_metaData[$key][$index];
			foreach($data as $key => $value) {
				if(isset($data[$key]) === false) {
					$data[$key] = $value;
				}
			}
		}

		$this->_metaData[$key][$index] = $data;
	}

	/**
	 * Reads the ordered/reversed column map for certain model
	 *
	 *<code>
	 *	print_r($metaData->readColumnMap(new Robots()));
	 *</code>
	 *
	 * @param \Phalcon\Mvc\ModelInterface $model
	 * @return array
	 * @throws Exception
	 */
	public function readColumnMap($model)
	{
		if(is_object($model) === false ||
			$model instanceof ModelInterface === false) {
			throw new Exception('A model instance is required to retrieve the meta-data');
		}

		$keyName = strtolower(get_class($model));

		if(isset($this->_columnMap[$keyName]) === false) {
			$this->_initialize($model, null, null, null);
		}

		return $this->_columnMap[$keyName];
	}

	/**
	 * Reads column-map information for certain model using a MODEL_* constant
	 *
	 *<code>
	 *	print_r($metaData->readColumnMapIndex(new Robots(), MetaData::MODELS_REVERSE_COLUMN_MAP));
	 *</code>
	 *
	 * @param \Phalcon\Mvc\ModelInterface $model
	 * @param int $index
	 * @throws Exception
	 */
	public function readColumnMapIndex($model, $index)
	{
		if(is_object($model) === false ||
			$model instanceof ModelInterface === false) {
			throw new Exception('A model instance is required to retrieve the meta-data');
		}

		if(is_int($index) === false) {
			throw new Exception('Index must be a valid integer constant');
		}

		$keyName = strtolower(get_class($model));

		if(isset($this->_columnMap[$keyName]) === false) {
			$this->_initialize($model, null, null, null);
		}

		return $this->_columnMap[$keyName][$index];
	}

	/**
	 * Returns table attributes names (fields)
	 *
	 *<code>
	 *	print_r($metaData->getAttributes(new Robots()));
	 *</code>
	 *
	 * @param \Phalcon\Mvc\ModelInterface $model
	 * @return array
	 * @throws Exception
	 */
	public function getAttributes($model)
	{
		if(is_object($model) === false ||
			$model instanceof ModelInterface === false) {
			throw new Exception('Invalid parameter type.');
		}

		$data = $this->readMetaDataIndex($model, 0);
		if(is_array($data) === false) {
			throw new Exception('The meta-data is invalid or is corrupted');
		}

		return $data;
	}

	/**
	 * Returns an array of fields which are part of the primary key
	 *
	 *<code>
	 *	print_r($metaData->getPrimaryKeyAttributes(new Robots()));
	 *</code>
	 *
	 * @param \Phalcon\Mvc\ModelInterface $model
	 * @return array
	 * @throws Exception
	 */
	public function getPrimaryKeyAttributes($model)
	{
		if(is_object($model) === false ||
			$model instanceof ModelInterface === false) {
			throw new Exception('Invalid parameter type.');
		}

		$data = $this->readMetaDataIndex($model, 1);
		if(is_array($data) === false) {
			throw new Exception('The meta-data is invalid or is corrupted');
		}

		return $data;
	}

	/**
	 * Returns an arrau of fields which are not part of the primary key
	 *
	 *<code>
	 *	print_r($metaData->getNonPrimaryKeyAttributes(new Robots()));
	 *</code>
	 *
	 * @param \Phalcon\Mvc\ModelInterface $model
	 * @return array
	 * @throws Exception
	 */
	public function getNonPrimaryKeyAttributes($model)
	{
		if(is_object($model) === false ||
			$model instanceof ModelInterface === false) {
			throw new Exception('Invalid parameter type.');
		}

		$data = $this->readMetaDataIndex($model, 2);
		if(is_array($data) === false) {
			throw new Exception('The meta-data is invalid or is corrupted');
		}

		return $data;
	}

	/**
	 * Returns an array of not null attributes
	 *
	 *<code>
	 *	print_r($metaData->getNotNullAttributes(new Robots()));
	 *</code>
	 *
	 * @param \Phalcon\Mvc\ModelInterface $model
	 * @return array
	 * @throws Exception
	 */
	public function getNotNullAttributes($model)
	{
		if(is_object($model) === false ||
			$model instanceof ModelInterface === false) {
			throw new Exception('Invalid parameter type.');
		}

		$data = $this->readMetaDataIndex($model, 3);
		if(is_array($data) === false) {
			throw new Exception('The meta-data is invalid or is corrupted');
		}

		return $data;
	}

	/**
	 * Returns attributes and their data types
	 *
	 *<code>
	 *	print_r($metaData->getDataTypes(new Robots()));
	 *</code>
	 *
	 * @param \Phalcon\Mvc\ModelInterface $model
	 * @return array
	 * @throws Exception
	 */
	public function getDataTypes($model)
	{
		if(is_object($model) === false ||
			$model instanceof ModelInterface === false) {
			throw new Exception('Invalid parameter type.');
		}

		$data = $this->readMetaDataIndex($model, 4);
		if(is_array($data) === false) {
			throw new Exception('The meta-data is invalid or is corrupted');
		}

		return $data;
	}

	/**
	 * Returns attributes which types are numerical
	 *
	 *<code>
	 *	print_r($metaData->getDataTypesNumeric(new Robots()));
	 *</code>
	 *
	 * @param  \Phalcon\Mvc\ModelInterface $model
	 * @return array
	 * @throws Exception
	 */
	public function getDataTypesNumeric($model)
	{
		if(is_object($model) === false ||
			$model instanceof ModelInterface === false) {
			throw new Exception('Invalid parameter type.');
		}

		$data = $this->readMetaDataIndex($model, 5);
		if(is_array($data) === false) {
			throw new Exception('The meta-data is invalid or is corrupted');
		}

		return $data;
	}

	/**
	 * Returns the name of identity field (if one is present)
	 *
	 *<code>
	 *	print_r($metaData->getIdentityField(new Robots()));
	 *</code>
	 *
	 * @param  \Phalcon\Mvc\ModelInterface $model
	 * @return string
	 * @throws Exception
	 */
	public function getIdentityField($model)
	{
		if(is_object($model) === false ||
			$model instanceof ModelInterface === false) {
			throw new Exception('Invalid parameter type.');
		}

		return $this->readMetaDataIndex($model, 8);
	}

	/**
	 * Returns attributes and their bind data types
	 *
	 *<code>
	 *	print_r($metaData->getBindTypes(new Robots()));
	 *</code>
	 *
	 * @param \Phalcon\Mvc\ModelInterface $model
	 * @return array
	 * @throws Exception
	 */
	public function getBindTypes($model)
	{
		if(is_object($model) === false ||
			$model instanceof ModelInterface === false) {
			throw new Exception('Invalid parameter type.');
		}

		$data = $this->readMetaDataIndex($model, 9);
		if(is_array($data) === false) {
			throw new Exception('The meta-data is invalid or is corrupted');
		}

		return $data;
	}

	/**
	 * Returns attributes that must be ignored from the INSERT SQL generation
	 *
	 *<code>
	 *	print_r($metaData->getAutomaticCreateAttributes(new Robots()));
	 *</code>
	 *
	 * @param \Phalcon\Mvc\ModelInterface $model
	 * @return array
	 * @throws Exception
	 */
	public function getAutomaticCreateAttributes($model)
	{
		if(is_object($model) === false ||
			$model instanceof ModelInterface === false) {
			throw new Exception('Invalid parameter type.');
		}

		$data = $this->readMetaDataIndex($model, 10);
		if(is_array($data) === false) {
			throw new Exception('The meta-data is invalid or is corrupted');
		}

		return $data;
	}

	/**
	 * Returns attributes that must be ignored from the UPDATE SQL generation
	 *
	 *<code>
	 *	print_r($metaData->getAutomaticUpdateAttributes(new Robots()));
	 *</code>
	 *
	 * @param \Phalcon\Mvc\ModelInterface $model
	 * @return array
	 * @throws Exception
	 */
	public function getAutomaticUpdateAttributes($model)
	{
		if(is_object($model) === false ||
			$model instanceof ModelInterface === false) {
			throw new Exception('Invalid parameter type.');
		}

		$data = $this->readMetaDataIndex($model, 11);
		if(is_array($data) === false) {
			throw new Exception('The meta-data is invalid or is corrupted');
		}

		return $data;
	}

	/**
	 * Set the attributes that must be ignored from the INSERT SQL generation
	 *
	 *<code>
	 *	$metaData->setAutomaticCreateAttributes(new Robots(), array('created_at' => true));
	 *</code>
	 *
	 * @param \Phalcon\Mvc\ModelInterface $model
	 * @param array $attributes
	 * @param boolean $replace
	 */
	public function setAutomaticCreateAttributes($model, $attributes, $replace)
	{
		$this->writeMetaDataIndex($model, 10, $attributes, $replace);
	}

	/**
	 * Set the attributes that must be ignored from the UPDATE SQL generation
	 *
	 *<code>
	 *	$metaData->setAutomaticUpdateAttributes(new Robots(), array('modified_at' => true));
	 *</code>
	 *
	 * @param \Phalcon\Mvc\ModelInterface $model
	 * @param array $attributes
	 * @param boolean $replace
	 */
	public function setAutomaticUpdateAttributes($model, $attributes, $replace)
	{
		$this->writeMetaDataIndex($model, 11, $attributes, $replace);
	}

	/**
	 * Returns the column map if any
	 *
	 *<code>
	 *	print_r($metaData->getColumnMap(new Robots()));
	 *</code>
	 *
	 * @param \Phalcon\Mvc\ModelInterface $model
	 * @return array
	 */
	public function getColumnMap($model)
	{
		$data = $this->readColumnMapIndex($model, 0);
		if(is_null($data) === false && is_array($data) === false) {
			throw new Exception('The meta-data is invalid or is corrupted');
		}

		return $data;
	}

	/**
	 * Returns the reverse column map if any
	 *
	 *<code>
	 *	print_r($metaData->getReverseColumnMap(new Robots()));
	 *</code>
	 *
	 * @param \Phalcon\Mvc\ModelInterface $model
	 * @return array
	 */
	public function getReverseColumnMap($model)
	{
		$data = $this->readColumnMapIndex($model, 1);
		if(is_null($data) === false && is_array($data) === false) {
			throw new Exception('The meta-data is invalid or is corrupted');
		}

		return $data;
	}

	/**
	 * Check if a model has certain attribute
	 *
	 *<code>
	 *	var_dump($metaData->hasAttribute(new Robots(), 'name'));
	 *</code>
	 *
	 * @param \Phalcon\Mvc\ModelInterface $model
	 * @param string $attribute
	 * @return boolean
	 * @throws Exception
	 */
	public function hasAttribute($model, $attribute)
	{
		if(is_string($attribute) === false) {
			throw new Exception('Attribute must be a string');
		}

		$columnMap = $this->getReverseColumnMap($model);
		if(is_array($columnMap) === true) {
			return isset($columnMap[$attribute]);
		} else {
			$metaData = $this->readMetaData($model);
			return isset($metaData[4][$attribute]);
		}
	}

	/**
	 * Checks if the internal meta-data container is empty
	 *
	 *<code>
	 *	var_dump($metaData->isEmpty());
	 *</code>
	 *
	 * @return boolean
	 */
	public function isEmpty()
	{
		return (count($this->_metaData) === 0 ? true : false);
	}

	/**
	 * Resets internal meta-data in order to regenerate it
	 *
	 *<code>
	 *	$metaData->reset();
	 *</code>
	 */
	public function reset()
	{
		$this->_metaData = array();
		$this->_columnMap = array();
	}
}