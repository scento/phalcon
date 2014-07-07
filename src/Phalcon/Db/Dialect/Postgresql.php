<?php
/**
 * PostgreSQL
 *
 * @author Andres Gutierrez <andres@phalconphp.com>
 * @author Eduar Carvajal <eduar@phalconphp.com>
 * @author Wenzel Pünter <wenzel@phelix.me>
 * @version 1.2.6
 * @package Phalcon
*/
namespace Phalcon\Db\Dialect;

use  \Phalcon\Db\Dialect,
	\Phalcon\Db\DialectInterface,
	\Phalcon\Db\ColumnInterface,
	\Phalcon\Db\Exception;

/**
 * Phalcon\Db\Dialect\Postgresql
 *
 * Generates database specific SQL for the PostgreSQL RBDM
 * 
 *  @see https://github.com/phalcon/cphalcon/blob/1.2.6/ext/db/dialect/postgresql.c
 */
class Postgresql extends Dialect implements DialectInterface
{
	/**
	 * Ecape Character
	 * 
	 * @var string
	 * @access protected
	*/
	protected $_escapeChar = '"';

	/**
	 * Gets the column name in PostgreSQL
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

		switch((int)$column->getType())
		{
			case 0:
				return 'INT';
			case 1:
				return 'DATE';
			case 2:
				return 'CHARACTER VARYING('.$column->getSize().')';
			case 3:
				return 'NUMERIC('.$column->getSize().','.$column->getScale().')';
			case 4:
				return 'TIMESTAMP';
			case 5:
				return 'CHARACTER('.$column->getSIze().')';
			case 6:
				return 'TEXT';
			case 7:
				return 'FLOAT';
			case 8:
				return 'SMALLINT(1)';
			default:
				throw new Exception('Unrecognized PostgreSQL data type');
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
	protected function _getTableOptions()
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
	public function dropTable($tableName, $schemaName, $ifExists=null)
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
		}

		return 'DROP TABLE '.$table;
	}


	/**
	 * Generates SQL to create a view
	 *
	 * @param string $viewName
	 * @param array $definition
	 * @param string|null $schemaName
	 * @return string
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
	public function dropView($viewName, $schemaName, $ifExists=null)
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
			$view = $viewName.'.'.$schemaName;
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
	 * <code>echo $dialect->tableExists("posts", "blog")</code>
	 * <code>echo $dialect->tableExists("posts")</code>
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
			return "SELECT CASE WHEN COUNT(*) > 0 THEN 1 ELSE 0 END FROM information_schema.tables WHERE table_schema = '".$schemaName."' AND table_name='".$tableName."'";
		} else {
			return "SELECT CASE WHEN COUNT(*) > 0 THEN 1 ELSE 0 END FROM information_schema.tables WHERE table_schema = 'public' AND table_name='".$tableName."'";
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
			return "SELECT CASE WHEN COUNT(*) > 0 THEN 1 ELSE 0 END FROM pg_views WHERE viewname='".$viewName."' AND schemaname='".$schemaName."'";
		} else {
			return "SELECT CASE WHEN COUNT(*) > 0 THEN 1 ELSE 0 END FROM pg_views WHERE viewname='".$viewName."'";
		}
	}


	/**
	 * Generates a SQL describing a table
	 *
	 * <code>print_r($dialect->describeColumns("posts") ?></code>
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
			return "SELECT DISTINCT c.column_name AS Field, c.data_type AS Type, c.character_maximum_length AS Size, c.numeric_precision AS NumericSize, c.numeric_scale AS NumericScale, c.is_nullable AS Null, CASE WHEN pkc.column_name NOTNULL THEN 'PRI' ELSE '' END AS Key, CASE WHEN c.data_type LIKE '%int%' AND c.column_default LIKE '%nextval%' THEN 'auto_increment' ELSE '' END AS Extra, c.ordinal_position AS Position FROM information_schema.columns c LEFT JOIN ( SELECT kcu.column_name, kcu.table_name, kcu.table_schema FROM information_schema.table_constraints tc INNER JOIN information_schema.key_column_usage kcu on (kcu.constraint_name = tc.constraint_name and kcu.table_name=tc.table_name and kcu.table_schema=tc.table_schema) WHERE tc.constraint_type='PRIMARY KEY') pkc ON (c.column_name=pkc.column_name AND c.table_schema = pkc.table_schema AND c.table_name=pkc.table_name) WHERE c.table_schema='".$schema."' AND c.table_name='".$table."' ORDER BY c.ordinal_position";
		} else {
			return "SELECT DISTINCT c.column_name AS Field, c.data_type AS Type, c.character_maximum_length AS Size, c.numeric_precision AS NumericSize, c.numeric_scale AS NumericScale, c.is_nullable AS Null, CASE WHEN pkc.column_name NOTNULL THEN 'PRI' ELSE '' END AS Key, CASE WHEN c.data_type LIKE '%int%' AND c.column_default LIKE '%nextval%' THEN 'auto_increment' ELSE '' END AS Extra, c.ordinal_position AS Position FROM information_schema.columns c LEFT JOIN ( SELECT kcu.column_name, kcu.table_name, kcu.table_schema FROM information_schema.table_constraints tc INNER JOIN information_schema.key_column_usage kcu on (kcu.constraint_name = tc.constraint_name and kcu.table_name=tc.table_name and kcu.table_schema=tc.table_schema) WHERE tc.constraint_type='PRIMARY KEY') pkc ON (c.column_name=pkc.column_name AND c.table_schema = pkc.table_schema AND c.table_name=pkc.table_name) WHERE c.table_schema='public' AND c.table_name='".$table."' ORDER BY c.ordinal_position";
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
			return "SELECT table_name FROM information_schema.tables WHERE table_schema = '".$schemaName."' ORDER BY table_name";
		} else {
			return "SELECT table_name FROM information_schema.tables WHERE table_schema = 'public' ORDER BY table_name";
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
			return "SELECT viewname AS view_name FROM pg_views WHERE schemaname = '".$schemaName."' ORDER BY view_name";
		} else {
			return "SELECT viewname AS view_name FROM pg_views WHERE schemaname = 'public' ORDER BY view_name";
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

		//@note no schema
		return "SELECT 0 as c0, t.relname as table_name, i.relname as key_name, 3 as c3, a.attname as column_name FROM pg_class t, pg_class i, pg_index ix, pg_attribute a WHERE t.oid = ix.indrelid AND i.oid = ix.indexrelid AND a.attrelid = t.oid AND a.attnum = ANY(ix.indkey) AND t.relkind = 'r' AND t.relname = '".$table."' ORDER BY t.relname, i.relname;";
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
			return false;
		}

		$sql = "SELECT tc.table_name as TABLE_NAME, kcu.column_name as COLUMN_NAME, tc.constraint_name as CONSTRAINT_NAME, tc.table_catalog as REFERENCED_TABLE_SCHEMA, ccu.table_name AS REFERENCED_TABLE_NAME, ccu.column_name AS REFERENCED_COLUMN_NAME FROM information_schema.table_constraints AS tc JOIN information_schema.key_column_usage AS kcu ON tc.constraint_name = kcu.constraint_name JOIN information_schema.constraint_column_usage AS ccu ON ccu.constraint_name = tc.constraint_name WHERE constraint_type = 'FOREIGN KEY' AND ";

		if(is_string($schema) === true &&
			$schema == true) {
			return $sql."tc.table_schema = '".$schema."' AND tc.table_name='".$table."'";
		} else {
			return $sql."tc.table_name='".$table."'";
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
}