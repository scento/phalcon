<?php
/**
 * Line Formatter
 *
 * @author Andres Gutierrez <andres@phalconphp.com>
 * @author Eduar Carvajal <eduar@phalconphp.com>
 * @author Wenzel Pünter <wenzel@phelix.me>
 * @version 1.2.6
 * @package Phalcon
*/
namespace Phalcon\Logger\Formatter;

use \Phalcon\Logger\Formatter,
	\Phalcon\Logger\FormatterInterface,
	\Phalcon\Logger\Exception;

/**
 * Phalcon\Logger\Formatter\Line
 *
 * Formats messages using an one-line string
 * 
 * @see https://github.com/phalcon/cphalcon/blob/1.2.6/ext/logger/formatter/line.c
 */
class Line extends Formatter implements FormatterInterface
{
	/**
	 * Date Format
	 * 
	 * @var string
	 * @access protected
	*/
	protected $_dateFormat = 'D, d M y H:i:s O';

	/**
	 * Format
	 * 
	 * @var string
	 * @access protected
	*/
	protected $_format = '[%date%][%type%] %message%';

	/**
	 * \Phalcon\Logger\Formatter\Line construct
	 *
	 * @param string|null $format
	 * @param string|null $dateFormat
	 * @throws Exception
	 */
	public function __construct($format = null, $dateFormat = null)
	{
		if(is_string($format) === true) {
			$this->_format = $format;
		} elseif(is_null($format) === false) {
			throw new Exception('Invalid parameter type.');
		}
	}

	/**
	 * Set the log format
	 *
	 * @param string $format
	 * @throws Excepiton
	 */
	public function setFormat($format)
	{
		if(is_string($format) === false) {
			throw new Exception('Invalid parameter type.');
		}

		$this->_format = $format;
	}

	/**
	 * Returns the log format
	 *
	 * @return string
	 */
	public function getFormat()
	{
		return $this->_format;
	}

	/**
	 * Sets the internal date format
	 *
	 * @param string $date
	 * @throws Exception
	 */
	public function setDateFormat($date)
	{
		if(is_string($date) === false) {
			throw new Exception('Invalid parameter type.');
		}

		$this->_dateFormat = $date;
	}

	/**
	 * Returns the internal date format
	 *
	 * @return string
	 */
	public function getDateFormat()
	{
		return $this->_dateFormat;
	}

	/**
	 * Applies a format to a message before sent it to the internal log
	 *
	 * @param string $message
	 * @param int $type
	 * @param int $timestamp
	 * @return string
	 * @throws Exception
	 */
	public function format($message, $type, $timestamp)
	{
		/* Type check */
		if(is_string($message) === false ||
			is_int($type) === false ||
			is_int($timestamp) === false) {
			throw new Exception('Invalid parameter type.');
		}

		/* Format */
		$format = $this->_format;

		if(strpos($format, '%date%') !== false) {
			$format = str_replace('%date%', date($this->_dateFormat, $timestamp),
				$format);
		}

		if(strpos($format, '%type%') !== false) {
			$format = str_replace('%type%', $this->getTypeString($type), $format);
		}

		return str_replace('%message%', $message, $format).\PHP_EOL;
	}
}