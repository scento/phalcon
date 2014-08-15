<?php
/**
 * Query Builder
 *
 * @author Andres Gutierrez <andres@phalconphp.com>
 * @author Eduar Carvajal <eduar@phalconphp.com>
 * @author Wenzel Pünter <wenzel@phelix.me>
 * @version 1.2.6
 * @package Phalcon
*/
namespace Phalcon\Mvc\Model\Query;

use \Phalcon\Mvc\Model\Exception,
	\Phalcon\Mvc\Model\Query,
	\Phalcon\Mvc\Model\Query\BuilderInterface,
	\Phalcon\DI\InjectionAwareInterface,
	\Phalcon\DiInterface,
	\Phalcon\DI;

/**
 * Phalcon\Mvc\Model\Query\Builder
 *
 * Helps to create PHQL queries using an OO interface
 *
 *<code>
 *$resultset = $this->modelsManager->createBuilder()
 *   ->from('Robots')
 *   ->join('RobotsParts')
 *   ->limit(20)
 *   ->orderBy('Robots.name')
 *   ->getQuery()
 *   ->execute();
 *</code>
 * 
 * @see https://github.com/phalcon/cphalcon/blob/1.2.6/ext/mvc/model/query/builder.c
 */
class Builder implements BuilderInterface, InjectionAwareInterface
{
	/**
	 * Dependency Injector
	 * 
	 * @var null|\Phalcon\DiInterface
	 * @access protected
	*/
	protected $_dependencyInjector;

	/**
	 * Columns
	 * 
	 * @var null|string|array
	 * @access protected
	*/
	protected $_columns;

	/**
	 * Models
	 * 
	 * @var null|string|array
	 * @access protected
	*/
	protected $_models;

	/**
	 * Joins
	 * 
	 * @var null|array
	 * @access protected
	*/
	protected $_joins;

	/**
	 * Conditions
	 * 
	 * @var null|string
	 * @access protected
	*/
	protected $_conditions;

	/**
	 * Group
	 * 
	 * @var null|string
	 * @access protected
	*/
	protected $_group;

	/**
	 * Having
	 * 
	 * @var null|string
	 * @access protected
	*/
	protected $_having;

	/**
	 * Order
	 * 
	 * @var null|string
	 * @access protected
	*/
	protected $_order;

	/**
	 * Limit
	 * 
	 * @var null|int
	 * @access protected
	*/
	protected $_limit;

	/**
	 * Offset
	 * 
	 * @var null|int
	 * @access protected
	*/
	protected $_offset;

	/**
	 * For Update
	 * 
	 * @var null
	 * @access protected
	*/
	protected $_forUpdate;

	/**
	 * Shared Lock
	 * 
	 * @var null
	 * @access protected
	*/
	protected $_sharedLock;

	/**
	 * Bind Params
	 * 
	 * @var null|array
	 * @access protected
	*/
	protected $_bindParams;

	/**
	 * Bind Types
	 * 
	 * @var null|array
	 * @access protected
	*/
	protected $_bindTypes;

	/**
	 * Hidden Parameter Number
	 * 
	 * @var int
	 * @access protected
	*/
	protected $_hiddenParamNumber = 0;

