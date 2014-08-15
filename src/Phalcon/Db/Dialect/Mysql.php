<?php
/**
 * Mysql
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
	\Phalcon\Db\ColumnInterface,
	\Phalcon\Db\IndexInterface,
	\Phalcon\Db\ReferenceInterface,
	\Phalcon\Db\Exception;

/**
 * Phalcon\Db\Dialect\Mysql
 *
 * Generates database specific SQL for the MySQL RBDM
 * 
 * @see https://github.com/phalcon/cphalcon/blob/1.2.6/ext/db/dialect/mysql.c
 */
class Mysql extends Dialect implements DialectInterface
{
	/**
	 * Escape Char
	 * 
	 * @var string
	 * @access protected
	*/
	protected $_escapeChar = '`';

	/**
	 * Gets the column name in MySQL
	 *
	 * @param \Phalcon\Db\ColumnInterface $column
	 * @return string
	 * @throws Exception
	 */
	public function getColumnDefinition($column)
	{
		if(is_object($column) === false ||
			$column instanceof ColumnInterface === false) {
			throw new Exception('Column definition must be an object compatible with Phalcon\\Db\\ColumnInterface');
		}

		$size = $column->getSize();

		switch((int)$column->getType()) {
			case 0:
				return 'INT('.$size.')'.($column->isUnsigned() === true ? ' UNSIGNED' : '');
				break;
			case 1:
				return 'DATE';
				break;
			case 2:
				return 'VARCHAR('.$size.')';
				break;
			case 3:
				return 'DECIMAL('.$size.','.$column->getScale().')'.
				($column->isUnsigned() === true ? ' UNSIGNED' : '');
				break;
			case 4:
				return 'DATETIME';
				break;
			case 5:
				return 'CHAR('.$size.')';
				break;
			case 6:
			 	return 'TEXT';
			 	break;
			 case 7:
			 	$columnSql = 'FLOAT';

			 	$scale = $column->getScale();
			 	if($size == true) {
			 		$columnSql .= '('.$size;
			 		if($scale == true) {
			 			$columnSql .= ','.$scale.')';
			 		} else {
			 			$columnSql .= ')';
			 		}
			 	}

			 	if($column->isUnsigned() === true) {
			 		$columnSql .= ' UNSIGNED';
			 	}
			 	return $columnSql;
			 	break;
			 case 8:
			 	return 'TINYINT(1)';
			 	break;
			 default:
			 	throw new Exception('Unrecognized MySQL data type');
			 	break;
		}
	}

	/**
	 * Generates SQL to add a column to a table
	 *
	 * @param string $tableName
	 * @param string|null $schemaName
	 * @param \Phalcon\Db\ColumnInterface $column
	 * @return string
	 * @throws Exception
	 */
	public function addColumn($tableName, $schemaName, $column)
	{
		if(is_string($tableName) === false) {
			throw new Exception('Invalid parameter type.');
		}

		if(is_object($column) === false ||
			$column instanceof ColumnInterface === false) {
			throw new Exception('Column parameter must be an instance of Phalcon\\Db\\Column');
		}

		if(is_string($schemaName) === true &&
			$schemaName == true) {
			$sql = 'ALTER TABLE `'.$schemaName.'`.`'.$tableName.'` ADD ';
		} else {
			$sql = 'ALTER TABLE `'.$tableName.'` ADD ';
		}

		$sql .= '`'.$column->getName().'` '.$this->getColumnDefinition($column);

		if($column->isNotNull() === true) {
			$sql .= ' NOT NULL';
		}

		if($column->isFirst() === true) {
			$sql .= ' FIRST';
		} else {
			$afterPosition = $column->getAfterPosition();
			if($afterPosition == true) {
				$sql .= ' AFTER '.$afterPosition;
			}
		}

		return $sql;
	}

