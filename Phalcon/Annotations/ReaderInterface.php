<?php
/**
 * Reader
 *
 * @author Andres Gutierrez <andres@phalconphp.com>
 * @author Eduar Carvajal <eduar@phalconphp.com>
 * @version 1.2.6
 * @package Phalcon
*/
namespace Phalcon\Annotations;

/**
 * Phalcon\Annotations\ReaderInterface initializer
 * 
 * @see https://github.com/phalcon/cphalcon/blob/master/ext/annotations/readerinterface.c
 */
interface ReaderInterface
{
	/**
	 * Reads annotations from the class dockblocks, its methods and/or properties
	 *
	 * @param string $className
	 * @return array
	 */
	public function parse($className);

	/**
	 * Parses a raw doc block returning the annotations found
	 *
	 * @param string $docBlock
	 * @param string|null $file
	 * @param string|null $line
	 * @return array
	 */
	public static function parseDocBlock($docBlock, $file = null, $line = null);
}