<?php
/**
 * Multiple
 *
 * @author Andres Gutierrez <andres@phalconphp.com>
 * @author Eduar Carvajal <eduar@phalconphp.com>
 * @author Wenzel Pünter <wenzel@phelix.me>
 * @version 1.2.6
 * @package Phalcon
*/
namespace Phalcon\Logger;

use \Phalcon\Logger\Exception,
	\Phalcon\Logger\AdapterInterface,
	\Phalcon\Logger\FormatterInterface,
	\Phalcon\Logger;

/**
 * Phalcon\Logger\Multiple
 *
 * Handles multiples logger handlers
 * 
 * @see https://github.com/phalcon/cphalcon/blob/1.2.6/ext/logger/multiple.c
 */
class Multiple
{
	/**
	 * Loggers
	 * 
	 * @var null|array
	 * @access protected
	*/
	protected $_loggers;

	/**
	 * Formatter
	 * 
	 * @var null|\Phalcon\Logger\FormatterInterface
	 * @access protected
	*/
	protected $_formatter;

	/**
	 * Pushes a logger to the logger tail
	 *
	 * @param \Phalcon\Logger\AdapterInterface $logger
	 * @throws Exception
	 */
	public function push($logger)
	{
		if(is_object($logger) === false ||
			$logger instanceof AdapterInterface === false) {
			throw new Exception('The logger is invalid');
		}

		if(is_array($this->_loggers) === false) {
			$this->_loggers = array();
		}

		$this->_loggers[] = $logger;
	}

	/**
	 * Returns the registered loggers
	 *
	 * @return \Phalcon\Logger\AdapterInterface[]|null
	 */
	public function getLoggers()
	{
		return $this->_loggers;
	}

	/**
	 * Sets a global formatter
	 *
	 * @param \Phalcon\Logger\FormatterInterface $formatter
	 * @throws Exception
	 */
	public function setFormatter($formatter)
	{
		if(is_object($formatter) === false ||
			$formatter instanceof FormatterInterface === false) {
			throw new Exception('Invalid parameter type.');
		}

		if(is_array($this->_loggers) === true) {
			foreach($this->_loggers as $logger) {
				$logger->setFormatter($formatter);
			}
		}

		$this->_formatter = $formatter;
	}

	/**
	 * Returns a formatter
	 *
	 * @return \Phalcon\Logger\FormatterInterface|null
	 */
	public function getFormatter()
	{
		return $this->_formatter;
	}

	/**
	 * Sends a message to each registered logger
	 *
	 * @param string $message
	 * @param int|null $type
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

		if(is_array($this->_loggers) === true) {
			foreach($this->_loggers as $logger) {
				$logger->log($message, $type);
			}
		}
	}

	/**
	 * Sends/Writes an emergence message to the log
	 *
	 * @param string $message
	 */
	public function emergence($message)
	{
		$this->log($message, Logger::EMERGENCE);
	}

	/**
	 * Sends/Writes a debug message to the log
	 *
	 * @param string $message
	 */
	public function debug($message)
	{
		$this->log($message, Logger::DEBUG);
	}

	/**
	 * Sends/Writes an error message to the log
	 *
	 * @param string $message
	 */
	public function error($message)
	{
		$this->log($message, Logger::ERROR);
	}

	/**
	 * Sends/Writes an info message to the log
	 *
	 * @param string $message
	 */
	public function info($message)
	{
		$this->log($message, Logger::INFO);
	}

	/**
	 * Sends/Writes a notice message to the log
	 *
	 * @param string $message
	 */
	public function notice($message)
	{
		$this->log($message, Logger::NOTICE);
	}

	/**
	 * Sends/Writes a warning message to the log
	 *
	 * @param string $message
	 */
	public function warning($message)
	{
		$this->log($message, Logger::WARNING);
	}

	/**
	 * Sends/Writes an alert message to the log
	 *
	 * @param string $message
	 */
	public function alert($message)
	{
		$this->log($message, Logger::ALERT);
	}
}