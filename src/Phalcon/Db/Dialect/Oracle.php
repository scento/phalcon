<?php
/**
 * Oracle
 *
 * @author Andres Gutierrez <andres@phalconphp.com>
 * @author Eduar Carvajal <eduar@phalconphp.com>
 * @author Wenzel Pünter <wenzel@phelix.me>
 * @version 1.2.6
 * @package Phalcon
*/
namespace Phalcon\Db\Dialect;

use \Phalcon\Db\Dialect,
	\Phalcon\Db\DialectInterface,
	\Phalcon\Db\Exception,
	\Phalcon\Db\ColumnInterface;

/**
 * Phalcon\Db\Dialect\Oracle
 *
 * Generates database specific SQL for the Oracle RBDM
 * 
 * @see https://github.com/phalcon/cphalcon/blob/1.2.6/ext/db/dialect/oracle.c
 */
class Oracle extends Dialect implements DialectInterface
{
	/**
	 * Escape Char
	 * 
	 * @var string
	 * @access protected
	*/
	protected $_escapeChar = '';

	/**
	 * Gets the column name in Oracle
	 *
	 * @param \Phalcon\Db\ColumnInterface $column
	 * @return string
	 * @throws Exception
	 */
	public function getColumnDefinition($column)
	{
		if(is_object($column) === false ||
			$column instanceof ColumnInterface === false) {
			throw new Exception('Column definition must be an object compatbiel with Phalcon\\Db\\ColumnInterface');
		}

		switch((int)$column->getType()) {
			case 0:
				return 'INTEGER';
			case 1:
				return 'DATE';
			case 2:
				return 'VARCHAR2('.$column->getSize().')';
			case 3:
				return 'NUMBER('.$column->getSize().','.$column->getScale().')';
			case 4:
				return 'TIMESTAMP';
			case 5:
				return 'CHAR('.$column->getSize().')';
			case 6:
				return 'TEXT';
			case 7:
				return 'FLOAT('.$column->getSize().','.$column->getScale().')';
			case 8:
				return 'TINYINT(1)';
			default:
				throw new Exception('Unrecognized Oracle data type');
		}
	}

	/**
	 * Generates SQL to add a column to a table
	 *
	 * @param string $tableName
	 * @param string $schemaName
	 * @param \Phalcon\Db\ColumnInterface $column
	 * @return string
	 * @throws Exception
	 */
	public function addColumn($tableName, $schemaName, $column)
	{
		throw new Exception('Not implemented yet');
	}

	/**
	 * Generates SQL to modify a column in a table
	 *
	 * @param string $tableName
	 * @param string $schemaName
	 * @param \Phalcon\Db\ColumnInterface $column
	 * @return string
	 * @throws Exception
	 */
	public function modifyColumn($tableName, $schemaName, $column)
	{
		throw new Exception('Not implemented yet');
	}

	/**
	 * Generates SQL to delete a column from a table
	 *
	 * @param string $tableName
	 * @param string $schemaName
	 * @param string $columnName
	 * @return 	string
	 * @throws Exception
	 */
	public function dropColumn($tableName, $schemaName, $columnName)
	{
		throw new Exception('Not implemented yet');
	}

	/**
	 * Generates SQL to add an index to a table
	 *
	 * @param string $tableName
	 * @param string $schemaName
	 * @param \Phalcon\Db\Index $index
	 * @return string
	 * @throws Exception
	 */
	public function addIndex($tableName, $schemaName, $index)
	{
		throw new Exception('Not implemented yet');
	}

	/**
	 * Generates SQL to delete an index from a table
	 *
	 * @param string $tableName
	 * @param string $schemaName
	 * @param string $indexName
	 * @return string
	 * @throws Exception
	 */
	public function dropIndex($tableName, $schemaName, $indexName)
	{
		throw new Exception('Not implemented yet');
	}

	/**
	 * Generates SQL to add the primary key to a table
	 *
	 * @param string $tableName
	 * @param string $schemaName
	 * @param \Phalcon\Db\Index $index
	 * @return string
	 * @throws Exception
	 */
	public function addPrimaryKey($tableName, $schemaName, $index)
	{
		throw new Exception('Not implemented yet');
	}