	/**
	 * Generates SQL to modify a column in a table
	 *
	 * @param string $tableName
	 * @param string|null $schemaName
	 * @param \Phalcon\Db\ColumnInterface $column
	 * @return string
	 * @throws Exception
	 */
	public function modifyColumn($tableName, $schemaName, $column)
	{
		if(is_string($tableName) === false) {
			throw new Exception('Invalid parameter type.');
		}

		if(is_object($column) === false ||
			$column instanceof ColumnInterface === false) {
			throw new Exception('Column parameter must be an instance of Phalcon\\Db\\Column');
		}

		if(is_string($schemaName) === true &&
			$schemaName == true) {
			$sql = 'ALTER TABLE `'.$schemaName.'`.`'.$tableName.'` MODIFY ';
		} else {
			$sql = 'ALTER TABLE `'.$tableName.'` MODIFY ';
		}

		$sql .= '`'.$column->getName().'` '.$this->getColumnDefinition($column);

		if($column->isNotNull() === true) {
			$sql .= ' NOT NULL';
		}

		return $sql;
	}

	/**
	 * Generates SQL to delete a column from a table
	 *
	 * @param string $tableName
	 * @param string|null $schemaName
	 * @param string $columnName
	 * @return string
	 * @throws Exception
	 */
	public function dropColumn($tableName, $schemaName, $columnName)
	{
		if(is_string($tableName) === false ||
			is_string($columnName) === false) {
			throw new Exception('Invalid parameter type.');
		}

		if(is_string($schemaName) === true &&
			$schemaName == true) {
			$sql .= 'ALTER TABLE `'.$schemaName.'`.`'.$tableName.'` DROP COLUMN ';
		} else {
			$sql = 'ALTER TABLE `'.$tableName.'` DROP COLUMN ';
		}

		return $sql.'`'.$columnName.'`';
	}

	/**
	 * Generates SQL to add an index to a table
	 *
	 * @param string $tableName
	 * @param string|null $schemaName
	 * @param \Phalcon\Db\IndexInterface $index
	 * @return string
	 * @throws Exception
	 */
	public function addIndex($tableName, $schemaName, $index)
	{
		if(is_string($tableName) === false) {
			throw new Exception('Invalid parameter type.');
		}

		if(is_object($index) === false ||
			$index instanceof IndexInterface === false) {
			throw new Exception('Index parameter must be an instance of Phalcon\\Db\\Index');
		}

		if(is_string($schemaName) === true &&
			$schemaName == true) {
			$sql = 'ALTER TABLE `'.$schemaName.'`.`'.$tableName.'` ADD INDEX ';
		} else {
			$sql = 'ALTER TABLE `'.$tableName.'` ADD INDEX ';
		}

		$columns = $index->getColumns();

		return $sql.'`'.$index->getName().'` ('.$this->getColumnList($columns).')';
	}

	/**
	 * Generates SQL to delete an index from a table
	 *
	 * @param string $tableName
	 * @param string|null $schemaName
	 * @param string $indexName
	 * @return string
	 * @throws Exception
	 */
	public function dropIndex($tableName, $schemaName, $indexName)
	{
		if(is_string($tableName) === false ||
			is_string($indexName) === false) {
			throw new Exception('Invalid parameter type.');
		}

		if(is_string($schemaName) === true &&
			$schemaName == true) {
			$sql = 'ALTER TABLE `'.$schemaName.'`.`'.$tableName.'` DROP INDEX ';
		} else {
			$sql = 'ALTER TABLE `'.$tableName.'` DROP INDEX ';
		}

		return $sql.'`'.$indexName.'`';
	}

	/**
	 * Generates SQL to add the primary key to a table
	 *
	 * @param string $tableName
	 * @param string|null $schemaName
	 * @param \Phalcon\Db\IndexInterface $index
	 * @return string
	 * @throws Exception
	 */
	public function addPrimaryKey($tableName, $schemaName, $index)
	{
		if(is_string($tableName) === false) {
			throw new Exception('Invalid parameter type.');
		}

		if(is_object($index) === false ||
			$index instanceof IndexInterface === false) {
			throw new Exception('Index parameter must be an instance of Phalcon\\Db\\Index');
		}

		if(is_string($schemaName) === true &&
			$schemaName == true) {
			$sql = 'ALTER TABLE `'.$schemaName.'`.`'.$tableName.'` ADD PRIMARY KEY ';
		} else {
			$sql = 'ALTER TABLE `'.$tableName.'``ADD PRIMARY KEY ';
		}

		return $sql.'('.$this->getColumnList($index->getColumns()).')';
	}

