<?php
/**
 * User Component
 *
 * @author Andres Gutierrez <andres@phalconphp.com>
 * @author Eduar Carvajal <eduar@phalconphp.com>
 * @version 1.2.6
 * @package Phalcon
*/
namespace Phalcon\Mvc\User;

use Phalcon\DI\Injectable,
	\Phalcon\Events\EventsAwareInterface,
	\Phalcon\DI\InjectionAwareInterface;

/**
 * Phalcon\Mvc\User\Component
 *
 * This class can be used to provide user components easy access to services
 * in the application
 * 
 * @see https://github.com/phalcon/cphalcon/blob/1.2.6/ext/mvc/user/component.c
 */
class Component extends Injectable implements EventsAwareInterface, InjectionAwareInterface
{

}