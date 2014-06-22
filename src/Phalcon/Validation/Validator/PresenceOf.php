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
namespace Phalcon\Validation\Validator;

use \Phalcon\Validation\Validator,
	\Phalcon\Validation\ValidatorInterface,
	\Phalcon\Validation\Message,
	\Phalcon\Validation\Exception,
	\Phalcon\Validation;

/**
 * Phalcon\Validation\Validator\PresenceOf
 *
 * Validates that a value is not null or empty string
 *
 *<code>
 *use Phalcon\Validation\Validator\PresenceOf;
 *
 *$validator->add('name', new PresenceOf(array(
 *   'message' => 'The name is required'
 *)));
 *</code>
 * 
 * @see https://github.com/phalcon/cphalcon/blob/1.2.6/ext/validation/validator/presenceof.c
 */
class PresenceOf extends Validator implements ValidatorInterface
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

		$value = $validator->getValue($attribute);
		if(empty($value) === true) {
			$message = $this->getOption('message');

			if(empty($message) === true) {
				$message = $attribute.' is required';
			}

			$validator->appendMessage(new Message($message, $attribute, 'PresenceOf'));

			return false;
		}

		return true;
	}
}