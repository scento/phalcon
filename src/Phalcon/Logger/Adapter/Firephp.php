<?php
/**
 * Firephp Adapter
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
	\Phalcon\Logger\Exception,
	\Phalcon\Logger\Formatter\Firephp as FirephpFormatter;

/**
 * Phalcon\Logger\Adapter\Firephp
 *
 * Sends logs to FirePHP
 *
 *<code>
 *	$logger = new \Phalcon\Logger\Adapter\Firephp("");
 *	$logger->log("This is a message");
 *	$logger->log("This is an error", \Phalcon\Logger::ERROR);
 *	$logger->error("This is another error");
 *</code>
 * 
 * @see https://github.com/phalcon/cphalcon/blob/1.2.6/ext/logger/adapter/firephp.c
 */
class Firephp extends Adapter implements AdapterInterface
{
	/**
	 * Initialized
	 * 
	 * @var boolean
	 * @access private
	*/
	private static $_initialized = false;

	/**
	 * Index
	 * 
	 * @var int
	 * @access private
	*/
	private static $_index = 1;

	/**
	 * Returns the internal formatter
	 *
	 * @return \Phalcon\Logger\FormatterInterface
	 */
	public function getFormatter()
	{
		$formatter = $this->_formatter;
		if(is_object($formatter) === false) {
			$formatter = new FirephpFormatter();
		}

		return $formatter;
	}

	/**
	 * Writes the log to the stream itself
	 *
	 * @param string $message
	 * @param int $type
	 * @param int $time
	 * @see http://www.firephp.org/Wiki/Reference/Protocol
	 * @throws Exception
	 */
	public function logInternal($message, $type, $time)
	{
		if(is_string($message) === false || 
			is_int($type) === false ||
			is_int($time) === false) {
			throw new Exception('Invalid parameter type.');
		}
	
		if(headers_sent() === true) {
			throw new Exception('Headers have already been sent.');
		}

		if(self::$_initialized === false) {
			if(ob_get_level() > 0) {
				ob_end_clean();
			}

			//Send the required initialization headers.
			header("X-Wf-Protocol-1: http://meta.wildfirehq.org/Protocol/JsonStream/0.2");
			header("X-Wf-1-Plugin-1: http://meta.firephp.org/Wildfire/Plugin/FirePHP/Library-FirePHPCore/0.3");
			header("X-Wf-1-Structure-1: http://meta.firephp.org/Wildfire/Structure/FirePHP/FirebugConsole/0.1");

			self::$_initialized = true;
		}

		$appliedFormat = $this->getFormatter()->format($message, $type, $time);
		if(is_string($appliedFormat) === false) {
			throw new Exception('The formatted message is not valid');
		}

		$index = self::$_index;
		$size = strlen($appliedFormat);
		$offset = 0;

		//We need to send the data in chunks not exceeding 5,000 bytes.
		while($size > 0) {
			$str = 'X-Wf-1-1-1-'.$index.': ';
			$numBytes = ($size > 4500 ? 4500 : $size);

			if($offset !== 0) {
				$str .= '|';
			}

			$str .= substr($appliedFormat, $offset, $offset + 4500);

			$size -= $numBytes;
			$offset += $numBytes;

			if($size > 0) {
				$str .= "|\\";
			}

			header($str);
			$index++;
		}

		self::$_index = $index;
	}

	/**
	 * Closes the logger
	 *
	 * @return boolean
	 */
	public function close()
	{
		return true;
	}
}
