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
	\Phalcon\Filter\Exception as FilterException;

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
	 * @throws FilterException
	 */
	public function add($name, $handler)
	{
		if(is_string($name) === false) {
			throw new FilterException('Filter name must be string');
		}

		if(is_object($handler) === false) {
			throw new FilterException('Filter must be an object');
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
						$arrayValue = array();

						foreach($value as $itemKey => $itemValue) {
							//@note no type check of $itemKey
							$arrayValue[$itemKey] = $this->_sanitize($itemValue, $filter);
						}

						$value = $arrayValue;
					} else {
						$value = $this->_sanitize($value, $filter);
					}
				}
			}

			return $value;
		}

		//Apply a single filter value
		if(is_array($value) === true) {
			$sanizitedValue = array();
			foreach($value as $key => $itemValue) {
				//@note no type check of $key
				$sanizitedValue[$key] = $this->_sanitize($itemValue, $filters);
			}
		} else {
			$sanizitedValue = $this->_sanitize($value, $filters);
		}

		return $sanizitedValue;
	}

	/**
	 * Internal sanitize wrapper to filter_var
	 *
	 * @param mixed $value
	 * @param string $filter
	 * @return mixed
	 * @throws FilterException
	 */
	protected function _sanitize($value, $filter)
	{
		if(is_string($filter) === false) {
			throw new FilterException('Invalid parameter type.');
		}

		/* User-defined filter */
		if(isset($filters[$filter]) === true) {
			$filterObject = $this->_filters[$filter];
			if($filterObject instanceof \Closure) {
				return call_user_func_array($filterObject, array($value));
			}

			return $filterObject->filter($value);
		}

		/* Predefined filter */
		switch($filter) {
			case 'email':
				return filter_var(str_replace('\'', '', $value), 517);
				break;
			case 'int':
				return filter_var($value, 519);
				break;
			case 'string':
				return filter_var($value, 513);
				break;
			case 'float':
				return filter_var($value, 520, array('flags' => 4096));
				break;
			case 'alphanum':
				$filtered = '';
				$value = (string)$value;
				$valueLength = strlen($value);
				$zeroChar = chr(0);

				for($i = 0; $i < $valueLength; ++$i) {
					if($value[$i] == $zeroChar) {
						break;
					}

					if(ctype_alnum($value[$i]) === true) {
						$filtered .= $value[$i];
					}
				}
				return $filtered;
				break;
			case 'trim':
				return trim($value);
				break;
			case 'striptags':
				return strip_tags($value);
				break;
			case 'lower':
				if(function_exists('mb_strtolower') === true) {
					return mb_strtolower($value);
				} else {
					//@note we use the default implementation instad of a custom one
					return strtolower($value);
				}
				break;
			case 'upper':
				if(function_exists('mb_strtoupper') === true) {
					return mb_strtoupper($value);
				} else {
					//@note we use the default implementation instad of a custom one
					return strtoupper($value);
				}
		}

		throw new FilterException('Sanitize filter '.$filter.' is not supported');
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