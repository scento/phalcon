<?php
/**
 * Email Validator
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
	\Phalcon\Mvc\Model\Exception;

/**
 * Phalcon\Mvc\Model\Validator\Email
 *
 * Allows to validate if email fields has correct values
 *
 *<code>
 *	use Phalcon\Mvc\Model\Validator\Email as EmailValidator;
 *
 *	class Subscriptors extends Phalcon\Mvc\Model
 *	{
 *
 *		public function validation()
 *		{
 *			$this->validate(new EmailValidator(array(
 *				'field' => 'electronic_mail'
 *      	)));
 *      	if ($this->validationHasFailed() == true) {
 *				return false;
 *      	}
 *  	}
 *
 *	}
 *</code>
 *
 * @see https://github.com/phalcon/cphalcon/blob/1.2.6/ext/mvc/model/validator/email.c
 */
class Email extends Validator implements ValidatorInterface
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
		if(is_object($record) === false &&
			$record instanceof ModelInterface === false) {
			throw new Exception('Invalid parameter type.');
		}

		$field_name = $this->getOption('field');
		if(is_string($field_name) === false) {
			throw new Exception('Field name must be a string');
		}

		$value = $record->readAttribute($field_name);

		$regs = null;

		//We check if the email has a valid format using a regular expression
		$match_pattern = preg_match('/^([^\\x00-\\x20\\x22\\x28\\x29\\x2c\\x2e\\x3a-\\x3c\\x3e\\x40\\x5b-\\x5d\\x7f-\\xff]+|\\x22([^\\x0d\\x22\\x5c\\x80-\\xff]|\\x5c[\\x00-\\x7f])*\\x22)(\\x2e([^\\x00-\\x20\\x22\\x28\\x29\\x2c\\x2e\\x3a-\\x3c\\x3e\\x40\\x5b-\\x5d\\x7f-\\xff]+|\\x22([^\\x0d\\x22\\x5c\\x80-\\xff]|\\x5c[\\x00-\\x7f])*\\x22))*\\x40([^\\x00-\\x20\\x22\\x28\\x29\\x2c\\x2e\\x3a-\\x3c\\x3e\\x40\\x5b-\\x5d\\x7f-\\xff]+|\\x5b([^\\x0d\\x5b-\\x5d\\x80-\\xff]|\\x5c[\\x00-\\x7f])*\\x5d)(\\x2e([^\\x00-\\x20\\x22\\x28\\x29\\x2c\\x2e\\x3a-\\x3c\\x3e\\x40\\x5b-\\x5d\\x7f-\\xff]+|\\x5b([^\\x0d\\x5b-\\x5d\\x80-\\xff]|\\x5c[\\x00-\\x7f])*\\x5d))*$/', $value, $regs);

		if($match_pattern == true) {
			$invalid = ($regs[0] !== $value ? true : false);
		} else {
			$invalid = false;
		}

		if($invalid === true) {
			//Check if the developer has defined a custom message
			$message = $this->getOption('message');
			if(isset($message) === false) {
				$message = "Value of field '".$field_name."' must have a valid e-mail format";
			}

			$this->appendMessage($message, $field_name, 'Email');
			return false;
		}

		return true;
	}
}