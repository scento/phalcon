<?php
/**
 * Direct Flash Messages
 *
 * @author Andres Gutierrez <andres@phalconphp.com>
 * @author Eduar Carvajal <eduar@phalconphp.com>
 * @author Wenzel Pünter <wenzel@phelix.me>
 * @version 1.2.6
 * @package Phalcon
*/
namespace Phalcon\Flash;

use \Phalcon\Flash,
	\Phalcon\FlashInterface;

/**
 * Phalcon\Flash\Direct
 *
 * This is a variant of the Phalcon\Flash that inmediately outputs any message passed to it
 * 
 * @see https://github.com/phalcon/cphalcon/blob/1.2.6/ext/flash/direct.c
 */
class Direct extends Flash implements FlashInterface
{
	/**
	 * Outputs a message
	 *
	 * @param string $type
	 * @param string $message
	 * @return string
	 */
	public function message($type, $message)
	{
		return $this->outputMessage($type, $message);
	}
}