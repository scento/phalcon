<?php
/**
 * Status
 *
 * @author Andres Gutierrez <andres@phalconphp.com>
 * @author Eduar Carvajal <eduar@phalconphp.com>
 * @author Wenzel Pünter <wenzel@phelix.me>
 * @version 1.2.6
 * @package Phalcon
*/
namespace Phalcon\Mvc\Model\Query;

use \Phalcon\Mvc\ModelInterface,
	\Phalcon\Mvc\Model\Exception,
	\Phalcon\Mvc\Model\Query\StatusInterface;

/**
 * Phalcon\Mvc\Model\Query\Status
 *
 * This class represents the status returned by a PHQL
 * statement like INSERT, UPDATE or DELETE. It offers context
 * information and the related messages produced by the
 * model which finally executes the operations when it fails
 *
 *<code>
 *$phql = "UPDATE Robots SET name = :name:, type = :type:, year = :year: WHERE id = :id:";
 *$status = $app->modelsManager->executeQuery($phql, array(
 *   'id' => 100,
 *   'name' => 'Astroy Boy',
 *   'type' => 'mechanical',
 *   'year' => 1959
 *));
 *
 * //Check if the update was successful
 * if ($status->success() == true) {
 *   echo 'OK';
 * }
 *</code>
 * 
 * @see https://github.com/phalcon/cphalcon/blob/1.2.6/ext/mvc/model/query/status.c
 */
class Status implements StatusInterface
{
	/**
	 * Success
	 * 
	 * @var null|boolean
	 * @access protected
	*/
	protected $_success;

	/**
	 * Model
	 * 
	 * @var null|\Phalcon\Mvc\ModelInterface
	 * @access protected
	*/
	protected $_model;

	/**
	 * \Phalcon\Mvc\Model\Query\Status
	 *
	 * @param boolean $success
	 * @param \Phalcon\Mvc\ModelInterface $model
	 * @throws Exception
	 */
	public function __construct($success, $model)
	{
		if(is_bool($success) === false ||
			is_object($model) === false ||
			$model instanceof ModelInterface === false) {
			throw new Exception('Invalid parameter type.');
		}

		$this->_success = $success;
		$this->_model = $model;
	}

	/**
	 * Returns the model that executed the action
	 *
	 * @return \Phalcon\Mvc\ModelInterface
	 */
	public function getModel()
	{
		return $this->_model;
	}

	/**
	 * Returns the messages produced by a failed operation
	 *
	 * @return \Phalcon\Mvc\Model\MessageInterface[]
	 */
	public function getMessages()
	{
		if(is_object($this->_model) === true) {
			return $this->_model->getMessages();
		}

		return array();
	}

	/**
	 * Allows to check if the executed operation was successful
	 *
	 * @return boolean
	 */
	public function success()
	{
		return $this->_success;
	}
}