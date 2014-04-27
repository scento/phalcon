<?php
/**
 * Native Array Translation Adapter
 *
 * @author Andres Gutierrez <andres@phalconphp.com>
 * @author Eduar Carvajal <eduar@phalconphp.com>
 * @author Wenzel Pünter <wenzel@phelix.me>
 * @version 1.2.6
 * @package Phalcon
*/
namespace Phalcon\Translate\Adapter;

use \Phalcon\Translate\Adapter,
	\ArrayAccess,
	\Phalcon\Translate\AdapterInterface,
	\Phalcon\Translate\Exception;
	
/**
 * Phalcon\Translate\Adapter\NativeArray
 *
 * Allows to define translation lists using PHP arrays
 *
 * @see https://github.com/phalcon/cphalcon/blob/1.2.6/ext/translate/adapter/nativearray.c
 */
class NativeArray extends Adapter implements \ArrayAccess, AdapterInterface
{
	/**
	 * Translate
	 * 
	 * @var null|array
	 * @access protected
	*/
	protected $_translate;

	/**
	 * \Phalcon\Translate\Adapter\NativeArray constructor
	 *
	 * @param array $options
	 * @throws Exception
	 */
	public function __construct($options)
	{
		if(is_array($options) === false) {
			throw new Exception('Invalid options');
		}

		if(isset($options['content']) === false) {
			throw new Exception('Translation content was not provided');
		}

		if(is_array($options['content']) === false) {
			throw new Exception('Translation data must be an array');
		}

		$this->_translate = $options['content'];
	}

	/**
	 * Returns the translation related to the given key
	 *
	 * @param string $index
	 * @param array|null $placeholders
	 * @return string
	 * @throws Exception
	 */
	public function query($index, $placeholders = null)
	{
		if(is_string($index) === false) {
			throw new Exception('Invalid parameter type.');
		}

		if(is_array($placeholders) === false &&
			is_null($placeholders) === false) {
			throw new Exception('Invalid parameter type.');
		}

		if(isset($this->_translate[$index]) === true) {
			$translation = $this->_translate[$index];
			if(is_array($placeholders) === true && empty($placeholders) === false) { 
				foreach($placeholders as $key => $value) {
					$translation = str_replace('%'.$key.'%', $value, $translation);
				}
			}

			return $translation;
		}

		return $index;
	}

	/**
	 * Check whether is defined a translation key in the internal array
	 *
	 * @param string $index
	 * @return boolean
	 * @throws Exception
	 */
	public function exists($index)
	{
		if(is_string($index) === false) {
			throw new Exception('Invalid parameter type.');
		}

		return isset($this->_translate[$index]);
	}
}
