<?php
/**
 * Behavior Interface
 *
 * @author Andres Gutierrez <andres@phalconphp.com>
 * @author Eduar Carvajal <eduar@phalconphp.com>
 * @version 1.2.6
 * @package Phalcon
*/
namespace Phalcon\Mvc\Model;

/**
 * Phalcon\Mvc\Model\BehaviorInterface initializer
 * 
 * @see https://github.com/phalcon/cphalcon/blob/1.2.6/ext/mvc/model/behaviorinterface.c
 */
interface BehaviorInterface
{
	/**
	 * \Phalcon\Mvc\Model\Behavior
	 *
	 * @param array|null $options
	 */
	public function __construct($options = null);

	/**
	 * This method receives the notifications from the EventsManager
	 *
	 * @param string $type
	 * @param \Phalcon\Mvc\ModelInterface $model
	 */
	public function notify($type, $model);

	/**
	 * Calls a method when it's missing in the model
	 *
	 * @param \Phalcon\Mvc\ModelInterface $model
	 * @param string $method
	 * @param array|null $arguments
	 */
	public function missingMethod($model, $method, $arguments = null);
}