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

		$className = get_class($model);
		$schema = $model->getSchema();
		$table = $model->getSource();
		$readConnection = $model->getReadConnection();

		//Check if the mapped table exists on the database
		if(!$readConnection->tableExists($table, $schema)) {
			//The table does not exist
			throw new Exception('Table "'.($schema == true ? $schema.'"."'.$table : $table).
				'" doesn\'t exist on database when dumping meta-data for '.$className);
		}

		//Try to describe the table
		$columns = $readConnection->describeColumns($table, $schema);
		if(count($columns) == 0) {
			throw new Exception('Cannot obtain table columns for the mapped source "'.
				($schema == true ? $schema.'"."'.$table : $table).'" used in model "'.$className);
		}

		//Initialize meta-data
		$attributes = array();
		$primaryKeys = array();
		$nonPrimaryKeys = array();
		$numericTyped = array();
		$notNull = array();
		$fieldTypes = array();
		$fieldBindTypes = array();
		$automaticDefault = array();
		$identityField = false;

		foreach($columns as $column) {
			$fieldName = $column->getName();
			$attributes[] = $fieldName;

			//Mark fields as priamry keys
			if($column->isPrimary() === true) {
				$primaryKeys[] = $fieldName;
			} else {
				$nonPrimaryKeys[] = $fieldName;
			}

			//Mark fields as numeric
			if($column->isNumeric() === true) {
				$numericTyped[$fieldName] = true;
			}

			//Mark fields as not null
			if($column->isNotNull() === true) {
				$notNull[] = $fieldName;
			}

			//Mark fields as identity columns
			if($column->isAutoIncrement() === true) {
				$identityField = $fieldName;
			}

			//Get the internal types
			$fieldTypes[$fieldName] = $column->getType();

			//Mark how fields must be escaped
			$fieldBindTypes[$fieldName] = $column->getBindType();
		}

		return array(0 => $attributes, 1 => $primaryKeys, 2 => $nonPrimaryKeys, 3 => $notNull, 4 => $fieldTypes,
			5 => $numericTyped, 8 => $identityField, 9 => $fieldBindTypes, 10 => $automaticDefault, 11 => $automaticDefault);
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

		$orderedColumnMap = null;
		$reversedColumnMap = null;

		//Check for a columnMap() method on the model
		if(method_exists($model, 'columnMap') === true) {
			$userColumnMap = $model->columnMap();
			if(is_array($userColumnMap) === false) {
				throw new Exception('columnMap() not returned an array');
			}

			$reversedColumnMap = array();
			$orderedColumnMap = $userColumnMap;

			foreach($userColumnMap as $name => $userName) {
				$reversedColumnMap[$userName] = $name;
			}
		}

		//Store the column map
		return array($orderedColumnMap, $reversedColumnMap);
	}
}