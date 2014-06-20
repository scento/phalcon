<?php
/**
* Annotations
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
 * Phalcon\Mvc\Model\MetaData\Strategy\Annotations
 *
 * Queries the table meta-data in order to instrospect the model's metadata
 * 
 * @see https://github.com/phalcon/cphalcon/blob/1.2.6/ext/mvc/model/metadata/strategy/annotations.c
 */
class Annotations
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
			$model instanceof ModelInterface === false) {
			throw new Exception('Invalid parameter type.');
		}

		if(is_object($dependencyInjector) === false ||
			$dependencyInjector instanceof DiInterface === false) {
			throw new Exception('Invalid parameter type.');
		}

		$annotations = $dependencyInjector->get('annotations');
		$class_name = get_class($model);

		$reflection = $annotations->get($class_name);
		if(is_object($reflection) === false) {
			throw new Exception('No annotations were found in class '.$class_name);
		}

		//Get the properties defined in
		$properties_annotations = $reflection->getPropertiesAnnotations();
		if(count($properties_annotations) == 0) {
			throw new Exception('No properties with annotations were found in class '.$class_name);
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

		foreach($properties_annotations as $property => $prop_annotations) {
			//All columns marked with the 'Column' annotation are considered columns
			if($prop_annotations->has('Column') === false) {
				continue;
			}

			//Fetch the 'Column' annotation
			$column_annotation = $prop_annotations->get('Column');

			//Check if annotation has the 'type' named parameter
			$feature = $column_annotation->getNamedParameter('type');
			if($feature === 'integer') {
				$field_types[$property] = 0;
				$field_bind_types[$property] = 1;
				$numeric_typed[$property] = 1;
			} elseif($feature === 'decimal') {
				$field_types[$property] = 3;
				$field_bind_types[$property] = 32;
				$numeric_typed[$property] = 1;
			} elseif($feature === 'boolean') {
				$field_types[$property] = 8;
				$field_bind_types[$property] = 5;
			} elseif($feature === 'date') {
				$field_types[$property] = 1;
				$field_bind_types[$property] = 2;
			} else {
				$field_types[$property] = 2;
				$field_bind_types[$property] = 2;
			}

			//All columns marked with the 'Primary' annotation are considered primary keys
			if($prop_annotations->has('Primary') === true) {
				$primary_keys[] = $property;
			} else {
				$non_primary_keys[] = $property;
			}

			if($prop_annotations->has('Identity') === true) {
				$identity_field = $property;
			}

			if($column_annotation->getNamedParameter('nullable') != true) {
				$not_null[] = $property;
			}
		}

		//Create an array using the MODELS_* constants as indexes
		return array(0 => $attributes, 1 => $primary_keys, 2 => $non_primary_keys, 3 => $not_null, 
			4 => $field_types, 5 => $numeric_typed, 8 => $identity_field, 9 => $field_bind_types, 
			10 => $automatic_default, 11 => $automatic_default);
	}

	/**
	 * Read the model's column map, this can't be infered
	 *
	 * @param \Phalcon\Mvc\ModelInterface $model
	 * @param \Phalcon\DiInterface $dependencyInjector
	 * @return null
	 */
	public function getColumnMaps()
	{

	}
}