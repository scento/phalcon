<?php
/**
 * Sqlite
 *
 * @author Andres Gutierrez <andres@phalconphp.com>
 * @author Eduar Carvajal <eduar@phalconphp.com>
 * @author Wenzel Pünter <wenzel@phelix.me>
 * @version 1.2.6
 * @package Phalcon
*/
namespace Phalcon\Db\Adapter\Pdo;

use \Phalcon\Db\Adapter\Pdo;
use \Phalcon\Db\AdapterInterface;
use \Phalcon\Db\Exception;
use \Phalcon\Db\Column;
use \Phalcon\Db\Index;
use \Phalcon\Db\Reference;
use \Phalcon\Events\EventsAwareInterface;

/**
 * Phalcon\Db\Adapter\Pdo\Sqlite
 *
 * Specific functions for the Sqlite database system
 * <code>
 *
 * $config = array(
 *  "dbname" => "/tmp/test.sqlite"
 * );
 *
 * $connection = new Phalcon\Db\Adapter\Pdo\Sqlite($config);
 *
 * </code>
 *
 * @see https://github.com/phalcon/cphalcon/blob/1.2.6/ext/db/adapter/pdo/sqlite.c
 */
class Sqlite extends Pdo implements EventsAwareInterface, AdapterInterface
{
    /**
     * Type
     *
     * @var string
     * @access protected
    */
    protected $_type = 'sqlite';

    /**
     * Dialect Type
     *
     * @var string
     * @access protected
    */
    protected $_dialectType = 'Sqlite';

    /**
     * This method is automatically called in \Phalcon\Db\Adapter\Pdo constructor.
     * Call it when you need to restore a database connection.
     *
     * @param array|null $descriptor
     * @return boolean
     * @throws Exception
     */
    public function connect($descriptor = null)
    {
        if (is_null($descriptor) === true) {
            $descriptor = $this->_descriptor;
        } elseif (is_array($descriptor) === false) {
            throw new Exception('Invalid parameter type.');
        }

        if (isset($descriptor['dbname']) === false) {
            throw new Exception('dbname must be specified');
        }

        $descriptor['dns'] = $descriptor['dbname'];

        parent::connect($descriptor);
    }

    /**
     * Returns an array of \Phalcon\Db\Column objects describing a table
     *
     * <code>
     * print_r($connection->describeColumns("posts")); ?>
     * </code>
     *
     * @param string $table
     * @param string $schema
     * @return \Phalcon\Db\Column[]
     * @throws Exception
     */
    public function describeColumns($table, $schema = null)
    {
        if (is_string($table) === false ||
            (is_string($schema) === false &&
                is_null($schema) === false)) {
            throw new Exception('Invalid parameter type.');
        }

        $columns = array();
        $sql =  $this->_dialect->describeColumns($table, $schema);

        //We're using FETCH_NUM to fetch the columns
        $describe = $this->fetchAll($sql, 3);

        $oldColumn = null;
        foreach ($describe as $field) {
            $definition = array('bindType' => 2);
            $columnType = $field[2];

            //Check the column type to get the correct Phalcon type
            while (true) {
                if (strpos($columnType, 'tinyint(1)') !== false) {
                    $definition['type'] = 8;
                    $definition['bindType'] = 5;
                    $columnType = 'boolean'; //Change column type to skip size check
                    break;
                }

                if (strpos($columnType, 'int') !== false) {
                    $definition['type'] = 0;
                    $definition['isNumeric'] = true;
                    $definition['bindType'] = 1;

                    if ($field[5] == true) {
                        $definition['autoIncrement'] = true;
                    }
                    break;
                }

                if (strpos($columnType, 'varchar') !== false) {
                    $definition['type'] = 2;
                    break;
                }

                if (strpos($columnType, 'date') !== false) {
                    $definition['type'] = 1;
                    break;
                }

                if (strpos($columnType, 'timestamp') !== false) {
                    $definition['type'] = 1;
                    break;
                }

                if (strpos($columnType, 'decimal') !== false) {
                    $definition['type'] = 3;
                    $definition['isNumeric'] = true;
                    $definition['bindType'] = 32;
                    break;
                }

                if (strpos($columnType, 'char') !== false) {
                    $definition['type'] = 5;
                    break;
                }

                if (strpos($columnType, 'datetime') !== false) {
                    $definition['type'] = 4;
                    break;
                }

                if (strpos($columnType, 'text') !== false) {
                    $definition['type'] = 6;
                    break;
                }

                if (strpos($columnType, 'float') !== false) {
                    $definition['type'] = 7;
                    $definition['isNumeric'] = true;
                    $definition['bindType'] = 32;
                    break;
                }

                if (strpos($columnType, 'enum') !== false) {
                    $definition['type'] = 5;
                    break;
                }

                $definition['type'] = 2;
                break;
            }

            if (strpos($columnType, '(') !== false) {
                $matches = null;
                if (preg_match("#\\(([0-9]++)(?:,\\s*([0-9]++))?\\)#", $columnType, $matches) == true) {
                    if (isset($matches[1]) === true) {
                        $definition['size'] = $matches[1];
                    }

                    if (isset($matches[2]) === true) {
                        $definition['scale'] = $matches[2];
                    }
                }
            }

            if (strpos($columnType, 'unsigned') !== false) {
                $definition['unsigned'] = true;
            }

            if (is_null($oldColumn) === true) {
                $definition['first'] = true;
            } else {
                $definition['after'] = $oldColumn;
            }

            //Check if the field is primary key
            if ($field[5] == true) {
                $definition['primary'] = true;
            }

            //Check if the column allows null values
            if ($field[3] == true) {
                $definition['notNull'] = true;
            }

            //Every column is stored as a Phalcon\Db\Column
            $column = new Column($field[1], $definition);
            $columns[] = $column;
            $oldColumn = $field[1];
        }

        return $columns;
    }

