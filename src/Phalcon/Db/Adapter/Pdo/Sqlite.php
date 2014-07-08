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

use \Phalcon\Db\Adapter\Pdo,
	\Phalcon\Db\AdapterInterface,
	\Phalcon\Db\Exception,
	\Phalcon\Db\Column,
	\Phalcon\Db\Index,
	\Phalcon\Db\Reference,
	\Phalcon\Events\EventsAwareInterface;


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
class Sqlite extendsPdo implements EventsAwareInterface, AdapterInterface
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
	protected $_dialectType = 'sqlite';

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
		if(is_null($descriptor) === true) {
			$descriptor = $this->_descriptor;
		} elseif(is_array($descriptor) === false) {
			throw new Exception('Invalid parameter type.');
		}

		if(isset($descriptor['dbname']) === false) {
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
		$columns = array();
		$dialect = $this->_dialect;
		$sql = $dialect->describeColumns($table, $schema);

		//We're using FETCH_NUM to fetch the columns
		$describe = $this->fetchAll($sql, 3);

		$old_column = null;
		foreach($describe as $field) {
			$definition = array('bindType' => 2);
			$column_type = $field[2];

			//Check the column type to get the correct Phalcon type
			while(true) {
				if(strpos($column_type, 'tinyint(1)') !== false) {
					$definition['type'] = 8;
					$definition['bindType'] = 5;
					$column_type = 'boolean'; //Change column type to skip size check
					break;
				}

				if(strpos($column_type, 'int') !== false) {
					$definition['type'] = 0;
					$definition['isNumeric'] = true;
					$definition['bindType'] = 1;

					if($field[5] == true) {
						$definition['autoIncrement'] = true;
					}
					break;
				}

				if(strpos($column_type, 'varchar') !== false) {
					$definition['type'] = 2;
					break;
				}

				if(strpos($column_type, 'date') !== false) {
					$definition['type'] = 1;
					break;
				}

				if(strpos($column_type, 'timestamp') !== false) {
					$definition['type'] = 1;
					break;
				}

				if(strpos($column_type, 'decimal') !== false) {
					$definition['type'] = 3;
					$definition['isNumeric'] = true;
					$definition['bindType'] = 32;
					break;
				}

				if(strpos($column_type, 'char') !== false) {
					$definition['type'] = 5;
					break;
				}

				if(strpos($column_type, 'datetime') !== false) {
					$definition['type'] = 4;
					break;
				}

				if(strpos($column_type, 'text') !== false) {
					$definition['type'] = 6;
					break;
				}

				if(strpos($column_type, 'float') !== false) {
					$definition['type'] = 7;
					$definition['isNumeric'] = true;
					$definition['bindType'] = 32;
					break;
				}

				if(strpos($column_type, 'enum') !== false) {
					$definition['type'] = 5;
					break;
				}

				$definition['type'] = 2;
				break;
			}

			if(strpos($column_type, '(') !== false) {
				$matches = null;
				if(preg_match("#\\(([0-9]++)(?:,\\s*([0-9]++))?\\)#", $column_type, $matches) == true) {
					if(isset($matches[1]) === true) {
						$definition['size'] = $matches[1];
					}

					if(isset($matches[2]) === true) {
						$definition['scale'] = $matches[2];
					}
				}
			}

			if(strpos($column_type, 'unsigned') !== false) {
				$definition['unsigned'] = true;
			}

			if(is_null($old_column) === true) {
				$definition['first'] = true;
			} else {
				$definition['after'] = $old_column;
			}

			//Check if the field is primary key
			if($field[5] == true) {
				$definition['primary'] = true;
			}

			//Check if the column allows null values
			if($field[3] == true) {
				$definition['notNull'] = true;
			}

			//Every column is stored as a Phalcon\Db\Column
			$column = new Column($field[1], $definition);
			$columns[] = $column;
			$old_column = $field[1];
		}

		return $columns;
	}

	/**
	 * Lists table indexes
	 *
	 * @param string $table
	 * @param string $schema
	 * @return \Phalcon\Db\Index[]
	 */
	public function describeIndexes($table, $schema=null){ }

	/**
	 * Lists table references
	 *
	 * @param string $table
	 * @param string $schema
	 * @return \Phalcon\Db\Reference[]
	 */
	public function describeReferences($table, $schema=null){ }

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