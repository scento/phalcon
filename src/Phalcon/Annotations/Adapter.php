<?php
/**
 * Annotations Adapter Class
 *
 * @author Andres Gutierrez <andres@phalconphp.com>
 * @author Eduar Carvajal <eduar@phalconphp.com>
 * @author Wenzel Pünter <wenzel@phelix.me>
 * @version 1.2.6
 * @package Phalcon
*/
namespace Phalcon\Annotations;

use \Phalcon\Annotations\Exception;
use \Phalcon\Annotations\ReaderInterface;
use \Phalcon\Annotations\Reader;
use \Phalcon\Annotations\Reflection;
use \Phalcon\Annotations\Collection;

/**
 * Phalcon\Annotations\Adapter
 *
 * This is the base class for Phalcon\Annotations adapters
 *
 * @see https://github.com/phalcon/cphalcon/blob/1.2.6/ext/annotations/adapter.c
 */
abstract class Adapter
{

    /**
     * Annotations Parser
     *
     * @var null|object
     * @access protected
    */
    protected $_reader;

    /**
     * Annotations
     *
     * @var null|array
     * @access protected
    */
    protected $_annotations;

    /**
     * Sets the annotations parser
     *
     * @param \Phalcon\Annotations\ReaderInterface $reader
     * @throws Exception
     */
    public function setReader($reader)
    {
        if (is_object($reader) === false ||
            $reader instanceof ReaderInterface === false) {
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
        if (is_object($this->_reader) === false) {
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
        if (is_object($className) === true) {
            $realClassName = get_class($className);
        } elseif (is_string($className) === true) {
            $realClassName = $className;
        } else {
            throw new Exception('Invalid parameter type.');
        }

        if (isset($this->_annotations[$realClassName]) === true) {
            return $this->_annotations[$realClassName];
        }

        //Try to read the annotations from the adapter
        $classAnnotations = $this->read($realClassName);
        if (is_null($classAnnotations) === true) {
            $reader = $this->getReader();
            $parsedAnnotations = $reader->parse($realClassName);

            if (is_array($parsedAnnotations) === true) {
                $classAnnotations = new Reflection($parsedAnnotations);
                $this->_annotations[$realClassName] = $classAnnotations;
                $this->write($realClassName, $classAnnotations);
            }
        }

        return $classAnnotations;
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
        if (is_object($annotations) === true) {
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
        if (is_string($methodName) === false) {
            throw new Exception('Invalid parameter type.');
        }

        $classAnnotations = $this->get($className);

        if (is_object($classAnnotations) === true) {
            $methods = $classAnnotations->getMethodsAnnotations();
            if (is_array($methods) === true) {
                foreach ($methods as $name => $method) {
                    if ($name == $methodName) {
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
        $classAnnotations = $this->get($className);

        if (is_object($classAnnotations) === true) {
            return $classAnnotations->getPropertiesAnnotations();
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
        if (is_string($propertyName) === false) {
            throw new Exception('Invalid parameter type.');
        }

        $classAnnotations = $this->get($className);

        if (is_object($classAnnotations) === true) {
            $properties = $classAnnotations->getPropertiesAnnotations();
            if (is_array($properties) === true) {
                foreach ($properties as $name => $property) {
                    if ($name == $propertyName) {
                        return $property;
                    }
                }
            }
        }

        return new Collection();
    }
}