    /**
     * Lists table indexes
     *
     * @param string $table
     * @param string|null $schema
     * @return \Phalcon\Db\Index[]
     * @throws Exception
     */
    public function describeIndexes($table, $schema = null)
    {
        if (is_string($table) === false ||
            (is_string($schema) === false &&
            is_null($schema) === false)) {
            throw new Exception('Invalid parameter type.');
        }

        $dialect = $this->_dialect;

        //We're using FETCH_NUM to fetch the columns
        $sql = $dialect->describeIndexes($table, $schema);
        $describe = $this->fetchAll($sql, 3);

        //Cryptic Guide: 0 - position, 1 - name
        $indexes = array();
        foreach ($describe as $index) {
            $keyName = $index[1];
            if (isset($indexes[$keyName]) === false) {
                $indexes[$keyName] = array();
            }

            $sqlIndexDescribe = $dialect->describeIndex($keyName);
            $describeIndex = $this->fetchAll($sqlIndexDescribe, 3);

            foreach ($describeIndex as $indexColumn) {
                $indexes[$keyName][] = $indexColumn[2];
            }
        }

        $indexObjects = array();
        foreach ($indexes as $name => $indexColumns) {
            $index = new Index($name, $indexColumns);
            $indexObjects[$name] = $index;
        }

        return $indexObjects;
    }

    /**
     * Lists table references
     *
     * @param string $table
     * @param string|null $schema
     * @return \Phalcon\Db\Reference[]
     * @throws Exception
     */
    public function describeReferences($table, $schema = null)
    {
        if (is_string($table) === false ||
            (is_string($schema) === false &&
            is_null($schema) === false)) {
            throw new Exception('Invalid parameter type.');
        }

        $dialect = $this->_dialect;

        //Get the SQL to describe the references
        $sql = $dialect->describeReferences($table, $schema);

        //We're using FETCH_NUM to fetch the columns
        $describe = $this->fetchAll($sql, 3);

        //Cryptic Guide: 2 - table, 3 - from, 4 - to
        $referenceObjects = array();
        foreach ($describe as $number => $referenceDescribe) {
            $constraintName = 'foreign_key_'.$number;
            $referencedTable = $referenceDescribe[2];
            $columns = array($referenceDescribe[3]);
            $referencedColumns = array($referenceDescribe[4]);

            $referenceArray = array('referencedSchema' => null, 'referencedTable' => $referencedTable,
                'columns' => $columns, 'referencedColumns' => $referencedColumns);

            //Every route is abstracted as a Phalcon\Db\Reference instance
            $reference = new Reference($constraintName, $referenceArray);
            $referenceObjects[$constraintName] = $reference;
        }

        return $referenceObjects;
    }

    /**
     * Check whether the database system requires an explicit value for identity columns
     *
     * @return boolean
     */
    public function useExplicitIdValue()
    {
        return true;
    }
}
