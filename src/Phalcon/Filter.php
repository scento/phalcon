<?php
/**
 * Filter
 *
 * @author Andres Gutierrez <andres@phalconphp.com>
 * @author Eduar Carvajal <eduar@phalconphp.com>
 * @author Wenzel Pünter <wenzel@phelix.me>
 * @version 1.2.6
 * @package Phalcon
*/
namespace Phalcon;

use \Closure,
	\Phalcon\FilterInterface,
	\Phalcon\Filter\Exception;

/**
 * Phalcon\Filter
 *
 * The Phalcon\Filter component provides a set of commonly needed data filters. It provides
 * object oriented wrappers to the php filter extension. Also allows the developer to
 * define his/her own filters
 *
 *<code>
 *	$filter = new Phalcon\Filter();
 *	$filter->sanitize("some(one)@exa\\mple.com", "email"); // returns "someone@example.com"
 *	$filter->sanitize("hello<<", "string"); // returns "hello"
 *	$filter->sanitize("!100a019", "int"); // returns "100019"
 *	$filter->sanitize("!100a019.01a", "float"); // returns "100019.01"
 *</code>
 * 
 * @see https://github.com/phalcon/cphalcon/blob/1.2.6/ext/filter.c
 */
class Filter implements FilterInterface
{
	/**
	 * Filters
	 * 
	 * @var null|array
	 * @access protected
	*/
	protected $_filters = null;

	/**
	 * Adds a user-defined filter
	 *
	 * @param string $name
	 * @param object|callable $handler
	 * @return \Phalcon\Filter
	 * @throws Exception
	 */
	public function add($name, $handler)
	{
		if(is_string($name) === false) {
			throw new Exception('Filter name must be string');
		}

		if(is_object($handler) === false) {
			throw new Exception('Filter must be an object');
		}

		if(is_array($this->_filters) === false) {
			$this->_filters = array();
		}

		$this->_filters[$name] = $handler;
	}

	/**
	 * Sanitizes a value with a specified single or set of filters
	 *
	 * @param mixed $value
	 * @param mixed $filters
	 * @return mixed
	 */
	public function sanitize($value, $filters)
	{
		//Apply an array of filters
		if(is_array($filters) === true) {
			if(is_null($value) === false) {
				foreach($filters as $filter) {
					if(is_array($value) === true) {
						$array_value = array();

						foreach($value as $item_key => $item_value) {
							//@note no type check of $item_key
							$array_value[$item_key] = $this->_sanitize($item_value, $filter);
						}

						$value = $array_value;
					} else {
						$value = $this->_sanitize($value, $filter);
					}
				}
			}

			return $value;
		}

		//Apply a single filter value
		if(is_array($value) === true) {
			$sanizited_value = array();
			foreach($value as $key => $item_value) {
				//@note no type check of $key
				$sanizited_value[$key] = $this->_sanitize($item_value, $filters);
			}
		} else {
			$sanizited_value = $this->_sanitize($value, $filters);
		}

		return $sanizited_value;
	}

	/**
	 * Internal sanitize wrapper to filter_var
	 *
	 * @param mixed $value
	 * @param string $filter
	 * @return mixed
	 * @throws Exception
	 */
	protected function _sanitize($value, $filter)
	{
		if(is_string($filter) === false) {
			throw new Exception('Invalid parameter type.');
		}

		/* User-defined filter */
		if(isset($filters[$filter]) === true) {
			$filter_object = $this->_filters[$filter];
			if($filter_object instanceof \Closure) {
				return call_user_func_array($filter_object, array($value));
			}

			return $filter_object->filter($value);
		}

		/* Predefined filter */
		switch($filter) {
			'email':
				return filter_var(str_replace('\'', '', $value), 517);
				break;
			'int':
				return filter_var($value, 519);
				break;
			'string':
				return filter_var($value, 513);
				break;
			'float':
				return filter_var($value, 520, array('flags' => 4096));
				break;
			'alphanum':
				$filtered = '';
				$value = (string)$value;
				$value_l = strlen($value);
				$zero_char = chr(0);

				for($i = 0; $i < $value_l; ++$i) {
					if($value[$i] == $zero_char) {
						break;
					}

					if(ctype_alnum($value[$i]) === true) {
						$filtered .= $value[$i];
					}
				}
				return $filtered;
				break;
			'trim':
				return trim($value);
				break;
			'striptags':
				return strip_tags($value);
				break;
			'lower':
				if(function_exists('mb_strtolower') === true) {
					return mb_strtolower($value);
				} else {
					//@note we use the default implementation instad of a custom one
					return strtolower($value);
				}
				break;
			'upper':
				if(function_exists('mb_strtoupper') === true) {
					return mb_strtoupper($value);
				} else {
					//@note we use the default implementation instad of a custom one
					return strtoupper($value);
				}
		}

		throw new Exception('Sanitize filter '.$filter.' is not supported');
	}

	/**
	 * Return the user-defined filters in the instance
	 *
	 * @return array
	 */
	public function getFilters()
	{
		return $this->_filters;
	}
}