	/**
	 * Generates SQL to delete primary key from a table
	 *
	 * @param string $tableName
	 * @param string|null $schemaName
	 * @return string
	 * @throws Exception
	 */
	public function dropPrimaryKey($tableName, $schemaName)
	{
		if(is_string($tableName) === false) {
			throw new Exception('Invalid parameter type.');
		}

		if(is_string($schemaName) === true &&
			$schemaName == true) {
			return 'ALTER TABLE `'.$schemaName.'`.`'.$tableName.'` DROP PRIMARY KEY';
		} elseif(is_null($schemaName) === true) {
			return 'ALTER TABLE `'.$tableName.'` DROP PRIMARY KEY';
		} else {
			throw new Exception('Invalid parameter type.');
		}
	}

	/**
	 * Generates SQL to add an index to a table
	 *
	 * @param string $tableName
	 * @param string|null $schemaName
	 * @param \Phalcon\Db\ReferenceInterface $reference
	 * @return string
	 * @throws Exception
	 */
	public function addForeignKey($tableName, $schemaName, $reference)
	{
		if(is_string($tableName) === false) {
			throw new Exception('Invalid parameter type.');
		}

		if(is_object($reference) === false ||
			$reference instanceof ReferenceInterface === false) {
			throw new Exception('Reference parameter must be an instance of Phalcon\\Db\\Reference');
		}

		if(is_string($schemaName) === true &&
			$schemaName == true) {
			$sql = 'ALTER TABLE `'.$schemaName.'`.`'.$tableName.'` ';
		} else {
			$sql = 'ALTER TABLE `'.$tableName.'` ';
		}

		$sql .= 'ADD CONSTRAINT `'.$reference->getName().'` FOREIGN KEY ('.
		$this->getColumnList($reference->getColumns()).') REFERENCES ';

		//Add the schema
		$referencedSchema = $reference->getReferencedSchema();
		if(is_string($referencedSchema) === true) {
			$sql .= '`'.$referencedSchema.'`.';
		}

		return $sql.'`'.$reference->getReferencedTable().'`('.
			$this->getColumnList($reference->getReferencedColumns()).')';
	}

	/**
	 * Generates SQL to delete a foreign key from a table
	 *
	 * @param string $tableName
	 * @param string|null $schemaName
	 * @param string $referenceName
	 * @return string
	 * @throws Exception
	 */
	public function dropForeignKey($tableName, $schemaName, $referenceName)
	{
		if(is_string($tableName) === false ||
			is_string($referenceName) === false) {
			throw new Exception('Invalid parameter type.');
		}

		if(is_string($schemaName) === true &&
			$schemaName == true) {
			$sql = 'ALTER TABLE `'.$schemaName.'`.`'.$tableName.'` DROP FOREIGN KEY ';
		} else {
			$sql = 'ALTER TABLE `'.$tableName.'` DROP FOREIGN KEY ';
		}

		return $sql.'`'.$referenceName.'`';
	}

	/**
	 * Generates SQL to add the table creation options
	 *
	 * @param array $definition
	 * @return string|null
	 * @throws Exception
	 */
	protected function _getTableOptions($definition)
	{
		if(is_array($definition) === false) {
			throw new Exception('Invalid parameter type.');
		}

		if(isset($definition['options']) === true) {
			$tableOptions = array();
			$options = $definition['options'];

			//Check if there is an ENGINE option
			if(isset($options['ENGINE']) === true &&
				$options['ENGINE'] == true) {
				$tableOptions[] = 'ENGINE='.$options['ENGINE'];
			}

			//Check if there is a n AUTO_INCREMENT option
			if(isset($options['AUTO_INCREMENT']) === true &&
				$options['AUTO_INCREMENT'] == true) {
				$tableOptions[] = 'AUTO_INCREMENT='.$options['AUTO_INCREMENT'];
			}

			//Check if there is an TABLE_COLLATION option
			if(isset($options['TABLE_COLLATION']) === true &&
				$options['TABLE_COLLATION'] == true) {
				$collationParts = explode('_', $options['TABLE_COLLATION']);
				$tableOptions[] = 'DEFAULT CHARSET='.$collationParts[0];
				$tableOptions[] = 'COLLATE='.$options['TABLE_COLLATION'];
			}

			if(count($tableOptions) > 0) {
				return implode(' ', $tableOptions);
			}
		}
	}

