<?php
/**
 * Failed
 *
 * @author Andres Gutierrez <andres@phalconphp.com>
 * @author Eduar Carvajal <eduar@phalconphp.com>
 * @author Wenzel Pünter <wenzel@phelix.me>
 * @version 1.2.6
 * @package Phalcon
*/
namespace Phalcon\Mvc\Model\Transaction;

use \Phalcon\Mvc\Model\Transaction\Exception,
	\Phalcon\Mvc\ModelInterface;

/**
 * Phalcon\Mvc\Model\Transaction\Failed
 *
 * This class will be thrown to exit a try/catch block for isolated transactions
 * 
 * @see https://github.com/phalcon/cphalcon/blob/1.2.6/ext/mvc/model/transaction/failed.c
 */
class Failed extends Exception
{
	/**
	 * Record
	 * 
	 * @var null|\Phalcon\Mvc\ModelInterface
	 * @access protected
	*/
	protected $_record;

	/**
	 * \Phalcon\Mvc\Model\Transaction\Failed constructor
	 *
	 * @param string $message
	 * @param \Phalcon\Mvc\ModelInterface $record
	 * @throws Exception
	 */
	public function __construct($message, $record)
	{
		if(is_string($message) === false ||
			is_object($record) === false ||
			$record instanceof ModelInterface === false) {
			throw new Exception('Invalid parameter type.');
		}

		$this->_record = $record;
		parent::__construct($message);
	}

	/**
	 * Returns validation record messages which stop the transaction
	 *
	 * @return \Phalcon\Mvc\Model\MessageInterface[]
	 */
	public function getRecordMessages()
	{
		if(is_null($this->_record) === false) {
			return $this->_record->getMessages();
		}

		return $this->getMessage();
	}

	/**
	 * Returns validation record messages which stop the transaction
	 *
	 * @return \Phalcon\Mvc\ModelInterface
	 */
	public function getRecord()
	{
		return $this->_record;
	}
}