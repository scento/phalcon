<?php
/**
* Resultset
*
* @author Andres Gutierrez <andres@phalconphp.com>
* @author Eduar Carvajal <eduar@phalconphp.com>
* @author Wenzel Pünter <wenzel@phelix.me>
* @version 1.2.6
* @package Phalcon
*/
namespace Phalcon\Mvc\Model;

use \Phalcon\Mvc\Model\ResultsetInterface,
	\Phalcon\Mvc\Model\Exception,
	\Iterator,
	\SeekableIterator,
	\Countable,
	\ArrayAccess,
	\Serializable,
	\Closure;

/**
 * Phalcon\Mvc\Model\Resultset
 *
 * This component allows to Phalcon\Mvc\Model returns large resulsets with the minimum memory consumption
 * Resulsets can be traversed using a standard foreach or a while statement. If a resultset is serialized
 * it will dump all the rows into a big array. Then unserialize will retrieve the rows as they were before
 * serializing.
 *
 * <code>
 *
 * //Using a standard foreach
 * $robots = Robots::find(array("type='virtual'", "order" => "name"));
 * foreach ($robots as $robot) {
 *  echo $robot->name, "\n";
 * }
 *
 * //Using a while
 * $robots = Robots::find(array("type='virtual'", "order" => "name"));
 * $robots->rewind();
 * while ($robots->valid()) {
 *  $robot = $robots->current();
 *  echo $robot->name, "\n";
 *  $robots->next();
 * }
 * </code>
 *
 * @see https://github.com/phalcon/cphalcon/blob/1.2.6/ext/mvc/model/resultset.c
 */
abstract class Resultset implements ResultsetInterface, Iterator, SeekableIterator, Countable, ArrayAccess, Serializable
{
	/**
	 * Type: Full Result
	 * 
	 * @var int
	*/
	const TYPE_RESULT_FULL = 0;

	/**
	 * Type: Partial Result
	 * 
	 * @var int
	*/
	const TYPE_RESULT_PARTIAL = 1;

	/**
	 * Hydrate: Records
	 * 
	 * @var int
	*/
	const HYDRATE_RECORDS = 0;

	/**
	 * Hydrate: Objects
	 * 
	 * @var int
	*/
	const HYDRATE_OBJECTS = 2;

	/**
	 * Hydrate: Arrays
	 * 
	 * @var int
	*/
	const HYDRATE_ARRAYS = 1;

	/**
	 * Type
	 * 
	 * @var int
	 * @access protected
	*/
	protected $_type = 0;

	/**
	 * Result
	 * 
	 * @var null|\Phalcon\Db\ResultInterface
	 * @access protected
	*/
	protected $_result;

	/**
	 * Cache
	 * 
	 * @var null|\Phalcon\Cache\BackendInterface
	 * @access protected
	*/
	protected $_cache;

	/**
	 * Is Fresh
	 * 
	 * @var boolean
	 * @access protected
	*/
	protected $_isFresh = true;

	/**
	 * Pointer
	 * 
	 * @var int
	 * @access protected
	*/
	protected $_pointer = -1;

	/**
	 * Count
	 * 
	 * @var null|int
	 * @access protected
	*/
	protected $_count;

	/**
	 * Active Row
	 * 
	 * @var null|\Phalcon\Mvc\ModelInterface
	 * @access protected
	*/
	protected $_activeRow;

	/**
	 * Rows
	 * 
	 * @var null|array
	 * @access protected
	*/
	protected $_rows;

	/**
	 * Error Messages
	 * 
	 * @var null|array
	 * @access protected
	*/
	protected $_errorMessages;

	/**
	 * Hydrate Mode
	 * 
	 * @var int|null
	 * @access protected
	*/
	protected $_hydrateMode;

	/**
	 * Moves cursor to next row in the resultset
	 */
	public function next()
	{
		$this->_pointer++;
	}

	/**
	 * Gets pointer number of active row in the resultset
	 *
	 * @return int
	 */
	public function key()
	{
		return $this->_pointer;
	}

