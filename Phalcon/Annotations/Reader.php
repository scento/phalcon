<?php
/**
 * Reader
 *
 * @author Andres Gutierrez <andres@phalconphp.com>
 * @author Eduar Carvajal <eduar@phalconphp.com>
 * @author Wenzel Pünter <wenzel@phelix.me>
 * @version 0.1
 * @package Phalcon
*/
namespace Phalcon\Annotations;

use \Phalcon\Annotations\ReaderInterface,
	\Phalcon\Annotations\Exception,
	\ReflectionClass;

/**
 * Phalcon\Annotations\Reader
 *
 * Parses docblocks returning an array with the found annotations
 * 
 * @see https://github.com/phalcon/cphalcon/blob/master/ext/annotations/reader.c
 */
class Reader implements ReaderInterface
{
	/**
	 * Integer Type
	 * 
	 * @var int
	*/
	const PHANNOT_T_INTEGER = 301;

	/**
	 * Double Type
	 * 
	 * @var int
	*/
	const PHANNOT_T_DOUBLE = 302;

	/**
	 * String Type
	 * 
	 * @var int
	*/
	const PHANNOT_T_STRING = 303;

	/**
	 * Null Type
	 * 
	 * @var int
	*/
	const PHANNOT_T_NULL = 304;

	/**
	 * False Type
	 * 
	 * @var int
	*/
	const PHANNOT_T_FALSE =  305;

	/**
	 * True Type
	 * 
	 * @var int
	*/
	const PHANNOT_T_TRUE = 306;

	/**
	 * Identifer Type
	 * 
	 * @var int
	*/
	const PHANNOT_T_IDENTIFIER = 307;

	/**
	 * Array Type
	 * 
	 * @var int
	*/
	const PHANNOT_T_ARRAY = 308;

	/**
	 * Annotation Type
	 * 
	 * @var int
	*/
	const PHANNOT_T_ANNOTATION = 300;

	/**
	 * Reads annotations from the class dockblocks, its methods and/or properties
	 *
	 * @param string $className
	 * @return array
	 */
	public function parse($className)
	{
		if(is_string($className) === false)
		{
			throw new Exception('Invalid parameter type.');
		}

		if(class_exists($className) === true)
		{
			throw new Exception('Class '.$className.' does not exist');
		}

		$reflection = new ReflectionClass($className);
		$path = $reflection->getFileName();

		if($path === false) {
			return array();
		} else {
			$annotations = array();

			//Class info
			if($reflection->getDocComment() !== false)
			{
				$annotations['class'] = $this->parseDocBlock($reflection->getDocComment(), $path, $reflection->getStartLine());
			}

			//Class properties
			$properties = $reflection->getProperties();
			foreach($properties as $property)
			{
				$annotations['properties'][] = $this->parseDocBlock($method->getDocComment(), $path, $property->getStartLine());
			}

			//Class methods
			$methods = $reflection->getMethods();
			foreach($methods as $method)
			{
				if($method->getDocComment() !== false)
				{
					$annotations['methods'][] = $this->parseDocBlock($method->getDocComment(), $path, $method->getStartLine());
				}
			}

			return $annotations;
		}
	}

	/**
	 * Parses a raw doc block returning the annotations found
	 *
	 * @param string $docBlock
	 * @param string|null $file
	 * @param int|null $line
	 * @return array
	 * @throws Exception
	 */
	public static function parseDocBlock($docBlock, $file = null, $line = null)
	{
		if(is_string($docBlock) === false)
		{
			throw new Exception('Invalid parameter type.');
		}

		if(is_null($file) === true) {
			$file = '';
		} elseif(is_string($file) === false) {
			throw new Exception('Invalid parameter type.');
		}

		if(is_null($line) === true) {
			$file = '';
		} elseif(is_string($line) === false) {
			throw new Exception('Invalid parameter type.');
		}

		if(strlen($docBlock) < 2)
		{
			return false;
		}

		$matches = array();

		if(preg_match_all('/@(.*)\r?\n/m', $docBlock, $matches) === false)
		{
			throw new Exception('Error parsing annotation');
		}

		if(empty($matches[1]) === true)
		{
			return false;
		}

		$result = array();
		foreach($matches[1] as $match)
		{
			if(strpos($match, '(') !== false)
			{
				//Parameterized annotation
				$rematch = array();
				$name = preg_match('/(?P<name>\w+)\((?<param>.*)\)\)?/', $match, $rematch);
				try {
					$result[] = array(
						'type' => 300,
						'name' => $rematch['name'],
						'arguments' => self::parseDocBlockArguments('('.(string)$rematch['param'].')'),
						'file' => $file,
						'line' => $line
					);
				} catch(Exception $e) {
					throw new Exception('Error parsing annotation');
				}
			} else {
				//Only the name
				$rematch = array();
				$name = preg_match('/(\w+)(\s+(.*))?/', $match, $rematch);
				$result[] = array(
					'type' => 300,
					'name' => $rematch[1],
					'file' => $file,
					'line' => $line
				);
			}
		}
	
		return $result;
	}

