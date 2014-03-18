<?php
/**
 * Annotations Adapter Class
 *
 * @author Andres Gutierrez <andres@phalconphp.com>
 * @author Eduar Carvajal <eduar@phalconphp.com>
 * @author Wenzel Pünter <wenzel@phelix.me>
 * @version 0.1
 * @package Phalcon
*/
namespace Phalcon\Annotations;

use \Phalcon\Annotations\Exception,
	\Phalcon\Annotations\ReaderInterface,
	\Phalcon\Annotations\Reader,
	\Phalcon\Annotations\Reflection;

/**
 * Phalcon\Annotations\Adapter
 *
 * This is the base class for Phalcon\Annotations adapters
 * 
 * @see https://github.com/phalcon/cphalcon/blob/master/ext/annotations/adapter.c
 */
abstract class Adapter
{

	/**
	 * Annotations Parser
	 * 
	 * @var null
	 * @access protected
	*/
	protected $_reader = null;

	/**
	 * Annotations
	 * 
	 * @var null
	 * @access protected
	*/
	protected $_annotations = null;

	/**
	 * Sets the annotations parser
	 *
	 * @param \Phalcon\Annotations\ReaderInterface $reader
	 * @throws Exception
	 */
	public function setReader(ReaderInterface $reader)
	{
		if(is_object($reader) === false)
		{
			throw new Exception('Invalid annotations reader');
		}

		$this->_reader = $reader;
	}


	/**
	 * Returns the annotation reader
	 *
	 * @return \Phalcon\Annotations\ReaderInterface
	 */
	public function getReader()
	{
		if(is_object($this->_reader) === false)
		{
			$this->_reader = new Reader();
		}
		return $this->_reader;
	}


	/**
	 * Parses or retrieves all the annotations found in a class
	 *
	 * @param string|object $className
	 * @return \Phalcon\Annotations\Reflection
	 * @throws Exception
	 */
	public function get($className)
	{
		if(is_object($className) === true) {
			$real_class_name = get_class($className);
		} elseif(is_string($className) === true) {
			$real_class_name = $className;
		} else {
			throw new Exception('Invalid parameter type.');
		}

		if(isset($this->_annotations[$real_class_name]) === true)
		{
			return $this->_annotations[$real_class_name];
		}

		//Try to read the annotations from the adapter
		$class_annotations = $this->read($real_class_name);
		if(is_null($class_annotations) === true)
		{
			$reader = $this->getReader();
			$parsed_annotations = $reader->parse($real_class_name);

			if(is_array($parsed_annotations) === true)
			{
				$class_annotations = new Reflection($parsed_annotations);
				$this->_annotations[$real_class_name] = $class_annotations;
				$this->write($real_class_name, $class_annotations);
			}
		}

		return $class_annotations;
	}


	/**
	 * Returns the annotations found in all the class' methods
	 *
	 * @param string $className
	 * @return array
	 */
	public function getMethods($className)
	{
		$annotations = $this->get($className);
		if(is_object($annotations) === true)
		{
			$annotations->getMethodsAnnotations();
			return $annotations;
		}

		return array();
	}


	/**
	 * Returns the annotations found in a specific method
	 *
	 * @param string $className
	 * @param string $methodName
	 * @return \Phalcon\Annotations\Collection
	 * @throws Exception
	 */
	public function getMethod($className, $methodName)
	{
		if(is_string($methodName) === false)
		{
			throw new Exception('Invalid parameter type.');
		}

		$class_annotations = $this->get($className);

		if(is_object($class_annotations) === true)
		{
			$methods = $class_annotations->getMethodsAnnotations();
			if(is_array($methods) === true)
			{
				foreach($methods as $name => $method)
				{
					if($name == $methodName)
					{
						return $method;
					}
				}
			}
		}

		return new Collection();
	}


	/**
	 * Returns the annotations found in all the class' methods
	 *
	 * @param string $className
	 * @return array
	 */
	public function getProperties($className)
	{
		$class_annotations = $this->get($className);

		if(is_object($class_annotations) === true)
		{
			return $class_annotations->getPropertiesAnnotations();
		}

		return array();
	}


	/**
	 * Returns the annotations found in a specific property
	 *
	 * @param string $className
	 * @param string $propertyName
	 * @return \Phalcon\Annotations\Collection
	 * @throws Exception
	 */
	public function getProperty($className, $propertyName)
	{
		if(is_string($propertyName) === false)
		{
			throw new Exception('Invalid parameter type.');
		}

		$class_annotations = $this->get($className);

		if(is_object($class_annotations) === true)
		{
			$properties = $class_annotations->getPropertiesAnnotations();
			if(is_array($properties) === true)
			{
				foreach($properties as $name => $property)
				{
					if($name == $propertyName)
					{
						return $property;
					}
				}
			}
		}

		return new Collection();
	}
}