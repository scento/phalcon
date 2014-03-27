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
	 * Parses a comma-separated parameter list using tokens
	 * 
	 * @param string $raw
	 * @return array
	 * @throws Exception
	*/
	private static function parse_comma_seperate($raw)
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
			return array('type' => self::PHANNOT_T_NULL);

		} elseif($raw == 'false') {
			/* Type: boolean (false) */
			return array('type' => self::PHANNOT_T_FALSE);

		} elseif($raw == 'true') {
			/* Type: boolean (true) */
			return array('type' => self::PHANNOT_T_TRUE);

		} elseif(preg_match('#^([+-](?:[0-9])+)$#', $raw, $matches) > 0) {
			/* Type: integer */
			return array('type' => self::PHANNOT_T_INTEGER, 'value' => (int)$matches[0]);

		} elseif(preg_match('#^([+-](?:[0-9.])+)$#', $raw, $matches) > 0) {
			/* Type: float */
			return array('type' => self::PHANNOT_T_DOUBLE, 'value' => (float)$matches[0]);

		} elseif(preg_match('#^"(.*)"$#', $raw, $matches) > 0) {
			/* Type: quoted string */
			return array('type' => self::PHANNOT_T_STRING, 'value' => (string)$matches[0]);

		} elseif(preg_match('#^([\w]+):(?:[\s]*)(?:([\w"]+)?|(?:(\{(?:.*)\}))|(\[(?:.*)\]))$#', $raw, $matches) > 0) {
			/* Colon-divided named parameters */

		} elseif(preg_match('#^([\w]+)=(?:([\w"]+)?|(?:(\{(?:.*)\}))|(\[(?:.*)\]))$#', $raw, $matches) > 0) {
			/* Equal-divided named parameter */

		} elseif(preg_match('#^\((?:(\[[^()]+\]|\{[^()]+\}|[^{}[\](),]{1,})(?:,?))*\)$#', $raw) > 0) {
			/* Argument list (default/root element) */
			$results = array();
			$arguments = self::parse_comma_seperate($raw);
			foreach($arguments as $argument) {
				$results[]['expr'] = self::parseDocBlockArguments($argument);
			}
			return $results;

	 	} elseif(preg_match_all('#^{(?:(?:(?:(?:(["\w])(?::|=)(?:\s?))?)(["\w])(?:,?)(?:\s?))*)}$#', $raw, $matches) > 0) {
			/* Associative Array */

		} elseif(preg_match_all('#^\[(?:(["\w])(?:,(?:\s?))?)+\]$#', $raw, $matches) > 0) {
			/* Type: Array */

		} elseif(ctype_alnum($raw) === true) {
			/* Type: identifier */
			return array('type' => self::PHANNOT_T_IDENTIFIER, 'value' => (string)$raw);

		} else {
			/* Invalid annotation parameters */
			throw new Exception('Invalid argument.');

		}
	}
}