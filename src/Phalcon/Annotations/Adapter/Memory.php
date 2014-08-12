<?php
/**
 * Annotations Memory Adapter
 *
 * @author Andres Gutierrez <andres@phalconphp.com>
 * @author Eduar Carvajal <eduar@phalconphp.com>
 * @author Wenzel Pünter <wenzel@phelix.me>
 * @version 1.2.6
 * @package Phalcon
*/
namespace Phalcon\Annotations\Adapter;

use \Phalcon\Annotations\AdapterInterface,
	\Phalcon\Annotations\Adapter,
	\Phalcon\Annotations\Reflection,
	\Phalcon\Annotations\Exception;

/**
 * Phalcon\Annotations\Adapter\Memory
 *
 * Stores the parsed annotations in memory. This adapter is the suitable development/testing
 * 
 * @see https://github.com/phalcon/cphalcon/blob/1.2.6/ext/annotations/adapter/memory.c
 */
class Memory extends Adapter implements AdapterInterface
{
	/**
	 * Annotations
	 * 
	 * @var array|null
	 * @access protected
	*/
	protected $_data = null;

	/**
	 * Reads parsed annotations from memory
	 *
	 * @param string $key
	 * @return \Phalcon\Annotations\Reflection|null
	 * @throws Exception
	 */
	public function read($key)
	{
		if(is_string($key) === false) {
			throw new Exception('Invalid parameter type.');
		}

		$lowercasedKey = strtolower($key);

		if(isset($this->_data[$lowercasedKey]) === true) {
			return $this->_data[$lowercasedKey];
		} else {
			return null;
		}
	}

	/**
	 * Writes parsed annotations to memory
	 *
	 * @param string $key
	 * @param \Phalcon\Annotations\Reflection $data
	 * @throws Exception
	 */
	public function write($key, $data)
	{
		if(is_string($key) === false ||
			is_object($data) === false ||
			$data instanceof Reflection === false) {
			throw new Exception('Invalid parameter type.');
		}

		$this->_data[strtolower($key)] = $data;
	}
}