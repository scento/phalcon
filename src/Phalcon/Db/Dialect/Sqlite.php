<?php
/**
 * SQLite
 *
 * @author Andres Gutierrez <andres@phalconphp.com>
 * @author Eduar Carvajal <eduar@phalconphp.com>
 * @author Wenzel Pünter <wenzel@phelix.me>
 * @version 1.2.6
 * @package Phalcon
*/
namespace Phalcon\Db\Dialect;

use \Phalcon\Db\Dialect;
use \Phalcon\Db\DialectInterface;
use \Phalcon\Db\ColumnInterface;
use \Phalcon\Db\IndexInterface;
use \Phalcon\Db\Exception;

/**
 * Phalcon\Db\Dialect\Sqlite
 *
 * Generates database specific SQL for the Sqlite RBDM
 *
 *  @see https://github.com/phalcon/cphalcon/blob/1.2.6/ext/db/dialect/sqlite.c
 */
class Sqlite extends Dialect implements DialectInterface
{
    /**
     * Escape Character
     *
     * @var string
     * @access protected
    */
    protected $_escapeChar = '"';

    /**
     * Gets the column name in Sqlite
     *
     * @param \Phalcon\Db\ColumnInterface $column
     * @return string
     * @throws Exception
     */
    public function getColumnDefinition($column)
    {
        if (is_object($column) === false ||
            $column instanceof ColumnInterface === false) {
            throw new Exception('Column definition must be an instance of Phalcon\\Db\\Column');
        }

        switch((int)$column->getType()) {
            case 0:
                return 'INT';
            case 1:
                return 'DATE';
            case 2:
                return 'VARCHAR('.$column->getSize().')';
            case 3:
                return 'NUMERIC('.$column->getSize().','.$column->getScale().')';
            case 4:
                return 'TIMESTAMP';
            case 5:
                return 'CHARACTER('.$column->getSize().')';
            case 6:
                return 'TEXT';
            case 7:
                return 'FLOAT';
            default:
                throw new Exception('Unrecognized SQLite data type');
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
        if (is_string($tableName) === false) {
            throw new Exception('Invalid parameter type.');
        }

        if (is_object($column) === false ||
            $column instanceof ColumnInterface === false) {
            throw new Exception('Column parameter must be an instance of Phalcon\\Db\\Column');
        }

        if (is_string($schemaName) === true &&
            $schemaName == true) {
            $sql = 'ALTER TABLE "'.$schemaName.'"."'.$tableName.'" ADD COLUMN ';
        } else {
            $sql = 'ALTER TABLE "'.$tableName.'" ADD COLUMN ';
        }

        $sql .= '"'.$column->getName().'" '.$this->getColumnDefinition($column);

        if ($column->isNotNull() === true) {
            $sql .= ' NOT NULL';
        }

        return $sql;
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
        throw new Exception('Altering a DB column is not supported by SQLite');
    }

    /**
     * Generates SQL to delete a column from a table
     *
     * @param string $tableName
     * @param string $schemaName
     * @param string $columnName
     * @return  string
     * @throws Exception
     */
    public function dropColumn($tableName, $schemaName, $columnName)
    {
        throw new Exception('Dropping DB column is not supported by SQLite');
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
        if (is_string($tableName) === false) {
            throw new Exception('Invalid parameter type.');
        }

        if (is_object($index) === false ||
            $index instanceof IndexInterface === false) {
            throw new Exception('Index parameter must be an instance of Phalcon\\Db\\Index');
        }

        if (is_string($schemaName) === true &&
            $schemaName == true) {
            $sql = 'CREATE INDEX "'.$schemaName.'"."'.$index->getName().'" ON "'.$tableName.'" (';
        } else {
            $sql = 'CREATE INDEX "'.$index->getName().'" ON "'.$tableName.'" (';
        }

        return $sql.$this->getColumnList($index->getColumns()).')';
    }

    /**
     * Generates SQL to delete an index from a table
     *
     * @param string $tableName
     * @param string|null $schemaName
     * @param string $indexName
     * @return string
     */
    public function dropIndex($tableName, $schemaName, $indexName)
    {
        if (is_string($indexName) === false) {
            throw new Exception('Invalid parameter type.');
        }

        if (is_string($schemaName) === true &&
            $schemaName == true) {
            return 'DROP INDEX "'.$schemaName.'"."'.$indexName.'"';
        } else {
            return 'DROP INDEX "'.$indexName.'"';
        }
    }

    /**
     * Generates SQL to add the primary key to a table
     *
     * @param string $tableName
     * @param string $schemaName
     * @param \Phalcon\Db\IndexInterface $index
     * @return string
     * @throws Exception
     */
    public function addPrimaryKey($tableName, $schemaName, $index)
    {
        throw new Exception('Adding a primary key after table has been created is not supported by SQLite');
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
        throw new Exception('Removing a primary key after table has been created is not supported by SQLite');
    }

    /**
     * Generates SQL to add an index to a table
     *
     * @param string $tableName
     * @param string $schemaName
     * @param \Phalcon\Db\Reference $reference
     * @return string
     * @throws Exception
     */
    public function addForeignKey($tableName, $schemaName, $reference)
    {
        throw new Exception('Adding a foreign key constraint to an existing table is not supported by SQLite');
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
        throw new Exception('Dropping a foreign key constraint is not supported by SQLite');
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
     * Generates SQL to create a table in Sqlite
     *
     * @param string $tableName
     * @param string $schemaName
     * @param array $definition
     * @return  string
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
     */
    public function dropTable($tableName, $schemaName, $ifExists = null)
    {
        if (is_string($tableName) === false) {
            throw new Exception('Invalid parameter type.');
        }

        if (is_null($ifExists) === true) {
            $ifExists = true;
        } elseif (is_bool($ifExists) === false) {
            throw new Exception('Invalid parameter type.');
        }

        if (is_string($schemaName) === true &&
            $schemaName == true) {
            $table = $schemaName.'"'."".$tableName;
        } else {
            $table = $tableName;
        }

        if ($ifExists === true) {
            return 'DROP TABLE IF EXISTS "'.$table.'"';
        } else {
            return 'DROP TABLE "'.$table.'"';
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
        if (is_string($viewName) === false ||
            is_array($definition) === false) {
            throw new Exception('Invalid parameter type.');
        }

        if (isset($definition['sql']) === false) {
            throw new Exception("The index 'sql' is required in the definition array");
        }

        if (is_string($schemaName) === true &&
            $schemaName == true) {
            $view = $schemaName.'"."'.$viewName;
        } else {
            $view = $viewName;
        }

        return 'CREATE VIEW "'.$view.'" AS '.$definition['sql'];
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
        if (is_string($viewName) === false) {
            throw new Exception('Invalid parameter type.');
        }

        if (is_null($ifExists) === true) {
            $ifExists = true;
        } elseif (is_bool($ifExists) === false) {
            throw new Exception('Invalid parameter type');
        }

        if (is_string($schemaName) === true &&
            $schemaName == true) {
            $view = $schemaName.'"."'.$viewName;
        } else {
            $view = $viewName;
        }

        if ($ifExists === true) {
            return 'DROP VIEW IF EXISTS "'.$view.'"';
        } else {
            return 'DROP VIEW "'.$view.'"';
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
        if (is_string($tableName) === false) {
            throw new Exception('Invalid parameter type');
        }

        //@note no schema
        return "SELECT CASE WHEN COUNT(*) > 0 THEN 1 ELSE 0 END FROM sqlite_master WHERE type='table' AND tbl_name='".$tableName."'";
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
        if (is_string($viewName) === false) {
            throw new Exception('Invalid parameter type.');
        }

        //@note no schema
        return "SELECT CASE WHEN COUNT(*) > 0 THEN 1 ELSE 0 END FROM sqlite_master WHERE type='view' AND tbl_name='".$viewName."'";
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
        if (is_string($table) === false) {
            throw new Exception('Invalid parameter type.');
        }

        //@note no schema
        return "PRAGMA table_info('".$table."')";
    }

    /**
     * List all tables on database
     *
     * <code>print_r($dialect->listTables("blog")) ?></code>
     *
     * @param string|null $schemaName
     * @return array
     */
    public function listTables($schemaName = null)
    {
        //@note no schema
        return  "SELECT tbl_name FROM sqlite_master WHERE type = 'table' ORDER BY tbl_name";
    }

    /**
     * Generates the SQL to list all views of a schema or user
     *
     * @param string|null $schemaName
     * @return array
     */
    public function listViews($schemaName = null)
    {
        //@note no schema
        return "SELECT tbl_name FROM sqlite_master WHERE type = 'view' ORDER BY tbl_name";
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
        if (is_string($table) === false) {
            throw new Exception('Invalid parameter type.');
        }

        return "PRAGMA index_list('".$table."')";
    }

    /**
     * Generates SQL to query indexes detail on a table
     *
     * @param string $indexName
     * @return string
     * @throws Exception
     */
    public function describeIndex($indexName)
    {
        if (is_string($indexName) === false) {
            throw new Exception('Invalid parameter type.');
        }

        return "'PRAGMA index_info('".$indexName."')";
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
        if (is_string($table) === false) {
            throw new Exception('Invalid parameter type.');
        }

        //@note no schema
        return "PRAGMA foreign_key_list('".$table."')";
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