	/**
	 * \Phalcon\Mvc\Model\Query\Builder constructor
	 *
	 *<code>
	 * $params = array(
	 *    'models'     => array('Users'),
	 *    'columns'    => array('id', 'name', 'status'),
	 *    'conditions' => "created > '2013-01-01' AND created < '2014-01-01'",
	 *    'group'      => array('id', 'name'),
	 *    'having'     => "name = 'Kamil'",
	 *    'order'      => array('name', 'id'),
	 *    'limit'      => 20,
	 *    'offset'     => 20,
	 *);
	 *$queryBuilder = new \Phalcon\Mvc\Model\Query\Builder($params);
	 *</code> 
	 *
	 * @param array|null $params
	 * @param \Phalcon\DiInterface|null $dependencyInjector
	 */
	public function __construct($params = null, $dependencyInjector = null)
	{
		if(is_array($params) === true) {
			/* Process parameters */

			//Conditions
			if(isset($params[0]) === true) {
				$this->_conditions = $params[0];
			} elseif(isset($params['conditions']) === true) {
				$this->_conditions = $params['conditions'];
			}

			//Assign 'FROM' clause
			if(isset($params['models']) === true) {
				$this->_models = $params['models'];
			}

			//Assign COLUMNS clause
			if(isset($params['columns']) === true) {
				$this->_columns = $params['columns'];
			}

			//Assign JOINS clause
			if(isset($params['joins']) === true) {
				$this->_joins = $params['joins'];
			}

			//Assign GROUP clause
			if(isset($params['group']) === true) {
				$this->_group = $params['group'];
			}

			//Assign HAVING clause
			if(isset($params['having']) === true) {
				$this->_having = $params['having'];
			}

			//Assign ORDER clause
			if(isset($params['order']) === true) {
				$this->_order = $params['order'];
			}

			//Assign LIMIT clause
			if(isset($params['limit']) === true) {
				$this->_limit = $params['limit'];
			}

			//Assign OFFSET clause
			if(isset($params['offset']) === true) {
				$this->_offset = $params['offset'];
			}

			//Assign FOR UPDATE clause
			if(isset($params['for_update']) === true) {
				$this->_forUpdate = $params['forUpdate'];
			}

			//Assign SHARED LOCK clause
			if(isset($params['shared_lock']) === true) {
				$this->_sharedLock = $params['shared_lock'];
			}
		}

		//Update the dependency injector if any
		if(is_object($dependencyInjector) === true &&
			$dependencyInjector instanceof DiInterface) {
			$this->_dependencyInjector = $dependencyInjector;
		}
	}