	/**
	 * Generates SQL to delete primary key from a table
	 *
	 * @param string $tableName
	 * @param string $schemaName
	 * @return string
	 * @throws Exception
	 */
	public function dropPrimaryKey($tableName, $schemaName)
	{
		throw new Exception('Not implemented yet');
	}

	/**
	 * Generates SQL to add an index to a table
	 *
	 * @param string $tableName
	 * @param string $schemaName
	 * @param \Phalcon\Db\ReferenceInterface $reference
	 * @return string
	 * @throws Exception
	 */
	public function addForeignKey($tableName, $schemaName, $reference)
	{
		throw new Exception('Not implemented yet');
	}

	/**
	 * Generates SQL to delete a foreign key from a table
	 *
	 * @param string $tableName
	 * @param string $schemaName
	 * @param string $referenceName
	 * @return string
	 * @throws Exception
	 */
	public function dropForeignKey($tableName, $schemaName, $referenceName)
	{
		throw new Exception('Not implemented yet');
	}

	/**
	 * Generates SQL to add the table creation options
	 *
	 * @param array $definition
	 * @return array
	 */
	protected function _getTableOptions($definition)
	{
		return array();
	}

	/**
	 * Generates SQL to create a table in PostgreSQL
	 *
	 * @param string $tableName
	 * @param string $schemaName
	 * @param array $definition
	 * @return 	string
	 * @throws Exception
	 */
	public function createTable($tableName, $schemaName, $definition)
	{
		throw new Exception('Not implemented yet');
	}

	/**
	 * Generates SQL to drop a table
	 *
	 * @param string $tableName
	 * @param string|null $schemaName
	 * @param boolean|null $ifExists
	 * @return boolean
	 * @throws Exception
	 */
	public function dropTable($tableName, $schemaName, $ifExists = null)
	{
		if(is_string($tableName) === false) {
			throw new Exception('Invalid parameter type.');
		}

		if(is_null($ifExists) === true) {
			$ifExists = true;
		} elseif(is_bool($ifExists) === false) {
			throw new Exception('Invalid parameter type.');
		}

		if(is_string($schemaName) === true &&
			$schemaName == true) {
			$table = $schemaName.'.'.$tableName;
		} else {
			$table = $tableName;
		}

		if($ifExists === true) {
			return 'DROP TABLE IF EXISTS '.$table;
		} else {
			return 'DROP TABLE '.$table;
		}
	}

	/**
	 * Generates SQL to create a view
	 *
	 * @param string $viewName
	 * @param array $definition
	 * @param string|null $schemaName
	 * @return string
	 * @throws Exception
	 */
	public function createView($viewName, $definition, $schemaName)
	{
		if(is_string($viewName) === false ||
			is_array($definition) === false) {
			throw new Exception('Invalid parameter type.');
		}

		if(isset($definition['sql']) === false) {
			throw new Exception("The index 'sql' is required in the definition array");
		}

		if(is_string($schemaName) === true &&
			$schemaName == true) {
			$view = $viewName.'.'.$schemaName;
		} else {
			$view = $viewName;
		}

		return 'CREATE VIEW '.$view.' AS '.$definition['sql'];
	}

	/**
	 * Generates SQL to drop a view
	 *
	 * @param string $viewName
	 * @param string|null $schemaName
	 * @param boolean|null $ifExists
	 * @return string
	 * @throws Exception
	 */
	public function dropView($viewName, $schemaName, $ifExists = null)
	{
		if(is_string($viewName) === false) {
			throw new Exception('Invalid parameter type.');
		}

		if(is_null($ifExists) === true) {
			$ifExists = true;
		} elseif(is_bool($ifExists) === false) {
			throw new Exception('Invalid parameter type.');
		}

		//@note this is the first time anything is escaped
		if(is_string($schemaName) === true &&
			$schemaName == true) {
			$view = '`'.$schemaName.'`.`'.$viewName.'`';
		} else {
			$view = '`'.$viewName.'`';
		}

		if($ifExists === true) {
			return 'DROP VIEW IF EXISTS '.$view;
		} else {
			return 'DROP VIEW '.$view;
		}
	}

