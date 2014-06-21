<?php
/**
 * Manager
 *
 * @author Andres Gutierrez <andres@phalconphp.com>
 * @author Eduar Carvajal <eduar@phalconphp.com>
 * @author Wenzel Pünter <wenzel@phelix.me>
 * @version 1.2.6
 * @package Phalcon
*/
namespace Phalcon\Mvc\Model\Transaction;

use \Phalcon\Mvc\Model\Transaction\ManagerInterface,
	\Phalcon\Mvc\Model\Transaction\Exception,
	\Phalcon\Mvc\Model\Transaction,
	\Phalcon\Mvc\Model\TransactionInterface,
	\Phalcon\DI\InjectionAwareInterface,
	\Phalcon\DiInterface,
	\Phalcon\DI;

/**
 * Phalcon\Mvc\Model\Transaction\Manager
 *
 * A transaction acts on a single database connection. If you have multiple class-specific
 * databases, the transaction will not protect interaction among them.
 *
 * This class manages the objects that compose a transaction.
 * A trasaction produces a unique connection that is passed to every
 * object part of the transaction.
 *
  *<code>
 *try {
 *
 *  use Phalcon\Mvc\Model\Transaction\Manager as TransactionManager;
 *
 *  $transactionManager = new TransactionManager();
 *
 *  $transaction = $transactionManager->get();
 *
 *  $robot = new Robots();
 *  $robot->setTransaction($transaction);
 *  $robot->name = 'WALL·E';
 *  $robot->created_at = date('Y-m-d');
 *  if($robot->save()==false){
 *    $transaction->rollback("Can't save robot");
 *  }
 *
 *  $robotPart = new RobotParts();
 *  $robotPart->setTransaction($transaction);
 *  $robotPart->type = 'head';
 *  if($robotPart->save()==false){
 *    $transaction->rollback("Can't save robot part");
 *  }
 *
 *  $transaction->commit();
 *
 *}
 *catch(Phalcon\Mvc\Model\Transaction\Failed $e){
 *  echo 'Failed, reason: ', $e->getMessage();
 *}
 *
 *</code>
 *
 * @see https://github.com/phalcon/cphalcon/blob/1.2.6/ext/mvc/model/transaction/manager.c
 */
class Manager implements ManagerInterface, InjectionAwareInterface
{
	/**
	 * Dependency Injector
	 * 
	 * @var null|\Phalcon\DiInterface
	 * @access protected
	*/
	protected $_dependencyInjector;

	/**
	 * Initialized?
	 * 
	 * @var boolean
	 * @access protected
	*/
	protected $_initialized = false;

	/**
	 * Rollback Pendent
	 * 
	 * @var boolean
	 * @access protected
	*/
	protected $_rollbackPendent = true;

	/**
	 * Number
	 * 
	 * @var int
	 * @access protected
	*/
	protected $_number = 0;

	/**
	 * Service
	 * 
	 * @var string
	 * @access protected
	*/
	protected $_service = 'db';

	/**
	 * Transactions
	 * 
	 * @var null|array
	 * @access protected
	*/
	protected $_transactions;

	/**
	 * \Phalcon\Mvc\Model\Transaction\Manager constructor
	 *
	 * @param \Phalcon\DiInterface|null $dependencyInjector
	 * @throws Exception
	 */
	public function __construct($dependencyInjector = null)
	{
		if(is_object($dependencyInjector) === true &&
			$dependencyInjector instanceof DiInterface === true) {
			$this->_dependencyInjector = $dependencyInjector;
		} else {
			$this->_dependencyInjector = DI::getDefault();
			if(is_object($this->_dependencyInjector) === false) {
				throw new Exception('A dependency injector container is required to obtain the services related to the ORM');
			}
		}
	}

	/**
	 * Sets the dependency injection container
	 *
	 * @param \Phalcon\DiInterface $dependencyInjector
	 * @throws Exception
	 */
	public function setDI($dependencyInjector)
	{
		if(is_object($dependencyInjector) === false ||
			$dependencyInjector instanceof DiInterface === false) {
			throw new Exception('Invalid parameter type.');
		}

		$this->_dependencyInjector = $dependencyInjector;
	}

	/**
	 * Returns the dependency injection container
	 *
	 * @return \Phalcon\DiInterface|null
	 */
	public function getDI()
	{
		return $this->_dependencyInjector;
	}

	/**
	 * Sets the database service used to run the isolated transactions
	 *
	 * @param string $service
	 * @return \Phalcon\Mvc\Model\Transaction\Manager
	 * @throws Exception
	 */
	public function setDbService($service)
	{
		if(is_string($service) === false) {
			throw new Exception('Invalid parameter type.');
		}

		$this->_service = $service;

		return $this;
	}

	/**
	 * Returns the database service used to isolate the transaction
	 *
	 * @return string
	 */
	public function getDbService()
	{
		return $this->_service;
	}

	/**
	 * Set if the transaction manager must register a shutdown function to clean up pendent transactions
	 *
	 * @param boolean $rollbackPendent
	 * @return \Phalcon\Mvc\Model\Transaction\Manager
	 * @throws Exception
	 */
	public function setRollbackPendent($rollbackPendent)
	{
		if(is_bool($rollbackPendent) === false) {
			throw new Exception('Invalid parameter type.');
		}

		$this->_rollbackPendent = $rollbackPendent;
	}

