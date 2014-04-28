<?php
/**
 * Message
 *
 * @author Andres Gutierrez <andres@phalconphp.com>
 * @author Eduar Carvajal <eduar@phalconphp.com>
 * @author Wenzel Pünter <wenzel@phelix.me>
 * @version 1.2.6
 * @package Phalcon
*/
namespace Phalcon\Validation;

use \Phalcon\Validation\Exception;

/**
 * Phalcon\Validation\Message
 *
 * Encapsulates validation info generated in the validation process
 * 
 * @see https://github.com/phalcon/cphalcon/blob/1.2.6/ext/validation/message.c
 */
class Message
{
	/**
	 * Type
	 * 
	 * @var null|string
	 * @access protected
	*/
	protected $_type;

	/**
	 * Message
	 * 
	 * @var null|string
	 * @access protected
	*/
	protected $_message;

	/**
	 * Field
	 * 
	 * @var null|string
	 * @access protected
	*/
	protected $_field;

	/**
	 * \Phalcon\Validation\Message constructor
	 *
	 * @param string $message
	 * @param string|null $field
	 * @param string|null $type
	 * @throws Exception
	 */
	public function __construct($message, $field = null, $type = null)
	{
		if(is_string($message) === false) {
			throw new Exception('Invalid parameter type.');
		}

		if(is_string($field) === false && is_null($field) === false) {
			throw new Exception('Invalid parameter type.');
		}

		if(is_string($type) === false && is_null($type) === false) {
			throw new Exception('Invalid parameter type.');
		}

		$this->_message = $message;
		$this->_field = $field;
		$this->_type = $type;
	}

	/**
	 * Sets message type
	 *
	 * @param string $type
	 * @return \Phalcon\Mvc\Model\Message
	 * @throws Exception
	 */
	public function setType($type)
	{
		if(is_string($type) === false) {
			throw new Exception('Invalid parameter type.');
		}

		$this->_type = $type;
	}

	/**
	 * Returns message type
	 *
	 * @return string|null
	 */
	public function getType()
	{
		return $this->_type;
	}

	/**
	 * Sets verbose message
	 *
	 * @param string $message
	 * @return \Phalcon\Mvc\Model\Message
	 * @throws Exception
	 */
	public function setMessage($message)
	{
		if(is_string($message) === false) {
			throw new Exception('Invalid parameter type.');
		}

		$this->_message = $message;
	}

	/**
	 * Returns verbose message
	 *
	 * @return string
	 */
	public function getMessage()
	{
		return $this->_message;
	}

	/**
	 * Sets field name related to message
	 *
	 * @param string $field
	 * @return \Phalcon\Mvc\Model\Message
	 * @throws Exception
	 */
	public function setField($field)
	{
		if(is_string($field) === false) {
			throw new Exception('Invalid parameter type.');
		}

		$this->_field = $field;
	}

	/**
	 * Returns field name related to message
	 *
	 * @return string|null
	 */
	public function getField()
	{
		return $this->_field;
	}

	/**
	 * Magic __toString method returns verbose message
	 *
	 * @return string
	 */
	public function __toString()
	{
		return $this->_message;
	}

	/**
	 * Magic __set_state helps to recover messsages from serialization
	 *
	 * @param array $message
	 * @return \Phalcon\Mvc\Model\Message
	 * @throws Exception
	 */
	public static function __set_state($message)
	{
		if(is_array($message) === false) {
			throw new Exception('Invalid parameter type.');
		}

		return new Message($message['_message'], $message['_field'], $message['_type']);
	}
}