	/**
	 * Generates SQL to create a table in MySQL
	 *
	 * @param string $tableName
	 * @param string|null $schemaName
	 * @param array $definition
	 * @return string
	 * @throws Exception
	 */
	public function createTable($tableName, $schemaName, $definition)
	{
		if(is_string($tableName) === false ||
			is_array($definition) === false) {
			throw new Exception('Invalid parameter type.');
		}

		if(isset($definition['columns']) === false) {
			throw new Exception("The index 'columns' is required in the definition array");
		}

		if(is_string($schemaName) === true &&
			$schemaName == true) {
			$table = '`'.$schemaName.'`.`'.$tableName.'`';
		} else {
			$table = '`'.$tableName.'`';
		}

		$temporary = false;
		if(isset($definition['options']) === true &&
			isset($definition['options']['temporary']) === true) {
			$temporary = (bool)$definition['options']['temporary'];
		}

		//Create a temporary or normal table
		if($temporary === true) {
			$sql = 'CREATE TEMPORARY TABLE '.$table." (\n\t";
		} else {
			$sql = 'CREATE TABLE '.$table." (\n\t";
		}

		$createLines = array();

		foreach($definition['columns'] as $column) {
			$columnLine = '`'.$column->getName().'` '.$this->getColumnDefinition($column);

			//Add a NOT NULL clause
			if($column->isNotNull() === true) {
				$columnLine .= ' NOT NULL';
			}

			//Add an AUTO_INCREMENT clause
			if($column->isAutoIncrement() === true) {
				$columnLine .= ' AUTO_INCREMENT';
			}

			//Mark the column as primary key
			if($column->isPrimary() === true) {
				$columnLine .= ' PRIMARY KEY';
			}

			$createLines[] = $columnLine;
		}

		//Create related indexes
		if(isset($definition['indexes']) === true) {
			foreach($definition['indexes'] as $index) {
				$indexName = $index->getName();
				if($indexName === 'PRIMARY') {
					$createLines[] = 'PRIMARY KEY ('.$this->getColumnList($index->getColumns()).')';
				} else {
					$createLines[] = 'KEY `'.$indexName.'` ('.$this->getColumnList($index->getColumns()).')';
				}
			}
		}

		//Create related references
		if(isset($definition['references']) === true) {
			foreach($definition['references'] as $reference) {
				$name = $reference->getName();
				//$columns = $reference->getColumns();
				//$columnList = $this->getColumnList($columns);
				$referencedTable = $reference->getReferencedTable();
				$referencedColumns = $reference->getReferencedColumns();
				$columnList = $this->getColumnList($referencedColumns);

				$constraintSql = 'CONSTRAINT `'.$name.'` FOREIGN KEY ('.$columnList.')';
				$createLines[] = $constraintSql.' REFERENCES `'.$referencedTable.'`('.$columnList.')';
				//@note there should be two different kinds of $columnList
			}
		}

		$sql .= implode(",\n\t", $createLines)."\n)";
		if(isset($definition['options']) === true) {
			$sql .= $this->_getTableOptions($definition);
		}

		return $sql;
	}

