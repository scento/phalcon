<?php
/**
 * URL Validator
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
 * Phalcon\Mvc\Model\Validator\Url
 *
 * Allows to validate if a field has a url format
 *
 *<code>
 *use Phalcon\Mvc\Model\Validator\Url as UrlValidator;
 *
 *class Posts extends Phalcon\Mvc\Model
 *{
 *
 *  public function validation()
 *  {
 *      $this->validate(new UrlValidator(array(
 *          'field' => 'source_url'
 *      )));
 *      if ($this->validationHasFailed() == true) {
 *          return false;
 *      }
 *  }
 *
 *}
 *</code>
 *
 * @see https://github.com/phalcon/cphalcon/blob/1.2.6/ext/mvc/model/validator/url.c
 */
class Url extends Validator implements ValidatorInterface
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

        $value = $record->readAttribute($field);

        //Filters the format using FILTER_VALIDATE_URL
        if (filter_var($value, 273) === false) {
            //Check if the developer has defined a custom message
            $message = $this->getOption('message');

            if (isset($message) === false) {
                $message = "'".$field."' does not have a valid url format";
            }

            $this->appendMessage($message, $field, 'Url');
            return false;
        }

        return true;
    }
}
