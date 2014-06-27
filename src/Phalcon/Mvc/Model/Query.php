<?php
/**
* Query
*
* @author Andres Gutierrez <andres@phalconphp.com>
* @author Eduar Carvajal <eduar@phalconphp.com>
* @author Wenzel Pünter <wenzel@phelix.me>
* @version 1.2.6
* @package Phalcon
*/
namespace Phalcon\Mvc\Model;

use \Phalcon\DiInterface,
	\Phalcon\DI\InjectionAwareInterface,
	\Phalcon\Mvc\Model\QueryInterface,
	\Phalcon\Mvc\Model\Exception,
	\Phalcon\Mvc\Model\ManagerInterface,
	\Phalcon\Mvc\Model\RelationInterface,
	\Phalcon\Mvc\Model\Row,
	\Phalcon\Mvc\Model\Resultset\Simple,
	\Phalcon\Mvc\Model\Query\Lang,
	\Phalcon\Mvc\Model\Query\Status,
	\Phalcon\Mvc\Model,
	\Phalcon\Db\RawValue;

/**
 * Phalcon\Mvc\Model\Query
 *
 * This class takes a PHQL intermediate representation and executes it.
 *
 *<code>
 *
 * $phql = "SELECT c.price*0.16 AS taxes, c.* FROM Cars AS c JOIN Brands AS b
 *          WHERE b.name = :name: ORDER BY c.name";
 *
 * $result = $manager->executeQuery($phql, array(
 *   'name' => 'Lamborghini'
 * ));
 *
 * foreach ($result as $row) {
 *   echo "Name: ", $row->cars->name, "\n";
 *   echo "Price: ", $row->cars->price, "\n";
 *   echo "Taxes: ", $row->taxes, "\n";
 * }
 *
 *</code>
 * 
 * @see https://github.com/phalcon/cphalcon/blob/1.2.6/ext/mvc/model/query.c
 */
class Query implements QueryInterface, InjectionAwareInterface
{
	/**
	 * Type: Select
	 * 
	 * @var int
	*/
	const TYPE_SELECT = 309;

	/**
	 * Type: Insert
	 * 
	 * @var int
	*/
	const TYPE_INSERT = 306;

	/**
	 * Type: Update
	 * 
	 * @var int
	*/
	const TYPE_UPDATE = 300;

	/**
	 * Type: Delete
	 * 
	 * @var int
	*/
	const TYPE_DELETE = 303;

	/**
	 * Dependency Injector
	 * 
	 * @var null|\Phalcon\DiInterface
	 * @access protected
	*/
	protected $_dependencyInjector;

	/**
	 * Manager
	 * 
	 * @var null|object
	 * @access protected
	*/
	protected $_manager;

	/**
	 * Metadata
	 * 
	 * @var null|object
	 * @access protected
	*/
	protected $_metaData;

	/**
	 * Type
	 * 
	 * @var null|int
	 * @access protected
	*/
	protected $_type;

	/**
	 * PHQL
	 * 
	 * @var null|string
	 * @access protected
	*/
	protected $_phql;

	/**
	 * AST
	 * 
	 * @var null|array
	 * @access protected
	*/
	protected $_ast;

	/**
	 * Intermediate
	 * 
	 * @var null|array
	 * @access protected
	*/
	protected $_intermediate;

	/**
	 * Models
	 * 
	 * @var null|array
	 * @access protected
	*/
	protected $_models;

	/**
	 * SQL Aliases
	 * 
	 * @var null|array
	 * @access protected
	*/
	protected $_sqlAliases;

	/**
	 * SQL Aliases Models
	 * 
	 * @var null|array
	 * @access protected
	*/
	protected $_sqlAliasesModels;

	/**
	 * SQL Models Aliases
	 * 
	 * @var null|array
	 * @access protected
	*/
	protected $_sqlModelsAliases;

	/**
	 * SQL Aliases Models Instances
	 * 
	 * @var null|array
	 * @access protected
	*/
	protected $_sqlAliasesModelsInstances;

	/**
	 * SQL Column Aliases
	 * 
	 * @var null|array
	 * @access protected
	*/
	protected $_sqlColumnAliases;

	/**
	 * Model Instances
	 * 
	 * @var null|array
	 * @access protected
	*/
	protected $_modelsInstances;

	/**
	 * Cache
	 * 
	 * @var null|\Phalcon\Cache\BackendInterface
	 * @access protected
	*/
	protected $_cache;

	/**
	 * Cache Options
	 * 
	 * @var null|array
	 * @access protected
	*/
	protected $_cacheOptions;

	/**
	 * Unique Row
	 * 
	 * @var null|boolean
	 * @access protected
	*/
	protected $_uniqueRow;

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
	 * IR PHQL Cache
	 * 
	 * @var null|array
	 * @access protected
	*/
	protected static $_irPhqlCache;

