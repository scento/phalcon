<?php
/**
 * Validation Failed
 *
 * @author Andres Gutierrez <andres@phalconphp.com>
 * @author Eduar Carvajal <eduar@phalconphp.com>
 * @author Wenzel Pünter <wenzel@phelix.me>
 * @version 1.2.6
 * @package Phalcon
*/
namespace Phalcon\Mvc\Model;

use \Phalcon\Mvc\Model\Exception;
use \Phalcon\Mvc\Model;

/**
 * Phalcon\Mvc\Model\ValidationFailed
 *
 * This exception is generated when a model fails to save a record
 * Phalcon\Mvc\Model must be set up to have this behavior
 *
 * @see https://github.com/phalcon/cphalcon/blob/1.2.6/ext/mvc/model/validationfailed.c
 */
class ValidationFailed extends Exception
{
    /**
     * Model
     *
     * @var null|\Phalcon\Mvc\Model
     * @access protected
    */
    protected $_model;

    /**
     * Messages
     *
     * @var null|array
     * @access protected
    */
    protected $_messages;

    /**
     * \Phalcon\Mvc\Model\ValidationFailed constructor
     *
     * @param \Phalcon\Mvc\Model $model
     * @param \Phalcon\Mvc\Model\Message[] $validationMessages
     * @throws Exception
     */
    public function __construct($model, $validationMessages)
    {
        if (is_object($model) === false ||
            $model instanceof Model === false ||
            is_array($validationMessages) === false ||
            empty($validationMessages) === true) {
            throw new Exception('Validation failed');
        }

        $message_str = $validationMessages[0]->getMessage();
        $this->_model = $model;
        $this->_messages = $validationMessages;
        parent::__construct($validationMessages[0]);
    }

    /**
     * Returns the complete group of messages produced in the validation
     *
     * @return \Phalcon\Mvc\Model\Message[]|null
     */
    public function getMessages()
    {
        return $this->_messages;
    }

    /**
     * Returns the model that generated the messages
     *
     * @return \Phalcon\Mvc\Model|null
     */
    public function getModel()
    {
        return $this->_model;
    }
}