	/**
	 * Generates SQL checking for the existence of a schema.table
	 *
	 *<code>
	 *	var_dump($dialect->tableExists("posts", "blog"));
	 *	var_dump($dialect->tableExists("posts"));
	 *</code>
	 *
	 * @param string $tableName
	 * @param string|null $schemaName
	 * @return string
	 * @throws Exception
	 */
	public function tableExists($tableName, $schemaName = null)
	{
		if(is_string($tableName) === false) {
			throw new Exception('Invalid parameter type.');
		}

		if(is_string($schemaName) === true &&
			$schemaName == true) {
			return "SELECT CASE WHEN COUNT(*) > 0 THEN 1 ELSE 0 END RET FROM ALL_TABLES WHERE TABLE_NAME='".$tableName."' AND OWNER = '".$schemaName."'";
		} else {
			return "SELECT CASE WHEN COUNT(*) > 0 THEN 1 ELSE 0 END RET FROM ALL_TABLES WHERE TABLE_NAME='".$tableName."'";
		}
	}

	/**
	 * Generates SQL checking for the existence of a schema.view
	 *
	 * @param string $viewName
	 * @param string|null $schemaName
	 * @return string
	 * @throws Exception
	 */
	public function viewExists($viewName, $schemaName = null)
	{
		if(is_string($viewName) === false) {
			throw new Exception('Invalid parameter type.');
		}

		if(is_string($schemaName) === true &&
			$schemaName == true) {
			return "SELECT CASE WHEN COUNT(*) > 0 THEN 1 ELSE 0 END RET FROM ALL_VIEWS WHERE VIEW_NAME='".$viewName."' AND OWNER='".$schemaName."'";
		} else {
			return "SELECT CASE WHEN COUNT(*) > 0 THEN 1 ELSE 0 END RET FROM ALL_VIEWS WHERE VIEW_NAME='".$viewName."'";
		}
	}

	/**
	 * Generates a SQL describing a table
	 *
	 *<code>
	 *	print_r($dialect->describeColumns("posts")); ?>
	 *</code>
	 *
	 * @param string $table
	 * @param string|null $schema
	 * @return string
	 * @throws Exception
	 */
	public function describeColumns($table, $schema = null)
	{
		if(is_string($table) === false) {
			throw new Exception('Invalid parameter type.');
		}

		if(is_string($schema) === true &&
			$schema == true) {
			return "SELECT TC.COLUMN_NAME, TC.DATA_TYPE, TC.DATA_LENGTH, TC.DATA_PRECISION, TC.DATA_SCALE, TC.NULLABLE, C.CONSTRAINT_TYPE, TC.DATA_DEFAULT, CC.POSITION FROM ALL_TAB_COLUMNS TC LEFT JOIN (ALL_CONS_COLUMNS CC JOIN ALL_CONSTRAINTS C ON (CC.CONSTRAINT_NAME = C.CONSTRAINT_NAME AND CC.TABLE_NAME = C.TABLE_NAME AND CC.OWNER = C.OWNER AND C.CONSTRAINT_TYPE = 'P')) ON TC.TABLE_NAME = CC.TABLE_NAME AND TC.COLUMN_NAME = CC.COLUMN_NAME WHERE TC.TABLE_NAME = '".$table."' AND TC.OWNER = '".$schema."' ORDER BY TC.COLUMN_ID";
		} else {
			return "SELECT TC.COLUMN_NAME, TC.DATA_TYPE, TC.DATA_LENGTH, TC.DATA_PRECISION, TC.DATA_SCALE, TC.NULLABLE, C.CONSTRAINT_TYPE, TC.DATA_DEFAULT, CC.POSITION FROM ALL_TAB_COLUMNS TC LEFT JOIN (ALL_CONS_COLUMNS CC JOIN ALL_CONSTRAINTS C ON (CC.CONSTRAINT_NAME = C.CONSTRAINT_NAME AND CC.TABLE_NAME = C.TABLE_NAME AND CC.OWNER = C.OWNER AND C.CONSTRAINT_TYPE = 'P')) ON TC.TABLE_NAME = CC.TABLE_NAME AND TC.COLUMN_NAME = CC.COLUMN_NAME WHERE TC.TABLE_NAME = '".$table."' ORDER BY TC.COLUMN_ID";
		}
	}

