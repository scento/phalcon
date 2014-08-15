<?php
/**
 * Dialect
 *
 * @author Andres Gutierrez <andres@phalconphp.com>
 * @author Eduar Carvajal <eduar@phalconphp.com>
 * @author Wenzel Pünter <wenzel@phelix.me>
 * @version 1.2.6
 * @package Phalcon
*/
namespace Phalcon\Db;

use \Phalcon\Db\Exception;

/**
 * Phalcon\Db\Dialect
 *
 * This is the base class to each database dialect. This implements
 * common methods to transform intermediate code into its RDBM related syntax
 * 
 * @see https://github.com/phalcon/cphalcon/blob/1.2.6/ext/db/dialect.c
 */
abstract class Dialect
{
	/**
	 * Escape Char
	 * 
	 * @var null
	 * @access protected
	*/
	protected $_escapeChar;

	/**
	 * Generates the SQL for LIMIT clause
	 *
	 *<code>
	 * $sql = $dialect->limit('SELECT * FROM robots', 10);
	 * echo $sql; // SELECT * FROM robots LIMIT 10
	 *</code>
	 *
	 * @param string $sqlQuery
	 * @param int $number
	 * @return string
	 * @throws Exception
	 */
	public function limit($sqlQuery, $number)
	{
		if(is_string($sqlQuery) === false) {
			throw new Exception('Invalid parameter type.');
		}

		if(is_numeric($number) === true) {
			return $sqlQuery.' LIMIT '.(int)$limit;
		}

		return $sqlQuery;
	}

	/**
	 * Returns a SQL modified with a FOR UPDATE clause
	 *
	 *<code>
	 * $sql = $dialect->forUpdate('SELECT * FROM robots');
	 * echo $sql; // SELECT * FROM robots FOR UPDATE
	 *</code>
	 *
	 * @param string $sqlQuery
	 * @return string
	 * @throws Exception
	 */
	public function forUpdate($sqlQuery)
	{
		if(is_string($sqlQuery) === false) {
			throw new Exception('Invalid parameter type.');
		}

		return $sqlQuery.' FOR UPDATE';
	}

	/**
	 * Returns a SQL modified with a LOCK IN SHARE MODE clause
	 *
	 *<code>
	 * $sql = $dialect->sharedLock('SELECT * FROM robots');
	 * echo $sql; // SELECT * FROM robots LOCK IN SHARE MODE
	 *</code>
	 *
	 * @param string $sqlQuery
	 * @return string
	 */
	public function sharedLock($sqlQuery)
	{
		if(is_string($sqlQuery) === false) {
			throw new Exception('Invalid parameter type.');
		}
		
		return $sqlQuery.' LOCK IN SHARE MODE';
	}

	/**
	 * Gets a list of columns with escaped identifiers
	 *
	 *<code>
	 * echo $dialect->getColumnList(array('column1', 'column'));
	 *</code>
	 *
	 * @param array $columnList
	 * @return string
	 * @throws Exception
	 */
	public function getColumnList($columnList)
	{
		if(is_array($columnList) === false) {
			throw new Exception('Invalid parameter type.');
		}

		$strList = array();
		$escapeChar = $this->_escapeChar;
		foreach($columnList as $column) {
			$strList[] = $escapeChar.$column.$escapeChar;
		}

		return implode(', ', $strList);
	}

