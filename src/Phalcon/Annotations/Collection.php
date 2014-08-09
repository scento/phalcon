<?php
/**
 * Collection
 *
 * @author Andres Gutierrez <andres@phalconphp.com>
 * @author Eduar Carvajal <eduar@phalconphp.com>
 * @author Wenzel Pünter <wenzel@phelix.me>
 * @version 1.2.6
 * @package Phalcon
*/
namespace Phalcon\Annotations;

use \Iterator,
	\Countable,
	\Phalcon\Annotations\Exception,
	\Phalcon\Annotations\Annotation;

/**
 * Phalcon\Annotations\Collection
 *
 * Represents a collection of annotations. This class allows to traverse a group of annotations easily
 *
 *<code>
 * //Traverse annotations
 * foreach ($classAnnotations as $annotation) {
 *     echo 'Name=', $annotation->getName(), PHP_EOL;
 * }
 *
 * //Check if the annotations has a specific
 * var_dump($classAnnotations->has('Cacheable'));
 *
 * //Get an specific annotation in the collection
 * $annotation = $classAnnotations->get('Cacheable');
 *</code>
 * 
 * @see https://github.com/phalcon/cphalcon/blob/1.2.6/ext/annotations/collection.c
 */
class Collection implements Iterator, Countable
{
	/**
	 * Position
	 * 
	 * @var int
	 * @access protected
	*/
	protected $_position = 0;

	/**
	 * Annotations
	 * 
	 * @var null|array
	 * @access protected
	*/
	protected $_annotations = null;

	/**
	 * \Phalcon\Annotations\Collection constructor
	 *
	 * @param array|null $reflectionData
	 * @throws Exception
	 */
	public function __construct($reflectionData = null)
	{
		if(is_array($reflectionData) === true) {
			$annotations = array();

			foreach($reflectionData as $annotation_data)
			{
				$annotations[] = new Annotation($annotation_data);
			}

			$this->_annotations = $annotations;
		} elseif(is_null($reflectionData) === false) {
			throw new Exception('Reflection data must be an array');
		}
	}

	/**
	 * Returns the number of annotations in the collection
	 *
	 * @return int
	 */
	public function count()
	{
		if(is_array($this->_annotations) === true)
		{
			return count($this->_annotations);
		} else {
			return 0;
		}
	}

	/**
	 * Rewinds the internal iterator
	 */
	public function rewind()
	{
		$this->_position = 0;
	}

	/**
	 * Returns the current annotation in the iterator
	 *
	 * @return \Phalcon\Annotations\Annotation|null
	 */
	public function current()
	{
		if(isset($this->_annotations[$this->_position]) === true)
		{
			return $this->_annotations[$this->_position];
		} else {
			return null;
		}
	}

	/**
	 * Returns the current position/key in the iterator
	 *
	 * @return int
	 */
	public function key()
	{
		return $this->_position;
	}

	/**
	 * Moves the internal iteration pointer to the next position
	 *
	 */
	public function next()
	{
		++$this->_position;
	}

	/**
	 * Check if the current annotation in the iterator is valid
	 *
	 * @return boolean
	 */
	public function valid()
	{
		return isset($this->_annotations[$this->_position]);
	}

	/**
	 * Returns the internal annotations as an array
	 *
	 * @return \Phalcon\Annotations\Annotation[]|null
	 */
	public function getAnnotations()
	{
		return $this->_annotations;
	}

	/**
	 * Returns the first annotation that match a name
	 *
	 * @param string $name
	 * @return \Phalcon\Annotations\Annotation
	 * @throws Exception
	 */
	public function get($name)
	{
		if(is_string($name) === false)
		{
			throw new Exception('Invalid parameter type.');
		}

		if(is_array($this->_annotations) === true)
		{
			foreach($this->_annotations as $annotation)
			{
				$annotation_name = $annotation->getName();
				if($name == $annotation_name)
				{
					return $annotation;
				}
			}
		}

		throw new Exception('The collection doesn\'t have an annotation called '.$name.'\'');
	}

	/**
	 * Returns all the annotations that match a name
	 *
	 * @param string $name
	 * @return \Phalcon\Annotations\Annotation[]
	 * @throws Exception
	 */
	public function getAll($name)
	{
		if(is_string($name) === false)
		{
			throw new Exception('Invalid parameter type.');
		}

		if(is_array($this->_annotations) === true)
		{
			$found = array();

			foreach($this->_annotations as $annotation)
			{
				$annotation_name = $annotation->getName();
				if($name == $annotation_name)
				{
					$found[] = $annotation;
				}
			}
		}

		return $found;
	}

	/**
	 * Check if an annotation exists in a collection
	 *
	 * @param string $name
	 * @return boolean
	 * @throws Exception
	 */
	public function has($name)
	{
		if(is_string($name) === false)
		{
			throw new Exception('Invalid parameter type.');
		}

		if(is_array($this->_annotations) === true)
		{
			foreach($this->_annotations as $annotation)
			{
				$annotation_name = $annotation->getName();
				if($name == $annotation_name)
				{
					return true;
				}
			}
		}

		return false;
	}
}