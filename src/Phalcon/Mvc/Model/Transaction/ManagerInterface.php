<?php
/**
* Manager Interface
*
* @author Andres Gutierrez <andres@phalconphp.com>
* @author Eduar Carvajal <eduar@phalconphp.com>
* @version 1.2.6
* @package Phalcon
*/
namespace Phalcon\Mvc\Model\Transaction;

/**
 * Phalcon\Mvc\Model\Transaction\ManagerInterface initializer
 * 
 * @see https://github.com/phalcon/cphalcon/blob/1.2.6/ext/mvc/model/transaction/managerinterface.c
 */
interface ManagerInterface
{
	/**
	 * \Phalcon\Mvc\Model\Transaction\Manager
	 *
	 * @param \Phalcon\DiInterface|null $dependencyInjector
	 */
	public function __construct($dependencyInjector = null);

	/**
	 * Checks whether manager has an active transaction
	 *
	 * @return boolean
	 */
	public function has();

	/**
	 * Returns a new \Phalcon\Mvc\Model\Transaction or an already created once
	 *
	 * @param boolean|null $autoBegin
	 * @return \Phalcon\Mvc\Model\TransactionInterface
	 */
	public function get($autoBegin = null);

	/**
	 * Rollbacks active transactions within the manager
	 */
	public function rollbackPendent();

	/**
	 * Commmits active transactions within the manager
	 */
	public function commit();

	/**
	 * Rollbacks active transactions within the manager
	 * Collect will remove transaction from the manager
	 *
	 * @param boolean|null $collect
	 */
	public function rollback($collect = null);

	/**
	 * Notifies the manager about a rollbacked transaction
	 *
	 * @param \Phalcon\Mvc\Model\TransactionInterface $transaction
	 */
	public function notifyRollback($transaction);

	/**
	 * Notifies the manager about a commited transaction
	 *
	 * @param \Phalcon\Mvc\Model\TransactionInterface $transaction
	 */
	public function notifyCommit($transaction);

	/**
	 * Remove all the transactions from the manager
	 */
	public function collectTransactions();
}