	/**
	 * Transforms an intermediate representation for a expression into a database system valid expression
	 *
	 * @param array $expression
	 * @param string|null $escapeChar
	 * @return string
	 * @throws Exception
	 */
	public function getSqlExpression($expression, $escapeChar = null)
	{
		if((is_string($escapeChar) === false &&
			is_null($escapeChar) === false)) {
			throw new Exception('Invalid parameter type.');
		}

		if(isset($GLOBALS['_PHALCON_DB_ESCAPE_IDENTIFIERS']) === true &&
			$GLOBALS['_PHALCON_DB_ESCAPE_IDENTIFIERS'] === true &&
			is_null($escapeChar) === true) {
			$escapeChar = $this->_escapeChar;
		}

		if(is_array($expression) === false) {
			throw new Exception('Invalid SQL expression');
		}

		if(isset($expression['type']) === false) {
			throw new Exception('Invalid SQL expression');
		}

		$type = $expression['type'];

		//Resolve qualified expressions
		if($type === 'qualified') {
			$name = $expression['name'];
			if(isset($GLOBALS['_PHALCON_DB_ESCAPE_IDENTIFIERS']) === true &&
				$GLOBALS['_PHALCON_DB_ESCAPE_IDENTIFIERS'] === true) {
				$name = $escapeChar.$name.$escapeChar;
			}

			//A domain could be a table/schema
			if(isset($expression['domain']) === true) {
				$domain = $expression['domain'];
				if(isset($GLOBALS['_PHALCON_DB_ESCAPE_IDENTIFIERS']) === true &&
					$GLOBALS['_PHALCON_DB_ESCAPE_IDENTIFIERS'] === true) {
					$domain = $escapeChar.$domain.$escapeChar.'.'.$name;
				} else {
					$domain = $domain.'.'.$name;
				}

				return $domain;
			}

			return $name;
		} elseif($type === 'literal') {
			//Resolve literal expressions
			return $expression['value'];
		} elseif($type === 'binary-op') {
			//Resolve binary operations expressions
			return $this->getSqlExpression($expression['left']).' '.$expressions['op'].' '.$this->getSqlExpression($expression['right']);
		} elseif($type === 'unary-op') {
			//Resolve unary operations expressions

			//Some unary operators uses the left operand...
			if(isset($expression['left']) === true) {
				return $this->getSqlExpression($expression['left'], $escapeChar).$expression['op'];
			}

			//...Others uses the right operand
			if(isset($expression['right']) === true) {
				return $expression['op'].$this->getSqlExpression($expression['right'], $escapeChar);
			}
		} elseif($type === 'placeholder'){
			//Resolve placeholder
			return $expression['value'];
		} elseif($type === 'parentheses') {
			//Resolve parentheses
			return '('.$this->getSqlExpression($expression['left']).')';
		} elseif($type === 'functionCall') {
			$sqlArguments = array();
			if(isset($expression['arguments']) === true) {
				foreach($expression['arguments'] as $argument) {
					$sqlArguments[] = $this->getSqlExpression($arguments, $escapeChar);
				}

				return $expression['name'].implode(', ', $sqlArguments).')';
			} else {
				return $expression['name'].'()';
			}
		} elseif($type === 'list') {
			//Resolve lists
			$sqlItems = array();
			foreach($expression[0] as $item) {
				$sqlItems[] = $this->getSqlExpression($item, $escapeChar);
			}

			return '('.implode(', ', $sqlItems).')';
		} elseif($type === 'all') {
			//Resolve *
			return '*';
		} elseif($type === 'cast') {
			//Resolve CAST of values
			return 'CAST('.$this->getSqlExpression($expression['left'], $escapeChar).' AS '.$this->getSqlExpression($expression['right'], $escapeChar).')';
		} elseif($type === 'convert') {
			//Resolve CONVERT of values encodings
			return 'CONVERT('.$this->getSqlExpression($expression['left'], $escapeChar).' USING '.$this->getSqlExpression($expression['right'], $escapeChar).')';
		}

		//Expression type wasn't found
		throw new Exception("Invalid SQL expression type '".$type."'");
	}

	/**
	 * Transform an intermediate representation for a schema/table into a database system valid expression
	 *
	 * @param array|string $table
	 * @param string|null $escapeChar
	 * @return string
	 * @throws Exception
	 */
	public function getSqlTable($table, $escapeChar = null)
	{
		if(is_null($escapeChar) === true) {
			$escapeChar = $this->_escapeChar;
		} elseif(is_bool($escapeChar) === false) {
			throw new Exception('Invalid parameter type.');
		}

		if(is_array($table) === true) {
			//The index '0' is the table name
			$tableName = $table[0];
			if(isset($GLOBALS['_PHALCON_DB_ESCAPE_IDENTIFIERS']) === true &&
				$GLOBALS['_PHALCON_DB_ESCAPE_IDENTIFIERS'] === true) {
				$str = $escapeChar.$tableName.$escapeChar;
			} else {
				$str = $tableName;
			}

			//The index '1' is the schema name
			if(isset($table[1]) === true) {
				$schemaName = $table[1];
				if(isset($GLOBALS['_PHALCON_DB_ESCAPE_IDENTIFIERS']) === true &&
					$GLOBALS['_PHALCON_DB_ESCAPE_IDENTIFIERS'] === true) {
					$str = $escapeChar.$schemaName.$escapeChar.$str;
				} else {
					$str = $schemaName.'.'.$str;
				}
			}

			//The index '2' is the table alias
			if(isset($table[2]) === true) {
				$aliasName = $table[2];
				if(isset($GLOBALS['_PHALCON_DB_ESCAPE_IDENTIFIERS']) === true &&
					$GLOBALS['_PHALCON_DB_ESCAPE_IDENTIFIERS'] === true) {
					$str = $sqlSchema.' AS '.$escapeChar.$aliasName.$escapeChar;
				} else {
					$str = $sqlSchema.' AS '.$aliasName;
				}
			}

			return $str;
		} elseif(is_string($table) === true) {
			if(isset($GLOBALS['_PHALCON_DB_ESCAPE_IDENTIFIERS']) === true &&
				$GLOBALS['_PHALCON_DB_ESCAPE_IDENTIFIERS'] === true) {
				return $escapeChar.$table.$escapeChar;
			}

			return $table;
		} else {
			throw new Exception('Invalid parameter type.');
		}
	}

