<?php
/**
 * Event
 *
 * @author Andres Gutierrez <andres@phalconphp.com>
 * @author Eduar Carvajal <eduar@phalconphp.com>
 * @author Wenzel Pünter <wenzel@phelix.me>
 * @version 1.2.6
 * @package Phalcon
*/
namespace Phalcon\Events;

use \Phalcon\Events\Exception;

/**
 * Phalcon\Events\Event
 *
 * This class offers contextual information of a fired event in the EventsManager
 * 
 * @see https://github.com/phalcon/cphalcon/blob/1.2.6/ext/events/event.c
 */
class Event 
{
	/**
	 * Type
	 * 
	 * @var null|string
	 * @access protected
	*/
	protected $_type;

	/**
	 * Source
	 * 
	 * @var null|object
	 * @access protected
	*/
	protected $_source;

	/**
	 * Data
	 * 
	 * @var mixed
	 * @access protected
	*/
	protected $_data;

	/**
	 * Stopped
	 * 
	 * @var boolean
	 * @access protected
	*/
	protected $_stopped = false;

	/**
	 * Cancelable
	 * 
	 * @var boolean
	 * @access protected
	*/
	protected $_cancelable = true;

	/**
	 * \Phalcon\Events\Event constructor
	 *
	 * @param string $type
	 * @param object $source
	 * @param mixed|null $data
	 * @param boolean|null $cancelable
	 * @throws Exception
	 */
	public function __construct($type, $source, $data = null, $cancelable = null)
	{
		if(is_string($type) === false) {
			throw new Exception('Invalid parameter type.');
		}

		if(is_object($source) === false) {
			throw new Exception('Invalid parameter type.');
		}

		if(is_null($cancelable) === true) {
			$cancelable = true;
		} elseif(is_bool($cancelable) === false) {
			throw new Exception('Invalid parameter type.');
		}

		$this->_type = $type;
		$this->_source = $source;
		$this->_data = $data;
		$this->_cancelable = $cancelable;
	}

	/**
	 * Set the event's type
	 *
	 * @param string $eventType
	 * @throws Exception
	 */
	public function setType($eventType)
	{
		if(is_string($eventType) === false) {
			throw new Exception('Invalid parameter type.');
		}

		$this->_type = $eventType;
	}

	/**
	 * Returns the event's type
	 *
	 * @return string
	 */
	public function getType()
	{
		return $this->_type;
	}

	/**
	 * Returns the event's source
	 *
	 * @return object
	 */
	public function getSource()
	{
		return $this->_source;
	}

	/**
	 * Set the event's data
	 *
	 * @param mixed $data
	 */
	public function setData($data)
	{
		$this->_data = $data;
	}

	/**
	 * Returns the event's data
	 *
	 * @return mixed
	 */
	public function getData()
	{
		return $this->_data;
	}

	/**
	 * Sets if the event is cancelable
	 *
	 * @param boolean $cancelable
	 * @throws Exception
	 */
	public function setCancelable($cancelable)
	{
		if(is_bool($cancelable) === false) {
			throw new Exception('Invalid parameter type.');
		}

		$this->_cancelable = $cancelable;
	}

	/**
	 * Check whether the event is cancelable
	 *
	 * @return boolean
	 */
	public function getCancelable()
	{
		return $this->_cancelable;
	}

	/**
	 * Stops the event preventing propagation
	 * 
	 * @throws Exception
	 */
	public function stop()
	{
		if($this->_cancelable === true) {
			$this->_stopped = true;
		} else {
			throw new Exception('Trying to cancel a non-cancelable event');
		}
	}

	/**
	 * Check whether the event is currently stopped
	 */
	public function isStopped()
	{
		return $this->_stopped;
	}
}