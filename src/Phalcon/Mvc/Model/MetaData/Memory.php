<?php
/**
* Memory Adapter
*
* @author Andres Gutierrez <andres@phalconphp.com>
* @author Eduar Carvajal <eduar@phalconphp.com>
* @author Wenzel Pünter <wenzel@phelix.me>
* @version 1.2.6
* @package Phalcon
*/
namespace Phalcon\Mvc\Model\MetaData;

use \Phalcon\Mvc\Model\MetaData,
	\Phalcon\Mvc\Model\Exception,
	\Phalcon\Mvc\Model\MetaDataInterface,
	\Phalcon\DI\InjectionAwareInterface;

/**
 * Phalcon\Mvc\Model\MetaData\Memory
 *
 * Stores model meta-data in memory. Data will be erased when the request finishes
 *
 * @see https://github.com/phalcon/cphalcon/blob/1.2.6/ext/mvc/model/metadata/memory.c
 */
class Memory extends MetaData implements InjectionAwareInterface, MetaDataInterface
{
	/**
	 * Models: Attributes
	 *
	 * @var int
	*/
	const MODELS_ATTRIBUTES = 0;

	/**
	 * Models: Primary Key
	 *
	 * @var int
	*/
	const MODELS_PRIMARY_KEY = 1;

	/**
	 * Models: Non Primary Key
	 *
	 * @var int
	*/
	const MODELS_NON_PRIMARY_KEY = 2;

	/**
	 * Models: Not Null
	 *
	 * @var int
	*/
	const MODELS_NOT_NULL = 3;

	/**
	 * Models: Data Types
	 *
	 * @var int
	*/
	const MODELS_DATA_TYPES = 4;

	/**
	 * Models: Data Types Numeric
	 *
	 * @var int
	*/
	const MODELS_DATA_TYPES_NUMERIC = 5;

	/**
	 * Models: Date At
	 *
	 * @var int
	*/
	const MODELS_DATE_AT = 6;

	/**
	 * Models: Date In
	 *
	 * @var int
	*/
	const MODELS_DATE_IN = 7;

	/**
	 * Models: Identity Column
	 *
	 * @var int
	*/
	const MODELS_IDENTITY_COLUMN = 8;

	/**
	 * Models: Data Types Bind
	 *
	 * @var int
	*/
	const MODELS_DATA_TYPES_BIND = 9;

	/**
	 * Models: Automatic Default Insert
	 *
	 * @var int
	*/
	const MODELS_AUTOMATIC_DEFAULT_INSERT = 10;

	/**
	 * Models: Automatic Default Update
	 *
	 * @var int
	*/
	const MODELS_AUTOMATIC_DEFAULT_UPDATE = 11;

	/**
	 * Models: Column Map
	 *
	 * @var int
	*/
	const MODELS_COLUMN_MAP = 0;

	/**
	 * Models: Reverse Column Map
	 *
	 * @var int
	*/
	const MODELS_REVERSE_COLUMN_MAP = 1;

	/**
	 * \Phalcon\Mvc\Model\MetaData\Memory constructor
	 *
	 * @param array|null $options
	 */
	public function __construct($options = null)
	{
		$this->_metaData = array();
	}

	/**
	 * Reads the meta-data from temporal memory
	 *
	 * @param string $key
	 */
	public function read($key)
	{
		
	}

	/**
	 * Writes the meta-data to temporal memory
	 *
	 * @param string $key
	 * @param array $metaData
	 */
	public function write($key, $metaData)
	{

	}
}