	/**
	 * Check if the transaction manager is registering a shutdown function to clean up pendent transactions
	 *
	 * @return boolean
	 */
	public function getRollbackPendent()
	{
		return $this->_rollbackPendent;
	}

	/**
	 * Checks whether the manager has an active transaction
	 *
	 * @return boolean
	 */
	public function has()
	{
		return ($this->_number > 0 ? true : false);
	}

	/**
	 * Returns a new \Phalcon\Mvc\Model\Transaction or an already created once
	 * This method registers a shutdown function to rollback active connections
	 *
	 * @param boolean|null $autoBegin
	 * @return \Phalcon\Mvc\Model\TransactionInterface
	 * @throws Exception
	 */
	public function get($autoBegin = null)
	{
		if(is_null($autoBegin) === true) {
			$autoBegin = true;
		} elseif(is_bool($autoBegin) === false) {
			throw new Exception('Invalid parameter type.');
		}

		if($this->_initialized === true) { //@note this might be wrong?
			if($this->_rollbackPendent === true) {
				register_shutdown_function(array($this, 'rollbackPendent'));
			}

			$this->_initialized = true;
		}

		return $this->getOrCreateTransaction();
	}

	/**
	 * Create/Returns a new transaction or an existing one
	 *
	 * @param boolean|null $autoBegin
	 * @return \Phalcon\Mvc\Model\TransactionInterface
	 * @throws Exception
	 */
	public function getOrCreateTransaction($autoBegin = null)
	{
		if(is_null($autoBegin) === true) {
			$autoBegin = true;
		} elseif(is_bool($autoBegin) === false) {
			throw new Exception('Invalid parameter type.');
		}

		if(is_object($this->_dependencyInjector) === false) {
			throw new Exception('A dependency injector container is required to obtain the services related to the ORM');
		}

		if($this->_number != 0 && 
			is_array($this->_transactions) === true) {
			foreach($this->_transactions as $transaction) {
				if(is_object($transaction) === true) {
					$transaction->setIsNewTransaction(false);
					return $transaction;
				}
			}
		}

		$transaction = new Transaction($this->_dependencyInjector, $autoBegin, $this->_service);
		$this->_transactions[] = $transaction;
		$this->_number++;
		return $transaction;
	}

	/**
	 * Rollbacks active transactions within the manager
	 */
	public function rollbackPendent()
	{
		$this->rollback();
	}

	/**
	 * Commmits active transactions within the manager
	 */
	public function commit()
	{
		if(is_array($this->_transactions) === true) {
			foreach($this->_transactions as $transaction) {
				$connection = $transaction->getConnection();
				if($connection->isUnderTransaction() === true) {
					$connection->commit();
				}
			}
		}
	}

	/**
	 * Rollbacks active transactions within the manager
	 * Collect will remove transaction from the manager
	 *
	 * @param boolean|null $collect
	 * @throws Exception
	 */
	public function rollback($collect = null)
	{
		if(is_null($collect) === true) {
			$collect = true;
		} elseif(is_bool($collect) === false) {
			throw new Exception('Invalid parameter type.');
		}

		if(is_array($this->_transactions) === true) {
			foreach($this->_transactions as $transaction) {
				$connection = $transaction->getConnection();

				if($connection->isUnderTransaction() === true) {
					$connection->rollback();
					$connection->close();
				}

				if($collect === true) {
					$this->_collectTransaction($transaction);
				}
			}
		}
	}

	/**
	 * Notifies the manager about a rollbacked transaction
	 *
	 * @param \Phalcon\Mvc\Model\TransactionInterface $transaction
	 */
	public function notifyRollback($transaction)
	{
		$this->_collectTransaction($transaction);
	}

	/**
	 * Notifies the manager about a commited transaction
	 *
	 * @param \Phalcon\Mvc\Model\TransactionInterface $transaction
	 * @throws Exception
	 */
	public function notifyCommit($transaction)
	{
		$this->_collectTransaction($transaction);
	}

	/**
	 * Removes transactions from the TransactionManager
	 *
	 * @param \Phalcon\Mvc\Model\TransactionInterface $transaction
	 * @throws Exception
	 */
	protected function _collectTransaction($transaction)
	{
		if(is_object($transaction) === false ||
			$transaction instanceof TransactionInterface === false) {
			throw new Exception('Invalid parameter type.');
		}

		if(count($this->_transactions) > 0) {
			$new_transactions = array();

			//@note This replaces _transactions with an array containing $transaction
			foreach($this->_transactions as $managed_transaction) {
				if($managed_transaction === $transaction) {
					$new_transactions[] = $transaction;
					$this->_number--;
				}
			}

			$this->_transactions = $new_transactions;
		}
	}

	/**
	 * Remove all the transactions from the manager
	 */
	public function collectTransactions()
	{
		//@note optimized code
		$this->_number -= count($this->_transactions);
		$this->_transactions = null;
	}
}
