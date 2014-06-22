<?php
/**
 * Item
 *
 * @author Andres Gutierrez <andres@phalconphp.com>
 * @author Eduar Carvajal <eduar@phalconphp.com>
 * @author Wenzel Pünter <wenzel@phelix.me>
 * @version 1.2.6
 * @package Phalcon
*/
namespace Phalcon\Logger;

use \Phalcon\Logger\Exception;

/**
 * Phalcon\Logger\Item
 *
 * Represents each item in a logging transaction
 *
 * @see https://github.com/phalcon/cphalcon/blob/1.2.6/ext/logger/item.c
 */
class Item
{
	/**
	 * Type
	 * 
	 * @var null|int
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
	 * Time
	 * 
	 * @var null|int
	 * @access protected
	*/
	protected $_time;

	/**
	 * \Phalcon\Logger\Item constructor
	 *
	 * @param string $message
	 * @param integer $type
	 * @param integer $time
	 * @throws Exception
	 */
	public function __construct($message, $type, $time = null)
	{
		if(is_string($message) === false || is_int($type) === false) {
			throw new Exception('Invalid parameter type.');
		}

		if(is_null($time) === true) {
			$time = 0;
		} elseif(is_int($time) === false) {
			throw new Exception('Invalid parameter type.');
		}

		$this->_message = $message;
		$this->_type = $type;
		$this->_time = $time;
	}

	/**
	 * Returns the message
	 *
	 * @return string
	 */
	public function getMessage()
	{
		return $this->_message;
	}

	/**
	 * Returns the log type
	 *
	 * @return integer
	 */
	public function getType()
	{
		return $this->_type;
	}

	/**
	 * Returns log timestamp
	 *
	 * @return integer
	 */
	public function getTime()
	{
		return $this->_time;
	}
}