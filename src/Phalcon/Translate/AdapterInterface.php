<?php
/**
 * Adapter Interface
 *
 * @author Andres Gutierrez <andres@phalconphp.com>
 * @author Eduar Carvajal <eduar@phalconphp.com>
 * @version 1.2.6
 * @package Phalcon
*/
namespace Phalcon\Translate;

/**
 * Phalcon\Translate\AdapterInterface initializer
 * 
 * @see https://github.com/phalcon/cphalcon/blob/1.2.6/ext/translate/adapterinterface.c
 */
interface AdapterInterface
{
	/**
	 * \Phalcon\Translate\Adapter\NativeArray constructor
	 *
	 * @param array $options
	 */
	public function __construct($options);

	/**
	 * Returns the translation string of the given key
	 *
	 * @param string $translateKey
	 * @param array|null $placeholders
	 * @return string
	 */
	public function _($translateKey, $placeholders = null);

	/**
	 * Returns the translation related to the given key
	 *
	 * @param string $index
	 * @param array|null $placeholders
	 * @return string
	 */
	public function query($index, $placeholders = null);

	/**
	 * Check whether is defined a translation key in the internal array
	 *
	 * @param string $index
	 * @return bool
	 */
	public function exists($index);
}