<?php
/**
* Introspection
*
* @author Andres Gutierrez <andres@phalconphp.com>
* @author Eduar Carvajal <eduar@phalconphp.com>
* @author Wenzel Pünter <wenzel@phelix.me>
* @version 1.2.6
* @package Phalcon
*/
namespace Phalcon\Mvc\Model\MetaData\Strategy;

use \Phalcon\Mvc\Model\Exception,
	\Phalcon\Mvc\ModelInterface,
	\Phalcon\DiInterface;

/**
 * Phalcon\Mvc\Model\MetaData\Strategy\Instrospection
 *
 * Queries the table meta-data in order to instrospect the model's metadata
 * 
 * @see https://github.com/phalcon/cphalcon/blob/1.2.6/ext/mvc/model/metadata/strategy/introspection.c
 */
class Introspection
{
	/**
	 * The meta-data is obtained by reading the column descriptions from the database information schema
	 *
	 * @param \Phalcon\Mvc\ModelInterface $model
	 * @param \Phalcon\DiInterface $dependencyInjector
	 * @return array
	 * @throws Exception
	 */
	public function getMetaData($model, $dependencyInjector)
	{
		if(is_object($model) === false ||
			$model instanceof ModelInterface === false ||
			is_object($dependencyInjector) === false ||
			$dependencyInjector instanceof DiInterface === false) {
			throw new Exception('Invalid parameter type.');
		}

		$class_name = get_class($model);
		$schema = $model->getSchema();
		$table = $model->getSource();
		$read_connection = $model->getReadConnection();

		//Check if the mapped table exists on the database
		if($read_connection->tableExists($table, $schema) !== true) {
			//The table does not exist
			throw new Exception('Table "'.($schema == true ? $schema.'"."'.$table : $table).
				'" doesn\'t exist on database when dumping meta-data for '.$class_name);
		}

		//Try to describe the table
		$columns = $read_connection->describeColumns($table, $schema);
		if(count($column) == 0) {
			throw new Exception('Cannot obtain table columns for the mapped source "'.
				($schema == true ? $schema.'"."'.$table : $table).'" used in model "'.$class_name);
		}

		//Initialize meta-data
		$attributes = array();
		$primary_keys = array();
		$non_primary_keys = array();
		$numeric_typed = array();
		$not_null = array();
		$field_types = array();
		$field_bind_types = array();
		$automatic_default = array();
		$identity_field = false;

		foreach($columns as $column) {
			$field_name = $column->getName();
			$attributes[] = $field_name;

			//Mark fields as priamry keys
			if($column->isPrimary() === true) {
				$primary_keys[] = $field_name;
			} else {
				$non_primary_keys[] = $field_name;
			}

			//Mark fields as numeric
			if($column->isNumeric() === true) {
				$numeric_typed[$field_name] = true;
			}

			//Mark fields as not null
			if($column->isNotNull() === true) {
				$not_null[] = $field_name;
			}

			//Mark fields as identity columns
			if($column->isAutoIncrement() === true) {
				$identity_field = $field_name;
			}

			//Get the internal types
			$field_types[$field_name] = $column->getType();

			//Mark how fields must be escaped
			$field_bind_types[$field_name] = $column->getBindType();
		}

		return array(0 => $attributes, 1 => $primary_keys, 2 => $non_primary_keys, 3 => $not_null, 4 => $field_types, 
			5 => $numeric_typed, 8 => $identity_field, 9 => $field_bind_types, 10 => $automatic_default, 11 => $automatic_default);
	}

	/**
	 * Read the model's column map, this can't be infered
	 *
	 * @param \Phalcon\Mvc\ModelInterface $model
	 * @param \Phalcon\DiInterface $dependencyInjector
	 * @return array
	 * @throws Exception
	 */
	public function getColumnMaps($model, $dependencyInjector)
	{
		if(is_object($model) === false ||
			$model instanceof ModelInterface === false ||
			is_object($dependencyInjector) === false ||
			$dependencyInjector instanceof DiInterface === false) {
			throw new Exception('Invalid parameter type.');
		}

		$ordered_column_map = null;
		$reversed_column_map = null;

		//Check for a columnMap() method on the model
		if(method_exists($model, 'columnMap') === true) {
			$user_column_map = $model->columnMap();
			if(is_array($user_column_map) === false) {
				throw new Exception('columnMap() not returned an array');
			}
		
			$reversed_column_map = array();
			$ordered_column_map = $user_column_map;

			foreach($user_column_map as $name => $user_name) {
				$reversed_column_map[$user_name] = $name;
			}
		}

		//Store the column map
		return array($ordered_column_map, $reversed_column_map);
	}
}