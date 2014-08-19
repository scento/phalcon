<?php
/**
 * Syslog Adapter
 *
 * @author Andres Gutierrez <andres@phalconphp.com>
 * @author Eduar Carvajal <eduar@phalconphp.com>
 * @author Wenzel Pünter <wenzel@phelix.me>
 * @version 1.2.6
 * @package Phalcon
*/
namespace Phalcon\Logger\Adapter;

use \Phalcon\Logger\Adapter,
	\Phalcon\Logger\AdapterInterface,
	\Phalcon\Logger\Exception;

/**
 * Phalcon\Logger\Adapter\Syslog
 *
 * Sends logs to the system logger
 *
 *<code>
 *	$logger = new \Phalcon\Logger\Adapter\Syslog("ident", array(
 *		'option' => LOG_NDELAY,
 *		'facility' => LOG_MAIL
 *	));
 *	$logger->log("This is a message");
 *	$logger->log("This is an error", \Phalcon\Logger::ERROR);
 *	$logger->error("This is another error");
 *</code>
 * 
 * @see https://github.com/phalcon/cphalcon/blob/1.2.6/ext/logger/adapter/syslog.c
 */
class Syslog extends Adapter implements AdapterInterface
{
	/**
	 * Opened
	 * 
	 * @var boolean
	 * @access protected
	*/
	protected $_opened = false;

	/**
	 * \Phalcon\Logger\Adapter\Syslog constructor
	 *
	 * @param string $name
	 * @param array|null $options
	 */
	public function __construct($name, $options = null)
	{
		if(is_string($name) === true) {

			//Open the log in LOG_ODELAY mode
			$option = 4;

			//By default the facility is LOG_USER
			$facility = 8;

			if(is_array($options) === true) {

				if(isset($options['option']) === true) {
					$option = $options['option'];
				}

				if(isset($options['facility']) === true) {
					$facility = $options['facility'];
				}
			}

			//@note no return value check
			openlog($name, $option, $facility);
			$this->_opened = true;
		}
	}

	/**
	 * Returns the internal formatter
	 *
	 * @return \Phalcon\Logger\FormatterInterface
	 */
	public function getFormatter()
	{
		if(is_object($this->_formatter) === false) {
			$this->_formatter = new \Phalcon\Logger\Formatter\Syslog();
		}

		return $this->_formatter;
	}

	/**
	 * Writes the log to the stream itself
	 *
	 * @param string $message
	 * @param int $type
	 * @param int $time
	 * @throws Exception
	 */
	public function logInternal($message, $type, $time)
	{
		if(is_string($message) === false ||
			is_int($type) === false ||
			is_int($time) === false) {
			throw new Exception('Invalid parameter type.');
		}

		$appliedFormat = $this->getFormatter()->format($message, $type, $time);
		if(is_array($appliedFormat) === false) {
			throw new Exception('The formatted message is not valid');
		}

		//@note no return value check
		syslog($appliedFormat[0], $appliedFormat[1]);
	}

	/**
	 * Closes the logger
	 *
	 * @return null
	 */
	public function close()
	{
		//@note we don't set $this->_opened = false!
		if($this->_opened === true) {
			//@note no return value check
			closelog();
		}

		//@note we don't return a boolean
	}
}