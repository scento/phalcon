<?php
/**
 * CLI Factory Default
 *
 * @author Andres Gutierrez <andres@phalconphp.com>
 * @author Eduar Carvajal <eduar@phalconphp.com>
 * @author Wenzel Pünter <wenzel@phelix.me>
 * @version 1.2.6
 * @package Phalcon
*/
namespace Phalcon\DI\FactoryDefault;

use \Phalcon\DI\FactoryDefault,
	\Phalcon\DiInterface,
	\Phalcon\DI\Service;

/**
 * Phalcon\DI\FactoryDefault\CLI
 *
 * This is a variant of the standard Phalcon\DI. By default it automatically
 * registers all the services provided by the framework.
 * Thanks to this, the developer does not need to register each service individually.
 * This class is specially suitable for CLI applications
 * 
 * @see https://github.com/phalcon/cphalcon/blob/1.2.6/ext/di/factorydefault/cli.c
 */
class CLI extends FactoryDefault implements DiInterface
{
	/**
	 * \Phalcon\DI\FactoryDefault\CLI constructor
	 */
	public function __construct()
	{
		//@note It might be better if CLI directly extends DI, 
		//since everything new from FactoryDefault gets overwritten
		parent::__construct();

		$services = array();

		/* Routing */
		$services['router'] = new Service('router', 'Phalcon\\CLI\\Router');
		$services['dispatcher'] = new Service('dispatcher', 'Phalcon\\CLI\\Dispatcher');

		/* ORM */
		$services['modelsManager'] = 
			new Service('modelsManager', 'Phalcon\\Mvc\\Model\Manager');
		$services['modelsMetadata'] = 
			new Service('modelsMetadata', 'Phalcon\\Mvc\\Model\\Metadata\\Memory');

		/* Filter/Escaper */
		$services['filter'] = new Service('filter', 'Phalcon\\Filter', true);
		$services['escaper'] = new Service('escaper', 'Phalcon\\Escaper', true);

		/* Other */
		$services['annotations'] = 
			new Service('annotations', 'Phalcon\\Annotations\\Adapter\\Memory', true);
		$services['security'] = new Service('security', 'Phalcon\\Security', true);

		/* Managers */
		$services['eventsManager'] = 
			new Service('eventsManager', 'Phalcon\\Events\\Manager', true);
		$services['transactionManager'] = 
			new Service('transactionManager', 'Phalcon\\Mvc\\Model\\Transaction\\Manager');

		//Update array
		$this->_services = $services;
	}
}