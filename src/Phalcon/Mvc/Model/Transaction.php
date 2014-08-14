<?php
/**
* Transaction
*
* @author Andres Gutierrez <andres@phalconphp.com>
* @author Eduar Carvajal <eduar@phalconphp.com>
* @author Wenzel Pünter <wenzel@phelix.me>
* @version 1.2.6
* @package Phalcon
*/
namespace Phalcon\Mvc\Model;

use \Phalcon\Mvc\Model\Transaction\Failed,
	\Phalcon\Mvc\Model\TransactionInterface,
	\Phalcon\Mvc\Model\Exception,
	\Phalcon\Mvc\ModelInterface,
	\Phalcon\DiInterface;

/**
 * Phalcon\Mvc\Model\Transaction
 *
 * Transactions are protective blocks where SQL statements are only permanent if they can
 * all succeed as one atomic action. Phalcon\Transaction is intended to be used with Phalcon_Model_Base.
 * Phalcon Transactions should be created using Phalcon\Transaction\Manager.
 *
 *<code>
 *try {
 *
 *  $manager = new Phalcon\Mvc\Model\Transaction\Manager();
 *
 *  $transaction = $manager->get();
 *
 *  $robot = new Robots();
 *  $robot->setTransaction($transaction);
 *  $robot->name = 'WALL·E';
 *  $robot->created_at = date('Y-m-d');
 *  if ($robot->save() == false) {
 *    $transaction->rollback("Can't save robot");
 *  }
 *
 *  $robotPart = new RobotParts();
 *  $robotPart->setTransaction($transaction);
 *  $robotPart->type = 'head';
 *  if ($robotPart->save() == false) {
 *    $transaction->rollback("Can't save robot part");
 *  }
 *
 *  $transaction->commit();
 *
 *} catch(Phalcon\Mvc\Model\Transaction\Failed $e) {
 *  echo 'Failed, reason: ', $e->getMessage();
 *}
 *
 *</code>
 * 
 * @see https://github.com/phalcon/cphalcon/blob/1.2.6/ext/mvc/model/transaction.c
 */
class Transaction implements TransactionInterface
{
	/**
	 * Connection
	 * 
	 * @var null|\Phalcon\Db\AdapterInterface
	 * @access protected
	*/
	protected $_connection;

	/**
	 * Active Transaction?
	 * 
	 * @var boolean
	 * @access protected
	*/
	protected $_activeTransaction = false;

	/**
	 * Is New Transaction?
	 * 
	 * @var boolean
	 * @access protected
	*/
	protected $_isNewTransaction = true;

	/**
	 * Rollback on Abort?
	 * 
	 * @var boolean
	 * @access protected
	*/
	protected $_rollbackOnAbort = false;

	/**
	 * Manager
	 * 
	 * @var null|\Phalcon\Mvc\Model\Transaction\ManagerInterface
	 * @access protected
	*/
	protected $_manager;

	/**
	 * Messages
	 * 
	 * @var null|array
	 * @access protected
	*/
	protected $_messages;

	/**
	 * Rollback Record
	 * 
	 * @var null|\Phalcon\Mvc\ModelInterface
	 * @access protected
	*/
	protected $_rollbackRecord;

	/**
	 * \Phalcon\Mvc\Model\Transaction constructor
	 *
	 * @param \Phalcon\DiInterface $dependencyInjector
	 * @param boolean|null $autoBegin
	 * @param string|null $service
	 * @throws Exception
	 */
	public function __construct($dependencyInjector, $autoBegin = null, $service = null)
	{
		if(is_object($dependencyInjector) === false ||
			$dependencyInjector instanceof DiInterface === false) {
			throw new Exception('A dependency injector container is required to obtain the services related to the ORM');
		}

		if(is_null($autoBegin) === true) {
			$autoBegin = false;
		} elseif(is_bool($autoBegin) === false) {
			throw new Exception('Invalid parameter type.');
		}

		if(is_null($service) === true) {
			$service = 'db';
		} elseif(is_string($service) === false) {
			throw new Exception('Invalid parameter type.');
		}

		$this->_connection = $dependencyInjector->get($service);

		if($autoBegin === true) {
			$this->_connection->begin();
		}
	}

