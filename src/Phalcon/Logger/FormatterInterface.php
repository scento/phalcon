<?php
/**
 * Formatter Interface
 *
 * @author Andres Gutierrez <andres@phalconphp.com>
 * @author Eduar Carvajal <eduar@phalconphp.com>
 * @version 1.2.6
 * @package Phalcon
*/
namespace Phalcon\Logger;

/**
 * Phalcon\Logger\FormatterInterface initializer
 * 
 * @see https://github.com/phalcon/cphalcon/blob/1.2.6/ext/logger/formatterinterface.c
 */
interface FormatterInterface
{
	/**
	 * Applies a format to a message before sent it to the internal log
	 *
	 * @param string $message
	 * @param int $type
	 * @param int $timestamp
	 */
	public function format($message, $type, $timestamp);
}