	/**
	 * Generates SQL to drop a table
	 *
	 * @param  string $tableName
	 * @param  string|null $schemaName
	 * @param  boolean|null $ifExists
	 * @return string
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
			$table = '`'.$schemaName.'`.`'.$tableName.'`';
		} else {
			$table = '`'.$tableName.'`';
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

		if(isset($definition['sql']) === true) {
			throw new Exception("The index 'sql' is required in the definition array");
		}

		if(is_string($schemaName) === true &&
			$schemaName == true) {
			$view = '`'.$schemaName.'`.`'.$viewName.'`';
		} else {
			$view = '`'.$viewName.'`';
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

		if(is_string($schemaName) === true &&
			$schemaName == true) {
			$view = $schemaName.'.'.$viewName; //@note no escape
		} else {
			$view = $viewName;
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
	 * <code>
	 * echo $dialect->tableExists("posts", "blog");
	 * echo $dialect->tableExists("posts");
	 * </code>
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
			$schemaName === true) {
			return "SELECT IF(COUNT(*)>0, 1 , 0) FROM `INFORMATION_SCHEMA`.`TABLES` WHERE `TABLE_NAME`= '".$tableName."' AND `TABLE_SCHEMA`='".$schemaName."'";
		}

		return "SELECT IF(COUNT(*)>0, 1 , 0) FROM `INFORMATION_SCHEMA`.`TABLES` WHERE `TABLE_NAME`='".$tableName."'";
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
			return "SELECT IF(COUNT(*)>0, 1 , 0) FROM `INFORMATION_SCHEMA`.`VIEWS` WHERE `TABLE_NAME`= '".$viewName."' AND `TABLE_SCHEMA`='".$schemaName."'";
		}

		return "SELECT IF(COUNT(*)>0, 1 , 0) FROM `INFORMATION_SCHEMA`.`VIEWS` WHERE `TABLE_NAME`='".$viewName."'";
	}

	/**
	 * Generates SQL describing a table
	 *
	 *<code>
	 *	print_r($dialect->describeColumns("posts")) ?>
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
			return 'DESCRIBE `'.$schema.'`.`'.$table.'`';
		}

		return 'DESCIRBE `'.$table.'`';
	}

	/**
	 * List all tables on database
	 *
	 *<code>
	 *	print_r($dialect->listTables("blog")) ?>
	 *</code>
	 *
	 * @param string|null $schemaName
	 * @return array
	 */
	public function listTables($schemaName = null)
	{
		if(is_string($schemaName) === true &&
			$schemaName == true) {
			return 'SHOW TABLES FROM `'.$schemaName.'`';
		}

		return 'SHOW TABLES';
	}

	/**
	 * Generates the SQL to list all views of a schema or user
	 *
	 * @param string|null $schemaName
	 * @return array
	 */
	public function listViews($schemaName = null)
	{
		if(is_string($schemaName) === true &&
			$schemaName == true) {
			return "SELECT `TABLE_NAME` AS view_name FROM `INFORMATION_SCHEMA`.`VIEWS` WHERE `TABLE_SCHEMA` = '".$schemaName."' ORDER BY view_name";
		}

		return 'SELECT `TABLE_NAME` AS view_name FROM `INFORMATION_SCHEMA`.`VIEWS` ORDER BY view_name';
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

		if(is_string($schema) === true &&
			$schema == true) {
			return 'SHOW INDEXES FROM `'.$schema.'`.`'.$table.'`';
		}

		return 'SHOW INDEXES FROM `'.$table.'`';
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

		$sql = 'SELECT TABLE_NAME,COLUMN_NAME,CONSTRAINT_NAME,REFERENCED_TABLE_SCHEMA,REFERENCED_TABLE_NAME,REFERENCED_COLUMN_NAME FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE WHERE REFERENCED_TABLE_NAME IS NOT NULL AND ';

		if(is_string($schema) === true &&
			$schema == true) {
			$sql .= 'CONSTRAINT_SCHEMA = "'.$schema.'" AND TABLE_NAME "'.$table.'"';
		} else {
			$sql .= 'TABLE NAME = "'.$table.'"';
		}

		return $sql;
	}

	/**
	 * Generates the SQL to describe the table creation options
	 *
	 * @param string $table
	 * @param string|null $schema
	 * @return string
	 * @throws Exception
	 */
	public function tableOptions($table, $schema = null)
	{
		if(is_string($table) === false) {
			throw new Exception('Invalid parameter type.');
		}

		$sql = 'SELECT TABLES.TABLE_TYPE AS table_type,TABLES.AUTO_INCREMENT AS auto_increment,TABLES.ENGINE AS engine,TABLES.TABLE_COLLATION AS table_collation FROM INFORMATION_SCHEMA.TABLES WHERE ';

		if(is_string($schema) === true &&
			$schema == true) {
			$sql .= 'TABLES.TABLE_SCHEMA = "'.$schema.'" AND TABLES.TABLE_NAME = "'.$table.'"';
		} else {
			$sql .= 'TABLES.TABLE_NAME = "'.$table.'"';
		}

		return $sql;
	}
}