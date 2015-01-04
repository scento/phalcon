<?php
/**
 * Assets Filter Interface
 *
 * @author Andres Gutierrez <andres@phalconphp.com>
 * @author Eduar Carvajal <eduar@phalconphp.com>
 * @version 1.2.6
 * @package Phalcon
*/
namespace Phalcon\Assets;

/**
 * Phalcon\Assets\FilterInterface initializer
 *
 * @see https://github.com/phalcon/cphalcon/1.2.6/master/ext/assets/filterinterface.c
 */
interface FilterInterface
{
    /**
     * Filters the content returning a string with the filtered content
     *
     * @param string $content
     * @return $content
     */
    public function filter($content);
}