	/**
	 * List all tables on database
	 *
	 *<code>
	 *	print_r($dialect->listTables("blog")) ?>
	 *</code>
	 *
	 * @param string|null $schemaName
	 * @return string
	 */
	public function listTables($schemaName = null)
	{
		if(is_string($schemaName) === true &&
			$schemaName == true) {
			return "SELECT TABLE_NAME, OWNER FROM ALL_TABLES WHERE OWNER='".$schemaName."' ORDER BY OWNER, TABLE_NAME";
		} else {
			return "SELECT TABLE_NAME, OWNER FROM ALL_TABLES ORDER BY OWNER, TABLE_NAME "; //@note one should remove the additional whitespace at the end
		}
	}

	/**
	 * Generates the SQL to list all views of a schema or user
	 *
	 * @param string|null $schemaName
	 * @return string
	 */
	public function listViews($schemaName = null)
	{
		if(is_string($schemaName) === true &&
			$schemaName == true) {
			return "SELECT VIEW_NAME FROM ALL_VIEWS WHERE OWNER='".$schemaName."' ORDER BY VIEW_NAME";
		} else {
			return "SELECT VIEW_NAME FROM ALL_VIEWS VIEW_NAME";
		}
	}

	/**
	 * Generates SQL to query indexes on a table
	 *
	 * @param string $table
	 * @param string|null $schema
	 * @return string
	 * @throws Exception
	 */
	public function describeIndexes($table, $schema = null)
	{
		if(is_string($table) === false) {
			throw new Exception('Invalid parameter type.');
		}

		if(is_string($schemaName) === true &&
			$schemaName == true) {
			return "SELECT I.TABLE_NAME, 0 AS C0, I.INDEX_NAME, IC.COLUMN_POSITION, IC.COLUMN_NAME FROM ALL_INDEXES I JOIN ALL_IND_COLUMNS IC ON I.INDEX_NAME = IC.INDEX_NAME WHERE  I.TABLE_NAME = '".$table."' AND IC.INDEX_OWNER = '".$schema."'";
		} else {
			return "SELECT I.TABLE_NAME, 0 AS C0, I.INDEX_NAME, IC.COLUMN_POSITION, IC.COLUMN_NAME FROM ALL_INDEXES I JOIN ALL_IND_COLUMNS IC ON I.INDEX_NAME = IC.INDEX_NAME WHERE  I.TABLE_NAME = '".$table."'";
		}
	}

	/**
	 * Generates SQL to query foreign keys on a table
	 *
	 * @param string $table
	 * @param string|null $schema
	 * @return string
	 * @throws Exception
	 */
	public function describeReferences($table, $schema = null)
	{
		if(is_string($table) === false) {
			throw new Exception('Invalid parameter type.');
		}

		$sql = "SELECT AC.TABLE_NAME, CC.COLUMN_NAME, AC.CONSTRAINT_NAME, AC.R_OWNER, RCC.TABLE_NAME R_TABLE_NAME, RCC.COLUMN_NAME R_COLUMN_NAME FROM ALL_CONSTRAINTS AC JOIN ALL_CONS_COLUMNS CC ON AC.CONSTRAINT_NAME = CC.CONSTRAINT_NAME JOIN ALL_CONS_COLUMNS RCC ON AC.R_OWNER = RCC.OWNER AND AC.R_CONSTRAINT_NAME = RCC.CONSTRAINT_NAME WHERE AC.CONSTRAINT_TYPE='R' ";

		if(is_string($schema) === true &&
			$schema == true) {
			return $sql."AND AC.OWNER='".$schema."' AND AC.TABLE_NAME = '".$table."'";
		} else {
			return $sql."AND AC.TABLE_NAME = '".$table."'";
		}
	}