	/**
	 * Sets transaction manager related to the transaction
	 *
	 * @param \Phalcon\Mvc\Model\Transaction\ManagerInterface $manager
	 * @throws Exception
	 */
	public function setTransactionManager($manager)
	{
		if(is_object($manager) === false ||
			$manager instanceof ManagerInterface === false) {
			throw new Exception('Manager must be an Object');
		}

		$this->_manager = $manager;
	}

	/**
	 * Starts the transaction
	 *
	 * @return boolean
	 */
	public function begin()
	{
		return $this->_connection->begin();
	}

	/**
	 * Commits the transaction
	 *
	 * @return boolean
	 */
	public function commit()
	{
		if(is_object($this->_manager) === true) {
			call_user_func(array($this->_manager, 'notifyCommit'), $this);
		}

		return $this->_connection->commit();
	}

	/**
	 * Rollbacks the transaction
	 *
	 * @param string|null $rollbackMessage
	 * @param \Phalcon\Mvc\ModelInterface|null $rollbackRecord
	 * @return boolean
	 * @throws Exception
	 */
	public function rollback($rollbackMessage = null, $rollbackRecord = null)
	{
		if(is_string($rollbackMessage) === false &&
			is_null($rollbackMessage) === false) {
			throw new Exception('Invalid parameter type.');
		}

		if((is_object($rollbackRecord) === false ||
			$rollbackRecord instanceof ModelInterface === false) &&
			is_null($rollbackRecord) === false) {
			throw new Exception('Invalid parameter type.');
		}

		if(is_object($this->_manager) === true) {
			call_user_func(array($this->_manager, 'notifyRollback'), $this);
		}

		if($this->_connection->rollback() === true) {
			if(is_null($rollbackMessage) === true) {
				$rollbackMessage = 'Transaction aborted';
			}

			if(is_object($rollbackRecord) === true) {
				$this->_rollbackRecord = $rollbackRecord;
			}

			throw new Failed($rollbackMessage, $rollbackRecord);
		}
	}

	/**
	 * Returns the connection related to transaction
	 *
	 * @return \Phalcon\Db\AdapterInterface
	 */
	public function getConnection()
	{
		if($this->_rollbackOnAbort === true &&
			connection_aborted() === true) {
			$this->rollback('The request was aborted');
		}

		return $this->_connection;
	}

	/**
	 * Sets if is a reused transaction or new once
	 *
	 * @param boolean $isNew
	 * @throws Exception
	 */
	public function setIsNewTransaction($isNew)
	{
		if(is_bool($isNew) === false) {
			throw new Exception('Invalid parameter type.');
		}

		$this->_isNewTransaction = $isNew;
	}

	/**
	 * Sets flag to rollback on abort the HTTP connection
	 *
	 * @param boolean $rollbackOnAbort
	 * @throws Exception
	 */
	public function setRollbackOnAbort($rollbackOnAbort)
	{
		if(is_bool($rollbackOnAbort) === false) {
			throw new Exception('Invalid parameter type.');
		}

		$this->_rollbackOnAbort = $rollbackOnAbort;
	}

	/**
	 * Checks whether transaction is managed by a transaction manager
	 *
	 * @return boolean
	 */
	public function isManaged()
	{
		return is_object($this->_manager);
	}

	/**
	 * Returns validations messages from last save try
	 *
	 * @return array
	 */
	public function getMessages()
	{
		return $this->_messages;
	}

	/**
	 * Checks whether internal connection is under an active transaction
	 *
	 * @return boolean
	 */
	public function isValid()
	{
		return $this->_connection->isUnderTransaction();
	}

	/**
	 * Sets object which generates rollback action
	 *
	 * @param \Phalcon\Mvc\ModelInterface $record
	 * @throws Exception
	 */
	public function setRollbackedRecord($record)
	{
		if(is_object($record) === false ||
			$record instanceof ModelInterface === false) {
			throw new Exception('Invalid parameter type.');
		}

		$this->_rollbackRecord = $record;
	}
}