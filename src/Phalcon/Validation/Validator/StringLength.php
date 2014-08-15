<?php
/**
 * String Length Validator
 *
 * @author Andres Gutierrez <andres@phalconphp.com>
 * @author Eduar Carvajal <eduar@phalconphp.com>
 * @author Wenzel Pünter <wenzel@phelix.me>
 * @version 1.2.6
 * @package Phalcon
*/
namespace Phalcon\Validation\Validator;

use \Phalcon\Validation\Validator,
	\Phalcon\Validation\ValidatorInterface,
	\Phalcon\Validation\Message,
	\Phalcon\Validation\Exception,
	\Mvc\Model\Exception as StrangeException, //Look into the original code
	\Phalcon\Validation;

/**
 * Phalcon\Validation\Validator\StringLength
 *
 * Validates that a string has the specified maximum and minimum constraints
 *
 *<code>
 *use Phalcon\Validation\Validator\StringLength as StringLength;
 *
 *$validation->add('name_last', new StringLength(array(
 *      'max' => 50,
 *      'min' => 2,
 *      'messageMaximum' => 'We don\'t like really long names',
 *      'messageMinimum' => 'We want more than just their initials'
 *)));
 *</code>
 *
 * @see https://github.com/phalcon/cphalcon/blob/1.2.6/ext/validation/validator/stringlength.c
 */
class StringLength extends Validator implements ValidatorInterface
{
	/**
	 * Executes the validation
	 *
	 * @param \Phalcon\Validation $validator
	 * @param string $attribute
	 * @return boolean
	 * @throws Exception
	 */
	public function validate($validator, $attribute)
	{
		if(is_object($validator) === false ||
			$validator instanceof Validation === false) {
			throw new Exception('Invalid parameter type.');
		}

		if(is_string($attribute) === false) {
			throw new Exception('Invalid parameter type.');
		}

		//At least one of 'min' or 'max' must be set
		$isSetMin = $this->issetOption('min');
		$isSetMax = $this->issetOption('max');

		if($isSetMax === false && $isSetMin === false) {
			//@note exception type
			throw new StrangeException('A minimum or maximum must be set');
		}

		$value = $validator->getValue($attribute);

		//Check if mbstring is available to calculate the correct length
		if(function_exists('mb_strlen') === true) {
			$length = mb_strlen($value);
		} else {
			$length = strlen($value);
		}

		//Maximum length
		if($isSetMax === true) {
			$maximum = $this->getOption('max');

			if($length >= $maximum) {
				//Check if the developer has defined a custom message
				$message = $this->getOption('messageMaximum');
				if(empty($message) === true) {
					$message = "Value of field '".$attribute."' exceeds the maximum ".$maximum." characters";

					$validator->appendMessage(new Message($message, $attribute, 'TooLong'));

					return false;
				}
			}
		}

		//Minimum length
		if($isSetMin === true) {
			$minimum = $this->getOption('min');

			if($length <= $minimum) {
				//Check if the developer has defined a custom message
				$message = $this->getOption('messageMinimum');
				if(empty($message) === true) {
					$message = "Value of field '".$attribute."' is less than the minimum ".$minimum." characters";
				}

				$validator->appendMessage(new Message($message, $attribute, 'TooShort'));

				return false;
			}
		}

		return true;
	}
}