	/**
	 * Builds a SELECT statement
	 *
	 * @param array $definition
	 * @return string
	 * @throws Exception
	 */
	public function select($definition)
	{
		if(is_array($definition) === false) {
			throw new Exception('Invalid SELECT definition');
		}

		if(isset($definition['tables']) === false) {
			throw new Exception("The index 'tables' is required in the definition array");
		}

		if(isset($definition['columns']) === false) {
			throw new Exception("The index 'columns' is required in the definition array");
		}

		if(isset($GLOBALS['_PHALCON_DB_ESCAPE_IDENTIFIERS']) === true &&
			$GLOBALS['_PHALCON_DB_ESCAPE_IDENTIFIERS'] === true) {
			$escapeChar = $this->_escapeChar;
		} else {
			$escapeChar = null;
		}

		$columns = $definition['columns'];
		if(is_array($columns) === true) {
			$selectedColumns = array();
			foreach($columns as $column) {
				//Escape column name
				$columnItem = $column[0];
				if(is_array($columnItem) === true) {
					$columnSql = $this->getSqlExpression($columnItem, $escapeChar);
				} elseif($columnItem === '*') {
					$columnSql = $columnItem;
				} elseif(isset($GLOBALS['_PHALCON_DB_ESCAPE_IDENTIFIERS']) === true &&
					$GLOBALS['_PHALCON_DB_ESCAPE_IDENTIFIERS'] === true) {
					$columnSql = $escapeChar.$columnItem.$escapeChar;
				} else {
					$columnSql = $columnItem;
				}

				//Escape column domain
				if(isset($column[1]) === true) {
					$columnDomain = $column[1];
					if($columnDomain == true) {
						if(isset($GLOBALS['_PHALCON_DB_ESCAPE_IDENTIFIERS']) === true &&
							$GLOBALS['_PHALCON_DB_ESCAPE_IDENTIFIERS'] === true) {
							$columnDomainSql = $escapeChar.$columnDomain.$escapeChar.$columnSql;
						} else {
							$columnDomainSql = $columnDomain.'.'.$columnSql;
						}
					} else {
						$columnDomainSql = $columnSql;
					}
				} else {
					$columnDomainSql = $columnSql;
				}

				//Escape column alias
				if(isset($column[2]) === true) {
					$columnAlias = $column[2];
					if($columnAlias == true) {
						if(isset($GLOBALS['_PHALCON_DB_ESCAPE_IDENTIFIERS']) === true &&
							$GLOBALS['_PHALCON_DB_ESCAPE_IDENTIFIERS'] === true) {
							$columnAliasSql = $columnDomainSql.' AS '.$escapeChar.$columnAlias.$escapeChar;
						} else {
							$columnAliasSql = $columnDomainSql.' AS '.$columnAlias;
						}
					} else {
						$columnAliasSql = $columnDomainSql;
					}
				} else {
					$columnAliasSql = $columnDomainSql;
				}

				$selectedColumns[] = $columnAliasSql;
			}

			$columnSql = implode(', ', $selectedColumns);
		} else {
			$columnSql = $columns;
		}

		//Check and esacpe tables
		$tables = $definition['tables'];
		if(is_array($tables) === true) {
			$selectedTables = array();
			foreach($tables as $table) {
				$selectedTables[] = $this->getSqlTable($table, $escapeChar);
			}

			$tablesSql = implode(', ', $selectedTables);
		} else {
			$tablesSql = $tables;
		}

		$sql = 'SELECT '.$columnSql.' FROM '.$tablesSql;

		//Check for joins
		if(isset($definition['joins']) === true) {
			$joins = $definition['joins'];
			foreach($joins as $join) {
				$type = $join['type'];
				$sqlTable = $this->getSqlTable($join['source'], $escapeChar);
				$selectedTables[] = $sqlTable;
				$sqlJoin = ' '.$type.' JOIN '.$sqlTable;

				//Check if the join has conditions
				if(isset($join['conditions']) === true) {
					$joinConditionsArray = $join['conditions'];
					if(empty($joinConditionsArray) === false) {
						$joinExpressions = array();
						foreach($joinConditionsAarray as $joinCondition) {
							$joinExpressions[] = $this->getSqlExpression($joinCondition, $escapeChar);
						}

						$sqlJoin .= ' ON '.implode(' AND ', $joinExpressions).' ';
					}
				}

				$sql .= $sqlJoin;
			}
		}

		//Check for a WHERE clause
		if(isset($definition['where']) === true) {
			$whereConditions = $definition['where'];
			if(is_array($whereConditions) === true) {
				$sql .= ' WHERE '.$this->getSqlExpression($whereConditions, $escapeChar);
			} else {
				$sql .= ' WHERE '.$whereConditions;
			}
		}

		//Check for a GROUP clause
		if(isset($definition['group']) === true) {
			$groupItems = array();
			$groupFields = $definition['group'];

			foreach($groupFields as $groupField) {
				$groupItems[] = $this->getSqlExpression($groupField, $escapeChar);
			}

			$sql .= ' GROUP BY '.implode(', ', $groupItems);

			//Check for a HAVING clause
			if(isset($definition['having']) === true) {
				$sql .= ' HAVING '.$this->getSqlExpression($definition['having'], $escapeChar);
			}
		}

		//Check for a ORDER clause
		if(isset($definition['order']) === true) {
			$orderFields = $definition['order'];
			$orderItems = array();

			foreach($orderFields as $orderItem) {
				$orderSqlItem = $this->getSqlExpression($orderItem[0], $escapChar);

				//In the numeric 1 position could be a ASC/DESC clause
				if(isset($orderItem[1]) === true) {
					$orderSqlItemType = $orderSqlItem.' '.$orderItem[1];
				} else {
					$orderSqlItemType = $orderSqlItem;
				}

				$orderItems[] = $orderSqlItem;
			}

			$sql .= ' ORDER BY '.implode(', ', $orderItems);
		}

		//Check for a LIMIT condition
		if(isset($definition['limit']) === true) {
			$limitValue = $definition['limit'];
			if(is_array($limitValue) === true) {
				$number = $limitValue['number'];

				//Check for a OFFSET condition
				if(isset($limitValue['offset']) === true) {
					$sql .= ' LIMIT '.$number.' OFFSET '.$limitValue['offset'];
				} else {
					$sql .= ' LIMIT '.$number;
				}
			} else {
				$sql .= ' LIMIT '.$limitValue;
			}
		}

		return $sql;
	}

