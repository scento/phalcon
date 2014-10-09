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
namespace Phalcon\Db\Adapter\Pdo;

use \Phalcon\Db\Adapter\Pdo,
	\Phalcon\Db\Column,
	\Phalcon\Db\AdapterInterface,
	\Phalcon\Db\Exception,
	\Phalcon\Events\EventsAwareInterface;

/**
 * Phalcon\Db\Adapter\Pdo\Mysql
 *
 * Specific functions for the Mysql database system
 *
 *<code>
 *
 *	$config = array(
 *		"host" => "192.168.0.11",
 *		"dbname" => "blog",
 *		"port" => 3306,
 *		"username" => "sigma",
 *		"password" => "secret"
 *	);
 *
 *	$connection = new Phalcon\Db\Adapter\Pdo\Mysql($config);
 *
 *</code>
 *
 * @see https://github.com/phalcon/cphalcon/blob/1.2.6/ext/db/adapter/pdo/mysql.c
 */
class Mysql extends Pdo implements EventsAwareInterface, AdapterInterface
{
	/**
	 * Type
	 *
	 * @var string
	 * @access protected
	*/
	protected $_type = 'mysql';

	/**
	 * Dialect Type
	 *
	 * @var string
	 * @access protected
	*/
	protected $_dialectType = 'Mysql';

	/**
	 * Escapes a column/table/schema name
	 *
	 * @param string|array $identifier
	 * @return string
	 * @throws Exception
	 */
	public function escapeIdentifier($identifier)
	{
		if(is_array($identifier) === true) {
			if(isset($GLOBALS['_PHALCON_DB_ESCAPE_IDENTIFIERS']) === true &&
				$GLOBALS['_PHALCON_DB_ESCAPE_IDENTIFIERS'] === true) {
				return '`'.$identifier[0].'`.`'.$identifier[1].'`';
			} else {
				return $identifier[0].'.'.$identifier[1];
			}
		} elseif(is_string($identifier) === true) {
			if(isset($GLOBALS['_PHALCON_DB_ESCAPE_IDENTIFIERS']) === true &&
				$GLOBALS['_PHALCON_DB_ESCAPE_IDENTIFIERS'] === true) {
				return '`'.$identifier.'`';
			} else {
				return $identifier;
			}
		} else {
			throw new Exception('Invalid parameter type.');
		}
	}

	/**
	 * Returns an array of \Phalcon\Db\Column objects describing a table
	 *
	 * <code>
	 * print_r($connection->describeColumns("posts")); ?>
	 * </code>
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

		$dialect = $this->_dialect;

		//Get the SQL to describe a table
		$sql = $dialect->describeColumns($table, $schema);

		//Get the describe
		$describe = $this->fetchAll($sql, 3);
		$oldColumn = null;
		$columns = array();

		//Field Indexes: 0 - Name, 1 - Type, 2 - Not Null, 3 - Key, 4 - Default, 5 - Extra
		foreach($describe as $field) {
			//By default the bind type is two
			$definition = array('bindType' => 2);

			//By checking every column type we convert it to a Phalcon\Db\Column
			$columnType = $field[1];

			//Check the column type to get the current Phalcon type
			while(true) {
				//Point are varchars
				if(strpos($columnType, 'point') !== false) {
					$definition['type'] = 2;
					break;
				}

				//Enum are treated as char
				if(strpos($columnType, 'enum') !== false) {
					$definition['type'] = 5;
					break;
				}

				//Tinyint(1) is boolean
				if(strpos($columnType, 'tinyint(1)') !== false) {
					$definition['type'] = 8;
					$definition['bindType'] = 5;
					$columnType = 'boolean';
					break;
				}

				//Smallint/Bigint/Integer/Int are int
				if(strpos($columnType, 'int') !== false) {
					$definition['type'] = 0;
					$definition['isNumeric'] = true;
					$definition['bindType'] = 1;
					break;
				}

				//Varchar are varchars
				if(strpos($columnType, 'varchar') !== false) {
					$definition['type'] = 2;
					break;
				}

				//Special type for datetime
				if(strpos($columnType, 'datetime') !== false) {
					$definition['type'] = 4;
					break;
				}

				//Decimals are floats
				if(strpos($columnType, 'decimal') !== false) {
					$definition['type'] = 3;
					$definition['isNumeric'] = true;
					$definition['bindType'] = 32;
					break;
				}

				//Chars are chars
				if(strpos($columnType, 'char') !== false) {
					$definition['type'] = 5;
					break;
				}

				//Date/Datetime are varchars
				if(strpos($columnType, 'date') !== false) {
					$definition['type'] = 1;
					break;
				}

				//Timestamp as date
				if(strpos($columnType, 'timstamp') !== false) {
					$definition['type'] = 1;
					break;
				}

				//Text are varchars
				if(strpos($columnType, 'text') !== false) {
					$definition['type'] = 6;
					break;
				}

				//Floats/Smallfloats/Decimals are float
				if(strpos($columnType, 'float') !== false) {
					$definition['type'] = 7;
					$definition['isNumeric'] = true;
					$definition['bindType'] = 32;
					break;
				}

				//Doubles are floats
				if(strpos($columnType, 'double') !== false) {
					$definition['type'] = 9;
					$definition['isNumeric'] = true;
					$definition['bindType'] = 32;
					break;
				}

				//By default: String
				$definition['type'] = 2;
				break;
			}

			//If the column type has a parentheses we try to get the column size from it
			if(strpos($columnType, '(') !== false) {
				$matches = null;
				$pos = preg_match("#\\(([0-9]++)(?:,\\s*([0-9]++))?\\)#", $columnType, $matches);
				if($pos == true) {
					if(isset($matches[1]) === true) {
						$definition['size'] = $matches[1];
					}

					if(isset($matches[2]) === true) {
						$definition['scale'] = $matches[2];
					}
				}
			}

			//Check if the column is unsigned, only MySQL supports this
			if(strpos($columnType, 'unsigned') !== false) {
				$definition['unsigned'] = true;
			}

			//Positions
			if($oldColumn != true) {
				$definition['first'] = true;
			} else {
				$definition['after'] = $oldColumn;
			}

			//Check if the field is primary key
			if($field[3] === 'PRI') {
				$definition['primary'] = true;
			}

			//Check if the column allows null values
			if($field[2] == 'NO') {
				$definition['notNull'] = true;
			}

			//Check if the column is auto increment
			if($field[5] === 'auto_increment') {
				$definition['autoIncrement'] = true;
			}

			$column = new Column($field[0], $definition);
			$columns[] = $column;
			$oldColumn = $field[0];
		}

		return $columns;
	}
}