	/**
	 * Generates the SQL to describe the table creation options
	 *
	 * @param string $table
	 * @param string|null $schema
	 * @return string
	 */
	public function tableOptions($table, $schema = null)
	{
		return '';
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
			$schemaName = $this->_escapeChar;
		} elseif(is_string($escapeChar) === false) {
			throw new Exception('Invalid parameter type.');
		}

		$escape_identifiers = (isset($GLOBALS['_PHALCON_DB_ESCAPE_IDENTIFIERS']) === true &&
			$GLOBALS['_PHALCON_DB_ESCAPE_IDENTIFIERS'] === true ? true : false);

		if(is_array($table) === true) {
			//The index '0' is the table name
			if($escape_identifiers === true) {
				$sql_table = $escapeChar.$table[0].$escapeChar;
			} else {
				$sql_table = $table[0];
			}

			//The index '1' is the schema name
			if(is_null($table[1]) === false) {
				if($escape_identifiers === true) {
					$sql_schema = $escapeChar.$table[1].$escapeChar.'.'.$sql_table;
				} else {
					$sql_schema = $table[1].'.'.$sql_table;
				}
			} else {
				$sql_schema = $sql_table;
			}

			//The index '2' is the table alias
			if(isset($table[2]) === true) {
				if($escape_identifiers === true) {
					$sql_table_alias = $sql_schema.' '.$escapeChar.$table[2].$escapeChar;
				} else {
					$sql_table_alias = $sql_schema.' '.$table[2];
				}
			} else {
				$sql_table_alias = $sql_schema;
			}

			return $sql_table_alias;
		} elseif(is_string($table) === true) {
			if($escape_identifiers === true) {
				return $escapeChar.$table.$escapeChar;
			} else {
				return $table;
			}
		} else {
			throw new Exception('Invalid parameter type.');
		}
	}

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
			return $sqlQuery.' LIMIT '.(int)$number;
		}

		return $sqlQuery;
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

		$escape_identifiers = (isset($GLOBALS['_PHALCON_DB_ESCAPE_IDENTIFIERS']) === true &&
			$GLOBALS['_PHALCON_DB_ESCAPE_IDENTIFIERS'] === true ? true : false);

		if($escape_identifiers === true) {
			$esacpe_char = $this->_escapeChar;
		} else {
			$escape_char = null;
		}

		if(is_array($definition['columns']) === true) {
			$selected_columns = array();
			foreach($definition['columns'] as $column) {

				//Escape column name
				$column_item = $column[0];
				if(is_array($column_item) === true) {
					$column_sql = $this->getSqlExpression($column_item, $escape_char);
				} else {
					if($column_item === '*') {
						$column_sql = $column_item;
					} else {
						if($escape_identifiers === true) {
							$column_sql = $escape_char.$column_item.$esacpe_char;
						} else {
							$column_sql = $column_item;
						}
					}
				}

				//Escape column domain
				if(isset($column[1]) === true) {
					$column_domain = $column[1];
					if($column_domain == true) {
						if($escape_identifiers === true) {
							$column_domain_sql = $escape_char.$column_domain.$escape_char.'.'.$column_sql;
						} else {
							$column_domain_sql = $column_domain.'.'.$column_sql;
						}
					} else {
						$column_domain_sql = $column_sql;
					}
				} else {
					$column_domain_sql = $column_sql;
				}

				//Esacpe column alias
				if(isset($column[2]) === true) {
					$column_alias = $column[2];
					if($column_alias == true) {
						if($escape_identifiers === true) {
							$column_alias_sql = $column_domain_sql.' '.$esacpe_char.$column_alias.$esacpe_char;
						} else {
							$column_alias_sql = $column_domain_sql.' '.$column_alias;
						}
					} else {
						$column_alias_sql = $column_domain_sql;
					}
				} else {
					$column_alias_sql = $column_domain_sql;
				}

				$selected_columns[] = $column_alias_sql;
			}

			$columns_sql = implode(', ', $selected_columns);
		} else { //@note better check for string and add exception fallback
			$columns_sql = $columns;
		}

		//Check and esacpe tables
		if(is_array($definition['tables']) === true) {
			$selected_tables = array();

			foreach($definition['tables'] as $table) {
				$selected_tables[] = $this->getSqlTable($table, $escape_char);
			}

			$tables_sql = implode(', ', $selected_tables);
		} else { //@note better check for string and add exception
			$tables_sql = $tables;
		}

		$sql = 'SELECT '.$columns_sql.' FROM '.$tables_sql;

		//Check for joins
		if(isset($definition['joins']) === true) {
			foreach($definition['joins'] as $join) {
				$sql_join = ' '.$join['type'].' JOIN '.$this->getSqlTable($join['source'], $escape_char);

				//Check if the join has conditions
				if(isset($join['conditions']) === true) {
					$join_conditions_array = $join['conditions'];
					if(count($join_conditions_array) > 0) {
						$join_expressions = array();
						foreach($join_conditions_array as $join_condition) {
							$join_expression = $this->getSqlExpression($join_condition, $escape_char);
							$join_expressions[] = $join_expression;
						}

						$join_conditions = implode(' AND ', $join_expressions);
						$sql_join .= ' ON '.$join_conditions;
					}
				}

				$sql .= $sql_join;
			}
		}

		//Check for a WHERE clause
		if(isset($definition['where']) === true) {
			if(is_array($definition['where']) === true) {
				$sql .= ' WHERE '.$this->getSqlExpression($definition['where'], $escape_char);
			} else { //@note better check for string and add exception fallback
				$sql .= ' WHERE '.$definition['where'];
			}
		}

		//Check for a GROUP clause
		if(isset($definition['group']) === true) {
			$group_items = array();
			foreach($definition['group'] as $group_field) {
				$group_items[] = $this->getSqlExpression($group_field, $esacpe_char);
			}

			$group_sql = implode(', ', $gorup_items);
			$sql .= ' GROUP BY '.$group_sql;

			//Check for a HAVING clause
			if(isset($definition['having']) === true) {
				$sql .= ' HAVING '.$this->getSqlExpression($definition['having'], $escape_char);
			}
		}

		//Check for a ORDER clause
		if(isset($definition['order']) === true) {
			$order_items = aray();
			foreach($definition['order'] as $order_item) {
				$order_sql_item = $this->getSqlExpression($order_item[0], $escape_char);

				//In the numeric position 1 could be a ASC/DESC clause
				if(isset($order_item[1]) === true) {
					$order_sql_item_type = $oder_sql_item.' '.$order_item[1];
				} else {
					$oder_sql_item_type = $order_sql_item;
				}

				$order_items[] = $oder_sql_item_type;
			}

			$sql .= ' ORDER BY '.implode(', ', $order_items);
		}

		/*
		* Oracle does not implement the LIMIT clause as some RDBMS do.
		* We have to simulate it with subqueries and ROWNUM.
		* Unfortunately because we use the column wildard "*",
		* this puts an extra column into the query result set.
		*/
		if(isset($definition['limit']) === true) {
			$limit_value = $definition['limit'];
			if(is_array($limit_value) === true) {
				$number = $limit_value['number'];
				$offset = (isset($limit_value['offset']) === true ? $limit_value['offset'] : 0);

				$sql = "SELECT Z2.* FROM (SELECT Z1.*, ROWNUM DB_ROWNUM FROM ( ".$sql." ) Z1 ) Z2 WHERE Z2.DB_ROWNUM BETWEEN ".($offset + 1)." AND ".($offset + $number);
			} else {
				$sql = "SELECT Z2.* FROM (SELECT Z1.*, ROWNUM DB_ROWNUM FROM ( ".$sql." ) Z1 ) Z2 WHERE Z2.DB_ROWNUM BETWEEN 1 AND ".(int)$limit_value;
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
		return false;
	}

	/**
	 * Checks whether the platform supports releasing savepoints.
	 *
	 * @return boolean
	 */
	public function supportsReleaseSavepoints()
	{
		return false;
	}
}