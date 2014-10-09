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
		$dependencyInjector = $record->getDi();
		$metaData = $dependencyInjector->getShared('modelsMetadata');

		//PostgreSQL check if the compared constant has the same type as the column, so we
		//make cast to the data passed to match those column types
		$bindTypes = array();
		$bindDataTypes = $metaData->getBindTypes($record);
		if(isset($GLOBALS['_PHALCON_ORM_COLUMN_RENAMING']) === true &&
			$GLOBALS['_PHALCON_ORM_COLUMN_RENAMING'] === true) {
			$columnMap = $metaData->getReverseColumnMap($record);
		} else {
			$columnMap = array();
		}

		$conditions = array();
		$bindParams = array();
		$number = 0;

		if(is_array($field) === true) {
			//The field can be an arary of values
			foreach($field as $composeField) {
				//The reversed column map is used in the case to get the real column name
				if(is_array($columnMap) === true) {
					if(isset($columnMap[$composeField]) === true) {
						$columnField = $columnMap[$composeField];
					} else {
						throw new Exception("Column '".$composeField.'" isn\'t part of the column map');
					}
				} else {
					$columnField = $composeField;
				}

				//Some database system require that we pass the values using bind casting
				if(isset($bindDataTypes[$columnField]) === false) {
					throw new Exception("Column '".$columnField.'" isn\'t part of the table columns');
				}

				//The attribute could be "protected" so we read using "readattribute"
				$value = $record->readattribute($composeField);
				$conditions[] = '['.$composeField.'] = ?'.$number;
				$bindParams[] = $value;
				$bindTypes[] = $bindDataTypes[$columnField];
				$number++;
			}
		} else {
			//The reversed column map is used in this case to get the real column name
			if(is_array($columnMap) === true) {
				if(isset($columnMap[$field]) === true) {
					$columnField = $columnMap[$field];
				} else {
					throw new Exception("Column '".$field.'" isn\'t part of the column map');
				}
			} else {
				$columnField = $field;
			}

			//Some database systems require that we pass the values using bind casting
			if(isset($bindDataTypes[$columnField]) === false) {
				throw new Exception("Column '".$columnField.'" isn\'t part of the table columns');
			}

			//We're checking the uniqueness with only one field
			$value = $record->readAttribute($field);
			$conditions[] = '['.$field.'] = ?0';
			$bindParams[] = $value;
			$bindTypes[] = $bindDataTypes[$columnField];
			$number++;
		}

		//If the operation is update, there must be values in the object
		if($record->getOperationMade() === 2) {
			//We build a query with the primary key attributes
			if(isset($GLOBALS['_PHALCON_ORM_COLUMN_RENAMING']) === true &&
				$GLOBALS['_PHALCON_ORM_COLUMN_RENAMING'] === true) {
				$columnMap = $metaData->getColumnMap($record);
			} else {
				$columnMap = null;
			}

			$primaryFields = $metaData->getPrimaryKeyAttributes($record);
			foreach($primaryFields as $primaryField) {
				if(isset($bindDataTypes[$primaryField]) === false) {
					throw new Exception("Column '".$primaryField.'" isn\'t part of the table columns');
				}

				//Rename the column if there is a column map
				if(is_array($columnMap) === true) {
					if(isset($columnMap[$primaryField]) === true) {
						$attributeField = $columnMap[$primaryField];
					} else {
						throw new Exception("Column '".$primaryField.'" isn\'t part of the column map');
					}
				} else {
					$attributeField = $primaryField;
				}

				//Create a condition based on the renamed primary key
				$value = $record->readAttribute($primaryField);

				$conditions[] = '['.$attributeField.'] <> ?'.$number;
				$bindParams[] = $value;
				$bindTypes[] = $bindDataTypes[$primaryField];
				$number++;
			}
		}

		$joinConditions = implode(' AND ', $conditions);

		//We don't trust the user, so we pass the parameters as bound parameters
		$params = array('di' => $dependencyInjector, 'conditions' => $joinConditions, 'bind' => $bindParams, 'bindTypes' => $bindTypes);
		$className = get_class($record);

		//Check using a standard count
		$number = $className::count($params);
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