	/**
	 * Rewinds resultset to its beginning
	 */
	public function rewind()
	{
		if($this->_type === 1) {
			//Here the resultset act as a result that is fetched one by one
			if($this->_result != false && is_null($this->_activeRow) === false) {
				$this->_result->dataSeek(0);
			}
		} else {
			//Here the resultset acts as an array
			if(is_null($this->_rows) === true && is_object($this->_result) === true) {
				$this->_rows = $this->_result->fetchAll();
			}

			if(is_array($this->_rows) === true) {
				reset($this->_rows);
			}
		}

		$this->_pointer = 0;
	}

	/**
	 * Changes internal pointer to a specific position in the resultset
	 *
	 * @param int $position
	 */
	public function seek($position)
	{
		if(is_int($position) === false) {
			return;
		}

		//We only seek the records if the current position is different than the passed one
		if($this->_pointer !== $position) {
			if($this->_type === 1) {
				//Here the resultset is fetched one by one because it is large
				$result = $this->_result;
				$result->dataSeek($position);
			} else {
				//Here the resultset is a small array
				//We need to fetch the records because rows is null
				if(is_null($this->_rows) === true &&
					$this->_result != false) {
					$this->_rows = $this->_result->fetchAll();
				}

				if(is_array($this->_rows) === true) {
					for($i = 0; $i < $position; ++$i) {
						next($this->_rows);
					}
				}

				$this->_pointer = $position;
			}
		}
	}

	/**
	 * Counts how many rows are in the resultset
	 *
	 * @return int
	 */
	public function count()
	{
		//We only calculate the row number if it wasn't calculated before
		if(is_null($this->_count) === true) {
			$this->_count = 0;

			if($this->_type === 1) {
				//Here the resultset acts as a result that is fetched one by one
				if($this->_result != false) {
					$this->_count = (int)$this->_result->numRows();
				}
			} else {
				//Here the resultset acts as an array
				if(is_null($this->_rows) === true && is_object($this->_result) === true) {
					$this->_rows = $this->_result->fetchAll();
				}

				$this->_count = count($this->_rows);
			}
		}

		return $this->_count;
	}

	/**
	 * Checks whether offset exists in the resultset
	 *
	 * @param int $index
	 * @return boolean
	 * @throws Exception
	 */
	public function offsetExists($index)
	{
		if(is_int($index) === false) {
			throw new Exception('Invalid parameter type.');
		}

		return ($index < $this->count() ? true : false);
	}

	/**
	 * Gets row in a specific position of the resultset
	 *
	 * @param int $index
	 * @return \Phalcon\Mvc\ModelInterface
	 * @throws Exception
	 */
	public function offsetGet($index)
	{
		if(is_int($index) === false) {
			throw new Exception('Invalid parameter type.');
		}

		$count = $this->count();
		if($index < $count) {
			//Check if the last record returned is the current requested
			if($this->_pointer === $index) {
				return $this->current();
			}

			//Move to the specific position
			$this->seek($index);

			//Check if the last record returned is the requested
			if($this->valid() !== false) {
				return $this->current();
			}

			return false;
		}

		throw new Exception('The index does not exist in the cursor');
	}

	/**
	 * Resultsets cannot be changed. It has only been implemented to meet the definition of the ArrayAccess interface
	 *
	 * @param int $index
	 * @param \Phalcon\Mvc\ModelInterface $value
	 * @throws Exception
	 */
	public function offsetSet($index, $value)
	{
		throw new Exception('Cursor is an immutable ArrayAccess object');
	}

	/**
	 * Resultsets cannot be changed. It has only been implemented to meet the definition of the ArrayAccess interface
	 *
	 * @param int $offset
	 * @throws Exception
	 */
	public function offsetUnset($offset)
	{
		throw new Exception('Cursor is an immutable ArrayAccess object');
	}

	/**
	 * Returns the internal type of data retrieval that the resultset is using
	 *
	 * @return int
	 */
	public function getType()
	{
		return $this->_type;
	}

	/**
	 * Get first row in the resultset
	 *
	 * @return \Phalcon\Mvc\ModelInterface|boolean
	 */
	public function getFirst()
	{
		//Check if the last record returned is the current requested
		if($this->_pointer === 0) {
			return $this->current();
		}

		//Otherwise re-execute the statement
		$this->rewind();
		if($this->valid() !== false) {
			return $this->current();
		}

		return false;
	}

