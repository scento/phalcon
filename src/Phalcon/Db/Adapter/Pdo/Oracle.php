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
namespace Phalcon\Db\Adapter\Pdo;

use \Phalcon\Db\Adapter\Pdo,
	\Phalcon\Db\AdapterInterface,
	\Phalcon\Db\Column,
	\Phalcon\Db\RawValue,
	\Phalcon\Db\Exception,
	\Phalcon\Events\EventsAwareInterface;

/**
 * Phalcon\Db\Adapter\Pdo\Oracle
 *
 * Specific functions for the Oracle database system
 * <code>
 *
 * $config = array(
 *  "dbname" => "//localhost/dbname",
 *  "username" => "oracle",
 *  "password" => "oracle"
 * );
 *
 * $connection = new Phalcon\Db\Adapter\Pdo\Oracle($config);
 *
 * </code>
 * 
 * @see https://github.com/phalcon/cphalcon/blob/1.2.6/ext/db/adapter/pdo/oracle.c
 */
class Oracle extends Pdo implements EventsAwareInterface, AdapterInterface
{
	/**
	 * Type
	 * 
	 * @var string
	 * @access protected
	*/
	protected $_type = 'oci';

	/**
	 * Dialect Type
	 * 
	 * @var string
	 * @access protected
	*/
	protected $_dialectType = 'oracle';

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
		if(is_array($descriptor) === false) {
				$descriptor = $this->_descriptor;
		}

		//Connect
		parent::__construct($descriptor);

		//Database session settings initiated with each HTTP request. Pracle behaviour
		//depends on particular NLS* parameter. Check if the developer has defined custom
		//startup or create one from scratch
		if(isset($descriptor['startup']) === true &&
			is_array($descriptor['starup']) === true) {
			foreach($descriptor['startup'] as $value) {
				$this->execute($value);
			}
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

		/* 
	 	*  0:column_name, 1:data_type, 2:data_length, 3:data_precision, 4:data_scale,
		* 5:nullable, 6:constraint_type, 7:default, 8:position;
		*/
		$old_column = null;
		foreach($descibe as $field) {
			$definition = array('bindType' => 2);
			$column_size = $field[2];
			$column_precision = $field[3];
			$column_scale = $field[4];
			$column_type = $field[1];

			//Check the column type to get the current Phalcon type
			while(true) {
				if(strpos($column_type, 'NUMBER') !== false) {
					$definition['type'] = 3;
					$definition['isNumeric'] = 1;
					$definition['size'] = $column_precision;
					$definition['scale'] = $column_scale;
					$definition['bindType'] = 32;
					break;
				}

				if(strpos($column_type, 'TINYINT(1)') !== false) {
					$definition['type'] = 8;
					$definition['bindType'] = 5;
					break;
				}

				if(strpos($column_type, 'INTEGER') !== false) {
					$definition['type'] = 0;
					$definition['isNumeric'] = 1;
					$definition['size'] = $column_precision;
					$definition['bindType'] = 1;
					break;
				}

				if(strpos($column_type, 'FLOAT') !== false) {
					$definition['type'] = 7;
					$definition['isNumeric'] = 1;
					$definition['size'] = $column_size;
					$definition['scale'] = $column_scale;
					$definition['bindType'] = 32;
					break;
				}

				if(strpos($column_type, 'TIMESTAMP') !== false) {
					$definition['type'] = 1;
					break;
				}

				if(strpos($column_type, 'RAW') !== false) {
					$definition['type'] = 6;
					break;
				}

				if(strpos($column_type, 'BLOB') !== false) {
					$definition['type'] = 6;
					break;
				}

				if(strpos($column_type, 'CLOB') !== false) {
					$definition['type'] = 6;
					break;
				}

				if(strpos($column_type, 'VARCHAR2') !== false) {
					$definition['type'] = 2;
					$definition['size'] = $column_size;
					break;
				}

				if(strpos($column_type, 'CHAR') !== false) {
					$definition['type'] = 5;
					$definition['size'] = $column_size;
					break;
				}

				if(strpos($column_type, 'text') !== false) {
					$definition['type'] = 6;
					break;
				}

				$definition['type'] = 2;
				break;
			}

			if(is_null($old_column) === true) {
				$definition['first'] = true;
			} else {
				$definition['after'] = $old_column;
			}

			//Check if the field is primary key
			if($field[6] === 'P') {
				$definition['primary'] = true;
			}

			//Check if the column allows null values
			if($field[5] === 'N') {
				$definition['notNull'] = true;
			}

			//Create a Phalcon\Db\Column to abstract the column
			$column = new Column($field[0], $definition);
			$columns[] = $column;
			$old_column = $column;
		}

		return $columns;
	}

	/**
	 * Returns the insert id for the auto_increment/serial column inserted in the lastest executed SQL statement
	 *
	 *<code>
	 * //Inserting a new robot
	 * $success = $connection->insert(
	 *     "robots",
	 *     array("Astro Boy", 1952),
	 *     array("name", "year")
	 * );
	 *
	 * //Getting the generated id
	 * $id = $connection->lastInsertId();
	 *</code>
	 *
	 * @param string|null $sequenceName
	 * @return int
	 * @throws Exception
	 */
	public function lastInsertId($sequenceName = null)
	{
		if(is_string($sequenceName) === false &&
			is_null($sequenceName) === false) {
			throw new Exception('Invalid parameter type.');
		}

		//@note no $sequenceName = null handeling
		return $this->fetchAll('SELECT '.$sequenceName.'.CURRVAL FROM dual', 3)[0];
	}

	/**
	 * Check whether the database system requires an explicit value for identity columns
	 *
	 * @return boolean
	 */
	public function useExplicitIdValue()
	{
		return false;
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