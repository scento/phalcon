<?php
/**
 * Module Definition Interface
 *
 * @author Andres Gutierrez <andres@phalconphp.com>
 * @author Eduar Carvajal <eduar@phalconphp.com>
 * @version 1.2.6
 * @package Phalcon
*/
namespace Phalcon\Mvc;

/**
 * Phalcon\Mvc\ModuleDefinitionInterface initializer
 * 
 * @see https://github.com/phalcon/cphalcon/blob/1.2.6/ext/mvc/moduledefinitioninterface.c
 */
interface ModuleDefinitionInterface
{
	/**
	 * Registers an autoloader related to the module
	 *
	 * @param \Phalcon\DiInterface|null $dependencyInjector
	 */
	public function registerAutoloaders($dependencyInjector = null);

	/**
	 * Registers services related to the module
	 *
	 * @param \Phalcon\DiInterface $dependencyInjector
	 */
	public function registerServices($dependencyInjector);
}