	/**
	 * Get last row in the resultset
	 *
	 * @return \Phalcon\Mvc\ModelInterface|boolean
	 */
	public function getLast()
	{
		$this->seek($this->count() - 1);
		if($this->valid() !== false) {
			return $this->current();
		}

		return false;
	}

	/**
	 * Set if the resultset is fresh or an old one cached
	 *
	 * @param boolean $isFresh
	 * @return \Phalcon\Mvc\Model\Resultset
	 * @throws Exception
	 */
	public function setIsFresh($isFresh)
	{
		if(is_bool($isFresh) === false) {
			throw new Exception('Invalid parameter type.');
		}

		$this->_isFresh = $isFresh;

		return $this;
	}

	/**
	 * Tell if the resultset if fresh or an old one cached
	 *
	 * @return boolean
	 */
	public function isFresh()
	{
		return $this->_isFresh;
	}

	/**
	 * Sets the hydration mode in the resultset
	 *
	 * @param int $hydrateMode
	 * @return \Phalcon\Mvc\Model\Resultset
	 * @throws Exception
	 */
	public function setHydrateMode($hydrateMode)
	{
		if(is_int($hydrateMode) === false) {
			throw new Exception('Invalid parameter type.');
		}

		$this->_hydrateMode = $hydrateMode;

		return $this;
	}

	/**
	 * Returns the current hydration mode
	 *
	 * @return int|null
	 */
	public function getHydrateMode()
	{
		return $this->_hydrateMode;
	}

	/**
	 * Returns the associated cache for the resultset
	 *
	 * @return \Phalcon\Cache\BackendInterface|null
	 */
	public function getCache()
	{
		return $this->_cache;
	}

	/**
	 * Returns current row in the resultset
	 *
	 * @return \Phalcon\Mvc\ModelInterface
	 */
	public function current()
	{
		return $this->_activeRow;
	}

	/**
	 * Returns the error messages produced by a batch operation
	 *
	 * @return \Phalcon\Mvc\Model\MessageInterface[]|null
	 */
	public function getMessages()
	{
		return $this->_errorMessages;
	}

	/**
	 * Deletes every record in the resultset
	 *
	 * @param Closure|null $conditionCallback
	 * @return boolean
	 * @throws Exception
	 */
	public function delete($conditionCallback = null)
	{
		if((is_object($conditionCallback) === false ||
			$conditionCallback instanceof Closure === false) &&
			is_null($conditionCallback) === false) {
			throw new Exception('Invalid parameter type.');
		}

		$transaction = false;
		$this->rewind();

		while($this->valid()) {
			$record = $this->current();

			//Start transaction
			if($transaction === false) {
				//We can only delete resultsets whose every element is a complete object
				if(method_exists($record, 'getWriteConnection') === false) {
					throw new Exception('The returned record is not valid');
				}

				$connection = $record->getWriteConnection();
				$connection->begin();
			}

			//Perform additional validations
			if(is_object($conditionCallback) === true) {
				if(call_user_func($conditionCallback, $record) === false) {
					continue;
				}
			}

			//Try to delete the record
			if($record->delete() !== true) {
				//Get the messages from the record that produces the error
				$this->_errorMessages = $record->getMessages();

				//Rollback the transaction
				$connection->rollback();
				$transaction = false;
				break;
			}

			//Next element
			$this->next();
		}

		//Commit the transaction
		if($transaction === true) {
			$connection->commit();
		}

		return true;
	}

	/**
	 * Filters a resultset returning only those the developer requires
	 *
	 *<code>
	 * $filtered = $robots->filter(function($robot){
	 *		if ($robot->id < 3) {
	 *			return $robot;
	 *		}
	 *	});
	 *</code>
	 *
	 * @param callable $filter
	 * @return \Phalcon\Mvc\Model[]
	 * @throws Exception
	 */
	public function filter($filter)
	{
		$records = array();
		$parameters = array();
		$this->rewind();

		while($this->valid()) {
			$record = $this->current();
			$parameters[0] = $record;
			$processedRecord = call_user_func_array($filter, $parameters);

			//Only add processed records to 'records' if the returned value is an array/object
			if(is_object($processedRecord) === false && is_array($processedRecord) === false) {
				continue;
			}

			$records[] = $processedRecord;
			$this->next();
		}

		return $records;
	}
}