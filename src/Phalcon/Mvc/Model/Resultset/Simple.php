<?php
/**
 * Simple Resultset
 *
 * @author Andres Gutierrez <andres@phalconphp.com>
 * @author Eduar Carvajal <eduar@phalconphp.com>
 * @author Wenzel Pünter <wenzel@phelix.me>
 * @version 1.2.6
 * @package Phalcon
*/
namespace Phalcon\Mvc\Model\Resultset;

use \Phalcon\Mvc\Model\Resultset,
	\Phalcon\Mvc\Model\ResultsetInterface,
	\Phalcon\Mvc\Model\Exception,
	\Phalcon\Mvc\ModelInterface,
	\Phalcon\Cache\BackendInterface,
	\Phalcon\Db\Result\Pdo,
	\Serializable,
	\ArrayAccess,
	\Countable,
	\SeekableIterator,
	\Iterator;

/**
 * Phalcon\Mvc\Model\Resultset\Simple
 *
 * Simple resultsets only contains complete objects.
 * This class builds every complete object as it is required
 * 
 * @see https://github.com/phalcon/cphalcon/blob/1.2.6/ext/mvc/model/resultset/simple.c
 */
class Simple extends Resultset implements Serializable, ArrayAccess, Countable, 
SeekableIterator, Iterator, ResultsetInterface
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
	 * Model
	 * 
	 * @var null|\Phalcon\Mvc\ModelInterface
	 * @access protected
	*/
	protected $_model;

	/**
	 * Column Map
	 * 
	 * @var null|array
	 * @access protected
	*/
	protected $_columnMap;

	/**
	 * Keep Snapshots
	 * 
	 * @var boolean
	 * @access protected
	*/
	protected $_keepSnapshots = false;

	/**
	 * \Phalcon\Mvc\Model\Resultset\Simple constructor
	 *
	 * @param array $columnMap
	 * @param \Phalcon\Mvc\ModelInterface $model
	 * @param \Phalcon\Db\Result\Pdo $result
	 * @param \Phalcon\Cache\BackendInterface|null $cache
	 * @param boolean|null $keepSnapshots
	 * @throws Exception
	 */
	public function __construct($columnMap, $model, $result, $cache = null, $keepSnapshots = null)
	{
		if(is_array($columnMap) === false ||
			is_object($model) === false ||
			$model instanceof ModelInterface === false) {
			throw new Exception('Invalid parameter type.');
		}

		if(is_null($cache) === false &&
			(is_object($cache) === false ||
				$cache instanceof BackendInterface === false)) {
			throw new Exception('Invalid parameter type.');
		}

		if(is_bool($keepSnapshots) === false &&
			is_null($keepSnapshots) === false) {
			throw new Exception('Invalid parameter type.');
		}

		$this->_model = $model;
		$this->_result = $result;
		$this->_cache = $cache;
		$this->_columnMap = $columnMap;
		$this->_keepSnapshots = $keepSnapshots;

		if(is_object($result) === false &&
			$result instanceof Pdo === false) {
			return;
		}

		//Use only fetch assoc
		$result->setFetchMode(1);
		$rowCount = $result->numRows();

		//Check if it's a big resultset
		if($limit < $rowCount) {
			$this->_type = 1;
		} else {
			$this->_type = 0;
		}

		//Update the row-count
		$this->_count = $rowCount;
	}

	/**
	 * Check whether the internal resource has rows to fetch
	 *
	 * @return boolean
	 */
	public function valid()
	{
		if($this->_type === 1) {
			$result = $this->_result;
			if(is_object($result) === true) {
				$row = $result->fetch($result); //@note ?!
			} else {
				$row = false;
			}
		} else {
			$rows = $this->_rows;
			if(is_array($rows) === false) {
				$result = $this->_result;
				if(is_object($result) === true) {
					$rows = $result->fetchAll();
					$this->_rows = $rows;
				}
			}

			if(is_array($rows) === true) {
				$row = current($rows);
				if($row !== false) {
					next($row);
				}
			} else {
				$row = false;
			}
		}

		if(is_array($row) === false) {
			$this->_activeRow = false;
			return false;
		}

		//Set records as dirty state PERSISTENT by default
		$dirtyState = 0;

		//Get current hydration mode
		$hydrateMode = $this->_hydrateMode;

		//Tell if the resultset is keeping snapshots
		$keepSnapshots = $this->_keepSnapshots;

		//Get the resultset column map
		$columnMap = $this->_columnMap;

		//Hydrate based on the current hydration
		switch((int)$hydrateMode) {
			case 0:
				//$this->model is the base entity
				$model = $this->_model;

				//Perform the standard hydration based on objects
				$activeRow = Model::cloneResultMap($model, $row, $columnMap, $dirtyState, $keepSnapshots);
				break;
			default:
				//Other kinds of hydrations
				$activeRow = Model::cloneResultMapHydrate($row, $columnMap, $hydrateMode);
				break;
		}

		$this->_activeRow = $activeRow;
		return true;
	}

	/**
	 * Returns a complete resultset as an array, if the resultset has a big number of rows
	 * it could consume more memory than it currently does. Exporting the resultset to an array
	 * couldn't be faster with a large number of records
	 *
	 * @param boolean|null $renameColumns
	 * @return array
	 * @throws Exception
	 */
	public function toArray($renameColumns = null)
	{
		if(is_null($renameColumns) === true) {
			$renameColumns = true;
		} elseif(is_bool($renameColumns) === false) {
			throw new Exception('Invalid parameter type.');
		}

		if($this->_type === 1) {
			$result = $this->_result;
			if(is_object($result) === true) {
				$activeRow = $this->_activeRow;

				//Check if we need to re-execute the query
				if(is_null($activeRow) === false) {
					$result->execute();
				}

				//We fetch all the results in memory
				$records = $result->fetchAll();
			} else {
				$records = array();
			}
		} else {
			$records = $this->_rows;
			if(is_array($records) === false) {
				$result = $this->_result;
				if(is_object($result) === true) {
					$activeRow = $this->_activeRow;

					//Check if we need to re-execute the query
					if(is_null($activeRow) === false) {
						$result->execute();
					}

					//We fetch all the results in memory again
					$records = $result->fetchAll();
					$this->_rows = $records;

					//Update the row count
					$this->_count = count($records);
				} else {
					$records = array();
				}
			}
		}

		//We need to rename the whole set here, this could be slow
		if($renameColumns === true) {
			//Get the resultset column map
			$columnMap = $this->_columnMap;
			if(is_array($columnMap) === false) {
				return $records;
			}

			$renamedRecords = array();
			if(is_array($records) === true) {
				foreach($records as $record) {
					$renamed = array();
					foreach($record as $key => $value) {
						//Check if the key is part of the column map
						if(isset($columnMap[$key]) === false) {
							throw new Exception("Column '".$key."' is not part of the column map");
						}

						//Add the value renamed
						$renamed[$columnMap[$key]] = $value;
					}

					//Append the renamed records to the main array
					$renamedRecords[] = $renamed;
				}
			}

			return $renamedRecords;
		}

		return $records;
	}

	/**
	 * Serializing a resultset will dump all related rows into a big array
	 *
	 * @return string
	 */
	public function serialize()
	{
		$data = array(
			'model' => $this->_model,
			'cache' => $this->_cache,
			'rows' => $this->toArray(false),
			'columnMap' => $this->_columnMap,
			'hydrateMode' => $this->_hydrateMode
		);

		//Force to re-execute the query
		$this->_activeRow = false;

		//Serialize the cache using the serialize function
		return serialize($data);
	}

	/**
	 * Unserializing a resultset only works on the rows present in the saved state
	 *
	 * @param string $data
	 * @throws Exception
	 */
	public function unserialize($data)
	{
		if(is_string($data) === false) {
			throw new Exception('Invalid parameter type.');
		}

		$this->_type = 0;

		$resultset = unserialize($data);

		if(is_array($resultset) === false) {
			throw new Exception('Invalid serialization data');
		}

		$this->_model = $resultset['model'];
		$this->_rows = $resultset['rows'];
		$this->_cache = $resultset['cache'];
		$this->_columnMap = $resultset['columnMap'];
		$this->_hydrateMode = $resultset['hydrateMode'];
	}
}