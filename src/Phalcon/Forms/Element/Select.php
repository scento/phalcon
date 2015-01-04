<?php
/**
 * Select
 *
 * @author Andres Gutierrez <andres@phalconphp.com>
 * @author Eduar Carvajal <eduar@phalconphp.com>
 * @author Wenzel Pünter <wenzel@phelix.me>
 * @version 1.2.6
 * @package Phalcon
*/
namespace Phalcon\Forms\Element;

use \Phalcon\Forms\Element;
use \Phalcon\Forms\ElementInterface;
use \Phalcon\Forms\Exception;
use \Phalcon\Tag\Select as TagSelect;

/**
 * Phalcon\Forms\Element\Select
 *
 * Component SELECT (choice) for forms
 *
 * @see https://github.com/phalcon/cphalcon/blob/1.2.6/ext/forms/element/select.c
 */
class Select extends Element implements ElementInterface
{
    /**
     * Options Values
     *
     * @var null|array|object
     * @access protected
    */
    protected $_optionsValues;

    /**
     * \Phalcon\Forms\Element constructor
     *
     * @param string $name
     * @param object|array|null $options
     * @param array|null $attributes
     * @throws Exception
     */
    public function __construct($name, $options = null, $attributes = null)
    {
        if (is_object($options) === false &&
            is_array($options) === false &&
            is_null($options) === false) {
            throw new Exception('Invalid parameter type.');
        }

        $this->_optionsValues = $options;
        parent::__construct($name, $attributes);
    }

    /**
     * Set the choice's options
     *
     * @param array|object $options
     * @return \Phalcon\Forms\Element
     * @throws Exception
     */
    public function setOptions($options)
    {
        if (is_object($options) === false &&
            is_array($options) === false) {
            throw new Exception('Invalid parameter type.');
        }

        $this->_optionsValues = $options;

        return $this;
    }

    /**
     * Returns the choices' options
     *
     * @return array|object|null
     */
    public function getOptions()
    {
        return $this->_optionsValues;
    }

    /**
     * Adds an option to the current options
     *
     * @param array $option
     * @return $this
     * @throws Exception
     */
    public function addOption($option)
    {
        if (is_array($option) === false) {
            throw new Exception('Invalid parameter type.');
        }

        $this->_optionsValues[] = $option;

        return $this;
    }

    /**
     * Renders the element widget returning html
     *
     * @param array|null $attributes
     * @return string
     * @throws Exception
     */
    public function render($attributes = null)
    {
        if (is_array($attributes) === false &&
            is_null($attributes) === false) {
            throw new Exception('Invalid parameter type.');
        }

        //Merge passed attributes with previously defined ones
        return TagSelect::selectField(
            $this->prepareAttributes($attributes),
            $this->_optionsValues
        );
    }
}
