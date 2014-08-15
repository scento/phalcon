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
namespace Phalcon\Db\Adapter\Pdo;

use \Phalcon\Db\Adapter\Pdo,
	\Phalcon\Db\AdapterInterface,
	\Phalcon\Db\Exception,
	\Phalcon\Db\RawValue,
	\Phalcon\Db\Column,
	\Phalcon\Events\EventsAwareInterface;

/**
 * Phalcon\Db\Adapter\Pdo\Postgresql
 *
 * Specific functions for the Postgresql database system
 * <code>
 *
 * $config = array(
 *  "host" => "192.168.0.11",
 *  "dbname" => "blog",
 *  "username" => "postgres",
 *  "password" => ""
 * );
 *
 * $connection = new Phalcon\Db\Adapter\Pdo\Postgresql($config);
 *
 * </code>
 * 
 * @see https://github.com/phalcon/cphalcon/blob/1.2.6/ext/db/adapter/pdo/postgresql.c
 */
class Postgresql extends Pdo implements EventsAwareInterface, AdapterInterface
{
	/**
	 * Type
	 * 
	 * @var string
	 * @access protected
	*/
	protected $_type = 'pgsql';

	/**
	 * Dialect
	 * 
	 * @var string
	 * @access protected
	*/
	protected $_dialectType = 'postgresql';

	/**
	 * This method is automatically called in \Phalcon\Db\Adapter\Pdo constructor.
	 * Call it when you need to restore a database connection.
	 *
	 * Support set search_path after connectted if schema is specified in config.
	 *
	 * @param array|null $descriptor
	 * @return boolean
	 * @throws Exception
	 */
	public function connect($descriptor = null)
	{
		if(is_null($descriptor) === true) {
			$descriptor = $this->_descriptor;
		} elseif(is_array($descriptor) === false) {
			throw new Exception('Invalid parameter type.');
		}

		if(isset($descriptor['schema']) === true) {
			$schema = $descriptor['schema'];
			unset($descriptor['schema']);
		} else {
			$schema = null;
		}

		parent::connect($descriptor);

		//Execute the search path in the after connect
		if(is_string($schema) === true) {
			$this->execute("SET search_path TO '".$schema."'");
		}
	}

	/**
	 * Returns an array of \Phalcon\Db\Column objects describing a table
	 *
	 * <code>print_r($connection->describeColumns("posts")); ?></code>
	 *
	 * @param string $table
	 * @param string|null $schema
	 * @return \Phalcon\Db\Column[]
	 * @throws Exception
	 */
	public function describeColumns($table, $schema = null)
	{
		if(is_string($table) === false ||
			(is_string($schema) === false &&
				is_null($schema) === false)) {
			throw new Exception('Invalid parameter type.');
		}
		
		$columns = array();
		$dialect = $this->_dialect;
		$sql = $dialect->describeColumns($table, $schema);

		//We're using FETCH_NUM to fetch the columns
		$describe = $this->fetchAll($sql, 3);

		//0: name, 1: type, 3: numeric size, 4: null, 5: key, 6: extra, 7: position
		$oldColumn = null;
		foreach($describe as $field) {
			$definition = array('bindType' => 2);
			$columnType = $field[1];

			//Check the column type to get the correct Phalcon type
			while(true) {
				if(strpos($columnType, 'smallint(1)') !== false) {
					$definition['type'] = 8;
					$definition['bindType'] = 5;
					break;
				}

				if(strpos($columnType, 'int') !== false) {
					$definition['type'] = 0;
					$definition['isNumeric'] = true;
					$definition['size'] = $field[3];
					$definition['bindType'] = 1;
					break;
				}

				if(strpos($columnType, 'varying') !== false) {
					$definition['type'] = 2;
					$definition['size'] = $field[2];
					break;
				}

				if(strpos($columnType, 'date') !== false) {
					$definition['type'] = 1;
					$definition['size'] = 0;
					break;
				}

				if(strpos($columnType, 'numeric') !== false) {
					$definition['type'] = 3;
					$definition['isNumeric'] = true;
					$definition['size'] = $field[3];
					$definition['scale'] = $field[4];
					$definition['bindType'] = 32;
					break;
				}

				if(strpos($columnType, 'char') !== false) {
					$definition['type'] = 5;
					$definition['size'] = $field[2];
					break;
				}

				if(strpos($columnType, 'timestamp') !== false) {
					$definition['type'] = 4;
					$definition['size'] = 0;
					break;
				}

				if(strpos($columnType, 'text') !== false) {
					$definition['type'] = 6;
					$definition['size'] = $field[2];
					break;
				}

				if(strpos($columnType, 'float') !== false) {
					$definition['type'] = 7;
					$definition['isNumeric'] = true;
					$definition['size'] = $field[3];
					$definition['bindType'] = 32;
					break;
				}

				if(strpos($columnType, 'bool') !== false) {
					$definition['type'] = 8;
					$definition['size'] = 0;
					$definition['bindType'] = 5;
					break;
				}

				if(strpos($columnType, 'uuid') !== false) {
					$definition['type'] = 5;
					$definition['size'] = 36;
					break;
				}

				$definition['type'] = 2;
				break;
			}

			if(strpos($columnType, 'unsigned') !== false) {
				$definition['unsigned'] = true;
			}

			if(is_null($oldColumn) === true) {
				$definition['first'] = true;
			} else {
				$definition['after'] = $oldColumn;
			}

			//Check if the field is primary key
			if($field[6] === 'PRI') {
				$definition['primary'] = true;
			}

			//Check if the column allows null values
			if($field[5] === 'NO') {
				$definition['notNull'] = true;
			}

			//Check if the column is auto increment
			if($field[7] == 'auto_increment') {
				$definition['autoIncrement'] = true;
			}

			//Create a Phalcon\Db\Column to abstract the column
			$column = new Column($field[0], $definition);
			$columns[] = $column;
			$oldColumn = $field[0];
		}

		return $columns;
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

	/**
	 * Return the default identity value to insert in an identity column
	 *
	 * @return \Phalcon\Db\RawValue
	 */
	public function getDefaultIdValue()
	{
		return new RawValue('default');
	}

	/**
	 * Check whether the database system requires a sequence to produce auto-numeric values
	 *
	 * @return boolean
	 */
	public function supportSequences()
	{
		return true;
	}
}