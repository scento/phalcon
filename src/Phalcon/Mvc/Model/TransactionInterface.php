<?php
/**
 * Transaction Interface
 *
 * @author Andres Gutierrez <andres@phalconphp.com>
 * @author Eduar Carvajal <eduar@phalconphp.com>
 * @version 1.2.6
 * @package Phalcon
*/
namespace Phalcon\Mvc\Model;

/**
 * Phalcon\Mvc\Model\TransactionInterface initializer
 *
 * @see https://github.com/phalcon/cphalcon/blob/1.2.6/ext/mvc/model/transactioninterface.c
 */
interface TransactionInterface
{
    /**
     * \Phalcon\Mvc\Model\Transaction constructor
     *
     * @param \Phalcon\DiInterface $dependencyInjector
     * @param boolean|null $autoBegin
     * @param string|null $service
     */
    public function __construct($dependencyInjector, $autoBegin = null, $service = null);

    /**
     * Sets transaction manager related to the transaction
     *
     * @param \Phalcon\Mvc\Model\Transaction\ManagerInterface $manager
     */
    public function setTransactionManager($manager);

    /**
     * Starts the transaction
     *
     * @return boolean
     */
    public function begin();

    /**
     * Commits the transaction
     *
     * @return boolean
     */
    public function commit();

    /**
     * Rollbacks the transaction
     *
     * @param string|null $rollbackMessage
     * @param \Phalcon\Mvc\ModelInterface|null $rollbackRecord
     * @return boolean
     */
    public function rollback($rollbackMessage = null, $rollbackRecord = null);

    /**
     * Returns connection related to transaction
     *
     * @return string
     */
    public function getConnection();

    /**
     * Sets if is a reused transaction or new once
     *
     * @param boolean $isNew
     */
    public function setIsNewTransaction($isNew);

    /**
     * Sets flag to rollback on abort the HTTP connection
     *
     * @param boolean $rollbackOnAbort
     */
    public function setRollbackOnAbort($rollbackOnAbort);

    /**
     * Checks whether transaction is managed by a transaction manager
     *
     * @return boolean
     */
    public function isManaged();

    /**
     * Returns validations messages from last save try
     *
     * @return array
     */
    public function getMessages();

    /**
     * Checks whether internal connection is under an active transaction
     *
     * @return boolean
     */
    public function isValid();

    /**
     * Sets object which generates rollback action
     *
     * @param \Phalcon\Mvc\ModelInterface $record
     */
    public function setRollbackedRecord($record);
}
