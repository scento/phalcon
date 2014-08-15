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

		$escapeIdentifiers = (isset($GLOBALS['_PHALCON_DB_ESCAPE_IDENTIFIERS']) === true &&
			$GLOBALS['_PHALCON_DB_ESCAPE_IDENTIFIERS'] === true ? true : false);

		if(is_array($table) === true) {
			//The index '0' is the table name
			if($escapeIdentifiers === true) {
				$sqlTable = $escapeChar.$table[0].$escapeChar;
			} else {
				$sqlTable = $table[0];
			}

			//The index '1' is the schema name
			if(is_null($table[1]) === false) {
				if($escapeIdentifiers === true) {
					$sqlSchema = $escapeChar.$table[1].$escapeChar.'.'.$sqlTable;
				} else {
					$sqlSchema = $table[1].'.'.$sqlTable;
				}
			} else {
				$sqlSchema = $sqlTable;
			}

			//The index '2' is the table alias
			if(isset($table[2]) === true) {
				if($escapeIdentifiers === true) {
					$sqlTableAlias = $sqlSchema.' '.$escapeChar.$table[2].$escapeChar;
				} else {
					$sqlTableAlias = $sqlSchema.' '.$table[2];
				}
			} else {
				$sqlTableAlias = $sqlSchema;
			}

			return $sqlTableAlias;
		} elseif(is_string($table) === true) {
			if($escapeIdentifiers === true) {
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

		$escapeIdentifiers = (isset($GLOBALS['_PHALCON_DB_ESCAPE_IDENTIFIERS']) === true &&
			$GLOBALS['_PHALCON_DB_ESCAPE_IDENTIFIERS'] === true ? true : false);

		if($escapeIdentifiers === true) {
			$escapeChar = $this->_escapeChar;
		} else {
			$escapeChar = null;
		}

		if(is_array($definition['columns']) === true) {
			$selectedColumns = array();
			foreach($definition['columns'] as $column) {

				//Escape column name
				$columnItem = $column[0];
				if(is_array($columnItem) === true) {
					$columnSql = $this->getSqlExpression($columnItem, $escapeChar);
				} else {
					if($columnItem === '*') {
						$columnSql = $columnItem;
					} else {
						if($escapeIdentifiers === true) {
							$columnSql = $escapeChar.$columnItem.$esacpeChar;
						} else {
							$columnSql = $columnItem;
						}
					}
				}

				//Escape column domain
				if(isset($column[1]) === true) {
					$columnDomain = $column[1];
					if($columnDomain == true) {
						if($escapeIdentifiers === true) {
							$columnDomainSql = $escapeChar.$columnDomain.$escapeChar.'.'.$columnSql;
						} else {
							$columnDomainSql = $columnDomain.'.'.$columnSql;
						}
					} else {
						$columnDomainSql = $columnSql;
					}
				} else {
					$columnDomainSql = $columnSql;
				}

				//Esacpe column alias
				if(isset($column[2]) === true) {
					$columnAlias = $column[2];
					if($columnAlias == true) {
						if($escapeIdentifiers === true) {
							$columnAliasSql = $columnDomainSql.' '.$escapeChar.$columnAlias.$escapeChar;
						} else {
							$columnAliasSql = $columnDomainSql.' '.$columnAlias;
						}
					} else {
						$columnAliasSql = $columnDomainSql;
					}
				} else {
					$columnAliasSql = $columnDomainSql;
				}

				$selectedColumns[] = $columnAliasSql;
			}

			$columnsSql = implode(', ', $selectedColumns);
		} else { //@note better check for string and add exception fallback
			$columnsSql = $columns;
		}

		//Check and esacpe tables
		if(is_array($definition['tables']) === true) {
			$selectedTables = array();

			foreach($definition['tables'] as $table) {
				$selectedTables[] = $this->getSqlTable($table, $escapeChar);
			}

			$tablesSql = implode(', ', $selectedTables);
		} else { //@note better check for string and add exception
			$tablesSql = $tables;
		}

		$sql = 'SELECT '.$columnsSql.' FROM '.$tablesSql;

		//Check for joins
		if(isset($definition['joins']) === true) {
			foreach($definition['joins'] as $join) {
				$sqlJoin = ' '.$join['type'].' JOIN '.$this->getSqlTable($join['source'], $escapeChar);

				//Check if the join has conditions
				if(isset($join['conditions']) === true) {
					$joinConditionsArray = $join['conditions'];
					if(count($joinConditionsArray) > 0) {
						$joinExpressions = array();
						foreach($joinConditionsArray as $joinCondition) {
							$joinExpression = $this->getSqlExpression($joinCondition, $escapeChar);
							$joinExpressions[] = $joinExpression;
						}

						$joinConditions = implode(' AND ', $joinExpressions);
						$sqlJoin .= ' ON '.$joinConditions;
					}
				}

				$sql .= $sqlJoin;
			}
		}

		//Check for a WHERE clause
		if(isset($definition['where']) === true) {
			if(is_array($definition['where']) === true) {
				$sql .= ' WHERE '.$this->getSqlExpression($definition['where'], $escapeChar);
			} else { //@note better check for string and add exception fallback
				$sql .= ' WHERE '.$definition['where'];
			}
		}

		//Check for a GROUP clause
		if(isset($definition['group']) === true) {
			$groupItems = array();
			foreach($definition['group'] as $groupField) {
				$groupItems[] = $this->getSqlExpression($groupField, $esacpeChar);
			}

			$groupSql = implode(', ', $gorupItems);
			$sql .= ' GROUP BY '.$groupSql;

			//Check for a HAVING clause
			if(isset($definition['having']) === true) {
				$sql .= ' HAVING '.$this->getSqlExpression($definition['having'], $escapeChar);
			}
		}

		//Check for a ORDER clause
		if(isset($definition['order']) === true) {
			$orderItems = aray();
			foreach($definition['order'] as $orderItem) {
				$orderSqlItem = $this->getSqlExpression($orderItem[0], $escapeChar);

				//In the numeric position 1 could be a ASC/DESC clause
				if(isset($orderItem[1]) === true) {
					$orderSqlItemType = $oderSqlItem.' '.$orderItem[1];
				} else {
					$orderSqlItemType = $oderSqlItem;
				}

				$orderItems[] = $orderSqlItemType;
			}

			$sql .= ' ORDER BY '.implode(', ', $orderItems);
		}

		/*
		* Oracle does not implement the LIMIT clause as some RDBMS do.
		* We have to simulate it with subqueries and ROWNUM.
		* Unfortunately because we use the column wildard "*",
		* this puts an extra column into the query result set.
		*/
		if(isset($definition['limit']) === true) {
			$limitValue = $definition['limit'];
			if(is_array($limitValue) === true) {
				$number = $limitValue['number'];
				$offset = (isset($limitValue['offset']) === true ? $limitValue['offset'] : 0);

				$sql = "SELECT Z2.* FROM (SELECT Z1.*, ROWNUM DB_ROWNUM FROM ( ".$sql." ) Z1 ) Z2 WHERE Z2.DB_ROWNUM BETWEEN ".($offset + 1)." AND ".($offset + $number);
			} else {
				$sql = "SELECT Z2.* FROM (SELECT Z1.*, ROWNUM DB_ROWNUM FROM ( ".$sql." ) Z1 ) Z2 WHERE Z2.DB_ROWNUM BETWEEN 1 AND ".(int)$limitValue;
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