	/**
	 * Parses an associative array using tokens
	 * 
	 * @param string $raw
	 * @return array
	 * @throws Exception
	*/
	private static function parse_assoc_array($raw) {
		$l = strlen($raw);

		//Remove parantheses
		$raw = substr($raw, 1, $l-1);
		$l = $l - 2;

		$open_braces = 0;
		$open_brackets = 0;
		$breakpoints = array();

		for($i = 0; $i < $l; ++$i) {
			switch($raw[$i]) {
				case ',':
					if($open_braces === 0 &&
						$open_brackets === 0) {
						$breakpoints[] = $i+1;
					}
					break;
				case '[':
					++$open_brackets;
					break;
				case ']':
					--$open_brackets;
					break;
				case '{':
					++$open_braces;
					break;
				case '}':
					--$open_braces;
					break;
				case '(':
					throw new Exception('Invalid token.');
					break;
				case ')':
					throw new Exception('Invalid token.');
					break;
			}
		}

		$breakpoints[] = $l+1;

		if($open_braces !== 0 || $open_brackets !== 0) {
			throw new Exception('Invalid annotation.');
		}

		$parameters = array();
		$last_break = 0;

		foreach($breakpoints as $break) {
			$str = substr($raw, $last_break, $break-$last_break-1);
			$pos_c = strpos($str, ':');
			$pos_e = strpos($str, '=');

			if($pos_c !== false && $pos_e === false) {
				$parameters[] = explode(':', $str, 2);
			} elseif($pos_e !== false && $pos_c === false) {
				$parameters[] = explode('=', $str, 2);
			} elseif($pos_c !== false && $pos_e !== false && $pos_c < $pos_e) {
				$parameters[] = explode(':', $str, 2);
			} elseif($pos_e !== false && $pos_e !== false && $pos_e < $pos_c) {
				$parameters[] = explode('=', $str, 2);
			} else {
				$parameters[] = $str;
			}
			$last_break = $break;
		}

		return $parameters;
	}

	/**
	 * Parses a comma-separated parameter list using tokens
	 * 
	 * @param string $raw
	 * @return array
	 * @throws Exception
	*/
	private static function parse_parameter_list($raw)
	{
		$l = strlen($raw);

		//Remove parantheses
		$raw = substr($raw, 1, $l-1);
		$l = $l - 2;

		$open_braces = 0;
		$open_brackets = 0;
		$breakpoints = array();

		for($i = 0; $i < $l; ++$i) {
			switch($raw[$i]) {
				case ',':
					if($open_braces === 0 &&
						$open_brackets === 0) {
						$breakpoints[] = $i+1;
					}
					break;
				case '[':
					++$open_brackets;
					break;
				case ']':
					--$open_brackets;
					break;
				case '{':
					++$open_braces;
					break;
				case '}':
					--$open_braces;
					break;
				case '(':
					throw new Exception('Invalid token.');
					break;
				case ')':
					throw new Exception('Invalid token.');
					break;
			}
		}

		$breakpoints[] = $l+1;

		if($open_braces !== 0 || $open_brackets !== 0) {
			throw new Exception('Invalid annotation.');
		}

		$parameters = array();
		$last_break = 0;

		foreach($breakpoints as $break) {
			$parameters[] = substr($raw, $last_break, $break-$last_break-1);
			$last_break = $break;
		}

		return $parameters;
	}

