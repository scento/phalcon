<?php
/**
 * Flash Interface
 *
 * @author Andres Gutierrez <andres@phalconphp.com>
 * @author Eduar Carvajal <eduar@phalconphp.com>
 * @version 1.2.6
 * @package Phalcon
*/
namespace Phalcon;

/**
 * Phalcon\FlashInterface initializer
 * 
 * @see https://github.com/phalcon/cphalcon/blob/1.2.6/ext/flashinterface.c
 */
interface FlashInterface
{
	/**
	 * Shows a HTML error message
	 *
	 * @param string $message
	 * @return string
	 */
	public function error($message);

	/**
	 * Shows a HTML notice/information message
	 *
	 * @param string $message
	 * @return string
	 */
	public function notice($message);

	/**
	 * Shows a HTML success message
	 *
	 * @param string $message
	 * @return string
	 */
	public function success($message);

	/**
	 * Shows a HTML warning message
	 *
	 * @param string $message
	 * @return string
	 */
	public function warning($message);

	/**
	 * Outputs a message
	 *
	 * @param  string $type
	 * @param  string $message
	 * @return string
	 */
	public function message($type, $message);
}