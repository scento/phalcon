<?php
/**
 * User Filter Interface
 *
 * @author Andres Gutierrez <andres@phalconphp.com>
 * @author Eduar Carvajal <eduar@phalconphp.com>
 * @version 1.2.6
 * @package Phalcon
*/
namespace Phalcon\Filter;

/**
 * Phalcon\Filter\UserFilterInterface initializer
 *
 * @see https://github.com/phalcon/cphalcon/blob/1.2.6/ext/filter/userfilterinterface.c
 */
interface UserFilterInterface
{
    /**
     * Filters a value
     *
     * @param mixed $value
     * @return mixed
     */
    public function filter($value);
}
