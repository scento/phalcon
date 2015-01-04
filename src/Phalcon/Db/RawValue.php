<?php
/**
 * Raw Value
 *
 * @author Andres Gutierrez <andres@phalconphp.com>
 * @author Eduar Carvajal <eduar@phalconphp.com>
 * @author Wenzel Pünter <wenzel@phelix.me>
 * @version 1.2.6
 * @package Phalcon
*/
namespace Phalcon\Db;

use \Phalcon\Db\Exception;

/**
 * Phalcon\Db\RawValue
 *
 * This class allows to insert/update raw data without quoting or formating.
 *
 *The next example shows how to use the MySQL now() function as a field value.
 *
 *<code>
 *  $subscriber = new Subscribers();
 *  $subscriber->email = 'andres@phalconphp.com';
 *  $subscriber->created_at = new Phalcon\Db\RawValue('now()');
 *  $subscriber->save();
 *</code>
 *
 * @see https://github.com/phalcon/cphalcon/blob/1.2.6/ext/db/rawvalue.c
 */
class RawValue
{
    /**
     * Value
     *
     * @var null|string
     * @access protected
    */
    protected $_value;

    /**
     * \Phalcon\Db\RawValue constructor
     *
     * @param string $value
     */
    public function __construct($value)
    {
        if (is_string($value) === false) {
            throw new Exception('Invalid parameter type.');
        }

        $this->_value = $value;
    }

    /**
     * Returns internal raw value without quoting or formating
     *
     * @return string
     */
    public function getValue()
    {
        return $this->_value;
    }

    /**
     * Magic method __toString returns raw value without quoting or formating
     */
    public function __toString()
    {
        return $this->_value;
    }
}
