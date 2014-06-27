<?php
/**
 * Status Interface
 *
 * @author Andres Gutierrez <andres@phalconphp.com>
 * @author Eduar Carvajal <eduar@phalconphp.com>
 * @version 1.2.6
 * @package Phalcon
*/
namespace Phalcon\Mvc\Model\Query;

/**
 * Phalcon\Mvc\Model\Query\StatusInterface initializer
 * 
 * @see https://github.com/phalcon/cphalcon/blob/1.2.6/ext/mvc/model/query/statusinterface.c
 */
interface StatusInterface
{
	/**
	 * \Phalcon\Mvc\Model\Query\Status
	 *
	 * @param boolean $success
	 * @param \Phalcon\Mvc\ModelInterface $model
	 */
	public function __construct($success, $model);

	/**
	 * Returns the model which executed the action
	 *
	 * @return \Phalcon\Mvc\ModelInterface
	 */
	public function getModel();

	/**
	 * Returns the messages produced by a operation failed
	 *
	 * @return \Phalcon\Mvc\Model\MessageInterface[]
	 */
	public function getMessages();

	/**
	 * Allows to check if the executed operation was successful
	 *
	 * @return boolean
	 */
	public function success();
}