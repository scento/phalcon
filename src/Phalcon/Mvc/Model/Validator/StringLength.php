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
namespace Phalcon\Mvc\Model\Validator;

use \Phalcon\Mvc\Model\Validator;
use \Phalcon\Mvc\Model\ValidatorInterface;
use \Phalcon\Mvc\Model\Exception;
use \Phalcon\Mvc\ModelInterface;

/**
 * Phalcon\Mvc\Model\Validator\StringLength
 *
 * Simply validates specified string length constraints
 *
 *<code>
 *use Phalcon\Mvc\Model\Validator\StringLength as StringLengthValidator;
 *
 *class Subscriptors extends Phalcon\Mvc\Model
 *{
 *
 *  public function validation()
 *  {
 *      $this->validate(new StringLengthValidator(array(
 *          'field' => 'name_last',
 *          'max' => 50,
 *          'min' => 2,
 *          'messageMaximum' => 'We don\'t like really long names',
 *          'messageMinimum' => 'We want more than just their initials'
 *      )));
 *      if ($this->validationHasFailed() == true) {
 *          return false;
 *      }
 *  }
 *
 *}
 *</code>
 *
 * @see https://github.com/phalcon/cphalcon/blob/1.2.6/ext/mvc/model/validator/stringlength.c
 */
class StringLength extends Validator implements ValidatorInterface
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
        if (is_object($record) === false ||
            $record instanceof ModelInterface === false) {
            throw new Exception('Invalid parameter type.');
        }

        $field = $this->getOption('field');
        if (is_string($field) === false) {
            throw new Exception('Field name must be a string');
        }

        $issetMin = $this->isSetOption('min');
        $issetMax = $this->isSetOption('max');

        //At least one of 'min' or 'max' must be set
        if ($issetMin === false &&
            $issetMax === false) {
            throw new Exception('A minimum or maximum must be set');
        }

        $value = $record->readAttribute($field);

        //Check if mbstring is available to calculate the correct length
        if (function_exists('mb_strlen') === true) {
            $length = mb_strlen($value);
        } else {
            $length = strlen($value);
        }

        //Maximum length
        if ($issetMax === true) {
            $maximum = $this->getOption('max');
            if ($maximum < $length) {
                //Check if the developer has defined a custom message
                $message = $this->getOption('messageMaximum');
                if (isset($message) === false) {
                    $message = "Value of field '".$field."' exceeds the maximum ".$maximum.' characters';
                }

                $this->appendMessage($message, $field, 'TooLong');
                return false;
            }
        }

        //Minimum length
        if ($issetMin === true) {
            $minimum = $this->getOption('min');
            if ($length < $minimum) {
                //Check if the developer has defined a custom message
                $message = $this->getOption('messageMinimum');
                if (isset($message) === false) {
                    $message = "Value of field '".$field."' is less than the minimum ".$minimum.' characters';
                }

                $this->appendMessage($message, $field, 'TooShort');
                return false;
            }
        }

        return true;
    }
}
