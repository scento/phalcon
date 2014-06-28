<?php
/**
 * Result Interface
 *
 * @author Andres Gutierrez <andres@phalconphp.com>
 * @author Eduar Carvajal <eduar@phalconphp.com>
 * @version 1.2.6
 * @package Phalcon
*/
namespace Phalcon\Db;

/**
 * Phalcon\Db\ResultInterface initializer
 * 
 * @see https://github.com/phalcon/cphalcon/blob/1.2.6/ext/db/resultinterface.c
 */
interface ResultInterface
{
	/**
	 * \Phalcon\Db\Result\Pdo constructor //@note wrong constructor name
	 *
	 * @param \Phalcon\Db\AdapterInterface $connection
	 * @param \PDOStatement $result
	 * @param string|null $sqlStatement
	 * @param array|null $bindParams
	 * @param array|null $bindTypes
	 */
	public function __construct($connection, $result, $sqlStatement = null, $bindParams = null, $bindTypes = null);

	/**
	 * Allows to executes the statement again. Some database systems don't support scrollable cursors,
	 * So, as cursors are forward only, we need to execute the cursor again to fetch rows from the begining
	 *
	 * @return boolean
	 */
	public function execute();

	/**
	 * Fetches an array/object of strings that corresponds to the fetched row, or FALSE if there are no more rows.
	 * This method is affected by the active fetch flag set using \Phalcon\Db\Result\Pdo::setFetchMode
	 *
	 * @return mixed
	 */
	public function fetch();

	/**
	 * Returns an array of strings that corresponds to the fetched row, or FALSE if there are no more rows.
	 * This method is affected by the active fetch flag set using \Phalcon\Db\Result\Pdo::setFetchMode
	 *
	 * @return mixed
	 */
	public function fetchArray();

	/**
	 * Returns an array of arrays containing all the records in the result
	 * This method is affected by the active fetch flag set using \Phalcon\Db\Result\Pdo::setFetchMode
	 *
	 * @return array
	 */
	public function fetchAll();

	/**
	 * Gets number of rows returned by a resulset
	 *
	 * @return int
	 */
	public function numRows();

	/**
	 * Moves internal resulset cursor to another position letting us to fetch a certain row
	 *
	 * @param int $number
	 */
	public function dataSeek($number);

	/**
	 * Changes the fetching mode affecting \Phalcon\Db\Result\Pdo::fetch()
	 *
	 * @param int $fetchMode
	 */
	public function setFetchMode($fetchMode);

	/**
	 * Gets the internal PDO result object
	 *
	 * @return \PDOStatement
	 */
	public function getInternalResult();
}