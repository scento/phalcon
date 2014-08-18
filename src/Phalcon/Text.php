<?php
/**
 * Text
 *
 * @author Andres Gutierrez <andres@phalconphp.com>
 * @author Eduar Carvajal <eduar@phalconphp.com>
 * @author Wenzel Pünter <wenzel@phelix.me>
 * @version 1.2.6
 * @package Phalcon
*/
namespace Phalcon;

use \Phalcon\Exception;

/**
 * Phalcon\Text
 *
 * Provides utilities to work with texts
 * 
 * @see https://github.com/phalcon/cphalcon/blob/1.2.6/ext/text.c
 */
abstract class Text
{
	/**
	 * Random: Alphanumeric
	 * 
	 * @var int
	*/
	const RANDOM_ALNUM = 0;

	/**
	 * Random: Alpha
	 * 
	 * @var int
	*/
	const RANDOM_ALPHA = 1;

	/**
	 * Random: Hexdecimal
	 * 
	 * @var int
	*/
	const RANDOM_HEXDEC = 2;

	/**
	 * Random: Numeric
	 * 
	 * @var int
	*/
	const RANDOM_NUMERIC = 3;

	/**
	 * Random: No Zero
	 * 
	 * @var int
	*/
	const RANDOM_NOZERO = 4;

	/**
	 * Converts strings to camelize style
	 *
	 *<code>
	 *	echo \Phalcon\Text::camelize('coco_bongo'); //CocoBongo
	 *</code>
	 *
	 * @param string $str
	 * @return string
	 */
	public static function camelize($str)
	{
		if(is_string($str) === false) {
			//@note triggers E_USER_ERROR instead of E_ERROR
			trigger_error('Invalid arguments supplied for camelize()', \E_USER_ERROR);
			return '';
		}

		$l = strlen($str);
		$camelized = '';

		for($i = 0; $i < $l; ++$i) {
			if($i === 0 || $str[$i] === '-' || $str[$i] === '_') {
				if($str[$i] === '-' || $str[$i] === '_') {
					++$i;
				}

				if(isset($str[$i]) === true) {
					$camelized .= strtoupper($str[$i]);
				} else {
					//Prevent pointer overflow, c emulation of strtoupper
					$camelized .= chr(0);
				}
			} else {
				$camelized .= strtolower($str[$i]);
			}
		}

		return $camelized;
	}

	/**
	 * Uncamelize strings which are camelized
	 *
	 *<code>
	 *	echo \Phalcon\Text::camelize('CocoBongo'); //coco_bongo
	 *</code>
	 *
	 * @param string $str
	 * @return string
	 */
	public static function uncamelize($str)
	{
		if(is_string($str) === false) {
			//@note triggers E_USER_ERROR instead of E_ERROR
			//@note changed "camelize" to "uncamelize"
			trigger_error('Invalid arguments supplied for uncamelize()', \E_USER_ERROR);
			return '';
		}

		$l = strlen($str);
		$uncamelized = '';

		for($i = 0; $i < $l; ++$i) {
			$ch = ord($str[$i]);

			if($ch === 0) {
				break;
			}

			if($ch >= 65 && $ch <= 90) {
				if($i > 0) {
					$uncamelized .= '_';
				}
				$uncamelized .= chr($ch + 32);
			} else {
				$uncamelized .= $str[$i];
			}
		}

		return $uncamelized;
	}

	/**
	 * Adds a number to a string or increment that number if it already is defined
	 *
	 *<code>
	 *	echo \Phalcon\Text::increment("a"); // "a_1"
	 *	echo \Phalcon\Text::increment("a_1"); // "a_2"
	 *</code>
	 *
	 * @param string $str
	 * @param string $separator
	 * @return string
	 * @throws Exception
	 */
	public static function increment($str, $separator = null)
	{
		if(is_null($separator) === true) {
			$separator = '_';
		} elseif(is_string($separator) === false) {
			throw new Exception('Invalid parameter type.');
		}

		$parts = explode($separator, $str);

		if(isset($parts[1]) === true) {
			$number = (int)$parts[1];
			$number++;
		} else {
			$number = 1;
		}

		return $parts[0].$separator.$number;
	}

