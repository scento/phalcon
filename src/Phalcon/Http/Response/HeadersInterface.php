<?php
/**
 * Headers Interface
 *
 * @author Andres Gutierrez <andres@phalconphp.com>
 * @author Eduar Carvajal <eduar@phalconphp.com>
 * @version 1.2.6
 * @package Phalcon
*/
namespace Phalcon\Http\Response;

/**
 * Phalcon\Http\Response\HeadersInterface initializer
 * 
 * @see https://github.com/phalcon/cphalcon/blob/1.2.6/ext/http/response/headersinterface.c
 */
interface HeadersInterface
{
	/**
	 * Sets a header to be sent at the end of the request
	 *
	 * @param string $name
	 * @param string $value
	 */
	public function set($name, $value);

	/**
	 * Gets a header value from the internal bag
	 *
	 * @param string $name
	 * @return string
	 */
	public function get($name);

	/**
	 * Sets a raw header to be sent at the end of the request
	 *
	 * @param string $header
	 */
	public function setRaw($header);

	/**
	 * Sends the headers to the client
	 *
	 * @return boolean
	 */
	public function send();

	/**
	 * Reset set headers
	 *
	 */
	public function reset();

	/**
	 * Restore a \Phalcon\Http\Response\Headers object
	 *
	 * @param array $data
	 * @return \Phalcon\Http\Response\HeadersInterface
	 */
	public static function __set_state($data);
}