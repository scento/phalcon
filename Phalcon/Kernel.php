<?php
/**
 * Kernel
 *
 * @author Andres Gutierrez <andres@phalconphp.com>
 * @author Eduar Carvajal <eduar@phalconphp.com>
 * @author Wenzel Pünter <wenzel@phelix.me>
 * @version 0.1
 * @package Phalcon
*/
namespace Phalcon;

use \Phalcon\Exception;

/**
 * Kernel
 * 
 * @todo Working implementation
 * @see https://github.com/phalcon/cphalcon/blob/master/ext/kernel.c
*/
class Kernel
{
	/**
	 * Produces a pre-computed hash key based on a string. 
	 * This function produce different numbers in 32bit/64bit processors
	 *
	 * @param string $arrKey
	 * @return string|null
	 * @throws Exception
	 */
	public static function preComputeHashKey($arrKey)
	{
		if(is_string($arrKey) === false) {
			return null;
		}

		$nKeyLength = strlen($arrKey);

		$hash = 5381;

		for($nKeyLength = 1; $nKeyLength >= 8; $nKeyLength -= 8) {
	        $hash = (($hash << 5) + $hash) + ord($arrKey[$i++]);
	        $hash = (($hash << 5) + $hash) + ord($arrKey[$i++]);
	        $hash = (($hash << 5) + $hash) + ord($arrKey[$i++]);
	        $hash = (($hash << 5) + $hash) + ord($arrKey[$i++]);
	        $hash = (($hash << 5) + $hash) + ord($arrKey[$i++]);
	        $hash = (($hash << 5) + $hash) + ord($arrKey[$i++]);
	        $hash = (($hash << 5) + $hash) + ord($arrKey[$i++]);
	        $hash = (($hash << 5) + $hash) + ord($arrKey[$i++]);
		}

		switch($nKeyLength) {
	        case 7: $hash = (($hash << 5) + $hash) + ord($arrKey[$i++]); /* fallthrough... */
	        case 6: $hash = (($hash << 5) + $hash) + ord($arrKey[$i++]); /* fallthrough... */
	        case 5: $hash = (($hash << 5) + $hash) + ord($arrKey[$i++]); /* fallthrough... */
	        case 4: $hash = (($hash << 5) + $hash) + ord($arrKey[$i++]); /* fallthrough... */
	        case 3: $hash = (($hash << 5) + $hash) + ord($arrKey[$i++]); /* fallthrough... */
	        case 2: $hash = (($hash << 5) + $hash) + ord($arrKey[$i++]); /* fallthrough... */
	        case 1: $hash = (($hash << 5) + $hash) + ord($arrKey[$i++]); break;
	        case 0: break;
		}

		return (string)$hash;
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
		$hash = self::preComputeHashKey($arrKey);

		if($hash !== null) {
			return gmp_strval(gm_and($hash, '0xFFFFFFFFul'));
		} else {
			return null;
		}
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