	/**
	 * Sets the DependencyInjector container
	 *
	 * @param \Phalcon\DiInterface $dependencyInjector
	 * @return \Phalcon\Mvc\Model\Query\Builder
	 * @throws Exception
	 */
	public function setDI($dependencyInjector)
	{
		if(is_object($dependencyInjector) === false ||
			$dependencyInjector instanceof DiInterface === false) {
			throw new Exception('The dependency injector must be an Object');
		}

		$this->_dependencyInjector = $dependencyInjector;

		return $this;
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
	 * Sets the columns to be queried
	 *
	 *<code>
	 *	$builder->columns(array('id', 'name'));
	 *</code>
	 *
	 * @param string|array $columns
	 * @return \Phalcon\Mvc\Model\Query\Builder
	 * @throws Exception
	 */
	public function columns($columns)
	{
		if(is_string($columns) === false &&
			is_array($columns) === false) {
			throw new Exception('Invalid parameter type.');
		}

		$this->_columns = $columns;

		return $this;
	}

	/**
	 * Return the columns to be queried
	 *
	 * @return string|array|null
	 */
	public function getColumns()
	{
		return $this->_columns;
	}

	/**
	 * Sets the models who makes part of the query
	 *
	 *<code>
	 *	$builder->from('Robots');
	 *	$builder->from(array('Robots', 'RobotsParts'));
	 *</code>
	 *
	 * @param string|array $models
	 * @return \Phalcon\Mvc\Model\Query\Builder
	 * @throws Exception
	 */
	public function from($models)
	{
		if(is_string($models) === false &&
			is_array($models) === false) {
			throw new Exception('Invalid parameter type.');
		}

		$this->_models = $models;

		return $this;
	}

	/**
	 * Add a model to take part of the query
	 *
	 *<code>
	 *	$builder->addFrom('Robots', 'r');
	 *</code>
	 *
	 * @param string $model
	 * @param string|null $alias
	 * @return \Phalcon\Mvc\Model\Query\Builder
	 */
	public function addFrom($model, $alias = null)
	{
		if(is_string($model) === false ||
			(is_string($alias) === false &&
			is_null($alias) === false)) {
			throw new Exception('Invalid parameter type.');
		}

		$models = $this->_models;

		if(is_array($this->_models) === false) {
			if(is_null($this->_models) === false) {
				$models = array($this->_models);
			} else {
				$models = array();
			}
		}

		if(is_string($alias) === false) {
			$models[$alias] = $model;
		} else {
			$models[] = $model;
		}

		$this->_models = $models;

		return $this;
	}

	/**
	 * Return the models who makes part of the query
	 *
	 * @return string|array|null
	 */
	public function getFrom()
	{
		return $this->_models;
	}

	/**
	 * Adds a INNER join to the query
	 *
	 *<code>
	 *	$builder->join('Robots');
	 *	$builder->join('Robots', 'r.id = RobotsParts.robots_id');
	 *	$builder->join('Robots', 'r.id = RobotsParts.robots_id', 'r');
	 *	$builder->join('Robots', 'r.id = RobotsParts.robots_id', 'r', 'LEFT');
	 *</code>
	 *
	 * @param string $model
	 * @param string|null $conditions
	 * @param string|null $alias
	 * @param string|null $type
	 * @return \Phalcon\Mvc\Model\Query\Builder
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

		if(is_array($this->_joins) === false) {
			$this->_joins = array();
		}

		$this->_joins[] = array($model, $conditions, $alias, $type);

		return $this;
	}

	/**
	 * Adds a INNER join to the query
	 *
	 *<code>
	 *	$builder->innerJoin('Robots');
	 *	$builder->innerJoin('Robots', 'r.id = RobotsParts.robots_id');
	 *	$builder->innerJoin('Robots', 'r.id = RobotsParts.robots_id', 'r');
	 *	$builder->innerJoin('Robots', 'r.id = RobotsParts.robots_id', 'r', 'LEFT');
	 *</code>
	 *
	 * @param string $model
	 * @param string|null $conditions
	 * @param string|null $alias
	 * @return \Phalcon\Mvc\Model\Query\Builder
	 */
	public function innerJoin($model, $conditions = null, $alias = null)
	{
		return $this->join($model, $conditions, $alias, 'INNER');
	}

	/**
	 * Adds a LEFT join to the query
	 *
	 *<code>
	 *	$builder->leftJoin('Robots', 'r.id = RobotsParts.robots_id', 'r');
	 *</code>
	 *
	 * @param string $model
	 * @param string|null $conditions
	 * @param string|null $alias
	 * @return \Phalcon\Mvc\Model\Query\Builder
	 */
	public function leftJoin($model, $conditions = null, $alias = null)
	{
		return $this->join($model, $conditions, $alias, 'LEFT');
	}

	/**
	 * Adds a RIGHT join to the query
	 *
	 *<code>
	 *	$builder->rightJoin('Robots', 'r.id = RobotsParts.robots_id', 'r');
	 *</code>
	 *
	 * @param string $model
	 * @param string|null $conditions
	 * @param string|null $alias
	 * @return \Phalcon\Mvc\Model\Query\Builder
	 */
	public function rightJoin($model, $conditions = null, $alias = null)
	{
		return $this->join($model, $conditions, $alias, 'RIGHT');
	}

	/**
	 * Sets the query conditions
	 *
	 *<code>
	 *	$builder->where('name = "Peter"');
	 *	$builder->where('name = :name: AND id > :id:', array('name' => 'Peter', 'id' => 100));
	 *</code>
	 *
	 * @param string $conditions
	 * @param array|null $bindParams
	 * @param array|null $bindTypes
	 * @return \Phalcon\Mvc\Model\Query\Builder
	 * @throws Exception
	 */
	public function where($conditions, $bindParams = null, $bindTypes = null)
	{
		if(is_string($conditions) === false ||
			(is_array($bindParams) === false &&
				is_null($bindParams) === false) ||
			(is_array($bindTypes) === false &&
				is_null($bindTypes) === false)) {
			throw new Exception('Invalid parameter type.');
		}

		$this->_conditions = $conditions;

		//Merge the bind params to the current ones
		if(is_array($bindParams) === true) {
			if(is_array($this->_bindParams) === true) {
				$bindParams = array_merge($bindParams, $this->_bindParams);
			}

			$this->_bindParams = $bindParams;
		}

		//Merge the bind types to the current ones
		if(is_array($bindTypes) === true) {
			if(is_array($this->_bindTypes) === true) {
				$bindTypes = array_merge($bindTypes, $this->_bindTypes);
			}

			$this->_bindTypes = $bindTypes;
		}

		return $this;
	}

	/**
	 * Appends a condition to the current conditions using a AND operator
	 *
	 *<code>
	 *	$builder->andWhere('name = "Peter"');
	 *	$builder->andWhere('name = :name: AND id > :id:', array('name' => 'Peter', 'id' => 100));
	 *</code>
	 *
	 * @param string $conditions
	 * @param array|null $bindParams
	 * @param array|null $bindTypes
	 * @return \Phalcon\Mvc\Model\Query\Builder
	 * @throws Exception
	 */
	public function andWhere($conditions, $bindParams = null, $bindTypes = null)
	{
		if(is_string($conditions) === false ||
			(is_array($bindParams) === false &&
				is_null($bindParams) === false) ||
			(is_array($bindTypes) === false &&
				is_null($bindTypes) === false)) {
			throw new Exception('Invalid parameter type.');
		}

		//Nest the conditions to the current ones or set as unique
		if(isset($this->_conditions) === true) {
			$conditions = '('.$this->_conditions.') AND ('.$conditions.')';
		}

		$this->_conditions = $conditions;

		//Merge the bind params to the current ones
		if(is_array($bindParams) === true) {
			if(is_array($this->_bindParams) === true) {
				$this->_bindParams = array_merge($bindParams, $this->_bindParams);
			} else {
				$this->_bindParams = $_bindParams;
			}
		}

		//Merge the bind types to the current ones
		if(is_array($bindTypes) === true) {
			if(is_array($this->_bindTypes) === true) {
				$this->_bindTypes = array_merge($bindTypes, $this->_bindTypes);
			} else {
				$this->_bindTypes = $bindTypes;
			}
		}

		return $this;
	}

	/**
	 * Appends a condition to the current conditions using a OR operator
	 *
	 *<code>
	 *	$builder->orWhere('name = "Peter"');
	 *	$builder->orWhere('name = :name: AND id > :id:', array('name' => 'Peter', 'id' => 100));
	 *</code>
	 *
	 * @param string $conditions
	 * @param array|null $bindParams
	 * @param array|null $bindTypes
	 * @return \Phalcon\Mvc\Model\Query\Builder
	 * @throws Exception
	 */
	public function orWhere($conditions, $bindParams = null, $bindTypes = null)
	{
		if(is_string($conditions) === false ||
			(is_array($bindParams) === false &&
				is_null($bindParams) === false) ||
			(is_array($bindTypes) === false &&
				is_null($bindTypes) === false)) {
			throw new Exception('Invalid parameter type.');
		}

		//Nest the conditions to the current ones or set as unique
		if(isset($this->_conditions) === true) {
			$conditions = '('.$this->_conditions.') OR ('.$conditions.')';
		}

		$this->_conditions = $conditions;

		//Merge the bind params to the current ones
		if(is_array($bindParams) === true) {
			if(is_array($this->_bindParams) === true) {
				$this->_bindParams = array_merge($bindParams, $this->_bindParams);
			} else {
				$this->_bindParams = $_bindParams;
			}
		}

		//Merge the bind types to the current ones
		if(is_array($bindTypes) === true) {
			if(is_array($this->_bindTypes) === true) {
				$this->_bindTypes = array_merge($bindTypes, $this->_bindTypes);
			} else {
				$this->_bindTypes = $bindTypes;
			}
		}

		return $this;
	}

	/**
	 * Appends a BETWEEN condition to the current conditions
	 *
	 *<code>
	 *	$builder->betweenWhere('price', 100.25, 200.50);
	 *</code>
	 *
	 * @param string $expr
	 * @param mixed $minimum
	 * @param mixed $maximum
	 * @return \Phalcon\Mvc\Model\Query\Builder
	 * @throws Exception
	 */
	public function betweenWhere($expr, $minimum, $maximum)
	{
		/* Type validation */
		if(is_string($expr) === false) {
			throw new Exception('Invalid parameter type.');
		}

		/* Create the parameters */
		$nextHiddenParam = $this->_hiddenParamNumber + 1;
		$minimumKey = 'phb'.$this->_hiddenParamNumber;
		$maximumKey = 'phb'.$nextHiddenParam;
		$conditions = $expr.' BETWEEN :'.$minimumKey.': AND :'.$maximumKey.':';
		$bindParams = array($minimumKey => $minimum, $maximumKey => $maximum);

		//Append the BETWEEN to the current conditions using 'AND'
		$this->andWhere($conditions, $bindParams);
		$this->_hiddenParamNumber = $nextHiddenParam;

		return $this;
	}

	/**
	 * Appends a NOT BETWEEN condition to the current conditions
	 *
	 *<code>
	 *	$builder->notBetweenWhere('price', 100.25, 200.50);
	 *</code>
	 *
	 * @param string $expr
	 * @param mixed $minimum
	 * @param mixed $maximum
	 * @return \Phalcon\Mvc\Model\Query\Builder
	 * @throws Exception
	 */
	public function notBetweenWhere($expr, $minimum, $maximum)
	{
		/* Type validation */
		if(is_string($expr) === false) {
			throw new Exception('Invalid parameter type.');
		}

		/* Create the parameters */
		$nextHiddenParam = $this->_hiddenParamNumber + 1;
		$minimumKey = 'phb'.$this->_hiddenParamNumber;
		$maximumKey = 'phb'.$nextHiddenParam;
		$conditions = $expr.' NOT BETWEEN :'.$minimumKey.': AND :'.$maximumKey.':';
		$bindParams = array($minimumKey => $minimum, $maximumKey => $maximum);

		//Append the BETWEEN to the current conditions using 'AND'
		$this->andWhere($conditions, $bindParams);
		$this->_hiddenParamNumber = $nextHiddenParam;

		return $this;
	}

	/**
	 * Appends an IN condition to the current conditions
	 *
	 *<code>
	 *	$builder->inWhere('id', [1, 2, 3]);
	 *</code>
	 *
	 * @param string $expr
	 * @param array $values
	 * @return \Phalcon\Mvc\Model\Query\Builder
	 * @throws Exception
	 */
	public function inWhere($expr, $values)
	{
		/* Type validation */
		if(is_string($expr) === false) {
			throw new Exception('Invalid parameter type.');
		}

		if(is_array($values) === false) {
			throw new Exception('Values must be an array');
		}

		/* Create the parameters */
		$hiddenParam = $this->_hiddenParamNumber;
		$bindParams = array();
		$bindKeys = array();

		foreach($values as $value) {
			//Key with auto-bind params
			$key = 'phi'.$hiddenParam;
			$queryKey = ':'.$key.':';
			$bindKeys[] = $queryKey;
			$bindParams[$key] = $value;
			$hiddenParam++;
		}

		//Create a standard IN condition with bind params
		$conditions = $expr.' IN ('.implode(', ', $bindKeys).')';

		//Append the IN to the current conditions using 'AND'
		$this->andWhere($conditions, $bindParams);
		$this->_hiddenParamNumber = $hiddenParam;

		return $this;
	}

	/**
	 * Appends a NOT IN condition to the current conditions
	 *
	 *<code>
	 *	$builder->notInWhere('id', [1, 2, 3]);
	 *</code>
	 *
	 * @param string $expr
	 * @param array $values
	 * @return \Phalcon\Mvc\Model\Query\Builder
	 * @throws Exception
	 */
	public function notInWhere($expr, $values)
	{
		/* Type validation */
		if(is_string($expr) === false) {
			throw new Exception('Invalid parameter type.');
		}

		if(is_array($values) === false) {
			throw new Exception('Values must be an array');
		}

		/* Create the parameters */
		$hiddenParam = $this->_hiddenParamNumber;
		$bindParams = array();
		$bindKeys = array();

		foreach($values as $value) {
			//Key with auto-bind params
			$key = 'phi'.$hiddenParam;
			$queryKey = ':'.$key.':';
			$bindKeys[] = $queryKey;
			$bindParams[$key] = $value;
			$hiddenParam++;
		}

		//Create a standard IN condition with bind params
		$conditions = $expr.' NOT IN ('.implode(', ', $bindKeys).')';

		//Append the IN to the current conditions using 'AND'
		$this->andWhere($conditions, $bindParams);
		$this->_hiddenParamNumber = $hiddenParam;

		return $this;
	}

	/**
	 * Return the conditions for the query
	 *
	 * @return string|array|null
	 */
	public function getWhere()
	{
		return $this->_conditions;
	}

	/**
	 * Sets a ORDER BY condition clause
	 *
	 *<code>
	 *	$builder->orderBy('Robots.name');
	 *	$builder->orderBy(array('1', 'Robots.name'));
	 *</code>
	 *
	 * @param string $orderBy
	 * @return \Phalcon\Mvc\Model\Query\Builder
	 * @throws Exception
	 */
	public function orderBy($orderBy)
	{
		if(is_string($orderBy) === false) {
			throw new Exception('Invalid parameter type.');
		}

		$this->_order = $orderBy;

		return $this;
	}

	/**
	 * Returns the set ORDER BY clause
	 *
	 * @return string|array|null
	 */
	public function getOrderBy()
	{
		return $this->_order;
	}

	/**
	 * Sets a HAVING condition clause. You need to escape PHQL reserved words using [ and ] delimiters
	 *
	 *<code>
	 *	$builder->having('SUM(Robots.price) > 0');
	 *</code>
	 *
	 * @param string $having
	 * @return \Phalcon\Mvc\Model\Query\Builder
	 * @throws Exception
	 */
	public function having($having)
	{
		if(is_string($having) === false) {
			throw new Exception('Invalid parameter type.');
		}

		$this->_having = $having;

		return $this;
	}

	/**
	 * Return the current having clause
	 *
	 * @return string|null
	 */
	public function getHaving()
	{
		return $this->_having;
	}

	/**
	 * Sets a LIMIT clause, optionally a offset clause
	 *
	 *<code>
	 *	$builder->limit(100);
	 *	$builder->limit(100, 20);
	 *</code>
	 *
	 * @param int $limit
	 * @param int|null $offset
	 * @return \Phalcon\Mvc\Model\Query\Builder
	 * @throws Exception
	 */
	public function limit($limit, $offset = null)
	{
		if(is_numeric($limit) === true) {
			$this->_limit = (int)$limit;
		} else {
			throw new Exception('Invalid parameter type.');
		}

		if(is_null($offset) === false && is_numeric($offset) === true) {
			$this->_offset = (int)$offset;
		}

		return $this;
	}

	/**
	 * Returns the current LIMIT clause
	 *
	 * @return int|null
	 */
	public function getLimit()
	{
		return $this->_limit;
	}

	/**
	 * Sets an OFFSET clause
	 *
	 *<code>
	 *	$builder->offset(30);
	 *</code>
	 *
	 * @param int $offset
	 * @return \Phalcon\Mvc\Model\Query\Builder
	 * @throws Exception
	 */
	public function offset($offset)
	{
		if(is_int($offset) === false) {
			throw new Exception('Invalid parameter type.');
		}

		$this->_offset = $offset;

		return $this;
	}

	/**
	 * Returns the current OFFSET clause
	 *
	 * @return int|null
	 */
	public function getOffset()
	{
		return $this->_offset;
	}

	/**
	 * Sets a GROUP BY clause
	 *
	 *<code>
	 *	$builder->groupBy(array('Robots.name'));
	 *</code>
	 *
	 * @param string $group
	 * @return \Phalcon\Mvc\Model\Query\Builder
	 * @throws Exception
	 */
	public function groupBy($group)
	{
		//@note the provided code does not match the expected data type
		if(is_string($group) === false) {
			throw new Exception('Invalid parameter type.');
		}

		$this->_group = $group;

		return $this;
	}

	/**
	 * Returns the GROUP BY clause
	 *
	 * @return string|null
	 */
	public function getGroupBy()
	{
		return $this->_group;
	}

	/**
	 * Returns a PHQL statement built based on the builder parameters
	 *
	 * @return string
	 * @throws Exception
	 */
	public function getPhql()
	{
		if(is_object($this->_dependencyInjector) === false) {
			$this->_dependencyInjector = DI::getDefault();
		}

		$dependencyInjector = $this->_dependencyInjector;
		$models = $this->_models;
		$conditions = $this->_conditions;
		$columns = $this->_columns;
		$joins = $this->_joins;
		$group = $this->_group;
		$having  = $this->_having;
		$order = $this->_order;
		$limit = $this->_limit;

		if(is_array($models) === true && count($models) == 0) {
			throw new Exception('At least one model is required to build the query');
		} elseif(isset($models) === false) {
			throw new Exception('At least one model is required to build the query');
		}

		if(is_numeric($conditions) === true) {
			//If the conditions is a single numeric field. We internally create a condition
			//using the related primary key
			if(is_array($models) === true) {
				if(1 < count($models)) {
					throw new Exception('Cannot build the query. Invalid condition');
				}
				$model = $models[0];
			} else {
				$model = $models;
			}

			//Get the models metadata service to obtain the column names, column map and
			//primary key
			$noPrimary = true;
			$modelInstance = new $model($dependencyInjector);
			$metaData = $dependencyInjector->getShared('modelsMetadata');
			$primaryKeys = $metaData->getPrimaryKeyAttributes($modelInstance);

			if(empty($primaryKeys) == false && isset($primaryKeys[0]) === true) {
				$firstPrimaryKey = $primaryKeys[0];

				//The PHQL contains the renamed columns if available
				if(isset($GLOBALS['_PHALCON_ORM_COLUMN_RENAMING']) === true &&
					$GLOBALS['_PHALCON_ORM_COLUMN_RENAMING'] === true) {
					$columnMap = $metaData->getColumnMap($modelInstance);
				} else {
					$columnMap = null;
				}

				if(is_array($columnMap) === true) {
					if(isset($columnMap[$firstPrimaryKey]) === true) {
						$attributeField = $columnMap[$firstPrimaryKey];
					} else {
						throw new Exception("Column '".$firstPrimaryKey.'" isn\'t part of the column map');
					}
				} else {
					$attributeField = $firstPrimaryKey;
				}

				$conditions = '['.$model.'].['.$attributeField.'] = '.$conditions;
				$noPrimary = false;
			}

			//A primary key is mandatory in these cases
			if($noPrimary === true) {
				throw new Exception('Source related to this model does not have a primary key defined');
			}
		}

		$phql = 'SELECT ';
		if(is_null($columns) === false) {
			//Generate PHQL for columns
			if(is_array($columns) === true) {
				$selectedColumns = array();
				foreach($columns as $columnAlias => $column) {
					if(is_int($columnAlias) === true) {
						$selectedColumns[] = $column;
					} else {
						$selectedColumns[] = $column.' AS '.$columnAlias;
					}
				}

				$phql .= implode(', ', $selectedColumns);
			} else {
				$phql .= $columns;
			}
		} else {
			//Automatically generate an array of models
			if(is_array($models) === true) {
				$selectedColumns = array();
				foreach($models as $modelColumnAlias => $model) {
					if(is_int($modelColumnAlias) === true) {
						$selectedColumns[] = '['.$model.'].*';
					} else {
						$selectedColumns[] = '['.$modelColumnAlias.'].*';
					}
				}

				$phql .= implode(', ', $selectedColumns);
			} else {
				$phql .= '['.$models.'].*';
			}
		}

		//Join multiple models or use a single one if it is a string
		if(is_array($models) === true) {
			$selectedModels = array();
			foreach($models as $modelAlias => $model) {
				if(is_string($modelAlias) === true) {
					$selectedModels[] = '['.$model.'] AS ['.$modelAlias.']';
				} else {
					$selectedModels[] = '['.$model.']';
				}
			}

			$phql .= ' FROM '.implode(', ', $selectedModels);
		} else {
			$phql .= ' FROM ['.$models.']';
		}

		//Check if joins were passed to the builders
		if(is_array($joins) === true) {
			foreach($joins as $join) {
				//Joined table
				$joinModel = $join[0];

				//Join conditions
				$joinConditions = $join[1];

				//Join alias
				$joinAlias = $join[2];

				//Join Type
				$joinType = $join[3];

				//Create the join according to the type
				if(isset($joinType)) {
					$phql .= ' '.$joinType.' JOIN ['.$joinModel.']';
				} else {
					$phql .= ' JOIN ['.$joinModel.']';
				}

				//Alias comes first
				if(isset($joinAlias) === true) {
					$phql .= ' AS ['.$joinAlias.']';
				}

				//Conditions then
				if(isset($joinConditions) === true) {
					$phql .= ' ON '.$joinConditions;
				}
			}
		}

		//Only append conditions if it's a string
		if(is_string($conditions) === true &&
			empty($conditions) === false) {
			$phql .= ' WHERE '.$conditions;
		}

		//Process group parameters
		if(is_null($group) === false) {
			if(is_array($group) === true) {
				$groupItems = array();
				foreach($group as $groupItem) {
					if(is_numeric($groupItem) === true) {
						$groupItems[] = $groupItem;
					} else {
						if(strpos($groupItem, '.') !== false) {
							$groupItems[] = $groupItem;
						} else {
							$groupItems[] = '['.$groupItem.']';
						}
					}
				}

				$phql .= ' GROUP BY '.implode(', ', $groupItems);
			} else {
				if(is_null($group) === true) {
					$phql .= 'GROUP BY '.$group;
				} else {
					if(strpos($group, '.') !== false) {
						$phql .= ' GROUP BY '.$group;
					} else {
						$phql .= ' GROUP BY ['.$group.']';
					}
				}
			}

			//Process having parameters
			if(is_null($having) === false && empty($having) === false) {
				$phql .= ' HAVING '.$having;
			}
		}

		//Process order clause
		if(is_null($order) === false) {
			if(is_array($order) === true) {
				$orderItems = array();
				foreach($order as $orderItem) {
					if(is_null($orderItem) === true) {
						$orderItems[] = $orderItem;
					} else {
						if(strpos($orderItem, '.') !== false) {
							$orderItems[] = $orderItem;
						} else {
							$orderItems[] = '['.$orderItem.']';
						}
					}
				}

				$phql .= ' ORDER BY '.implode(', ', $orderItems);
			} else {
				$phql .= ' ORDER BY '.$order;
			}
		}

		//Process limit parameters
		if(is_null($limit) === false) {
			if(is_array($limit) === true) {
				$number = $limit['number'];
				if(isset($limit['offset']) === true) {
					$offset = $limit['offset'];
					if(is_null($offset) === true) {
						$phql .= ' LIMIT '.$number.' OFFSET '.$offset;
					} else {
						$phql .= ' LIMIT '.$number.' OFFSET 0';
					}
				} else {
					$phql .= ' LIMIT '.$number;
				}
			} else {
				if(is_null($limit) === true) {
					$phql .= 'LIMIT '.$limit;

					$offset = $this->_offset;
					if(is_null($offset) === false) {
						if(is_null($offset) === true) {
							$phql .= ' OFFSET '.$offset;
						} else {
							$phql .= ' OFFSET 0';
						}
					}
				}
			}
		}

		return $phql;
	}

	/**
	 * Returns the query built
	 *
	 * @return \Phalcon\Mvc\Model\Query
	 */
	public function getQuery()
	{
		//Process the PHQL
		$phql = $this->getPhql();
		$query = new Query($phql, $this->_dependencyInjector);

		//Set default bind params
		$bindParams = $this->_bindParams;
		if(is_array($bindParams) === true) {
			$query->setBindParams($bindParams);
		}

		//Set default bind types
		$bindTypes = $this->_bindTypes;
		if(is_array($bindTypes) === true) {
			$query->setBindTypes($bindTypes);
		}

		return $query;
	}
}