	/**
	 * Parses a raw arguments expression
	 * 
	 * @param string $raw
	 * @return array
	 * @throws Exception
	 * @todo Implementation of parameter parser
	*/
	private static function parseDocBlockArguments($raw)
	{
		if(is_string($raw) === false)
		{
			throw new Exception('Invalid parameter type.');
		}

		$raw = trim($raw);
		$matches = array();

		if($raw == 'null') {
			/* Type: null */
			return array('expr' => array('type' => self::PHANNOT_T_NULL));

		} elseif($raw == 'false') {
			/* Type: boolean (false) */
			return array('expr' => array('type' => self::PHANNOT_T_FALSE));

		} elseif($raw == 'true') {
			/* Type: boolean (true) */
			return array('expr' => array('type' => self::PHANNOT_T_TRUE));

		} elseif(preg_match('#^((?:[+-]?)(?:[0-9])+)$#', $raw, $matches) > 0) {
			/* Type: integer */
			return array('expr' => array('type' => self::PHANNOT_T_INTEGER, 'value' => (string)$matches[0]));

		} elseif(preg_match('#^((?:[+-]?)(?:[0-9.])+)$#', $raw, $matches) > 0) {
			/* Type: float */
			return array('expr' => array('type' => self::PHANNOT_T_DOUBLE, 'value' => (string)$matches[0]));

		} elseif(preg_match('#^"(.*)"$#', $raw, $matches) > 0) {
			/* Type: quoted string */
			return array('expr' => array('type' => self::PHANNOT_T_STRING, 'value' => (string)$matches[0]));

		} elseif(preg_match('#^([\w]+):(?:[\s]*)((?:(?:[\w"]+)?|(?:(?:\{(?:.*)\}))|(?:\[(?:.*)\])))$#', $raw, $matches) > 0) {
			/* Colon-divided named parameters */
			return array_merge(array('name' => (string)$matches[1]), self::parseDocBlockArguments($matches[2]));

		} elseif(preg_match('#^([\w]+)=((?:(?:[\w"]+)?|(?:(?:\{(?:.*)\}))|(?:\[(?:.*)\])))$#', $raw, $matches) > 0) {
			/* Equal-divided named parameter */
			return array_merge(array('name' => (string)$matches[1]), self::parseDocBlockArguments($matches[2]));

		} elseif(preg_match('#^\((?:(\[[^()]+\]|\{[^()]+\}|[^{}[\](),]{1,})(?:,?))*\)(?:;?)$#', $raw) > 0) {
			/* Argument list (default/root element) */
			$results = array();
			$arguments = self::parse_parameter_list($raw);
			foreach($arguments as $argument) {
				$results[] = self::parseDocBlockArguments($argument);
			}
			return $results;

	 	} elseif(preg_match('#^\{(?:(?:([\w"]+)(?:[:=])[\s]*([\w"])+(?:}$|,[\s]*))|(?:([\w"]+)(?:[:=])[\s]*(\[(?:.*)\])(?:}$|,[\s]*))|(?:([\w"]+)(?:[:=][\s]*(\{(?:.*)\})(?:}$|,[\s]*)))|(?:([\w"]+)[\s]*(?:}$|,[\s]*)))+#', $raw) > 0) {
			/* Associative Array */
			$result = array();
			$arguments = self::parse_assoc_array($raw);
			foreach($arguments as $argument) {
				if(is_array($argument) === true) {
					$result[] = array_merge(array('name' => (string)$argument[0]), 
						self::parseDocBlockArguments($argument[1]));
				} else {
					$result[] = self::parseDocBlockArguments($argument);
				}
			}
			return array('expr' => array('type' => self::PHANNOT_T_ARRAY, 'items' => $result));

		} elseif(preg_match_all('#^\[(?:(["\w])(?:,(?:\s?))?)+\]$#', $raw, $matches) > 0) {
			/* Type: Array */
			return array();

		} elseif(ctype_alnum($raw) === true) {
			/* Type: identifier */
			return array('expr' => array('type' => self::PHANNOT_T_IDENTIFIER, 'value' => (string)$raw));

		} else {
			/* Invalid annotation parameters */
			throw new Exception('Invalid argument.');

		}
	}
}