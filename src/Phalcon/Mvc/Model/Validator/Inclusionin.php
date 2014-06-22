<?php
/**
 * Inclusion In Validator
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
 * Phalcon\Mvc\Model\Validator\InclusionIn
 *
 * Check if a value is included into a list of values
 *
 *<code>
 *	use Phalcon\Mvc\Model\Validator\InclusionIn as InclusionInValidator;
 *
 *	class Subscriptors extends Phalcon\Mvc\Model
 *	{
 *
 *		public function validation()
 *		{
 *			$this->validate(new InclusionInValidator(array(
 *				'field' => 'status',
 *				'domain' => array('A', 'I')
 *			)));
 *			if ($this->validationHasFailed() == true) {
 *				return false;
 *			}
 *		}
 *
 *	}
 *</code>
 * 
 * @see https://github.com/phalcon/cphalcon/blob/1.2.6/ext/mvc/model/validator/inclusionin.c
 */
class Inclusionin extends Validator implements ValidatorInterface
{
	/**
	 * Executes validator
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
		
		$field_name = $this->getOption('field');
		if(is_string($field_name) === false) {
			throw new Exception('Field name must be a string');
		}

		//The 'domain' option must be a valid array of not allowed values
		if($this->isSetOption('domain') === false) {
			throw new Exception("The option 'domain' is required for this validator");
		}

		$domain = $this->getOption('domain');
		if(is_array($domain) === false) {
			throw new Exception("Option 'domain' must be an array");
		}

		$value = $record->readAttribute($field_name);

		//We check if the value is contained in the array using "in_array"
		if(in_array($value, $domain) === false) {
			//Check if the developer has defined a custom message
			$message = $this->getOption('message');
			if(isset($message) === false) {
				$message = "Value of field '".$field_name."' must not be part of list: ".implode(', ', $domain);
			}

			$this->appendMessage($message, $field_name, 'Inclusion');
			return false;
		}

		return true;
	}
}