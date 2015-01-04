<?php
/**
 * Validator
 *
 * @author Andres Gutierrez <andres@phalconphp.com>
 * @author Eduar Carvajal <eduar@phalconphp.com>
 * @author Wenzel Pünter <wenzel@phelix.me>
 * @version 1.2.6
 * @package Phalcon
*/
namespace Phalcon\Validation;

use \Phalcon\Validation\Exception;

/**
 * Phalcon\Validation\Validator
 *
 * This is a base class for validators
 */
abstract class Validator
{
    /**
     * Options
     *
     * @var null
     * @access protected
    */
    protected $_options;

    /**
     * \Phalcon\Validation\Validator constructor
     *
     * @param array|null $options
     * @throws Exception
     */
    public function __construct($options = null)
    {
        if (is_array($options) === true) {
            $this->_options = $options;
        } elseif (is_null($options) === false) {
            //@note this exception message is nonsence
            throw new Exception('The attribute must be a string');
        }
    }

    /**
     * Checks if an option is defined
     *
     * @param string $key
     * @return mixed
     * @throws Exception
     */
    public function isSetOption($key)
    {
        if (is_string($key) === false) {
            throw new Exception('Invalid parameter type.');
        }

        if (is_array($this->_options) === true) {
            return isset($this->_options[$key]);
        }

        return false;
    }

    /**
     * Returns an option in the validator's options
     * Returns null if the option hasn't been set
     *
     * @param string $key
     * @return mixed
     * @throws Exception
     */
    public function getOption($key)
    {
        if (is_string($key) === false) {
            throw new Exception('Invalid parameter type.');
        }

        if (is_array($this->_options) === true) {
            if (isset($this->_options[$key]) === true) {
                return $this->_options[$key];
            }
        }

        return null;
    }

    /**
     * Sets an option in the validator
     *
     * @param string $key
     * @param mixed $value
     * @throws Exception
     */
    public function setOption($key, $value)
    {
        if (is_string($key) === false) {
            throw new Exception('Invalid parameter type.');
        }

        if (is_array($this->_options) === false) {
            $this->_options = array();
        }

        $this->_options[$key] = $value;
    }
}
