<?php
/**
 * Uniqueness Validator
 *
 * @author Andres Gutierrez <andres@phalconphp.com>
 * @author Eduar Carvajal <eduar@phalconphp.com>
 * @author Wenzel Pünter <wenzel@phelix.me>
 * @version 1.2.6
 * @package Phalcon
*/
namespace Phalcon\Mvc\Model\Validator;

use \Phalcon\Mvc\Model\Validator,
	\Phalcon\Mvc\Model\ValidatorInterface,
	\Phalcon\Mvc\Model\Exception,
	\Phalcon\Mvc\ModelInterface;

/**
 * Phalcon\Mvc\Model\Validator\Uniqueness
 *
 * Validates that a field or a combination of a set of fields are not
 * present more than once in the existing records of the related table
 *
 *<code>
 *use Phalcon\Mvc\Model\Validator\Uniqueness as Uniqueness;
 *
 *class Subscriptors extends Phalcon\Mvc\Model
 *{
 *
 *  public function validation()
 *  {
 *      $this->validate(new Uniqueness(array(
 *          'field' => 'email'
 *      )));
 *      if ($this->validationHasFailed() == true) {
 *          return false;
 *      }
 *  }
 *
 *}
 *</code>
 *
 * @see https://github.com/phalcon/cphalcon/blob/1.2.6/ext/mvc/model/validator/uniqueness.c
 */
class Uniqueness extends Validator implements ValidatorInterface
{
	/**
	 * Executes the validator
	 *
	 * @param \Phalcon\Mvc\ModelInterface $record
	 * @return boolean
	 * @throws Exception
	 */
	public function validate($record)
	{
		if(is_object($record) === false ||
			$record instanceof ModelInterface === false) {
			throw new Exception('Invalid parameter type.');
		}

		$field = $this->getOption('field');
		$dependency_injector = $record->getDi();
		$meta_data = $dependency_injector->getShared('modelsManager');

		//PostgreSQL check if the compared constant has the same type as the column, so we
		//make cast to the data passed to match those column types
		$bind_types = array();
		$bid_data_types = $meta_data->getBindTypes($record);
		if(isset($GLOBALS['_PHALCON_ORM_COLUMN_RENAMING']) === true &&
			$GLOBALS['_PHALCON_ORM_COLUMN_RENAMING'] === true) {
			$column_map = $meta_data->getReverseColumnMap($record);
		} else {
			$column_map = array();
		}

		$conditions = array();
		$bind_params = array();
		$number = 0;

		if(is_array($field) === true) {
			//The field can be an arary of values
			foreach($field as $compose_field) {
				//The reversed column map is used in the case to get the real column name
				if(is_array($column_map) === true) {
					if(isset($column_map[$compose_field]) === true) {
						$column_field = $column_map[$compose_field];
					} else {
						throw new Exception("Column '".$compose_field.'" isn\'t part of the column map');
					}
				} else {
					$column_field = $compose_field;
				}

				//Some database system require that we pass the values using bind casting
				if(isset($bind_data_types[$column_field]) === false) {
					throw new Exception("Column '".$column_field.'" isn\'t part of the table columns');
				}

				//The attribute could be "protected" so we read using "readattribute"
				$value = $record->readattribute($compose_field);
				$conditions[] = '['.$compose_field.'] = ?'.$number;
				$bind_params[] = $value;
				$bind_types[] = $bind_data_types[$column_field];
				$number++;
			}
		} else {
			//The reversed column map is used in this case to get the real column name
			if(is_array($column_map) === true) {
				if(isset($column_map[$field]) === true) {
					$column_field = $column_map[$field];
				} else {
					throw new Exception("Column '".$field.'" isn\'t part of the column map');
				}
			} else {
				$column_field = $field;
			}

			//Some database systems require that we pass the values using bind casting
			if(isset($bind_data_types[$column_field]) === false) {
				throw new Exception("Column '".$column_field.'" isn\'t part of the table columns');
			}

			//We're checking the uniqueness with only one field
			$value = $record->readAttribute($field);
			$conditions[] = '['.$field.'] = ?0';
			$bind_params[] = $value;
			$bind_types[] = $bind_data_types[$column_field];
			$number++;
		}

		//If the operation is update, there must be values in the object
		if($record->getOperationMade() === 2) {
			//We build a query with the primary key attributes
			if(isset($GLOBALS['_PHALCON_ORM_COLUMN_RENAMING']) === true &&
				$GLOBALS['_PHALCON_ORM_COLUMN_RENAMING'] === true) {
				$column_map = $meta_data->getColumnMap($record);
			} else {
				$column_map = null;
			}

			$primary_fields = $meta_data->getPrimaryKeyAttributes($record);
			foreach($primary_fields as $primary_field) {
				if(isset($bind_data_types[$primary_field]) === false) {
					throw new Exception("Column '".$primary_field.'" isn\'t part of the table columns');
				}

				//Rename the column if there is a column map
				if(is_array($column_map) === true) {
					if(isset($column_map[$primary_field]) === true) {
						$attribute_field = $column_map[$primary_field];
					} else {
						throw new Exception("Column '".$primary_field.'" isn\'t part of the column map');
					}
				} else {
					$attribute_field = $primary_field;
				}

				//Create a condition based on the renamed primary key
				$value = $record->readAttribute($primary_field);

				$conditions[] = '['.$attribute_field.'] <> ?'.$number;
				$bind_params[] = $value;
				$bind_types[] = $bind_data_types[$primary_field];
				$number++;
			}
		}

		$join_conditions = implode(' AND ', $conditions);

		//We don't trust the user, so we pass the parameters as bound parameters
		$params = array('di' => $dependency_injector, 'conditions' => $join_conditions, 'bind' => $bind_params, 'bindTypes' => $bind_types);
		$class_name = get_class($record);

		//Check using a standard count
		$number = $class_name::count($params);
		if($number !== 0) {
			//Check if the developer has defined a custom message
			$message = $this->getOption('message');
			if(isset($message) === false) {
				if(is_array($field) === true) {
					$message = "Value of fields: '".implode(', ', $field)."' are already present in another record"; //@note sic!
				} else {
					$message = "Value of field: '".$field."' is already present in another record";
				}
			}

			//Append the message to thew validator
			$this->appendMessage($message, $field, 'Unique');
			return false;
		}

		return true;
	}
}