	/**
	 * \Phalcon\Mvc\Model\Query constructor
	 *
	 * @param string|null $phql
	 * @param \Phalcon\DiInterface|null $dependencyInjector
	 */
	public function __construct($phql = null, $dependencyInjector = null)
	{
		if(is_string($phql) === true) {
			$this->_phql = $phql;
		}

		if(is_object($dependencyInjector) === true) {
			$this->setDi($dependencyInjector);
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
		if(is_object($dependencyInjector) === false ||
			$dependencyInjector instanceof DiInterface === false) {
			throw new Exception('A dependency injector container is required to obtain the ORM services');
		}

		$manager = $dependencyInjector->getShared('modelsManager');
		if(is_object($manager) === false) { //@note no interface validation
			throw new Exception("Injected service 'modelsManager' is invalid");
		}

		$meta_data = $dependencyInjector->getShared('modelsMetadata');
		if(is_object($meta_data) === false) { //@note no interface validation
			throw new Exception("Injected service 'modelsMetadata' is invalid");
		}

		$this->_manager = $manager;
		$this->_metaData = $meta_data;
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
	 * Tells to the query if only the first row in the resultset must be returned
	 *
	 * @param boolean $uniqueRow
	 * @return \Phalcon\Mvc\Model\Query
	 * @throws Exception
	 */
	public function setUniqueRow($uniqueRow)
	{
		if(is_object($uniqueRow) === false) {
			throw new Exception('Invalid parameter type.');
		}

		$this->_uniqueRow = $uniqueRow;

		return $this;
	}

	/**
	 * Check if the query is programmed to get only the first row in the resultset
	 *
	 * @return boolean|null
	 */
	public function getUniqueRow()
	{
		return $this->_uniqueRow;
	}

	/**
	 * Replaces the model's name to its source name in a qualifed-name expression
	 *
	 * @param array $expr
	 * @return string
	 * @throws Exception
	 * @todo optimize variable usage
	 */
	protected function _getQualified(array $expr)
	{
		$column_name = $expr['name'];
		$sql_column_aliases = $expr['_sqlColumnAliases'];

		//Check if the qualified name is a column alias
		if(isset($sql_column_aliases[$column_name]) === true) {
			return array('type' => 'qualified', 'name' => $column_name);
		}

		$meta_data = $this->_metaData;

		//Check if the qualified name has a domain
		if(isset($expr['domain']) === true) {
			$column_domain = $expr['domain'];
			$sql_aliases = $expr['_sqlAliases'];

			//The column has a domain, we need to check if it's an alias
			if(isset($sql_aliases[$column_domain]) === false) {
				throw new Exception("Unknown model or alias '".$column_domain."' (1), when preparing: ".$this->_phql);
			}

			$source = $sql_aliases[$column_domain];

			//Change the selected column by its real name on its mapped table
			if(isset($GLOBALS['_PHALCON_ORM_COLUMN_RENAMING']) === true &&
				$GLOBALS['_PHALCON_ORM_COLUMN_RENAMING'] === true) {
				//Retrieve the corresponding model by its alias
				$sql_aliases_models_instances = $this->_sqlAliasesModelsInstances;

				//We need the model instances to retrieve the reversed column map
				if(isset($sql_aliases_models_instances[$column_domain]) === false) {
					throw new Exception("There is no model related to model or alias '".$column_domain."', when executing: ".$this->_phql);
				}

				$model = $sql_aliases_models_instances[$column_domain];
				$column_map = $meta_data->getReverseColumnMap($model);
			} else {
				$column_map = null;
			}

			if(is_array($column_map) === true) {
				if(isset($column_map[$column_name]) === true) {
					$real_column_name = $column_map[$column_name];
				} else {
					throw new Exception("Column '".$column_name."' doesn't belong to the model or alias '".$column_domain."', when executing: ".$this->_phql);
				}
			} else {
				$real_column_name = $column_name;
			}
		} else {
			$number = 0;
			$has_model = false;

			$models_instances = $this->_modelsInstances;
			foreach($models_instances as $model) {
				//Check if the attribute belongs to the current model
				if($meta_data->hasAttribute($model, $column_name) === true) {
					$number++;
					if($number > 1) {
						throw new Exception("The column '".$column_name."' is ambiguous, when preparing: ".$this->_phql);
					}

					$has_model = $model;
				}
			}

			//After check in every model, the column does not belong to any of the selected models
			if($has_model === false) {
				throw new Exception("Column '".$column_name."' doesn't belong to any of the selected models (1), when preparing: ".$this->_phql);
			}

			//Check if the _models property is correctly prepared
			if(is_array($this->_models) === false) {
				throw new Exception('The models list was not loaded correctly');
			}

			//Obtain the model's source from the _models lsit
			$class_name = get_class($has_model);
			if(isset($this->_models[$class_name]) === true) {
				$source = $this->_models[$class_name];
			} else {
				throw new Exception("Column '".$column_name."' doesn't belong to any of the selected models (2) when preparing: ".$this->_phql);
			}

			//Rename the column
			if(isset($GLOBALS['_PHALCON_ORM_COLUMN_RENAMING']) === true &&
				$GLOBALS['_PHALCON_ORM_COLUMN_RENAMING'] === true) {
				$column_map = $meta_data->getReverseColumnMap($has_model);
			} else {
				$column_map = null;
			}

			if(is_array($column_map) === true) {
				//The real column name is in the column map
				if(isset($column_map[$column_name]) === true) {
					$real_column_name = $column_map[$column_name];
				} else {
					throw new Exception("Column '".$column_name."' doesn't belong to any of the selected models (3), when preparing: ".$this->_phql);
				}
			} else {
				$real_column_name = $column_name;
			}
		}

		//Create an array with the qualified info
		return array('type' => 'qualified', 'domain' => $source, 'name' => $real_column_name, 'balias' => $column_name);
	}

	/**
	 * Resolves a expression in a single call argument
	 *
	 * @param array $argument
	 * @return string
	 */
	protected function _getCallArgument(array $argument)
	{
		if($this->_type === 352) {
			return array('type' => 'all');
		}

		return $this->_getExpression();
	}

	/**
	 * Resolves a expression in a single call argument
	 *
	 * @param array $expr
	 * @return string
	 */
	protected function _getFunctionCall(array $expr)
	{
		if(isset($expr['arguments']) === true) {
			$arguments = $expr['arguments'];
			if(isset($arguments[0]) === true) {
				//There are more than one argument
				$function_args = array();
				foreach($arguments as $argument) {
					$function_args[] = $this->_getCallArgument($argument);
				}
			} else {
				//There is only one argument
				$function_args[] = $this->_getCallArgument[$arguments];
			}

			return array('type' => 'functionCall', 'name' => $expr['name'], 'arguments' => $function_args);
		} else {
			return array('type' => 'functionCall', 'name' => $expr['name']);
		}
	}

	/**
	 * Resolves an expression from its intermediate code into a string
	 *
	 * @param array $expr
	 * @param boolean|null $quoting
	 * @return string
	 * @throws Exception
	 */
	protected function _getExpression(array $expr, $quoting = null)
	{
		if(is_null($quoting) === true) {
			$quoting = true;
		} elseif(is_bool($quoting) === false) {
			throw new Exception('Invalid parameter type.');
		}

		if(isset($expr['type']) === true) {
			$temp_no_quoting = true;

			//Resolving left part of the expression if any
			if(isset($expr['left']) === true) {
				$left = $this->_getExpression($expr['left'], $temp_no_quoting);
			}

			//Resolving right part of the expression if any
			if(isset($expr['right']) === true) {
				$right = $this->_getExpression($expr['right'], $temp_no_quoting);
			}

			//Every node in the AST has a unique integer type
			switch((int)$expr['type']) {
				case 60:
					return array('type' => 'binary-op', 'op' => '<', 'left' => $left, 'right' => $right);
					break;
				case 61:
					return array('type' => 'binary-op', 'op' => '=', 'left' => $left, 'right' => $right);
					break;
				case 62:
					return array('type' => 'binary-op', 'op' => '>', 'left' => $left, 'right' => $right);
					break;
				case 270:
					return array('type' => 'binary-op', 'op' => '<>', 'left' => $left, 'right' => $right);
					break;
				case 271:
					return array('type' => 'binary-op', 'op' => '<=', 'left' => $left, 'right' => $right);
					break;
				case 272:
					return array('type' => 'binary-op', 'op' => '>=', 'left' => $left, 'right' => $right);
					break;
				case 266:
					return array('type' => 'binary-op', 'op' => 'AND', 'left' => $left, 'right' => $right);
					break;
				case 267:
					return array('type' => 'binary-op', 'op' => 'OR', 'left' => $left, 'right' => $right);
					break;
				case 355:
					return $this->_getQualified($expr);
					break;
				case 359:
					return $this->_getAliased($expr); //@todo?
					break;
				case 43:
					return array('type' => 'binary-op', 'op' => '+', 'left' => $left, 'right' => $right);
					break;
				case 35:
					return array('type' => 'binary-op', 'op' => '-', 'left' => $left, 'right' => $right);
					break;
				case 42:
					return array('type' => 'binary-op', 'op' => '*', 'left' => $left, 'right' => $right);
					break;
				case 47:
					return array('type' => 'binary-op', 'op' => '/', 'left' => $left, 'right' => $right);
					break;
				case 37:
					return array('type' => 'binary-op', 'op' => '%', 'left' => $left, 'right' => $right);
					break;
				case 38:
					return array('type' => 'binary-op', 'op' => '&', 'left' => $left, 'right' => $right);
					break;
				case 124:
					return array('type' => 'binary-op', 'op' => '|', 'left' => $left, 'right' => $right);
					break;
				case 356:
					return array('type' => 'parentheses', 'left' => $left);
					break;
				case 367:
					return array('type' => 'unary-op', 'op' => '-', 'right' => $right);
					break;
				case 258:
				case 259:
					return array('type' => 'literal', 'value' => $expr['value']);
					break;
				case 333:
					return array('type' => 'literal', 'value' => 'TRUE');
					break;
				case 334:
					return array('type' => 'literal', 'value' => 'FALSE');
					break;
				case 260:
					$value = $expr['value'];
					if($quoting === true) {
						//CHeck if static literals have single quotes and escape them
						if(strpos($value, "'") !== false) {
							$value = self::singleQuotes($value);
						}

						$value = "'".$value."'";
					}

					return array('type' => 'literal', 'value' => $value);
					break;
				case 273:
					return array('type' => 'placeholder', 'value' => str_replace('?', ':', $expr['value']));
					break;
				case 274:
					return array('type' => 'placeholder', 'value' => ':'.$expr['value']);
					break;
				case 322:
					return array('type' => 'literal', 'value' => 'NULL');
					break;
				case 268:
					return array('type' => 'binary-op', 'op' => 'LIKE', 'left' => $left, 'right' => $right);
					break;
				case 351:
					return array('type' => 'binary-op', 'op' => 'NOT LIKE', 'left' => $left, 'right' => $right);
					break;
				case 275:
					return array('type' => 'binary-op', 'op' => 'ILIKE', 'left' => $left, 'right' => $right);
					break;
				case 357:
					return array('type' => 'binary-op', 'op' => 'NOT ILIKE', 'left' => $left, 'right' => $right);
					break;
				case 33:
					return array('type' => 'unary-op', 'op' => 'NOT ', 'right' => $right);
					break;
				case 365:
					return array('type' => 'unary-op', 'op' => ' IS NULL', 'left' => $left);
					break;
				case 366:
					return array('type' => 'unary-op', 'op' => ' IS NOT NULL', 'left' => $left);
					break;
				case 315:
					return array('type' => 'binary-op', 'op' => 'IN', 'left' => $left, 'right' =>  $right);
					break;
				case 323:
					return array('type' => 'binary-op', 'op' => 'NOT IN', 'left' => $left, 'right' => $right);
					break;
				case 330:
					return array('type' => 'unary-op', 'op' => 'DISTINCT ', 'right' => $right);
					break;
				case 331:
					return array('type' => 'binary-op', 'op' => 'BETWEEN', 'left' => $left, 'right' => $right);
					break;
				case 276:
					return array('type' => 'binary-op', 'op' => 'AGAINST', 'left' => $left, 'right' => $right);
					break;
				case 332:
					return array('type' => 'cast', 'left' => $left, 'right' => $right);
					break;
				case 335:
					return array('type' => 'convert', 'left' => $left, 'right' => $right);
					break;
				case 358:
					return array('type' => 'literal', 'value' => $expr['name']);
					break;
				case 350:
					return $this->_getFunctionCall($expr);
					break;
				default:
					throw new Exception('Unknown expression type '.$expr['type']);
			}
		}

		//Is a qualified column?
		if(isset($expr['domain']) === true) {
			return $this->_getQualified($expr);
		}

		//If the expression doesn't have a type it's a list of nodes
		if(isset($expr[0]) === true) {
			$list_items = array();
			foreach($expr as $expr_list_item) {
				$list_items[] = $this->_getExpression($expr_list_item);
			}

			return array('type' => 'list', $list_items);
		}

		throw new Exception('Unknown expression');
	}

	/**
	 * Escapes single quotes into database single quotes
	 * 
	 * @param string $value
	 * @return string
	*/
	private static function singleQuotes($value)
	{
		if(is_string($value) === false) {
			return '';
		}
		$esc = '';

		$l = strlen($value);
		$n = chr(0);
		for($i = 0; $i < $l; ++$i) {
			if($value[$i] === $n) {
				break;
			}

			if($value[$i] === '\'') {
				if($i > 0) {
					if($value[$i-1] != '\\') {
						$esc .= '\'';
					}
				} else {
					$esc .= '\'';
				}
			}

			$esc .= $value[$i];
		}

		return $esc;
	}

	/**
	 * Resolves a column from its intermediate representation into an array used to determine
	 * if the resulset produced is simple or complex
	 *
	 * @param array $column
	 * @return array
	 * @throws Exception
	 */
	protected function _getSelectColumn(array $column)
	{
		if(isset($column['type']) === false) {
			throw new Exception('Corrupted SELECT AST');
		}

		$sql_columns = array();

		//Check for SELECT * (all)
		$column_type = $column['type'];
		if($column_type === 352) {
			foreach($this->_models as $model_name => $source) {
				$sql_columns[] = array('type' => 'object', 'model' => $model_name, 'column' => $source);
			}

			return $sql_columns;
		}

		if(isset($column['column']) === false) {
			throw new Exception('Corrupted SELECT AST');
		}

		//Check if selected column is qualified.
		if($column_type === 353) {
			$sql_aliases = $this->_sqlAliases;

			//We only allow the alias.
			$column_domain = $column['column'];

			if(isset($sql_aliases[$column_domain]) === false) {
				throw new Exception("Unknown model or alias '".$column_domain."' (2), when preparing: ".$this->_phql);
			}

			//Get the SQL alias if any
			$sql_column_alias = $sql_aliases[$column_domain];

			//Get the real source name
			$model_name = $this->_sqlAliasesModels[$column_domain];

			//Get the best alias for the column
			$best_alias = $this->_sqlModelsAliases[$model_name];

			//If the best alias is the model name we lowercase the first letter
			$alias = ($best_alias === $model_name ? lcfirst($model_name) : $best_alias);

			//The sql column is a complex type returning a complete object
			return array('type' => 'object', 'model' => $model_name, 'column' => $sql_column_alias, 'balias' => $alias);
		}

		//Check for columns qualified and not qualified
		if($column_type === 354) {
			//The sql_column is a scalar type returning a simple string
			$sql_column = array('type' => 'scalar');
			$sql_expr_column = $this->_getExpression($column['column']);

			//Create balias and sqlAlias
			if(isset($sql_expr_column['balias']) === true) {
				$balias = $sql_expr_column['balias'];
				$sql_column['balias'] = $balias;
				$sql_column['sqlAlias'] = $balias;
			}

			$sql_column['column'] = $sql_expr_column;
			$sql_columns[] = $sql_column;

			return $sql_columns;
		}

		throw new Exception("Unknown type of column ".$column_type);
	}

	/**
	 * Resolves a table in a SELECT statement checking if the model exists
	 *
	 * @param \Phalcon\Mvc\Model\ManagerInterface $manager
	 * @param array $qualifiedName
	 * @return string
	 * @throws Exception
	 */
	protected function _getTable(ManagerInterface $manager, array $qualifiedName)
	{
		if(isset($qualifiedName['name']) === true) {
			$Model = $manager->load($qualifiedName['name']);

			$schema = $model->getSchema();
			if($schema == true) {
				return array($schema, $model->getSource());
			}
			return $model->getSource();
		}

		throw new Exception('Corrupted SELECT AST');
	}

	/**
	 * Resolves a JOIN clause checking if the associated models exist
	 *
	 * @param \Phalcon\Mvc\Model\ManagerInterface $manager
	 * @param array $join
	 * @return array
	 * @throws Exception
	 */
	protected function _getJoin(ManagerInterface $manager, array $join)
	{
		if(isset($join['qualified']) === true && $join['qualified']['type'] === 355) {
				return array($model->getSchema(), $model->getSource(), 
					$join['qualified']['name'], $manager->load($join['qualified']['name']));
		}

		throw new Exception('Corrupted SELECT AST');
	}

	/**
	 * Resolves a JOIN type
	 *
	 * @param array $join
	 * @return string
	 * @throws Exception
	 */
	protected function _getJoinType(array $join)
	{
		if(isset($join['type']) === false) {
			throw new Exception('Corrupted SELECT AST');
		}

		switch((int)$join['type']) {
			case 360:
				return 'INNER';
				break;
			case 361:
				return 'LEFT';
				break;
			case 362:
				return 'RIGHT';
				break;
			case 363:
				return 'CROSS';
				break;
			case 364:
				return 'FULL OUTER';
				break;
			default:
				throw new Exception("Unknown join type ".$join['type'].', when preparing: '.$this->_phql);
				break;
		}
	}

	/**
	 * Resolves joins involving has-one/belongs-to/has-many relations
	 *
	 * @param string $joinType
	 * @param string $joinSource
	 * @param string $modelAlias
	 * @param string $joinAlias
	 * @param \Phalcon\Mvc\Model\RelationInterface $relation
	 * @return array
	 * @throws Exception
	 */
	protected function _getSingleJoin($joinType, $joinSource, $modelAlias, $joinAlias, RelationInterface $relation)
	{
		if(is_string($joinType) === false ||
			is_string($joinSource) === false ||
			is_string($modelAlias) === false ||
			is_string($joinAlias) === false) {
			throw new Exception('Invalid parameter type.');
		}

		//Local fields in the 'from' relation
		$fields = $relation->getFields();

		//Referenced fields in the joined relation
		$referenced_fields = $relation->getReferencedFields();

		if(is_array($fields) === false) {
			//Create the left part of the expression
			$left_expr = $this->_getQualified(array('type' => 355, 'domain' => $modelAlias, 'name' => $fields));

			//Create the right part of the expression
			$right_expr = $this->_getQualified(array('type' => 'qualified', 'domain' => $joinAlias, 'name' => $referenced_fields));

			//Create a binary operation for the join conditions
			$sql_join_conditions = array(array('type' => 'binary-op', 'op' => '=', 'left' => $left_expr, 'right' => $right_expr));
		} else {
			//Resolve the compound operation
			$sql_join_partial_conditions = array();
			foreach($fields as $position => $field) {
				if(isset($referenced_fields[$position]) === false) {
					throw new Exception('The number of fields must be equal to the number of referenced fields in join '.$modelAlias.'-'.$joinAlias.', when preparing: '.$this->_phql);
				}

				//Get the referenced field in the same position
				$referenced_field = $referenced_fields[$position];

				//Create the left part of the expression
				$left_expr = $this->_getQualified(array('type' => 355, 'domain' => $modelAlias, 'name' => $field));

				//Create the right part of the expression
				$right_expr = $this->_getQualified(array('type' => 'qualified', 'domain' => $joinAlias, 'name' => $referenced_fields));

				//Create a binary operation for the join conditions
				$sql_join_partial_conditions[] = array('type' => 'binary-op', 'op' => '=', 'left' => $left_expr, 'right' => $right_expr);
			}
		}

		//A single join
		return array('type' => $joinType, 'source' => $joinSource, 'conditions' => $sql_join_conditions); //@note sql_join_conditions is not set when $fields is an array
	}

	/**
	 * Resolves joins involving many-to-many relations
	 *
	 * @param string $joinType
	 * @param string $joinSource
	 * @param string $modelAlias
	 * @param string $joinAlias
	 * @param \Phalcon\Mvc\Model\RelationInterface $relation
	 * @return array
	 * @throws Exception
	 */
	protected function _getMultiJoin($joinType, $joinSource, $modelAlias, $joinAlias, RelationInterface $relation)
	{
		if(is_string($joinType) === false ||
			is_string($joinSource) === false ||
			is_string($modelAlias) === false ||
			is_string($joinAlias) === false) {
			throw new Exception('Invalid parameter type.');
		}

		$sql_joins = array();

		//Local fields in the 'from' relation
		$fields = $relation->getFields();

		//Referenced fields in the joined relation
		$referenced_fields = $relation->getReferencedFields();

		//Intermediate model
		$intermediate_model_name = $relation->getIntermediateModel();

		//Get the intermediate model instance
		$intermediate_model = $this->_manager->load($intermediate_model_name);

		//Source of the related model
		$intermediate_source = $intermediate_model->getSource();

		//$intermediate_full_source = array($intermediate_model->getSchema(),
		//	$intermediate_source); @note this variable is not used

		//Update the internal sqlAlias to set up the intermediate model
		$this->_sqlAliases[$intermediate_model_name] = $intermediate_source;

		//Update the internal _sqlAliasesModelsInstances to rename columns if necessary
		$this->_sqlAliasesModelsInstances[$intermediate_model_name] = $intermediate_model;

		//Fields that join the 'from' model with the 'intermediate' model
		$intermediate_fields = $relation->getIntermediateFields();

		//Fields that join the 'intermediate' model with the model
		$intermediate_referenced_fields = $relation->getIntermediateReferencedFields();

		//Intermediate model
		$referenced_model_name = $relation->getReferencedModel();
		if(is_array($fields) === true) {
			//@note when $fields is an array this function returns an empty array and the following code is unnecessary
			foreach($fields as $position => $field) {
				if(isset($referenced_fields[$position]) === false) {
					throw new Exception('The number of fields must be equal to the number of referenced fields in join '.
						$modelAlias.'-'.$joinAlias.', when preparing: '.$this->_phql);
				}

				//Get the referenced field in the same position @note this variable is not used
				//$intermediate_field = $intermediate_fields[$position];

				//Create the left part of the expression
				$left_expr = $this->_getQualified(array('type' => 355, 'domain' => $modelAlias, 'name' => $field));
				$right_expr = $this->_getQualified(array('type' => 'qualified', 'domain' => $joinAlias, 'name' => $referenced_fields));
				$sql_equals_join_condition = array('type' => 'binary-op', 'op' => '=', 'left' => $left_expr, 'right' => $right_expr);
			}
		} else {
			/* First Condition */
			$left_expr = $this->_getQualified(array('type' => 355, 'domain' => $modelAlias, 'name' => $fields));
			$right_expr = $this->_getQualified(array('type' => 'qualified', 'domain' => $intermediate_model_name, 'name' => $intermediate_fields));
			$sql_join_conditions_first = array(array('type' => 'binary-op', 'op' => '=', 'left' => $left_expr, 'right' => $right_expr));
			$sql_join_first = array('type' => $joinType, 'source' => $intermediate_source, 'conditions' => $sql_join_conditions_first);

			/* Second Condition */
			$left_expr = $this->_getQualified(array('type' => 355, 'domain' => $intermediate_model_name, 'name' => $intermediate_referenced_fields));
			$right_expr = $this->_getQualified(array('type' => 'qualified', 'domain' => $referenced_model_name, 'name' => $referenced_fields));
			$sql_join_conditions_second = array(array('type' => 'binary-op', 'op' => '=', 'left' => $left_expr, 'right' => $right_expr));
			$sql_join_second = array('type' => $joinType, 'source' => $joinSource, 'conditions' => $sql_join_conditions_second);

			/* Store in result array */
			$sql_joins[0] = $sql_join_first;
			$sql_joins[1] = $sql_join_second;
		}

		return $sql_joins;
	}

	/**
	 * Processes the JOINs in the query returning an internal representation for the database dialect
	 *
	 * @param array $select
	 * @return array
	 * @throws Exception
	 */
	protected function _getJoins(array $select)
	{
		$models = $this->_models;
		$sql_aliases = $this->_sqlAliases;
		$sql_aliases_models = $this->_sqlAliasesModels;
		$sql_models_aliases = $this->_sqlModelsAliases;
		$sql_aliases_models_instances = $this->_sqlAliasesModelsInstances;
		$models_instances = $this->_modelsInstances;
		$from_models = $models_instances;
		$manager = $this->_manager;

		$sql_joins = array();
		$join_models = array();
		$join_sources = array();
		$join_types = array();
		$join_pre_conditions = array();
		$join_prepared = array();

		if(isset($select['joins'][0]) === false) {
			$select_joins = array($select['joins']);
		} else {
			$select_joins = $select['joins'];
		}

		foreach($select_joins as $join_item) {
			//Check join alias
			$join_data = $this->_getJoin($manager, $join_item);
			$source = $join_data['source'];
			$schema = $join_data['schema'];
			$model = $join_data['model'];
			$model_name = $join_data['modelName'];
			$complete_source = array($source, $schema);

			//Check join alias
			$join_type = $this->_getJoinType($join_item);

			//Process join alias
			if(isset($join_item['alias']) === true) {
				$alias = $join_item['alias']['name'];

				//Check if alias is unique
				if(isset($join_models[$alias]) === true) {
					throw new Exception("Cannot use '".$alias."' as join alias because it was already used, when preparing: ".$this->_phql);
				}

				//Add the alias to the source
				$complete_source[] = $alias;

				//Set the join type
				$join_types[$alias] = $join_type;

				//Update alias => $alias
				$sql_aliases[$alias] = $alias;

				//Update model => alias
				$join_models[$alias] = $model_name;
				$sql_models_aliases[$model_name] = $alais;
				$sql_aliases_models[$alias] = $model_name;
				$sql_aliases_models_instances[$alias] = $model;

				//Update model => alias
				$models[$model_name] = $alias;

				//Complete source related to a model
				$join_sources[$alias] = $complete_source;
				$join_prepared[$alias] = $join_item;
			} else {
				//Check if alias is unique
				if(isset($join_models[$model_name]) === true) {
					throw new Exception("Cannot use '".$model_name."' as join because it was already used, when preparing: ".$this->_phql);
				}

				//Set the join type
				$join_types[$model_name] = $join_type;

				//Update model => source
				$sql_aliases[$model_name] = $source;
				$join_models[$model_name] = $source;

				//Update model => model
				$sql_models_aliases[$model_name] = $model_name;
				$sql_aliases_models[$model_name] = $model_name;

				//Update model => model instances
				$sql_aliases_models_instances[$model_name] = $model;

				//Update model => source
				$models[$model_name] = $source;

				//Complete source related to a model
				$join_sources[$model_name] = $complete_source;
				$join_prepared[$model_name] = $join_item;
			}

			$models_instances[$model_name] = $model;
		}

		//Update temporary properties
		$this->_models = $models;
		$this->_sqlAliases = $sql_aliases;
		$this->_sqlAliasesModels = $sql_aliases_models;
		$this->_sqlModelsAliases = $sql_models_aliases;
		$this->_sqlAliasesModelsInstances = $sql_aliases_models_instances;
		$this->_modelsInstances = $models_instances;

		foreach($join_prepared as $join_alias_name => $join_item) {
			//Check for predefined conditions
			if(isset($join_item['conditions']) === true) {
				$join_pre_conditions[$join_alias_name] = $this->_getExpression($join_item['conditions']);
			}
		}

		//Create join relationships dynamically
		foreach($from_models as $from_model_name => $source) {
			foreach($join_models as $join_alias => $join_model) {
				//Real source name for joined model
				$join_source = $join_sources[$join_alias];

				//Join type is: LEFT, RIGHT, INNER, etc.
				$join_type = $join_typs[$join_alias];

				//Check if the model already has pre-defined conditions
				if(isset($join_pre_conditions[$join_alias]) === false) {
					//Get the model name from its source
					$model_name_alias = $sql_aliases_models[$join_alias];

					//Check if the joined model is an alias
					$relation = $manager->getRelationByAlias($from_model_name, $model_name_alias);
					if($relation === false) {
						$relations = $manager->getRelationsBetween($from_model_name, $model_name_alias);

						if(is_array($relations) === true) {
							//More than one relation must throw an exception
							$number_relations = count($relations);
							if($number_relations !== 1) {
								throw new Exception("There is more than one relation between models '".$model_name.
									"' and '".$join_model.'", the join must be done using an alias, when preparing: '.$this->_phql);
							}

							//Get the first relationship
							$relation = $relations[0];
						}
					}

					//Valid relations are objects
					if(is_object($relation) === true) {
						//Get the related model alias of the left part
						$model_alias = $sql_models_aliases[$from_model_name];

						//Generate the conditions based on the type of join
						if($relation->isThrough() === false) {
							$sql_join = $this->_getSingleJoin($join_type, $join_source, $model_alias, $join_alias, $relation); //no Many-To-Many
						} else {
							$sql_join = $this->_getMultiJoin($join_type, $join_source, $model_alias, $join_alias, $relation);
						}

						//Append or merge joins
						if(isset($sql_joins[0]) === true) {
							$sql_joins = array_merge($sql_joins, $sql_join);
						} else {
							$sql_joins[] = $sql_join;
						}
					} else {
						$sql_join_conditions = array();

						//Join without conditions because no relation has been found between the models
						$sql_join = array('type' => $join_type, 'source' => $join_source, 'conditions' => $sql_join_conditions);
					}
				} else {
					//Get the conditions established by the developer
					$sql_join_conditions = array(array($join_pre_conditions, $join_alias));

					//Join with conditions established  by the devleoper
					$sql_joins[] = array('type' => $join_type, 'source' => $join_source, 'conditions' => $sql_join_conditions);
				}
			}
		}

		return $sql_joins;
	}

	/**
	 * Returns a processed order clause for a SELECT statement
	 *
	 * @param array $order
	 * @return string
	 */
	protected function _getOrderClause(array $order)
	{
		if(isset($order[0]) === false) {
			$order_columns = array($order);
		} else {
			$order_columns = $order;
		}

		$order_parts = array();

		foreach($order_columns as $order_item) {
			$order_part_expr = $this->_getExpression($order_item['column']);

			//Check if the order has a predefined ordering mode
			if(isset($order_item['sort']) === true) {
				if($order_item['sort'] === 327) {
					$order_part_sort = array($order_part_expr, 'ASC');
				} else {
					$order_part_sort = array($order_part_expr, 'DESC');
				}
			} else {
				$order_part_sort = array($order_part_expr);
			}

			$order_parts[] = $order_part_sort;
		}

		return $order_parts;
	}

	/**
	 * Returns a processed group clause for a SELECT statement
	 *
	 * @param array $group
	 * @return string
	 */
	protected function _getGroupClause(array $group)
	{
		if(isset($group[0]) === true) {
			//The SELECT is grouped by several columns
			$group_parts = array();
			foreach($group as $group_item) {
				$gruop_parts[] = $this->_getExpression($group_item);
			}
		} else {
			$group_parts = array($this->_getExpression($group));
		}

		return $group_parts;
	}

	/**
	 * Analyzes a SELECT intermediate code and produces an array to be executed later
	 *
	 * @return array
	 * @throws Exception
	 */
	protected function _prepareSelect()
	{
		$ast = $this->_ast;
		if(isset($ast['select']['tables']) === false ||
			isset($ast['select']['columns']) === false) {
			throw new Exception('Corrupted SELECT AST');
		}

		$sql_models = array();
		$sql_tables = array();
		$sql_aliases = array();
		$sql_columns = array();
		$sql_aliases_models = array();
		$sql_models_aliases = array();
		$sql_aliases_models_instances = array();
		$models = array();
		$models_instances = array();

		if(isset($ast['select']['tables'][0]) === false) {
			$selected_models = array($ast['select']['tables']);
		} else {
			$selected_models = $ast['select']['tables'];
		}

		//@note we don't need the metadata object

		//Processing selected columns
		foreach($selected_models as $selected_model) {
			$qualified_name = $selected_model['qualifiedName'];
			$model_name = $qualified_name['name'];

			//Load a model instance from the models manager
			$model = $this->_manager->load(
				(isset($qualified_name['ns-alias']) === true ? 
					$this->_manager->getNamespaceAlias($qualified_name['ns-alias']).'\\'.$model_name :
					$model_name)
				);

			//Define a complete schema/source
			$schema = $model->getSchema();
			$source = $model->getSource();

			//Obtain the real source including the schema
			if($schema == true) {
				$complete_source = array($source, $schema);
			} else {
				$complete_source = $source;
			}

			//If an alias is defined for a model the model cannot be referenced in the column list
			if(isset($selected_model['alias']) === true) {
				$alias = $selected_model['alias'];

				//Check that the alias hasn't been used before
				if(isset($sql_aliases[$alias]) === true) {
					throw new Exception('Alias "'.$alias.' is already used, when preparing: '.$this->_phql); //@note missing quote
				}

				$sql_aliases[$alias] = $alias;
				$sql_aliases_models[$alias] = $model_name;
				$sql_models_aliases[$model_name] = $alias;
				$sql_aliases_models_instances[$alias] = $model;

				//Append or convert complete source to an array
				if(is_array($complete_source) === true) {
					$complete_source[] = $alias;
				} else {
					$complete_source = array($source, null, $alias);
				}

				$models[$model_name] = $alias;
			} else {
				$sql_aliases[$model_name] = $alias;
				$sql_aliases_models[$model_name] = $model_name;
				$sql_models_aliases[$model_name] = $model_name;
				$sql_aliases_models_instances[$model_name] = $model;
				$models[$model_name] = $source;
			}

			$sql_models[] = $model_name;
			$sql_tables[] = $complete_source;
			$models_instances[$model_name] = $model;
		}

		//Assign models/tables information
		$this->_models = $models;
		$this->_modelsInstances = $models_instances;
		$this->_sqlAliases = $sql_aliases;
		$this->_sqlAliasesModels = $sql_aliases_models;
		$this->_sqlModelsAliases = $sql_models_aliases;
		$this->_sqlAliasesModelsInstances = $sql_aliases_models_instances;
		$this->_modelsInstances = $models_instances;

		//Processing joins
		if(isset($ast['select']['joins']) === true) {
			$sql_joins = (count($ast['select']['joins']) > 0 ? $this->_getJoins($ast['select']) : array());
		} else {
			$sql_joins = array();
		}

		//Processing selected columns
		if(isset($ast['select']['columns'][0]) === false) {
			$select_columns = array($ast['select']['columns']);
		} else {
			$select_columns = $ast['select']['columns'];
		}

		//Resolve selected columns
		$position = 0;
		$sql_column_aliases = array();

		foreach($select_columns as $column) {
			$sql_column_group = $this->_getSelectColumn($column);
			foreach($sql_column_group as $sql_column) {
				//If 'alias' is set, the user had defined an alias for the column
				if(isset($column['alias']) === true) {
					$alias = $column['alias'];

					//The best alias if the one provided by the user
					$sql_column['balias'] = $alias;
					$sql_column['sqlAlias'] = $alias;
					$sql_columns[$alias] = $sql_column;
					$this->_sqlColumnAliases[$alias] = true;
				} else {
					//'balias' is the best alias selected for the column
					if(isset($sql_column['balias']) === true) {
						$alias = $sql_column['balias'];
						$sql_column[$alias] = $sql_column;
					} else {
						if($sql_column['type'] === 'scalar') {
							$sql_columns['_'.$position] = $sql_column;
						} else {
							$sql_columns[] = $sql_column;
						}
					}
				}

				$position++;
			}
		}

		//sql_select is the final prepared SELECt
		$sql_select = array('models' => $sql_models, 'tables' => $sql_tables, 'columns' => $sql_columns);
		if(count($sql_joins) > 0) {
			$sql_select['joins'] = $sql_joins;
		}

		//Process WHERE clauses if any
		if(isset($ast['where']) === true) {
			$sql_select['where'] = $this->_getExpression($ast['where']);
		}

		//Process GROUP BY clauses if any
		if(isset($ast['groupBy']) === true) {
			$sql_select['group'] = $this->_getGroupClause($ast['groupBy']);
		}

		//Process HAVING clauses if any
		if(isset($ast['having']) === true) {
			$sql_select['having'] = $this->_getExpression($ast['having']);
		}

		//Process ORDER BY clauses if any
		if(isset($ast['orderBy']) === true) {
			$sql_select['order'] = $this->_getOrderClause($ast['orderBy']);
		}

		//Process LIMIT clauses if any
		if(isset($ast['limit']) === true) {
			$sql_select['limit'] = $ast['limit'];
		}

		return $sql_select;
	}

	/**
	 * Analyzes an INSERT intermediate code and produces an array to be executed later
	 *
	 * @return array
	 * @throws Exception
	 */
	protected function _prepareInsert()
	{
		$ast = $this->_ast;
		if(isset($ast['qualifiedName']) === false ||
			isset($ast['values']) === false ||
			isset($ast['qualifiedName']['name']) === false) {
			throw new Exception('Corrupted INSERT AST');
		}

		$model_name = $ast['qualifiedName']['name'];
		$model = $this->_manager->load($model_name);
		$source = $model->getSource();
		$schema = $model->getSchema();

		if($schema == true) {
			$source = array($schema, $source);
		}

		//@note sql_aliases is not used
		$expr_values = array();

		foreach($ast['values'] as $expr_value) {
			//Resolve every expression in the 'values' clause
			$expr_values[] = array('type' => $expr_value['type'], 
				'value' => $this->_getExpression($expr_value, false));
		}

		$sql_insert = array('model' => $model_name, 'table' => $source);

		if(isset($ast['fields']) === true) {
			$sql_fields = array();
			foreach($ast['fields'] as $field) {
				//Check that inserted fields are part of the model
				if($this->_metaData->hasAttribute($model, $field['name']) === false) {
					throw new Exception("The model '".$model_name."' doesn't have the attribute '".
					$field['name']."', when preparing: ".$this->_phql); //@note sic!
				}

				//Add the file to the insert list
				$sql_fields[] = $field['name'];
			}

			$sql_insert['fields'] = $sql_fields;
		}
		$sql_insert['values'] = $expr_values;

		return $sql_insert;
	}

	/**
	 * Analyzes an UPDATE intermediate code and produces an array to be executed later
	 *
	 * @return array
	 * @throws Exception
	 */
	protected function _prepareUpdate()
	{
		$ast = $this->_ast;
		if(isset($ast['update']) === false ||
			isset($ast['update']['tables']) === false ||
			isset($ast['update']['values']) === false) {
			throw new Exception('Corrupted UPDATE AST');
		}

		$update = $ast['update'];

		//We use these arrays to store info related to models, alias and its sources. With 
		//them we can rename columns later
		$models = array();
		$models_instances = array();
		$sql_tables = array();
		$sql_models = array();
		$sql_aliases = array();
		$sql_aliases_models_instances = array();

		if(isset($update['tables'][0]) === false) {
			$update_tables = array($update['tables']);
		} else {
			$update_tables = $update['tables'];
		}

		foreach($update_tables as $table) {
			$model_name = $table['qualifiedName']['name'];

			//Check if the table has a namespace alias
			if(isset($table['qualifiedName']['ns-alias']) === true) {
				$real_model_name = $this->_manager->getNamespaceAlias($table['qualifiedName']['ns-alias']).'\\'.$model_name;
			} else {
				$real_model_name = $model_name;
			}

			//Load a model instance from the models manager
			$model = $this->_manager->load($real_model_name);
			$source = $model->getSource();
			$schema = $model->getSchema();

			//Create a full source representation including schema
			if($schema == true) {
				$complete_source = array($source, $schema);
			} else {
				$complete_source = array($source, null);
			}

			//Check if table is aliased
			if(isset($table['alias']) === true) {
				$alias = $table['alias'];
				$sql_aliases[$alias] = $alias;
				$complete_source[] = $alias;
				$sql_tables[] = $complete_source;
				$sql_aliases_models_instances[$alias] = $model;
				$models[$alias] = $model_name;
			} else {
				$sql_aliases[$model_name] = $source;
				$sql_aliases_models_instances[$model_name] = $model;
				$sql_tables[] = $source;
				$models[$model_name] = $source;
			}

			$sql_models[] = $model_name;
			$models_instances[$model_name] = $model;
		}

		//Update the models/aliases/sources in the object
		$this->_models = $models;
		$this->_modelsInstances = $models_instances;
		$this->_sqlAliases = $sql_aliases;
		$this->_sqlAliasesModelsInstances = $sql_aliases_models_instances;

		$sql_fields = array();
		$sql_values = array();
		$update_values = (isset($update['values'][0]) === false ? array($update['values']) : $update['values']);

		foreach($update_values as $update_value) {
			$sql_fields[]  = $this->_getExpression($update_value['column'], false);
			$value = array('type' => $update_value['type'], 'value' => $this->_getExpression($update_value['expr'], false));
		}

		$sql_update = array('tables' => $sql_tables, 'models' => $sql_models, 'fields' => $sql_fields, 'values' => $sql_values);

		if(isset($ast['where']) === true) {
			$sql_update['where'] = $this->_getExpression($ast['where'], false);
		}

		if(isset($ast['limit']) === true) {
			$sql_update['limit'] = $ast['limit'];
		}

		return $sql_update;
	}

	/**
	 * Analyzes a DELETE intermediate code and produces an array to be executed later
	 *
	 * @return array
	 * @throws Exception
	 */
	protected function _prepareDelete()
	{
		$ast = $this->_ast;

		if(isset($ast['delete']) === false ||
			isset($ast['delete']['tables']) === false) {
			throw new Exception('Corrupted DELETE AST');
		}

		$delete = $ast['delete'];

		//We use these arrays to store info related to models, alias and its sources. With
		//them we can rename columns later
		$models = array();
		$models_instances = array();
		$sql_tables = array();
		$sql_models = array();
		$sql_aliases = array();
		$sql_aliases_models_instances = array();

		if(isset($delete['tables'][0]) === false) {
			$delete_tables = array($delete['tables']);
		} else {
			$delete_tables = $delete['tables'];
		}

		foreach($delete_tables as $table) {
			$model_name = $table['qualifiedName']['name'];

			//Check if the table has a namespace alias
			if(isset($table['qualifiedName']['ns-alias']) === true) {
				$real_model_name = $this->_manager->getNamespaceAlias($table['qualifiedName']['ns-alias']).'\\'.$model_name;
			} else {
				$real_model_name = $model_name;
			}

			//Load a model instance from the models manager
			$model = $this->_manager->load($real_model_name);
			$source = $model->getSoruce();
			$schema = $model->getSchema();

			if($schema == true) {
				$complete_source = array($source, $schema);
			} else {
				$complete_source = array($source, null);
			}

			if(isset($table['alias']) === true) {
				$alias = $table['alias'];
				$sql_aliases[$alias] = $alias;
				$complete_source[] = $alias;
				$sql_tables[] = $complete_source;
				$sql_aliases_models_instances[$alias] = $model;
				$models[$alias] = $model_name;
			} else {
				$sql_aliases[$model_name] = $source;
				$sql_aliases_models_instances[$model_name] = $model;
				$sql_tables[] = $source;
				$models[$model_name] = $source;
			}

			$sql_models[] = $model_name;
			$models_instances[$model_name] = $model;
		}

		//Update the models/aliases/sources in the object
		$this->_models = $models;
		$this->_modelsInstances = $models_instances;
		$this->_sqlAliases = $sql_aliases;
		$this->_sqlAliasesModelsInstances = $sql_aliases_models_instances;

		$sql_delete = array('tables' => $sql_tables, 'models' => $sql_models);

		if(isset($ast['where']) === true) {
			$sql_delete['where'] = $this->_getExpression($ast['where'], true); //@note here: "not_quoting" = true
		}

		if(isset($ast['limit']) === true) {
			$sql_delete['limit'] = $ast['limit'];
		}

		return $sql_delete;
	}

	/**
	 * Parses the intermediate code produced by \Phalcon\Mvc\Model\Query\Lang generating another
	 * intermediate representation that could be executed by \Phalcon\Mvc\Model\Query
	 *
	 * @return array
	 * @throws Exception
	 */
	public function parse()
	{
		if(is_array($this->_intermediate) === true) {
			return $this->_intermediate;
		}

		//This function parses the PHQL statement
		$ast = Lang::parsePHQL($this->_phql);

		$ir_phql = null;
		$ir_phql_cache = null;
		$unique_id = null;

		if(is_array($ast) === true) {
			//Check if the prepared PHQL is already cached
			if(isset($ast['id']) === true) {
				//Parsed ASTs have a unique id
				$unique_id = $ast['id'];
				$ir_phql_cache = self::$_irPhqlCache;
				if(isset($ir_phql_cache[$unique_id]) === true) {
					$ir_phql = $ir_phql_cache[$unqiue_id];
					if(is_array($ir_phql) === true) {
						//Assign the type to the query
						$this->_type = $ast['type'];
						return $ir_phql;
					}
				}
			}

			//A valid AST must have a type
			if(isset($ast['type']) === true) {
				$this->_ast = $ast;

				//Produce an independent database system representation
				$this->_type = $ast['type'];

				switch((int)$this->_type) {
					case 309:
						$ir_phql = $this->_prepareSelect();
						break;
					case 306:
						$ir_phql = $this->_prepareInsert();
						break;
					case 300:
						$ir_phql = $this->_prepareUpdate();
						break;
					case 303:
						$ir_phql = $this->_prepareDelete();
						break;
					default:
						throw new Exception('Unknown statement '.$this->_type.', when preparing: '.$this->_phql);
				}
			}
		}

		if(is_array($ir_phql) === false) {
			throw new Exception('Corrupted AST');
		}

		//Store the prepared AST in the cache
		if(is_int($unique_id) === true) {
			if(is_array($ir_phql_cache) === false) {
				$ir_phql_cache = array();
			}

			$ir_phql_cache[$unique_id] = $ir_phql;
			self::$_irPhqlCache = $ir_phql_cache;
		}

		$this->_intermediate = $ir_phql;
		return $ir_phql;
	}

	/**
	 * Sets the cache parameters of the query
	 *
	 * @param array $cacheOptions
	 * @return \Phalcon\Mvc\Model\Query
	 * @throws Exception
	 */
	public function cache($cacheOptions)
	{
		if(is_array($cache) === false) {
			throw new Exception('Invalid parameter type.');
		}

		$this->_cacheOptions = $cacheOptions;
		return $this;
	}

	/**
	 * Returns the current cache options
	 *
	 * @param array|null
	 */
	public function getCacheOptions()
	{
		return $this->_cacheOptions;
	}

	/**
	 * Returns the current cache backend instance
	 *
	 * @return \Phalcon\Cache\BackendInterface|null
	 */
	public function getCache()
	{
		return $this->_cache;
	}

	/**
	 * Executes the SELECT intermediate representation producing a \Phalcon\Mvc\Model\Resultset
	 *
	 * @param array $intermediate
	 * @param array $bindParams
	 * @param array $bindTypes
	 * @return \Phalcon\Mvc\Model\ResultsetInterface
	 * @throws Exception
	 */
	protected function _executeSelect(array $intermediate, array $bindParams, array $bindTypes)
	{
		$manager = $this->_manager;

		//Models instances is an array if the SELECT was prepared with parse
		$models_instances = $this->_modelsInstances;
		if(is_array($models_instances) === false) {
			$models_instances = array();
		}

		$models = $intermediate['models'];

		$number_models = count($models);
		if($number_models === 1) {
			//Load first model if it is not loaded
			$model_name = $models[0];
			if(isset($models_instances[$model_name]) === false) {
				$model = $manager->load($model_name);
				$models_instances[$model_name] = $model;
			} else {
				$model = $models_instances[$model_name];
			}

			//The 'selectConnection' method could be implemented
			if(method_exists($model, 'selectReadConnection') === true) {
				$connection = $model->selectReadConnection($intermediate, $bindParams, $bindTypes);
				if(is_object($connection) === false) {
					throw new Exception("'selectReadConnection' didn't returned a valid connection");
				}
			} else {
				//Get the current connection
				$connection = $model->getReadConnection();
			}
		} else {
			//Check if all the models belong to the same connection
			$connections = array();
			foreach($models as $model_name) {
				if(isset($models_instances[$model_name]) === false) {
					$model = $manager->load($model_name);
					$models_instances[$model_name] = $model;
				} else {
					$model = $models_instances[$model_name];
				}

				//Get the models connection
				$connection = $model->getReadConnection();

				//Get the type of connection the model is using (mysql, postgresql, etc)
				$type = $connection->getType();

				//Mark the type of connection in the connection flags
				$connections[$type] = true;
				$connection_types = count($connections);

				//More than one type of connection is not allowed
				if($connection_types === 2) {
					throw new Exception('Cannot use models of different database systems in the same query');
				}
			}
		}

		$columns = $intermediate['columns'];
		$have_objects = false;
		$have_scalars = false;
		$is_complex = false;

		//Check if the resultset has objects and how many of them
		$number_objects = 0;
		foreach($columns as $column) {
			$column_type = $column['type'];
			if($column_type === 'scalar') {
				if(isset($column['balias']) === false) {
					$is_complex = true;
				}

				$have_scalars = true;
			} else {
				$have_objects = true;
				$number_objects++;
			}
		}

		//Check if the resultset to return is complex or simple
		if($is_complex === false) {
			if($have_objects === true) {
				if($have_scalars === true) {
					$is_complex = true;
				} else {
					if($number_objects === 1) {
						$is_simple_std = false;
					} else {
						$is_complex = true;
					}
				}
			} else {
				$is_simple_std = true;
			}
		}

		//Processing selected columns
		$selected_columns = array();
		$simple_column_map = array();
		$meta_data = $this->_metaData;

		foreach($columns as $alias_copy => $column) {
			$type = $column['type'];
			$sql_column = $column['column'];

			//Complex objects are treated in a different way
			if($type === 'object') {
				$model_name = $column['model'];

				//Base instance
				if(isset($models_instances[$model_name]) === true) {
					$instance = $models_instances[$model_name];
				} else {
					$instance = $manager->load($model_name);
					$models_instances[$model_name] = $instance;
				}

				$attributes = $meta_data->getAttributes($instance);
				if($is_complex === true) {
					//If the resultset is complex we open every model into their columns
					if(isset($GLOBALS['_PHALCON_ORM_COLUMN_RENAMING']) === true &&
						$GLOBALS['_PHALCON_ORM_COLUMN_RENAMING'] === true) {
						$column_map = $meta_data->getColumnMap($instance);
					} else {
						$column_map = null;
					}

					//Add every attribute in the model to the gernerated select
					foreach($attributes as $attribute) {
						$hidden_alias = '_'.$sql_column.'_'.$attribute;
						$select_columns[] = array($attribute, $sql_column, $hidden_alias);
					}

					//We cache required meta-data to make its future access faster
					$columns[$alias_copy]['instance'] = $instnace;
					$columns[$alias_copy]['attributes'] = $attributes;
					$columns[$alias_copy]['columnMap'] = $column_map;

					//Check if the model keeps snapshots
					$is_keeping_snapshots = $manager->isKeepingSnapshots($instance);
					if($is_keeping_snapshots === true) {
						$columns[$alias_copy]['keepSnapshopts'] = $is_keeping_snapshots;
					}
				} else {
					//Query only the columns that are registered as attributes in the metaData
					foreach($attributes as $attribute) {
						$select_columns[] = array($attribute, $sql_column);
					}
				}
			} else {
				//Create an alias if the column doesn't have one
				if(is_int($alias_copy) === true) {
					$column_alias = array($sql_column, null);
				} else {
					$column_alias = array($sql_column, null, $alias_copy);
				}

				$select_columns[] = $column_alias;
			}

			//Simulate a column map
			if($is_complex === false) {
				if($is_simple_std === true) {
					if(isset($column['sqlAlias']) === true) {
						$simple_column_map[$column['sqlAlias']] = $alias_copy;
					} else {
						$simple_column_map[$alias_copy] = $alias_copy;
					}
				}
			}
		}

		unset($columns);
		$intermediate['columns'] = $select_columns;

		//The corresponding SQL dialect generates the SQL statement based on
		//the database system
		$dialect = $connection->getDialect();
		$sql_select = $dialect->select($intermediate);

		//Replace the placeholders
		if(is_array($bindParams) === true) {
			$processed = array();
			foreach($bindParams as $wildcard => $value) {
				if(is_int($wildcard) === true) {
					$processed[':'.$wildcard] = $value;
				} else {
					$processed[$wildcard] = $value;
				}
			}
		} else {
			$processed = $bindParams;
		}

		//Replace the bind types
		if(is_array($bindTypes) === true) {
			$processed_types = array();
			foreach($bindTypes as $type_wildcard => $value) {
				if(is_int($type_wildcard) === true) {
					$processed_types[':'.$type_wildcard] = $value;
				} else {
					$processed_types[$type_wildcard] = $value;
				}
			}
		} else {
			$processed_types = $bindTypes;
		}

		//Execute the query
		$result = $connection->query($sql_select, $processed, $processed_types);

		//Choose a resultset type
		if($is_complex === false) {
			//Select the base object
			if($is_simple_std === true) {
				//If the resultset is a simple standard object, use a Phalcon\Mvc\Model\Row as base
				$result_object = new Row();

				//Standard objects can't keep snapshots
				$is_keeping_snapshots = false;
			} else {
				$result_object = $model;

				//Get the column map
				$simple_column_map = $meta_data->getColumnMap($model);

				//Check if the model keeps snapshots
				$is_keeping_snapshots = $manger->isKeepingSnapshots($model);
			}
		}

		//Simple resultsets contain only complete objects
		return new Simple($simple_column_map, $result_object, 
			($result->numRows($result) > 0 ? $result : false), $this->_cache, $is_keeping_snapshots);
	}

	/**
	 * Executes the INSERT intermediate representation producing a \Phalcon\Mvc\Model\Query\Status
	 *
	 * @param array $intermediate
	 * @param array $bindParams
	 * @param array $bindTypes
	 * @return \Phalcon\Mvc\Model\Query\StatusInterface
	 * @throws Exception
	 */
	protected function _executeInsert(array $intermediate, array $bindParams, array $bindTypes)
	{
		$manager = $this->_manager;

		$model_name = $intermediate['model'];
		$models_instances = $this->_modelsInstances;

		if(isset($models_instances[$model_name]) === true) {
			$model = $models_instances[$model_name];
		} else {
			$model = $manager->load($model_name);
		}

		//Get the model connection
		$connection = $model->getWriteConnection();
		$automatic_fields = false;

		//The 'fields' index may already have the fields to be used in the query
		if(isset($intermediate['fields']) === tue) {
			$fields = $intermediate['fields'];
		} else {
			$automatic_fields = true;
			$fields = $this->_metaData->getAttributes($model);
			if(isset($GLOBALS['_PHALCON_ORM_COLUMN_RENAMING']) === true &&
				$GLOBALS['_PHALCON_ORM_COLUMN_RENAMING'] === true) {
				$column_map = $this->_metaData->getColumnMap($model);
			} else {
				$column_map = null;
			}
		}

		//The number of calculated values must be equal to the number of fields in the
		//model
		if(count($fields) !== count($intermediate['values'])) {
			throw new Exception('The column count does not match the values count');
		}

		//Get the dialect to resolve the SQL expressions
		$dialect = $connection->getDialect();
		$not_exists = false;
		$insert_values = array();

		foreach($intermediate['values'] as $number => $value) {
			switch((int)$value['type']) {
				case 260:
				case 258:
				case 259:
					$insert_value = $dialect->getSqlExpression($value['value']);
					break;
				case 332:
					$insert_value = null;
					break;
				case 273:
				case 274:
					if(is_array($bindParams) === false) {
						throw new Exception('Bound parameter cannot be replaced because placeholders is not an array');
					}

					$wildcard = str_replace(':', '', $dialect->getSqlExpression($value['value']));
					if(isset($bindParams[$wildcard]) === false) {
						throw new Exception("Bound parameter '".$wildcard."' cannot be replaced because it isn't in the placeholder list");
					}

					$insert_value = $bindParams[$wildcard];
					break;
				default:
					$insert_value = new RawValue($dialect->getSqlExpression($value['value']));
					break;
			}

			$field_name = $fields[$number];

			//If the user didn't defined a column list we assume all the model's attributes are columns
			if($automatic_fields === true) {
				if(is_array($column_map) === true) {
					if(isset($column_map[$field_name]) === true) {
						$attribute_name = $column_map[$field_name];
					} else {
						throw new Exception("Column '".$field_name."\" isn't part of the column map");
					}
				} else {
					$attribute_name = $field_name;
				}
			} else {
				$attribute_name = $field_name;
			}

			$insert_values[$attribute_name] = $insert_value;
		}

		//Get a base model from the models manager
		$base_model = $manager->load($model_name);

		//Clone the base model
		$insert_model = clone $base_model;

		//Call 'create' to ensure that an insert is performed
		//Return the insertation status
		return new Status($insert_model->create($insert_values), $insert_model);
	}

	/**
	 * Query the records on which the UPDATE/DELETE operation well be done
	 *
	 * @param \Phalcon\Mvc\Model $model
	 * @param array $intermediate
	 * @param array $bindParams
	 * @param array $bindTypes
	 * @return \Phalcon\Mvc\Model\ResultsetInterface
	 */
	protected function _getRelatedRecords(Model $model, array $intermediate, array $bindParams, array $bindTypes)
	{
		$select_columns = array(array(array('type' => 'object', 'model' => get_class($model), 'column' => $model->getSource())));

		//Instead of creating a PHQL string statement, we manually create the IR representation
		$select_ir = array('columns' => $select_columns, 'models' => $intermediate['models'], 'tables' => $intermediate['tables']);

		//Check if a WHERE clause was especified
		if(isset($intermediate['where']) === true) {
			$select_ir['where'] = $intermediate['where'];
		}

		if(isset($intermediate['limit']) === true) {
			$select_ir['limit'] = $intermediate['limit'];
		}

		//We create another Phalcon\Mvc\Model\Query to get the related records
		$query = new Query();
		$query->setDi($this->_dependencyInjector);
		$query->setType(309);
		$query->setIntermediate($select_ir);
		return $query->execute($bindParams, $bindTypes);
	}

	/**
	 * Executes the UPDATE intermediate representation producing a \Phalcon\Mvc\Model\Query\Status
	 *
	 * @param array $intermediate
	 * @param array $bindParams
	 * @param array $bindTypes
	 * @return \Phalcon\Mvc\Model\Query\StatusInterface
	 * @throws Exception
	 */
	protected function _executeUpdate(array $itermediate, array $bindParams, array $bindTypes)
	{
		//Get the model_name
		$models = $itermediate['models'];
		if(isset($models[1]) === true) {
			throw new Exception('Updating several models at the same time is still not supported');
		}
		$model_name = $models[0];

		//Load the model from the modelsManager or from the _modelsInstances property
		if(isset($this->_modelsInstances[$model_name]) === true) {
			$model = $this->_modelsInstances[$model_name];
		} else {
			$model = $this->_manager->load($model_name);
		}

		//Get the connection
		$connection = $model->getWriteConnection();
		$dialect = $connection->getDialect();

		//update_values is applied to every record
		$fields = $intermediate['fields'];
		$values = $intermediate['values'];
		$update_values = array();

		//If a placeholder is unused in the update values, we assume that it's used in the
		//SELECT
		$select_bind_params = $bindParams;
		$select_bind_types = $bindTypes;

		//Loop through fields
		foreach($fields as $number => $field) {
			$field_name = $field['name'];
			$value = $values[$number];

			switch((int)$value['type']) {
				case 260:
				case 258:
				case 259:
					$update_value = $dialect->getSqlExpression($value['value']);
					break;
				case 322:
					$update_value = null;
					break;
				case 273:
				case 274:
					if(is_array($bindParams) === false) {
						throw new Exception('Bound parameter cannot be replaced because placeholders is not an array');
					}

					$wildcard = str_replace(':', '', $dialect->getSqlExpression($value['value']));
					if(isset($bindParams[$wildcard]) === true) {
						$update_value = $bindParams[$wildcard];
						unset($select_bind_params[$wildcard]);
						unset($select_bind_types[$wildcard]);
					} else {
						throw new Exception("Bound parameter '".$wildcard."' cannnot be replaced because it's not in the placeholders list");
					}
					break;
				default:
					$update_value = new RawValue($dialect->getSqlExpression($value['value']));
					break;
			}
		}

		//We need to query the records related to the update
		$records = $this->_getRelatedRecords($model, $intermediate, $select_bind_params, $select_bind_types);

		//If there are no records to apple the update, we return success
		if(count($records) == 0) {
			return new Status(true, null);
		}

		//@note we don't need to get the write connection here again

		//Create a transaction in the write connection
		$connection->begin();
		$records->rewind();

		while($records->valid() !== false) {
			//Get the current record in the iterator
			$record = $records->current();

			//We apply the executed values to every record found
			if($record->update($update_values) !== true) {
				//Rollback the transaction on failure
				$connection->rollback();
				return new Status(false, $record);
			}

			//Move the cursor to the next record
			$records->next();
		}

		//Commit transaction on success
		$connection->commit();
		return new Status(true, null);
	}

	/**
	 * Executes the DELETE intermediate representation producing a \Phalcon\Mvc\Model\Query\Status
	 *
	 * @param array $intermediate
	 * @param array $bindParams
	 * @param array $bindTypes
	 * @return \Phalcon\Mvc\Model\Query\StatusInterface
	 * @throws Exception
	 */
	protected function _executeDelete(array $itermediate, array $bindParams, array $bindTypes)
	{
		$models = $intermediate['models'];
		if(isset($models[1]) === true) {
			throw new Exception('Delete from several models at the same time is still not supported');
		}

		$model_name = $models[0];

		//Load the model from the modelsManager or from the _modelsInstances property
		if(isset($this->_modelsInstances[$model_name]) === true) {
			$model = $models_instances[$model_name];
		} else {
			$model = $this->_manager->load($model_name);
		}

		//Get the records to be deleted
		$records = $this->_getRelatedRecords($model, $intermediate, $bindParams, $bindTypes);

		//If there are no records to delete we return success
		if(count($records) == 0) {
			return new Status(true, null);
		}

		//Create a transaction in the write connection
		$connection = $model->getWriteConnection();
		$connection->begin();
		$records->rewind();

		while($records->valid() !== false) {
			$record = $records->current();

			//We delete every record found
			if($record->delete() !== true) {
				//Rollback the transaction
				$connection->rollback();
				return new Status(false, $record);
			}

			//Move the cursor to the next record
			$records->next();
		}

		//Commit the transaction
		$connection->commit();

		//Create a status to report the deletion status
		return new Status(true, null);
	}

	/**
	 * Executes a parsed PHQL statement
	 *
	 * @param array|null $bindParams
	 * @param array|null $bindTypes
	 * @return mixed
	 * @throws Exception
	 */
	public function execute($bindParams = null, $bindTypes = null)
	{
		if((is_array($bindParams) === false &&
			is_null($bindParams) === false) ||
			(is_array($bindTypes) === false &&
				is_null($bindParams) === false)) {
			throw new Exception('Invalid parameter type.');
		}

		/* GET THE CACHE */
		$cache_options = $this->_cacheOptions;
		if(is_null($cache_options) === false) {
			if(is_array($cache_options) === false) {
				throw new Exception('Invalid caching options');
			}

			//The user must set a cache key
			if(isset($cache_options['key']) === true) {
				$key = $cache_options['key'];
			} else {
				throw new Exception('A cache key must be provided to identify the cached resultset in the cache backend');
			}

			//By default use 3600 seconds (1 hour) as cache lifetime
			if(isset($cache_options['lifetime']) === true) {
				$lifetime = $cache_options['lifetime'];
			} else {
				$lifetime = 3600;
			}

			//'modelsCache' is the default name for the models cache service
			if(isset($cache_options['service']) === true) {
				$cache_service = $cache_options['service'];
			} else {
				$cache_service = 'modelsCache';
			}

			$cache = $this->_dependencyInjector->getShared($cache_service);
			if(is_object($cache) === false) { //@note no interface validation
				throw new Exception('The cache service must be an object');
			}

			$result = $cache->get($key, $lifetime);
			if(is_null($result) === false) {
				if(is_object($result) === false) {
					throw new Exception("The cache didn't return a valid resultset"); //@note (sic!)
				}

				$result->setIsFresh(false);

				//Check if only the first two rows must be returned
				if($this->_uniqueRow == true) {
					$prepared_result = $result->getFirst();
				} else {
					$prepared_result = $result;
				}

				return $prepared_result;
			}

			$this->_cache = $cache;
		}

		//The statement is parsed from its PHQL string or a previously processed IR
		$intermediate = $this->parse();

		//Check for default bind parameters and merge them with the passed ones
		$default_bind_params = $this->_bindParams;
		if(is_array($default_bind_params) === true) {
			if(is_array($bindParams) === true) {
				$merged_params = array_merge($default_bind_params, $bindParams);
			} else {
				$merged_params = $default_bind_params;
			}
		} else {
			$merged_params = $bindParams;
		}

		//Check for default bind types and merge them with the passed onees
		$default_bind_types = $this->_bindTypes;
		if(is_array($default_bind_types) === true) {
			if(is_array($bindTypes) === true) {
				$merged_types = array_merge($default_bind_types, $bindTypes);
			} else {
				$merged_types = $default_bind_types;
			}
		} else {
			$merged_types = $bindTypes;
		}

		switch((int)$this->_type) {
			case 309:
				$result = $this->_executeSelect($intermediate, $merged_params, $merged_types);
				break;
			case 306:
				$result = $this->_executeInsert($intermediate, $merged_params, $merged_types);
				break;
			case 300:
				$result = $this->_executeUpdate($intermediate, $merged_params, $merged_types);
				break;
			case 303:
				$result = $this->_executeDelete($intermediate, $merged_params, $merged_types);
				break;
			default:
				throw new Exception('Unknown statement '.$this->_type);
				break;
		}

		//We store the resultset in the cache if any
		if(is_null($cache_options) === false) {
			//Only PHQL SELECTs can be cached
			if($type !== 309) {
				throw new Exception('Only PHQL statements return resultsets can be cached');
			}

			$cache->save($key, $result, $lifetime);
		}

		//Check if only the first row must be returned
		if($this->_uniqueRow == true) {
			return $result->getFirst();
		} else {
			return $result;
		}
	}

	/**
	 * Executes the query returning the first result
	 *
	 * @param array|null $bindParams
	 * @param array|null $bindTypes
	 * @return \Phalcon\Mvc\ModelInterface
	 */
	public function getSingleResult($bindParams = null, $bindTypes = null)
	{
		//The query is already programmed to return just one row
		if($this->_uniqueRow == true) {
			return $this->execute($bindParams, $bindTypes);
		}

		//return $this->execute($bindParams, $bindTypes)->getFirst();
		return $this->execute($bindParams, $bindTypes); //@note this is wrong, "first_result" should be returned instead
	}

	/**
	 * Sets the type of PHQL statement to be executed
	 *
	 * @param int $type
	 * @return \Phalcon\Mvc\Model\Query
	 * @throws Exception
	 */
	public function setType($type)
	{
		if(is_int($type) === false) {
			throw new Exception('Invalid parameter type.');
		}

		$this->_type = $type;
		return $this;
	}

	/**
	 * Gets the type of PHQL statement executed
	 *
	 * @return int|null
	 */
	public function getType()
	{
		return $this->_type;
	}

	/**
	 * Set default bind parameters
	 *
	 * @param array $bindParams
	 * @return \Phalcon\Mvc\Model\Query
	 * @throws Exception
	 */
	public function setBindParams($bindParams)
	{
		if(is_array($bindParams) === false) {
			throw new Exception('Bind parameters must be an array');
		}

		$this->_bindParams = $bindParams;

		return $this;
	}

	/**
	 * Returns default bind params
	 *
	 * @return array|null
	 */
	public function getBindParams()
	{
		return $this->bindParams;
	}

	/**
	 * Set default bind parameters
	 *
	 * @param array $bindTypes
	 * @return \Phalcon\Mvc\Model\Query
	 * @throws Exception
	 */
	public function setBindTypes($bindTypes)
	{
		if(is_array($bindTypes) === false) {
			throw new Exception('Bind types must be an array');
		}

		$this->_bindTypes = $bindTypes;

		return $this;
	}

	/**
	 * Returns default bind types
	 *
	 * @return array|null
	 */
	public function getBindTypes()
	{
		return $this->_bindTypes;
	}

	/**
	 * Allows to set the IR to be executed
	 *
	 * @param array $intermediate
	 * @return \Phalcon\Mvc\Model\Query
	 * @throws Exception
	 */
	public function setIntermediate($intermediate)
	{
		if(is_array($intermediate) === false) {
			throw new Exception('Invalid parameter type.');
		}

		$this->_intermediate = $intermediate;

		return $this;
	}

	/**
	 * Returns the intermediate representation of the PHQL statement
	 *
	 * @return array|null
	 */
	public function getIntermediate()
	{
		return $this->_intermediate;
	}
}