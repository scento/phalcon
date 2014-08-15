<?php
/**
 * PresenceOf Validator
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
 * Phalcon\Mvc\Model\Validator\PresenceOf
 *
 * Allows to validate if a filed have a value different of null and empty string ("")
 *
 *<code>
 *use Phalcon\Mvc\Model\Validator\PresenceOf;
 *
 *class Subscriptors extends Phalcon\Mvc\Model
 *{
 *
 *  public function validation()
 *  {
 *      $this->validate(new PresenceOf(array(
 *          'field' => 'name',
 *          'message' => 'The name is required'
 *      )));
 *      if ($this->validationHasFailed() == true) {
 *          return false;
 *      }
 *  }
 *
 *}
 *</code>
 *
 * @see https://github.com/phalcon/cphalcon/blob/1.2.6/ext/mvc/model/validator/presenceof.c
 */
class PresenceOf extends Validator implements ValidatorInterface
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

		$fieldName = $this->getOption('field');
		if(is_string($fieldName) === false) {
			throw new Exception('Field name must be a string');
		}

		//A value is numm when it is identical to null or a empty string
		$value = $record->readAttribute($fieldName);

		if(empty($value) === true) {
			//Check if the developer has defined a custom message
			$message = $this->getOption('message');
			if(isset($message) === false) {
				$message = "'".$fieldName."' is required";
			}

			$this->appendMessage($message, $fieldName, 'PresenceOf');
			return false;
		}

		return true;
	}
}