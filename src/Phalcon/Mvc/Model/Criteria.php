<?php
/**
* Criteria
*
* @author Andres Gutierrez <andres@phalconphp.com>
* @author Eduar Carvajal <eduar@phalconphp.com>
* @author Wenzel Pünter <wenzel@phelix.me>
* @version 1.2.6
* @package Phalcon
*/
namespace Phalcon\Mvc\Model;

use \Phalcon\DI\InjectionAwareInterface,
	\Phalcon\Mvc\Model\CriteriaInterface,
	\Phalcon\Mvc\Model\Exception,
	\Phalcon\DiInterface;

/**
 * Phalcon\Mvc\Model\Criteria
 *
 * This class allows to build the array parameter required by Phalcon\Mvc\Model::find
 * and Phalcon\Mvc\Model::findFirst using an object-oriented interface
 *
 *<code>
 *$robots = Robots::query()
 *    ->where("type = :type:")
 *    ->andWhere("year < 2000")
 *    ->bind(array("type" => "mechanical"))
 *    ->order("name")
 *    ->execute();
 *</code>
 * 
 * @see https://github.com/phalcon/cphalcon/blob/1.2.6/ext/mvc/model/criteria.c
 */
class Criteria implements CriteriaInterface, 
InjectionAwareInterface
{
	/**
	 * Model
	 * 
	 * @var null|string
	 * @access protected
	*/
	protected $_model;

	/**
	 * Params
	 * 
	 * @var array
	 * @access protected
	*/
	protected $_params = array();

	/**
	 * Hidden Parameter Number
	 * 
	 * @var int
	 * @access protected
	*/
	protected $_hiddenParamNumber = 0;

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
			throw new Exception('Dependency Injector is invalid');
		}

		$this->_params['di'] = $dependencyInjector;
	}

	/**
	 * Returns the DependencyInjector container
	 *
	 * @return \Phalcon\DiInterface|null
	 */
	public function getDI()
	{
		if(isset($this->_params['di']) === true) {
			return $this->_params['di'];
		}
	}

	/**
	 * Set a model on which the query will be executed
	 *
	 * @param string $modelName
	 * @return \Phalcon\Mvc\Model\CriteriaInterface
	 * @throws Exception
	 */
	public function setModelName($modelName)
	{
		if(is_string($modelName) === false) {
			throw new Exception('Model name must be string');
		}

		$this->_model = $modelName;

		return $this;
	}

	/**
	 * Returns an internal model name on which the criteria will be applied
	 *
	 * @return string|null
	 */
	public function getModelName()
	{
		return $this->_model;
	}

	/**
	 * Sets the bound parameters in the criteria
	 * This method replaces all previously set bound parameters
	 *
	 * @param arrary $bindParams
	 * @return \Phalcon\Mvc\Model\CriteriaInterface
	 * @throws Exception
	 */
	public function bind($bindParams)
	{
		if(is_array($bindParams) === false) {
			throw new Exception('Bound parameters must be an Array');
		}

		$this->_params['bind'] = $bindParams;

		return $this;
	}

	/**
	 * Sets the bind types in the criteria
	 * This method replaces all previously set bound parameters
	 *
	 * @param array $bindTypes
	 * @return \Phalcon\Mvc\Model\CriteriaInterface
	 */
	public function bindTypes($bindTypes)
	{
		if(is_array($bindTypes) === false) {
			throw new Exception('Bind types must be an Array');
		}

		$this->_params = $bindTypes;

		return $this;
	}

	/**
	 * Sets the columns to be queried
	 *
	 *<code>
	 *	$criteria->columns(array('id', 'name'));
	 *</code>
	 *
	 * @param string|array $columns
	 * @return \Phalcon\Mvc\Model\CriteriaInterface
	 */
	public function columns($columns)
	{
		if(is_array($columns) === false &&
			is_string($columns) === false) {
			throw new Exception('Invalid parameter type.');
		}

		$this->_params['columns'] = $columns;

		return $this;
	}

	/**
	 * Adds a INNER join to the query
	 *
	 *<code>
	 *	$criteria->join('Robots');
	 *	$criteria->join('Robots', 'r.id = RobotsParts.robots_id');
	 *	$criteria->join('Robots', 'r.id = RobotsParts.robots_id', 'r');
	 *	$criteria->join('Robots', 'r.id = RobotsParts.robots_id', 'r', 'LEFT');
	 *</code>
	 *
	 * @param string $model
	 * @param string|null $conditions
	 * @param string|null $alias
	 * @param string|null $type
	 * @return \Phalcon\Mvc\Model\CriteriaInterface
	 * @throws Exception
	 */
	public function join($model, $conditions = null, $alias = null, $type = null)
	{
		if(is_string($model) === false ||
			(is_string($conditions) === false &&
				is_null($conditions) === false) ||
			(is_string($alias) === false &&
				is_null($alias) === false) ||
			(is_string($type) === false &&
				is_null($type) === false)) {
			throw new Exception('Invalid parameter type.');
		}

		$join = array($model, $conditions, $alias, $type);
		if(isset($this->_params['joins']) === true) {
			$joins = $this->_params['joins'];
			if(is_array($joins) === true) {
				$joins = array_merge($this->_params['joins'], $join);
			} else {
				$joins = $join;
			}
		} else {
			$joins = array($join);
		}

		$this->_params['joins'] = $joins;

		return $this;
	}

	/**
	 * Adds a INNER join to the query
	 *
	 *<code>
	 *	$criteria->innerJoin('Robots');
	 *	$criteria->innerJoin('Robots', 'r.id = RobotsParts.robots_id');
	 *	$criteria->innerJoin('Robots', 'r.id = RobotsParts.robots_id', 'r');
	 *</code>
	 *
	 * @param string $model
	 * @param string|null $conditions
	 * @param string|null $alias
	 * @return \Phalcon\Mvc\Model\CriteriaInterface
	 */
	public function innerJoin($model, $conditions = null, $alias = null)
	{
		return $this->join($model, $conditions, $alias, 'INNER');
	}

	/**
	 * Adds a LEFT join to the query
	 *
	 *<code>
	 *	$criteria->leftJoin('Robots', 'r.id = RobotsParts.robots_id', 'r');
	 *</code>
	 *
	 * @param string $model
	 * @param string|null $conditions
	 * @param string|null $alias
	 * @return \Phalcon\Mvc\Model\CriteriaInterface
	 */
	public function leftJoin($model, $conditions = null, $alias = null)
	{
		return $this->join($model, $condition, $alias, 'LEFT');
	}

	/**
	 * Adds a RIGHT join to the query
	 *
	 *<code>
	 *	$criteria->rightJoin('Robots', 'r.id = RobotsParts.robots_id', 'r');
	 *</code>
	 *
	 * @param string $model
	 * @param string|null $conditions
	 * @param string|null $alias
	 * @return \Phalcon\Mvc\Model\CriteriaInterface
	 */
	public function rightJoin($model, $conditions = null, $alias = null)
	{
		return $this->join($model, $conditions, $alias, 'RIGHT');
	}

	/**
	 * Sets the conditions parameter in the criteria
	 *
	 * @param string $conditions
	 * @param array|null $bindParams
	 * @param array|null $bindTypes
	 * @return \Phalcon\Mvc\Model\CriteriaInterface
	 * @throws Exception
	 */
	public function where($conditions, $bindParams = null, $bindTypes = null)
	{
		if(is_string($conditions) === false) {
			throw new Exception('Conditions must be string');
		}

		$this->_params['conditions'] = $conditions;

		//Update or merge existing bound parameters
		if(is_array($bindParams) === true) {
			if(isset($this->_params['bind']) === true) {
				$bindParams = array_merge($this->_params['bind'], $bindParams);
			}

			$this->_params['bind'] = $bindParams;
		} elseif(is_null($bindParams) === false) {
			throw new Exception('Invalid parameter type.');
		}

		//Update or merge existing bind types
		if(is_array($bindTypes) === true) {
			if(isset($this->_params['bindTypes']) === true) {
				$bindTypes = array_merge($this->_params['bindTypes'], $bindTypes);
			}

			$this->_params['bindTypes'] = $bindTypes;
		} elseif(is_null($bindTypes) === false) {
			throw new Exception('Invalid parameter type.');
		}

		return $this;
	}

	/**
	 * Appends a condition to the current conditions using an AND operator (deprecated)
	 *
	 * @param string $conditions
	 * @param array|null $bindParams
	 * @param array|null $bindTypes
	 * @return \Phalcon\Mvc\Model\CriteriaInterface
	 */
	public function addWhere($conditions, $bindParams = null, $bindTypes = null)
	{
		$this->andWhere($conditions, $bindParams, $bindTypes);
		return $this;
	}

	/**
	 * Appends a condition to the current conditions using an AND operator
	 *
	 * @param string $conditions
	 * @param array|null $bindParams
	 * @param array|null $bindTypes
	 * @return \Phalcon\Mvc\Model\CriteriaInterface
	 * @throws Exception
	 */
	public function andWhere($conditions, $bindParams = null, $bindTypes = null)
	{
		if(is_string($conditions) === false) {
			throw new Exception('Invalid parameter type.');
		}

		if(isset($this->_params['conditions']) === true) {
			$conditions = '('.$this->_params['conditions'].') AND ('.$conditions.')';
		}

		$this->_params['conditions'] = $conditions;

		//Update or merge existing bound parameters
		if(is_array($bindParams) === true) {
			if(isset($this->_params['bind']) === true) {
				$bindParams = array_merge($this->_params['bind'], $bindParams);
			}

			$this->_params['bind'] = $bindParams;
		}

		//Update or merge existing bind types
		if(is_array($bindTypes) === true) {
			if(isset($this->_params['bindTypes']) === true) {
				$bindTypes = array_merge($this->_params['bindTypes'], $bindTypes);
			}

			$this->_params['bindTypes'] = $bindTypes;
		}

		return $this;
	}

	/**
	 * Appends a condition to the current conditions using an OR operator
	 *
	 * @param string $conditions
	 * @param array|null $bindParams
	 * @param array|null $bindTypes
	 * @return \Phalcon\Mvc\Model\CriteriaInterface
	 * @throws Exception
	 */
	public function orWhere($conditions, $bindParams = null, $bindTypes = null)
	{
		if(is_string($conditions) === false) {
			throw new Exception('Invalid parameter type.');
		}

		if(isset($this->_params['conditions']) === true) {
			$conditions = '('.$this->_params['conditions'].') OR ('.$conditions.')';
		}

		$this->_params['conditions'] = $conditions;

		//Update or merge existing bound parameters
		if(is_array($bindParams) === true) {
			if(isset($this->_params['bind']) === true) {
				$bindParams = array_merge($this->_params['bind'], $bindParams);
			}

			$this->_params['bind'] = $bindParams;
		}

		//Update or merge existing bind types
		if(is_array($bindTypes) === true) {
			if(isset($this->_params['bindTypes']) === true) {
				$bindTypes = array_merge($this->_params['bindTypes'], $bindTypes);
			}

			$this->_params['bindTypes'] = $bindTypes;
		}

		return $this;
	}

	/**
	 * Appends a BETWEEN condition to the current conditions
	 *
	 *<code>
	 *	$criteria->betweenWhere('price', 100.25, 200.50);
	 *</code>
	 *
	 * @param string $expr
	 * @param mixed $minimum
	 * @param mixed $maximum
	 * @return \Phalcon\Mvc\Model\CriteriaInterface
	 * @throws Exception
	 */
	public function betweenWhere($expr, $minimum, $maximum)
	{
		if(is_string($expr) === false) {
			throw new Exception('Invalid parameter type.');
		}

		$hiddenParam = $this->_hiddenParamNumber;
		$nextHiddenParam = $hiddenParam++;

		//Minimum key with auto bind-params
		$minimumKey = 'phb'.$hiddenParam;

		//Maximum key with auto bind-params
		$maximumKey = 'phb'.$nextHiddenParam;

		//Create a standard BETWEEN condition with bind params
		$conditions = $expr.' BETWEEN :'.$minimumKey.': AND :'.$maximumKey.':';

		//Append the BETWEEN to the current conditions using 'AND'
		$this->addWhere($condition, array($minimumKey, $maximumKey));
		$this->_hiddenParamNumber = $nextHiddenParam;

		return $this;
	}

	/**
	 * Appends a NOT BETWEEN condition to the current conditions
	 *
	 *<code>
	 *	$criteria->notBetweenWhere('price', 100.25, 200.50);
	 *</code>
	 *
	 * @param string $expr
	 * @param mixed $minimum
	 * @param mixed $maximum
	 * @return \Phalcon\Mvc\Model\CriteriaInterface
	 * @throws Exception
	 */
	public function notBetweenWhere($expr, $minimum, $maximum)
	{
		if(is_string($expr) === false) {
			throw new Exception('Invalid parameter type.');
		}

		$hiddenParam = $this->_hiddenParamNumber;
		$nextHiddenParam = $hiddenParam++;

		//Minimum key with auto bind-params
		$minimumKey = 'phb'.$hiddenParam;

		//Maximum key with auto bind-params
		$maximumKey = 'phb'.$nextHiddenParam;

		//Create a standard BETWEEN condition with bind params
		$conditions = $expr.' NOT BETWEEN :'.$minimumKey.': AND :'.$maximumKey.':';

		//Append the BETWEEN to the current conditions using 'AND'
		$this->addWhere($condition, array($minimumKey, $maximumKey));
		$this->_hiddenParamNumber = $nextHiddenParam;	

		return $this;
	}

	/**
	 * Appends an IN condition to the current conditions
	 *
	 *<code>
	 *	$criteria->inWhere('id', [1, 2, 3]);
	 *</code>
	 *
	 * @param string $expr
	 * @param array $values
	 * @return \Phalcon\Mvc\Model\CriteriaInterface
	 * @throws Exception
	 */
	public function inWhere($expr, $values)
	{
		if(is_string($expr) === false) {
			throw new Exception('Invalid parameter type.');
		}

		if(is_array($values) === false) {
			throw new Exception('Values must be an array');
		}

		$hiddenParam = $this->_hiddenParamNumber;
		$bindParams = array();
		$bindKeys = array();

		foreach($values as $value) {
			//Key with auto bind-params
			$key = 'phi'.$hiddenParam;
			$bindKeys[] = $key;
			$bindParams[$key] = ':'.$key.':';
			$hiddenParam++;
		}

		$joinedKeys = implode(', ', $bindKeys);

		//Create a standard IN condition with bind params
		$conditions = $expr.' IN ('.$joinedKeys.')';

		//Append the IN to the current conditions using 'AND'
		$this->andWhere($conditions, $bindParams);
		$this->_hiddenParamNumber = $hiddenParam;

		return $this;
	}

	/**
	 * Appends a NOT IN condition to the current conditions
	 *
	 *<code>
	 *	$criteria->notInWhere('id', [1, 2, 3]);
	 *</code>
	 *
	 * @param string $expr
	 * @param array $values
	 * @return \Phalcon\Mvc\Model\CriteriaInterface
	 * @throws Exception
	 */
	public function notInWhere($expr, $values)
	{
		if(is_array($values) === false) {
			throw new Exception('Values must be an array');
		}

		$hiddenParam = $this->_hiddenParamNumber;
		$bindParams = array();
		$bindKeys = array();

		foreach($values as $value) {
			//Key with auto bind-params
			$key = 'phi'.$hiddenParam;
			$bindKeys[] = ':'.$key.':';
			$bindParams[$key] = $value;
			$hiddenParam++;
		}

		$joinedKeys = implode(', ', $bindKeys);

		//Create a standard NOT IN condition with bind params
		$conditions = $expr.' NOT IN ('.$joinedKeys.')';

		//Append the IN to the current conditions using 'AND'
		$this->andWhere($conditions, $bindParams);
		$this->_hiddenParamNumber = $hiddenParam;

		return $this;
	}

	/**
	 * Adds the conditions parameter to the criteria
	 *
	 * @param string $conditions
	 * @return \Phalcon\Mvc\Model\CriteriaIntreface
	 * @throws Exception
	 */
	public function conditions($conditions)
	{
		if(is_string($conditions) === false) {
			throw new Exception('Conditions must be string');
		}

		$this->_params['conditions'] = $conditions;

		return $this;
	}

	/**
	 * Adds the order-by parameter to the criteria (deprecated)
	 *
	 * @param string $orderColumns
	 * @return \Phalcon\Mvc\Model\CriteriaInterface
	 * @throws Exception
	 */
	public function order($orderColumns)
	{
		if(is_string($orderColumns) === false) {
			throw new Exception('Order columns must be string');
		}

		$this->_params['order'] = $orderColumns;

		return $this;
	}

	/**
	 * Adds the order-by parameter to the criteria
	 *
	 * @param string $orderColumns
	 * @return \Phalcon\Mvc\Model\CriteriaInterface
	 * @throws Exception
	 */
	public function orderBy($orderColumns)
	{
		if(is_string($orderColumns) === false) {
			throw new Exception('Order columns must be string');
		}

		$this->_params['order'] = $orderColumns;

		return $this;
	}

	/**
	 * Adds the limit parameter to the criteria
	 *
	 * @param int $limit
	 * @param int|null $offset
	 * @return \Phalcon\Mvc\Model\CriteriaInterface
	 * @throws Exception
	 */
	public function limit($limit, $offset = null)
	{
		if(is_numeric($limit) === false) {
			throw new Exception('rows limit parameter must be integer');
		}

		if(is_null($offset) === true) {
			$this->_params['limit'] = (int)$limit;
		} else {
			$this->_params['limit'] = array('number' => (int)$limit, 'offset' => $offset);
		}

		return $this;
	}

	/**
	 * Adds the "for_update" parameter to the criteria
	 *
	 * @param boolean|null $forUpdate
	 * @return \Phalcon\Mvc\Model\CriteriaInterface
	 * @throws Exception
	 */
	public function forUpdate($forUpdate = null)
	{
		if(is_null($forUpdate) === true) {
			$forUpdate = true;
		} elseif(is_bool($forUpdate) === false) {
			throw new Exception('Invalid parameter type.');
		}

		$this->_params['for_update'] = $forUpdate;

		return $this;
	}

	/**
	 * Adds the "shared_lock" parameter to the criteria
	 *
	 * @param boolean|null $sharedLock
	 * @return \Phalcon\Mvc\Model\CriteriaInterface
	 * @throws Exception
	 */
	public function sharedLock($sharedLock = null)
	{
		if(is_null($sharedLock) === true) {
			$sharedLock = true;
		} elseif(is_bool($sharedLock) === false) {
			throw new Exception('Invalid parameter type.');
		}

		$this->_params['shared_lock'] = $sharedLock;

		return $this;
	}

	/**
	 * Returns the conditions parameter in the criteria
	 *
	 * @return string|null
	 */
	public function getWhere()
	{
		if(isset($this->_params['conditions']) === true) {
			return $this->_params['conditions'];
		}
	}

	/**
	 * Return the columns to be queried
	 *
	 * @return string|array|null
	 */
	public function getColumns()
	{
		if(isset($this->_params['columns']) === true) {
			return $this->_params['columns'];
		}
	}

	/**
	 * Returns the conditions parameter in the criteria
	 *
	 * @return string|null
	 */
	public function getConditions()
	{
		if(isset($this->_params['conditions']) === true) {
			return $this->_params['conditions'];
		}
	}

	/**
	 * Returns the limit parameter in the criteria
	 *
	 * @return string|array|null
	 */
	public function getLimit()
	{
		if(isset($this->_params['limit']) === true) {
			return $this->_params['limit'];
		}
	}

	/**
	 * Returns the order parameter in the criteria
	 *
	 * @return string|null
	 */
	public function getOrder()
	{
		if(isset($this->_params['order']) === true) {
			return $this->_params['order'];
		}
	}

	/**
	 * Returns all the parameters defined in the criteria
	 *
	 * @return array
	 */
	public function getParams()
	{
		return $this->_params;
	}

	/**
	 * Builds a \Phalcon\Mvc\Model\Criteria based on an input array like $_POST
	 *
	 * @param \Phalcon\DiInterface $dependencyInjector
	 * @param string $modelName
	 * @param array $data
	 * @return \Phalcon\Mvc\Model\Criteria
	 * @throws Exception
	 */
	public static function fromInput($dependencyInjector, $modelName, $data)
	{
		if(is_object($dependencyInjector) === false ||
			$dependencyInjector instanceof DiInterface === false) {
			throw new Exception('A dependency injector container is required to obtain the ORM services');
		}

		if(is_string($modelName) === false) {
			throw new Exception('Invalid parameter type.');
		}

		if(is_array($data) === false) {
			throw new Exception('Model data must be an Array');
		}

		if(empty($data) === false) {
			$conditions = array();
			$metaData = $dependencyInjector->getShared('modelsMetadata');
			$model = new $modelName();
			$dataTypes = $metaData->getDataTypes($model);
			$bind = array();

			//We look for attributes in the array passed as data
			foreach($data as $field => $value) {
				if(isset($dataTypes[$field]) === true &&
					is_null($value) === false && $value !== '') {
					if($dataTypes[$field] === 2) {
						//For varchar types we use LIKE operator
						$condition = $field.' LIKE :'.$field.':';
						$bind[$field] = '%'.$value.'%';
					} else {
						//For the rest of data types we use a plain = operator
						$condition = $field.'=:'.$field.':';
						$bind[$field] = $value;
					}

					$conditions[] = $condition;
				}
			}
		}

		//Create an object instance and pass the parameters to it
		$criteria = new Criteria();
		if(isset($conditions) === true && empty($conditions) === false) {
			$criteria->where(implode(' AND ', $conditions));
			$criteria->bind($bind);
		}

		$criteria->setModelName($modelName);
		return $criteria;
	}

	/**
	 * Executes a find using the parameters built with the criteria
	 *
	 * @return \Phalcon\Mvc\Model\ResultsetInterface
	 * @throws Exception
	 */
	public function execute()
	{
		if(is_string($this->_model) === false) {
			throw new Exception('Model name must be string');
		}

		$params = $this->getParams();
		
		$resultset = forward_static_call_array(array($this->_model, 'find'), $params);

		return $resultset;
	}
}