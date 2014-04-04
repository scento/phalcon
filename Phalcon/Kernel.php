<?php
/**
 * Kernel
 *
 * @author Andres Gutierrez <andres@phalconphp.com>
 * @author Eduar Carvajal <eduar@phalconphp.com>
 * @author Wenzel Pünter <wenzel@phelix.me>
 * @version 1.2.6
 * @package Phalcon
*/
namespace Phalcon;

/**
 * Kernel
 * 
 * @see https://github.com/phalcon/cphalcon/blob/master/ext/kernel.c
 * @todo Create Phalcon-compatible implementation (see e.g. commit 9fd2e306ad599f5b1a75e6a8c7b63bc06776dc94)
*/
class Kernel
{
	/**
	 * Produces a pre-computed hash key based on a string. 
	 * This function produce different numbers in 32bit/64bit processors
	 *
	 * @param string $arrKey
	 * @return string|null
	 */
	public static function preComputeHashKey($arrKey)
	{
		if(is_string($arrKey) === false) {
			return null;
		}

		return (string)md5($arrKey);
	}

	/**
	* Produces a pre-computed hash key based on a string.
	* This function produce a hash for a 32bits processor
	*
	* @param string $arrKey
	* @return string|null
	*/
	public static function preComputeHashKey32($arrKey)
	{
		return self::preComputeHashKey($arrKey);
	}

	/**
	* Produces a pre-computed hash key based on a string.
	* This function produce a hash for a 64bits processor
	*
	* @param string $arrKey
	* @return string|null
	*/
	public static function preComputeHashKey64($arrKey)
	{
		return self::preComputeHashKey($arrKey);
	}
}