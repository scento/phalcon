<?php
/**
 * Lazy Loader
 *
 * @author Andres Gutierrez <andres@phalconphp.com>
 * @author Eduar Carvajal <eduar@phalconphp.com>
 * @author Wenzel Pünter <wenzel@phelix.me>
 * @version 1.2.6
 * @package Phalcon
*/
namespace Phalcon\Mvc\Micro;

use \Phalcon\Mvc\Micro\Exception;

/**
 * Phalcon\Mvc\Micro\LazyLoader
 *
 * Lazy-Load of handlers for Mvc\Micro using auto-loading
 * 
 * @see https://github.com/phalcon/cphalcon/blob/1.2.6/ext/mvc/micro/lazyloader.c
 */
class LazyLoader
{
	/**
	 * Handler
	 * 
	 * @var null|object
	 * @access protected
	*/
	protected $_handler;

	/**
	 * Definition
	 * 
	 * @var null|string
	 * @access protected
	*/
	protected $_definition;

	/**
	 * \Phalcon\Mvc\Micro\LazyLoader constructor
	 *
	 * @param string $definition
	 * @throws Exception
	 */
	public function __construct($definition)
	{
		if(is_string($definition) === false) {
			throw new Exception('Only strings can be lazy loaded');
		}

		$this->_definition = $definition;
	}

	/**
	 * Initializes the internal handler, calling functions on it
	 *
	 * @param string $method
	 * @param array $arguments
	 * @return mixed
	 * @throws Exception
	 */
	public function __call($method, $arguments)
	{
		if(is_string($method) === false ||
			is_array($arguments) === false) {
			throw new Exception('Invalid parameter type.');
		}

		if(is_object($this->_handler) === false) {
			$this->_handler = new $this->_definition();
		}

		//Call the handler
		return call_user_func_array(array($this->_handler, $method), $arguments);
	}
}