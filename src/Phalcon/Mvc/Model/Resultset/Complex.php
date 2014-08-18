<?php
/**
 * Complex Resultset
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
	\Phalcon\Mvc\Model\Row,
	\Phalcon\Mvc\Exception,
	\Phalcon\Mvc\Model,
	\Phalcon\Db\ResultInterface,
	\Phalcon\Cache\BackendInterface,
	\Serializable,
	\ArrayAccess,
	\Countable,
	\SeekableIterator,
	\Iterator,
	\stdClass;

/**
 * Phalcon\Mvc\Model\Resultset\Complex
 *
 * Complex resultsets may include complete objects and scalar values.
 * This class builds every complex row as it is required
 * 
 * @see https://github.com/phalcon/cphalcon/blob/1.2.6/ext/mvc/model/resultset/complex.c
 */
class Complex extends Resultset implements Serializable, ArrayAccess, Countable, 
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
	 * Column Types
	 * 
	 * @var null|array
	 * @access protected
	*/
	protected $_columnTypes;

	/**
	 * \Phalcon\Mvc\Model\Resultset\Complex constructor
	 *
	 * @param array $columnsTypes
	 * @param \Phalcon\Db\ResultInterface $result
	 * @param \Phalcon\Cache\BackendInterface|null $cache
	 * @throws Exception
	 */
	public function __construct($columnsTypes, $result, $cache = null)
	{
		if(is_array($columnsTypes) === false) {
			throw new Exception('Invalid parameter type.');
		}

		if(is_object($result) === false ||
			$result instanceof ResultInterface === false) {
			throw new Exception('Invalid parameter type.');
		}

		//Column types tell the resultset how to build the result
		$this->_columnTypes = $columnsTypes;

		//Valid resultsets are Phalcon\Db\ResultInterface instances
		$this->_result = $result;

		//Update the related cache if any
		if(is_object($cache) === true &&
			$cache instanceof BackendInterface === true) {
			$this->_cache = $cache;
		} else {
			throw new Exception('Invalid parameter type.');
		}

		//Resultset type 1 are traversed one-by-one
		$this->_type = 1;

		//If the database result is an object, change it to fetch assoc
		if(is_object($result) === true) {
			$result->setFetchMode(1);
		}
	}

	/**
	 * Check whether internal resource has rows to fetch
	 *
	 * @return boolean
	 */
	public function valid()
	{
		if($this->_type === 1) {
			//The result is bigger than 32 rows so it's retrieved one by one
			if($this->_result !== false) {
				$row = $this->_result->fetch($result);
			} else {
				$row = false;
			}
		} else {
			//The full rows are dumped into $this->rows
			if(is_array($this->_rows) === true) {
				$row = current($this->_rows);
				if(is_object($row) === true) {
					next($this->_rows);
				}
			} else {
				$row = false;
			}
		}

		//Valid records are arrays
		if(is_array($row) === true ||
			is_object($row) === true) {
			//The result type=1 so we need to build every row
			if($type === 1) {
				//Each row in a complex result is a Phalcon\Mvc\Model\Row instance
				switch((int)$this->_hydrateMode) {
					case 0:
						$activeRow = new Row();
						break;
					case 1:
						$activeRow = array();
						break;
					case 2:
						$activeRow = new stdClass();
						break;
					//@note no default exception
				}

				//Set records as dirty state PERSISTENT by default
				$dirtyState = 0;

				foreach($this->_columnTypes as $alias => $column) {
					if($column['type'] === 'object') {
						//Object columns are assigned column by column
						$columnMap = $column['columnMap'];
						$attributes = $column['attributes'];

						$rowModel = array();
						foreach($column['attributes'] as $attribute) {
							//Columns are supposed to be in the form _table_field
							$rowModel[$attribute] = $row['_'.$column['column'].'_'.$attribute];
						}

						//Generate the column value according to the hydration type
						switch((int)$this->_hydrateMode) {
							case 0:
									//Check if the resultset must keep snapshots
								if(isset($column['keepSnapshots']) === true) {
									$keepSnapshots = $column['keepSnapshots'];
								} else {
									$keepSnapshots = false;
								}

								//Get the base instance
								$instace = $column['instance'];

								//Assign the values to the attributes using a column map
								$value = Model::cloneResultMap($instance, $rowModel, $columnMap, $dirtyState, $keepSnapshots);
								break;

							default:
								//Other kinds of hydrations
								$value = Model::cloneResultMapHydrate($rowModel, $columnMap, $this->_hydrateMode);
								break;
						}

						//The complete object is assigned to an attribute with the name of the alias or
						//the model name
						$attribute = null;

						if(isset($column['balias']) === true) {
							$attribute = $column['balias'];
						}
					} else {
						//Scalar columns are simply assigned to the result objects
						if(isset($column['sqlAlias']) === true) {
							$value = $row[$column['sqlAlias']];
						} else {
							if(isset($row[$alias]) === true) {
								$value = $row[$alias];
							}
						}

						//If a 'balias' is defined it is not an unnamed scalar
						if(isset($column['balias']) === true) {
							$attribute = $alias;
						} else {
							$attribute = str_replace('_', '', $alias);
						}
					}

					if(isset($attribute) === false) {
						$attribute = null;
					}

					//Assign the instance according to the hydration type
					switch((int)$this->_hydrateMode) {
						case 1:
							$activeRow[$attribute] = $value;
							break;
						default:
							$activeRow->$attribute = $value;
							break;
					}
				}

				//Store the generated row in $this->activeRow to be retrieved by 'current'
				$this->_activeRow = $activeRow;
			} else {
				//The row is already build so we just assign it to the activeRow
				$this->_activeRow = $row;
			}

			return true;
		}

		//There are no results to retrieve so we update $this->activeRow as false
		$this->_activeRow = false;
		return false;
	}

	/**
	 * Returns a complete resultset as an array, if the resultset has a big number of rows
	 * it could consume more memory than currently it does.
	 *
	 * @return array
	 */
	public function toArray()
	{
		$records = array();
		$this->rewind();
		while($this->valid()) {
			$records[] = $this->current();
			$this->next();
		}

		return $records;
	}

	/**
	 * Serializing a resultset will dump all related rows into a big array
	 *
	 * @return string|null
	 */
	public function serialize()
	{
		$serialized = serialize(array('cache' => $this->_cache, 'rows' => $this->toArray(),
			'columnTypes' => $this->_columnTypes, 'hydrateMode' => $this->_hydrateMode));

		if(is_string($serialized) === false) {
			return null;
		}

		return $serialized;
	}

	/**
	 * Unserializing a resultset will allow to only works on the rows present in the saved state
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

		if(is_array($result) === false) {
			throw new Exception('Invalid serialization data');
		}

		$this->_rows = $resultset['rows'];
		$this->_cache = $resultset['cache'];
		$this->_columnTypes = $resultset['columnTypes'];
		$this->_hydrateMode = $resultset['hydrateMode'];
	}
}