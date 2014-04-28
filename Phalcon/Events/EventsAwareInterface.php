<?php
/**
 * Events Aware Interface
 *
 * @author Andres Gutierrez <andres@phalconphp.com>
 * @author Eduar Carvajal <eduar@phalconphp.com>
 * @version 1.2.6
 * @package Phalcon
*/
namespace Phalcon\Events;

/**
 * Phalcon\Events\EventsAwareInterface initializer
 * 
 * @see https://github.com/phalcon/cphalcon/blob/1.2.6/ext/events/eventsawareinterface.c
 */
interface EventsAwareInterface
{
	/**
	 * Sets the events manager
	 *
	 * @param \Phalcon\Events\ManagerInterface $eventsManager
	 */
	public function setEventsManager($eventsManager);

	/**
	 * Returns the internal event manager
	 *
	 * @return \Phalcon\Events\ManagerInterface
	 */
	public function getEventsManager();
}