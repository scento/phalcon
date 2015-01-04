<?php
/**
 * Injection Aware Interface
 *
 * @author Andres Gutierrez <andres@phalconphp.com>
 * @author Eduar Carvajal <eduar@phalconphp.com>
 * @version 1.2.6
 * @package Phalcon
*/
namespace Phalcon\DI;

/**
 * Phalcon\DI\InjectionAwareInterface initializer
 *
 * @see https://github.com/phalcon/cphalcon/blob/1.2.6/ext/di/injectionawareinterface.c
 */
interface InjectionAwareInterface
{
    /**
     * Sets the dependency injector
     *
     * @param \Phalcon\DiInterface $dependencyInjector
     */
    public function setDI($dependencyInjector);

    /**
     * Returns the internal dependency injector
     *
     * @return \Phalcon\DiInterface
     */
    public function getDI();
}
