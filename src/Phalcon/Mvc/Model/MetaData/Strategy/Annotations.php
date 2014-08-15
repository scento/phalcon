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
		$className = get_class($model);

		$reflection = $annotations->get($className);
		if(is_object($reflection) === false) {
			throw new Exception('No annotations were found in class '.$className);
		}

		//Get the properties defined in
		$propertiesAnnotations = $reflection->getPropertiesAnnotations();
		if(count($propertiesAnnotations) == 0) {
			throw new Exception('No properties with annotations were found in class '.$className);
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

		foreach($propertiesAnnotations as $property => $propAnnotations) {
			//All columns marked with the 'Column' annotation are considered columns
			if($propAnnotations->has('Column') === false) {
				continue;
			}

			//Fetch the 'Column' annotation
			$columnAnnotation = $propAnnotations->get('Column');

			//Check if annotation has the 'type' named parameter
			$feature = $columnAnnotation->getNamedParameter('type');
			if($feature === 'integer') {
				$fieldTypes[$property] = 0;
				$fieldBindTypes[$property] = 1;
				$numericTyped[$property] = 1;
			} elseif($feature === 'decimal') {
				$fieldTypes[$property] = 3;
				$fieldBindTypes[$property] = 32;
				$numericTyped[$property] = 1;
			} elseif($feature === 'boolean') {
				$fieldTypes[$property] = 8;
				$fieldBindTypes[$property] = 5;
			} elseif($feature === 'date') {
				$fieldTypes[$property] = 1;
				$fieldBindTypes[$property] = 2;
			} else {
				$fieldTypes[$property] = 2;
				$fieldBindTypes[$property] = 2;
			}

			//All columns marked with the 'Primary' annotation are considered primary keys
			if($propAnnotations->has('Primary') === true) {
				$primaryKeys[] = $property;
			} else {
				$nonPrimaryKeys[] = $property;
			}

			if($propAnnotations->has('Identity') === true) {
				$identityField = $property;
			}

			if($columnAnnotation->getNamedParameter('nullable') != true) {
				$notNull[] = $property;
			}
		}

		//Create an array using the MODELS_* constants as indexes
		return array(0 => $attributes, 1 => $primaryKeys, 2 => $nonPrimaryKeys, 3 => $notNull, 
			4 => $fieldTypes, 5 => $numericTyped, 8 => $identityField, 9 => $fieldBindTypes, 
			10 => $automaticDefault, 11 => $automaticDefault);
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