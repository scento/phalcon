<?php
/**
 * User Plugin
 *
 * @author Andres Gutierrez <andres@phalconphp.com>
 * @author Eduar Carvajal <eduar@phalconphp.com>
 * @version 1.2.6
 * @package Phalcon
*/
namespace Phalcon\Mvc\User;

use \Phalcon\DI\Injectable;
use \Phalcon\Events\EventsAwareInterface;
use \Phalcon\DI\InjectionAwareInterface;

/**
 * Phalcon\Mvc\User\Plugin
 *
 * This class can be used to provide user plugins an easy access to services
 * in the application
 *
 * @see https://github.com/phalcon/cphalcon/blob/1.2.6/ext/mvc/user/plugin.c
 */
class Plugin extends Injectable implements EventsAwareInterface, InjectionAwareInterface
{
}