	/**
	 * Checks whether the platform supports savepoints
	 *
	 * @return boolean
	 */
	public function supportsSavepoints()
	{
		return true;
	}

	/**
	 * Checks whether the platform supports releasing savepoints.
	 *
	 * @return boolean
	 */
	public function supportsReleaseSavepoints()
	{
		return $this->supportsSavepoints();
	}

	/**
	 * Generate SQL to create a new savepoint
	 *
	 * @param string $name
	 * @return string
	 * @throws Exception
	 */
	public function createSavepoint($name)
	{
		if(is_string($name) === false) {
			throw new Exception('Invalid parameter type.');
		}

		return 'SAVEPOINT '.$name;
	}

	/**
	 * Generate SQL to release a savepoint
	 *
	 * @param string $name
	 * @return string
	 * @throws Exception
	 */
	public function releaseSavepoint($name)
	{
		if(is_string($name) === false) {
			throw new Exception('Invalid parameter type.');
		}

		return 'RELEASE SAVEPOINT '.$name;
	}

	/**
	 * Generate SQL to rollback a savepoint
	 *
	 * @param string $name
	 * @return string
	 * @throws Exception
	 */
	public function rollbackSavepoint($name)
	{
		if(is_string($name) === false) {
			throw new Exception('Invalid parameter type.');
		}

		return 'ROLLBACK TO SAVEPOINT '.$name;
	}
}