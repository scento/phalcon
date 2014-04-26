<?php
/**
 * Factory Default
 *
 * @author Andres Gutierrez <andres@phalconphp.com>
 * @author Eduar Carvajal <eduar@phalconphp.com>
 * @author Wenzel Pünter <wenzel@phelix.me>
 * @version 1.2.6
 * @package Phalcon
*/
namespace Phalcon\DI;

use \Phalcon\DI,
	\Phalcon\DiInterface,
	\Phalcon\DI\Service;

/**
 * Phalcon\DI\FactoryDefault
 *
 * This is a variant of the standard Phalcon\DI. By default it automatically
 * registers all the services provided by the framework. Thanks to this, the developer does not need
 * to register each service individually providing a full stack framework
 * 
 * @see https://github.com/phalcon/cphalcon/blob/1.2.6/ext/di/factorydefault.c
 */
class FactoryDefault extends DI implements DiInterface
{
	/**
	 * \Phalcon\DI\FactoryDefault constructor
	 */
	public function __construct()
	{
		parent::__construct();

		$services = array();

		/* Base */
		$services['router'] = new Service('router', 'Phalcon\\Mvc\\Router', true);
		$services['dispatcher'] = new Service('dispatcher', 'Phalcon\\Mvc\\Dispatcher', true);
		$services['url'] = new Service('url', 'Phalcon\\Mvc\\Url', true);

		/* Models */
		$services['modelsManager'] = new Service('modelsManager', 'Phalcon\\Mvc\\Model\\Manager', true);
		$services['modelsMetadata'] = new Service('modelsMetadata', 'Phalcon\\Mvc\\Model\\MetaData\\Memory', true);

		/* Request/Response */
		$services['response'] = new Service('response', 'Phalcon\\Http\\Response', true);
		$services['cookies'] = new Service('cookies', 'Phalcon\\Http\\Response\\Cookies', true);
		$services['request'] = new Service('request', 'Phalcon\\Http\\Request', true);

		/* Filter/Escaper */
		$services['filter'] = new Service('filter', 'Phalcon\\Filter', true);
		$services['escaper'] = new Service('escaper', 'Phalcon\\Escaper', true);

		/* Annotations */
		$services['annotations'] = new Service('annotations', 'Phalcon\\Annotations\\Adapter\\Memory', true);

		/* Security */
		$services['security'] = new Service('security', 'Phalcon\\Security', true);
		$services['crypt'] = new Service('crypt', 'Phalcon\\Crypt', true);

		/* Flash */
		$services['flash'] = new Service('flash', 'Phalcon\\Flash\\Direct', true);
		$services['flashSession'] = new Service('flashSession', 'Phalcon\\Flash\\Session', true);

		/* Tag/Helpers */
		$services['tag'] = new Service('tag', 'Phalcon\\Tag', true);

		/* Session */
		$services['session'] = new Service('session', 'Phalcon\\Session\\Adapter\\Files', true);
		$services['sessionBag'] = new Service('sessionBag', 'Phalcon\\Session\\Bag', true);

		/* Managers */
		$services['eventsManager'] = new Service('eventsManager', 'Phalcon\\Events\\Manager', true);
		$services['transactions'] = new Service('transactions', 'Phalcon\\Mvc\\Model\\Transaction\\Manager', true);
		$services['assets'] = new Service('assets', 'Phalcon\\Assets\\Manager', true);

		//Update the internal services property
		$this->_services = $services;
	}
}