	/**
	 * Generates a random string based on the given type. Type is one of the RANDOM_* constants
	 *
	 *<code>
	 *	echo \Phalcon\Text::random(Phalcon\Text::RANDOM_ALNUM); //"aloiwkqz"
	 *</code>
	 *
	 * @param int $type
	 * @param int|null $length
	 * @return string|null
	 */
	public static function random($type, $length = null)
	{
		if(is_int($type) === false || $type > self::RANDOM_NOZERO) {
			return null;
		}

		if(is_null($length) === true) {
			$length = 8;
		} elseif(is_int($length) === false) {
			return null;
		}

		//@note this function is not always usable for cryptographic usage
		mt_srand();

		$t = '';
		for($i = 0; $i < $length; ++$i) {
			switch($type) {
				case self::RANDOM_ALNUM:
					//[A-Za-z0-9]
					$r = mt_rand(0, 59);
					if($r < 10) {
						//[0-9]
						$t .= (string)$r;
					} elseif($r > 9 && $r < 36) {
						//[A-Z]
						$t .= chr(55 + $r);
					} else {
						//[a-z]
						$t .= chr(62 + $r);
					}
					break;
				case self::RANDOM_ALPHA:
					//[a-z]
					$r = mt_rand(0, 25);
					$t .= chr(97 + $r);
					break;
				case self::RANDOM_HEXDEC:
					//[0-9a-f]
					$r = mt_rand(0, 15);
					if($r < 10) {
						$t .= (string)$r;
					} else {
						$t .= chr(87 + $r);
					}
					break;
				case self::RANDOM_NUMERIC:
					//[0-9]
					$r = mt_rand(0, 9);
					$t .= (string)$r;
					break;
				case self::RANDOM_NOZERO:
					//[1-9]
					$r = mt_rand(1, 9);
					$t .= (string)$r;
					break;
			}
		}

		return $t;
	}

	/**
	 * Check if a string starts with a given string
	 *
	 *<code>
	 *	echo \Phalcon\Text::startsWith("Hello", "He"); // true
	 *	echo \Phalcon\Text::startsWith("Hello", "he"); // false
	 *	echo \Phalcon\Text::startsWith("Hello", "he", false); // true
	 *</code>
	 *
	 * @param string $str
	 * @param string $start
	 * @param boolean $ignoreCase
	 * @return boolean
	 * @throws Exception
	 */
	public static function startsWith($str, $start, $ignoreCase = null)
	{
		if(is_string($str) === false || is_string($start) === false) {
			throw new Exception('Invalid parameter type.');
		}

		if($ignoreCase === null || $ignoreCase === true) {
			return (stripos($str, $start) === 0 ? true : false);
		} else {
			return (strpos($str, $start) === 0 ? true : false);
		}
	}

	/**
	 * Check if a string ends with a given string
	 *
	 *<code>
	 *	echo \Phalcon\Text::endsWith("Hello", "llo"); // true
	 *	echo \Phalcon\Text::endsWith("Hello", "LLO"); // false
	 *	echo \Phalcon\Text::endsWith("Hello", "LLO", false); // true
	 *</code>
	 *
	 * @param string $str
	 * @param string $end
	 * @param boolean $ignoreCase
	 * @return boolean
	 * @throws Exception
	 */
	public static function endsWith($str, $end, $ignoreCase = null)
	{
		if(is_string($str) === false || is_string($end) === false) {
			throw new Exception('Invalid parameter type.');
		}

		$g = strlen($str)-strlen($end);
		if($ignoreCase === null || $ignoreCase === true) {
			return (strripos($str, $end) === $g ? true : false);
		} else {
			return (strrpos($str, $end) === $g ? true : false);
		}
	}

	/**
	 * Lowercases a string, this function makes use of the mbstring extension if available
	 *
	 * @param string $str
	 * @return string
	 * @throws Exception
	 */
	public static function lower($str)
	{
		if(is_string($str) === false) {
			throw new Exception('Invalid parameter type.');
		}

		if(function_exists('mb_strtolower') === true) {
			return mb_strtolower($str);
		}

		return strtolower($str);
	}

	/**
	 * Uppercases a string, this function makes use of the mbstring extension if available
	 *
	 * @param string $str
	 * @return string
	 * @throws Exception
	 */
	public static function upper($str)
	{
		if(is_string($str) === false) {
			throw new Exception('Invalid parameter type.');
		}

		if(function_exists('mb_strtoupper') === true) {
			return mb_strtoupper($str);
		}

		return strtoupper($str);
	}
}