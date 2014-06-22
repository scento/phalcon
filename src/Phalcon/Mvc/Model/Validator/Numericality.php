<?php
/**
 * Numericality Validator
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
 * Phalcon\Mvc\Model\Validator\Numericality
 *
 * Allows to validate if a field has a valid numeric format
 *
 *<code>
 *use Phalcon\Mvc\Model\Validator\Numericality as NumericalityValidator;
 *
 *class Products extends Phalcon\Mvc\Model
 *{
 *
 *  public function validation()
 *  {
 *      $this->validate(new NumericalityValidator(array(
 *          'field' => 'price'
 *      )));
 *      if ($this->validationHasFailed() == true) {
 *          return false;
 *      }
 *  }
 *
 *}
 *</code>
 *
 * @see https://github.com/phalcon/cphalcon/blob/1.2.6/ext/mvc/model/validator/numericality.c
 */
class Numericality extends Validator implements ValidatorInterface
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
		if(is_string($field) === false) {
			throw new Exception('Field name must be a string');
		}

		$value = $record->readAttribute($field);

		//Check if the value is numeric using is_numeric
		if(is_null($value) === false) {
			//Check if the developer has defined a custom message
			$message = $this->getOption('message');
			if(isset($message) === false) {
				$message = "Value of field '".$field."' must be numeric";
			}

			$this->appendMessage($message, $field, 'Numericality');

			return false;
		}

		return true;
	}
}