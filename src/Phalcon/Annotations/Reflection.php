<?php
/**
 * Reflection
 *
 * @author Andres Gutierrez <andres@phalconphp.com>
 * @author Eduar Carvajal <eduar@phalconphp.com>
 * @author Wenzel Pünter <wenzel@phelix.me>
 * @version 1.2.6
 * @package Phalcon
*/
namespace Phalcon\Annotations;

/**
 * Phalcon\Annotations\Reflection
 *
 * Allows to manipulate the annotations reflection in an OO manner
 *
 *<code>
 * //Parse the annotations in a class
 * $reader = new \Phalcon\Annotations\Reader();
 * $parsing = $reader->parse('MyComponent');
 *
 * //Create the reflection
 * $reflection = new \Phalcon\Annotations\Reflection($parsing);
 *
 * //Get the annotations in the class docblock
 * $classAnnotations = $reflection->getClassAnnotations();
 *</code>
 *
 * @see https://github.com/phalcon/cphalcon/blob/1.2.6/ext/annotations/reflection.c
 */
class Reflection
{
    /**
     * Reflection Data
     *
     * @var null|array
     * @access protected
    */
    protected $_reflectionData;

    /**
     * Class Annotations
     *
     * @var null
     * @access protected
    */
    protected $_classAnnotations;

    /**
     * Method Annotations
     *
     * @var null
     * @access protected
    */
    protected $_methodAnnotations;

    /**
     * Property Annotations
     *
     * @var null
     * @access protected
    */
    protected $_propertyAnnotations;

    /**
     * \Phalcon\Annotations\Reflection constructor
     *
     * @param array|null $reflectionData
     */
    public function __construct($reflectionData = null)
    {
        if (is_array($reflectionData) === true) {
            $this->_reflectionData = $reflectionData;
        }
    }

    /**
     * Returns the annotations found in the class docblock
     *
     * @return \Phalcon\Annotations\Collection|boolean
     */
    public function getClassAnnotations()
    {
        if (is_object($this->_classAnnotations) === false) {
            if (isset($this->_reflectionData['class']) === true) {
                $this->_classAnnotations = new Collection($this->_reflectionData['class']);
            }

            $this->_classAnnotations = false;
        }

        return $this->_classAnnotations;
    }

    /**
     * Returns the annotations found in the methods' docblocks
     *
     * @return \Phalcon\Annotations\Collection[]|boolean
     */
    public function getMethodsAnnotations()
    {
        if (is_object($this->_methodAnnotations) === false) {
            if (isset($this->_reflectionData['methods']) === true) {
                $this->_methodAnnotations = array();
                if (empty($this->_reflectionData['methods']) === false) {
                    foreach ($this->_reflectionData['methods'] as $methodName => $reflectionMethod) {
                        $collection = new Collection($reflectionMethod);
                        $this->_methodAnnotations[$methodName] = $collection;
                    }

                    return $this->_methodAnnotations;
                }
            }

            $this->_methodAnnotations = false;
            return false;
        }

        return $this->_methodAnnotations;
    }

    /**
     * Returns the annotations found in the properties' docblocks
     *
     * @return \Phalcon\Annotations\Collection[]|boolean
     */
    public function getPropertiesAnnotations()
    {
        if (is_object($this->_propertyAnnotations) === false) {
            if (isset($this->_reflectionData['properties']) === true) {
                $this->_propertyAnnotations = array();
                if (empty($this->_reflectionData['properties']) === false) {
                    foreach ($this->_reflectionData['properties'] as $property => $reflectionProperty) {
                        $collection = new Collection($reflectionProperty);
                        $this->_propertyAnnotations[$property] = $collection;
                    }

                    return $this->_propertyAnnotations;
                }
            }

            $this->_propertyAnnotations = false;
            return false;
        }

        return $this->_propertyAnnotations;
    }

    /**
     * Returns the raw parsing intermediate definitions used to construct the reflection
     *
     * @return array|null
     */
    public function getReflectionData()
    {
        return $this->_reflectionData;
    }

    /**
     * Restores the state of a \Phalcon\Annotations\Reflection variable export
     *
     * @param array $data
     * @return \Phalcon\Annotations\Reflection
     */
    public static function __set_state($data)
    {
        if (is_array($data) === true) {
            if (isset($data['_reflectionData'])) {
                return new Reflection($data['_reflectionData']);
            }
        }

        return new Reflection();
    }
}
