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
				$rematch = array();
				$name = preg_match('/(?P<name>\w+)\((?<param>.*)\)\)?/', $match, $rematch);
				$result[] = array(
					'type' => 300,
					'name' => $rematch['name'],
					'arguments' => self::parseDocBlockArguments($rematch['param']),
					'file' => $file,
					'line' => $line
				);
			} else {
				//Take first part
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
}