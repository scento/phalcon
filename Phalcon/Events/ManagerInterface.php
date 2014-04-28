<?php
/**
 * Manager Interface
 *
 * @author Andres Gutierrez <andres@phalconphp.com>
 * @author Eduar Carvajal <eduar@phalconphp.com>
 * @version 1.2.6
 * @package Phalcon
*/
namespace Phalcon\Events;

/**
 * Phalcon\Events\ManagerInterface initializer
 * 
 * @see https://github.com/phalcon/cphalcon/blob/1.2.6/ext/events/managerinterface.c
 */
interface ManagerInterface
{
	/**
	 * Attach a listener to the events manager
	 *
	 * @param string $eventType
	 * @param object $handler
	 */
	public function attach($eventType, $handler);

	/**
	 * Removes all events from the EventsManager
	 *
	 * @param string|null $type
	 */
	public function dettachAll($type = null);

	/**
	 * Fires a event in the events manager causing that the acive listeners will be notified about it
	 *
	 * @param string $eventType
	 * @param object $source
	 * @param mixed|null $data
	 * @return mixed
	 */
	public function fire($eventType, $source, $data = null);

	/**
	 * Returns all the attached listeners of a certain type
	 *
	 * @param string $type
	 * @return array
	 */
	public function getListeners($type);
}