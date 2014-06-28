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

		$str_list = array();
		$escape_char = $this->_escapeChar;
		foreach($columnList as $column) {
			$str_list[] = $escape_char.$column.$escape_char;
		}

		return implode(', ', $str_list);
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
			$sql_arguments = array();
			if(isset($expression['arguments']) === true) {
				foreach($expression['arguments'] as $argument) {
					$sql_arguments[] = $this->getSqlExpression($arguments, $escapeChar);
				}

				return $expression['name'].implode(', ', $sql_arguments).')';
			} else {
				return $expression['name'].'()';
			}
		} elseif($type === 'list') {
			//Resolve lists
			$sql_items = array();
			foreach($expression[0] as $item) {
				$sql_items[] = $this->getSqlExpression($item, $escapeChar);
			}

			return '('.implode(', ', $sql_items).')';
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
			$table_name = $table[0];
			if(isset($GLOBALS['_PHALCON_DB_ESCAPE_IDENTIFIERS']) === true &&
				$GLOBALS['_PHALCON_DB_ESCAPE_IDENTIFIERS'] === true) {
				$str = $escapeChar.$table_name.$escapeChar;
			} else {
				$str = $table_name;
			}

			//The index '1' is the schema name
			if(isset($table[1]) === true) {
				$schema_name = $table[1];
				if(isset($GLOBALS['_PHALCON_DB_ESCAPE_IDENTIFIERS']) === true &&
					$GLOBALS['_PHALCON_DB_ESCAPE_IDENTIFIERS'] === true) {
					$str = $escapeChar.$schema_name.$escapeChar.$str;
				} else {
					$str = $schema_name.'.'.$str;
				}
			}

			//The index '2' is the table alias
			if(isset($table[2]) === true) {
				$alias_name = $table[2];
				if(isset($GLOBALS['_PHALCON_DB_ESCAPE_IDENTIFIERS']) === true &&
					$GLOBALS['_PHALCON_DB_ESCAPE_IDENTIFIERS'] === true) {
					$str = $sql_schema.' AS '.$escapeChar.$alias_name.$escapeChar;
				} else {
					$str = $sql_schema.' AS '.$alias_name;
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
			$esacpe_char = $this->_escapeChar;
		} else {
			$esacpe_char = null;
		}

		$columns = $definition['columns'];
		if(is_array($columns) === true) {
			$selected_columns = array();
			foreach($columns as $column) {
				//Escape column name
				$column_item = $column[0];
				if(is_array($column_item) === true) {
					$column_sql = $this->getSqlExpression($column_item, $esacpe_char);
				} elseif($column_item === '*') {
					$column_sql = $column_item;
				} elseif(isset($GLOBALS['_PHALCON_DB_ESCAPE_IDENTIFIERS']) === true &&
					$GLOBALS['_PHALCON_DB_ESCAPE_IDENTIFIERS'] === true) {
					$column_sql = $escape_char.$column_item.$escape_char;
				} else {
					$column_sql = $column_item;
				}

				//Escape column domain
				if(isset($column[1]) === true) {
					$column_domain = $column[1];
					if($column_domain == true) {
						if(isset($GLOBALS['_PHALCON_DB_ESCAPE_IDENTIFIERS']) === true &&
							$GLOBALS['_PHALCON_DB_ESCAPE_IDENTIFIERS'] === true) {
							$column_domain_sql = $escape_char.$column_domain.$escape_char.$column_sql;
						} else {
							$column_domain_sql = $column_domain.'.'.$column_sql;
						}
					} else {
						$column_domain_sql = $column_sql;
					}
				} else {
					$column_domain_sql = $column_sql;
				}

				//Escape column alias
				if(isset($column[2]) === true) {
					$column_alias = $column[2];
					if($column_alias == true) {
						if(isset($GLOBALS['_PHALCON_DB_ESCAPE_IDENTIFIERS']) === true &&
							$GLOBALS['_PHALCON_DB_ESCAPE_IDENTIFIERS'] === true) {
							$column_alias_sql = $column_domain_sql.' AS '.$escape_char.$column_alias.$escape_char;
						} else {
							$column_alias_sql = $column_domain_sql.' AS '.$column_alias;
						}
					} else {
						$column_alias_sql = $column_domain_sql;
					}
				} else {
					$column_alias_sql = $column_domain_sql;
				}

				$selected_columns[] = $column_alias_sql;
			}

			$column_sql = implode(', ', $selected_columns);
		} else {
			$column_sql = $columns;
		}

		//Check and esacpe tables
		$tables = $definition['tables'];
		if(is_array($tables) === true) {
			$selected_tables = array();
			foreach($tables as $table) {
				$selected_tables[] = $this->getSqlTable($table, $escape_char);
			}

			$tables_sql = implode(', ', $selected_tables);
		} else {
			$tables_sql = $tables;
		}

		$sql = 'SELECT '.$column_sql.' FROM '.$tables_sql;

		//Check for joins
		if(isset($definition['joins']) === true) {
			$joins = $definition['joins'];
			foreach($joins as $join) {
				$type = $join['type'];
				$sql_table = $this->getSqlTable($join['source'], $escape_char);
				$selected_tables[] = $sql_table;
				$sql_join = ' '.$type.' JOIN '.$sql_table;

				//Check if the join has conditions
				if(isset($join['conditions']) === true) {
					$join_conditions_array = $join['conditions'];
					if(empty($join_conditions_array) === false) {
						$join_expressions = array();
						foreach($join_conditions_array as $join_condition) {
							$join_expressions[] = $this->getSqlExpression($join_condition, $escape_char);
						}

						$sql_join .= ' ON '.implode(' AND ', $join_expressions).' ';
					}
				}

				$sql .= $sql_join;
			}
		}

		//Check for a WHERE clause
		if(isset($definition['where']) === true) {
			$where_conditions = $definition['where'];
			if(is_array($where_conditions) === true) {
				$sql .= ' WHERE '.$this->getSqlExpression($where_conditions, $escape_char);
			} else {
				$sql .= ' WHERE '.$where_conditions;
			}
		}

		//Check for a GROUP clause
		if(isset($definition['group']) === true) {
			$group_items = array();
			$group_fields = $definition['group'];

			foreach($group_fields as $group_field) {
				$group_items[] = $this->getSqlExpression($group_field, $escape_char);
			}

			$sql .= ' GROUP BY '.implode(', ', $group_items);

			//Check for a HAVING clause
			if(isset($definition['having']) === true) {
				$sql .= ' HAVING '.$this->getSqlExpression($definition['having'], $escape_char);
			}
		}

		//Check for a ORDER clause
		if(isset($definition['order']) === true) {
			$order_fields = $definition['order'];
			$order_items = array();

			foreach($order_fields as $order_item) {
				$order_sql_item = $this->getSqlExpression($order_item[0], $escape_char);

				//In the numeric 1 position could be a ASC/DESC clause
				if(isset($order_item[1]) === true) {
					$order_sql_item_type = $order_sql_item.' '.$order_item[1];
				} else {
					$order_sql_item_type = $order_sql_item;
				}

				$order_items[] = $order_sql_item;
			}

			$sql .= ' ORDER BY '.implode(', ', $order_items);
		}

		//Check for a LIMIT condition
		if(isset($definition['limit']) === true) {
			$limit_value = $definition['limit'];
			if(is_array($limit_value) === true) {
				$number = $limit_value['number'];

				//Check for a OFFSET condition
				if(isset($limit_value['offset']) === true) {
					$sql .= ' LIMIT '.$number.' OFFSET '.$limit_value['offset'];
				} else {
					$sql .= ' LIMIT '.$number;
				}
			} else {
				$sql .= ' LIMIT '.$limit_value;
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