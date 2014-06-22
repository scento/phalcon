<?php
/**
 * Logging Adapter
 *
 * @author Andres Gutierrez <andres@phalconphp.com>
 * @author Eduar Carvajal <eduar@phalconphp.com>
 * @author Wenzel Pünter <wenzel@phelix.me>
 * @version 1.2.6
 * @package Phalcon
*/
namespace Phalcon\Logger;

use \Phalcon\Logger\Exception,
	\Phalcon\Logger\FormatterInterface,
	\Phalcon\Logger\Item,
	\Phalcon\Logger;

/**
 * Phalcon\Logger\Adapter
 *
 * Base class for Phalcon\Logger adapters
 * 
 * @see https://github.com/phalcon/cphalcon/blob/1.2.6/ext/logger/adapter.c
 */
abstract class Adapter
{
	/**
	 * Transaction
	 * 
	 * @var boolean
	 * @access protected
	*/
	protected $_transaction = false;

	/**
	 * Queue
	 * 
	 * @var null|array
	 * @access protected
	*/
	protected $_queue;

	/**
	 * Formatter
	 * 
	 * @var null|\Phalcon\Logger\FormatterInterface
	 * @access protected
	*/
	protected $_formatter;

	/**
	 * Log Level
	 * 
	 * @var int
	 * @access protected
	*/
	protected $_logLevel = 9;

	/**
	 * Filters the logs sent to the handlers that are less or equal than a specific level
	 *
	 * @param int $level
	 * @return \Phalcon\Logger\Adapter
	 * @throws Exception
	 */
	public function setLogLevel($level)
	{
		if(is_int($level) === false) {
			throw new Exception('The log level is not valid');
		}

		$this->_logLevel = $level;

		return $this;
	}

	/**
	 * Returns the current log level
	 *
	 * @return int
	 */
	public function getLogLevel()
	{
		return $this->_logLevel;
	}

	/**
	 * Sets the message formatter
	 *
	 * @param \Phalcon\Logger\FormatterInterface $formatter
	 * @return \Phalcon\Logger\Adapter
	 * @throws Exception
	 */
	public function setFormatter($formatter)
	{
		if(is_object($formatter) === false ||
			$formatter instanceof FormatterInterface === false) {
			throw new Exception('Invalid parameter type.');
		}

		$this->_formatter = $formatter;

		return $this;
	}

	/**
	 * Starts a transaction
	 *
	 * @return \Phalcon\Logger\Adapter
	 */
	public function begin()
	{
		$this->_transaction = true;

		return $this;
	}

	/**
	 * Commits the internal transaction
	 *
	 * @return \Phalcon\Logger\Adapter
	 * @throws Exception
	 */
	public function commit()
	{
		/* Set transaction state */
		if($this->_transaction === false) {
			throw new Exception('There is not active transaction');
		}

		$this->_transaction = false;

		/* Log queue data */
		if(is_array($this->_queue) === true) {
			foreach($this->_queue as $message) {
				//@note no interface validation
				$message_str = $message->getMessage();
				$type = $message->getType();
				$time = $message->getTime();
				$this->logInternal($message_str, $type, $time);
			}
		}

		/* Unset queue */
		$this->_queue = array();

		return $this;
	}

	/**
	 * Rollbacks the internal transaction
	 *
	 * @return \Phalcon\Logger\Adapter
	 * @throws Exception
	 */
	public function rollback()
	{
		if($this->_transaction === false) {
			throw new Exception('There is no active transaction');
		}

		$this->_transaction = false;
		$this->_queue = array();

		return $this;
	}

	/**
	 * Sends/Writes an emergence message to the log
	 *
	 * @param string $message
	 * @return \Phalcon\Logger\Adapter
	 */
	public function emergence($message)
	{
		$this->log($message, Logger::EMERGENCE);

		return $this;
	}

	/**
	 * Sends/Writes a debug message to the log
	 *
	 * @param string $message
	 * @return \Phalcon\Logger\Adapter
	 */
	public function debug($message)
	{
		$this->log($message, Logger::DEBUG);

		return $this;
	}

	/**
	 * Sends/Writes an error message to the log
	 *
	 * @param string $message
	 * @return \Phalcon\Logger\Adapter
	 */
	public function error($message)
	{
		$this->log($message, Logger::ERROR);

		return $this;
	}

	/**
	 * Sends/Writes an info message to the log
	 *
	 * @param string $message
	 * @return \Phalcon\Logger\Adapter
	 */
	public function info($message)
	{
		$this->log($message, Logger::INFO);

		return $this;
	}

	/**
	 * Sends/Writes a notice message to the log
	 *
	 * @param string $message
	 * @return \Phalcon\Logger\Adapter
	 */
	public function notice($message)
	{
		$this->log($message, Logger::NOTICE);

		return $this;
	}

	/**
	 * Sends/Writes a warning message to the log
	 *
	 * @param string $message
	 * @return \Phalcon\Logger\Adapter
	 */
	public function warning($message)
	{
		$this->log($message, Logger::WARNING);

		return $this;
	}

	/**
	 * Sends/Writes an alert message to the log
	 *
	 * @param string $message
	 * @return \Phalcon\Logger\Adapter
	 */
	public function alert($message)
	{
		$this->log($message, Logger::ALERT);

		return $this;
	}

	/**
	 * Logs messages to the internal logger. Appends messages to the log
	 *
	 * @param string $message
	 * @param int|null $type
	 * @return \Phalcon\Logger\Adapter
	 * @throws Exception
	 */
	public function log($message, $type = null)
	{
		if(is_string($message) === false) {
			throw new Exception('Invalid parameter type.');
		}

		if(is_null($type) === true) {
			$type = 7;
		} elseif(is_int($type) === false) {
			throw new Exception('Invalid parameter type.');
		}

		$timestamp = time();

		if($this->_logLevel >= $type) {
			if($this->_transaction === true) {
				$this->_queue[] = new Item($message, $type, $timestamp);
			} else {
				$this->logInternal($message, $type, $timestamp);
			}
		}

		return $this;
	}
}