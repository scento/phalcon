<?php
/**
 * Annotations Adapter Class
 *
 * @author Andres Gutierrez <andres@phalconphp.com>
 * @author Eduar Carvajal <eduar@phalconphp.com>
 * @version 0.1
 * @package Phalcon
*/
namespace Phalcon\Annotations;

/**
 * Phalcon\Annotations\AdapterInterface initializer
 * 
 * @see https://github.com/phalcon/cphalcon/blob/master/ext/annotations/adapterinterface.c
 */
interface AdapterInterface
{
	/**
	 * Sets the annotations parser
	 *
	 * @param \Phalcon\Annotations\ReaderInterface $reader
	 */
	public function setReader($reader);

	/**
	 * Returns the annotation reader
	 *
	 * @return \Phalcon\Annotations\ReaderInterface
	 */
	public function getReader();

	/**
	 * Parses or retrieves all the annotations found in a class
	 *
	 * @param string|object $className
	 * @return \Phalcon\Annotations\Reflection
	 */
	public function get($className);

	/**
	 * Returns the annotations found in all the class' methods
	 *
	 * @param string $className
	 * @return array
	 */
	public function getMethods($className);

	/**
	 * Returns the annotations found in a specific method
	 *
	 * @param string $className
	 * @param string $methodName
	 * @return \Phalcon\Annotations\Collection
	 */
	public function getMethod($className, $methodName);

	/**
	 * Returns the annotations found in all the class' methods
	 *
	 * @param string $className
	 * @return array
	 */
	public function getProperties($className);

	/**
	 * Returns the annotations found in a specific property
	 *
	 * @param string $className
	 * @param string $propertyName
	 * @return \Phalcon\Annotations\Collection
	 */
	public function getProperty($className, $propertyName);
}