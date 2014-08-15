<?php
/**
 * Column
 *
 * @author Andres Gutierrez <andres@phalconphp.com>
 * @author Eduar Carvajal <eduar@phalconphp.com>
 * @author Wenzel Pünter <wenzel@phelix.me>
 * @version 1.2.6
 * @package Phalcon
*/
namespace Phalcon\Db;

use \Phalcon\Db\ColumnInterface,
	\Phalcon\Db\Exception;

/**
 * Phalcon\Db\Column
 *
 * Allows to define columns to be used on create or alter table operations
 *
 *<code>
 *	use Phalcon\Db\Column as Column;
 *
 * //column definition
 * $column = new Column("id", array(
 *   "type" => Column::TYPE_INTEGER,
 *   "size" => 10,
 *   "unsigned" => true,
 *   "notNull" => true,
 *   "autoIncrement" => true,
 *   "first" => true
 * ));
 *
 * //add column to existing table
 * $connection->addColumn("robots", null, $column);
 *</code>
 *
 * @see https://github.com/phalcon/cphalcon/blob/1.2.6/ext/db/column.c
 */
class Column implements ColumnInterface
{
	/**
	 * Type: Integer
	 * 
	 * @var int
	*/
	const TYPE_INTEGER = 0;

	/**
	 * Type: Date
	 * 
	 * @var int
	*/
	const TYPE_DATE = 1;

	/**
	 * Type: Varchar
	 * 
	 * @var int
	*/
	const TYPE_VARCHAR = 2;

	/**
	 * Type: Decimal
	 * 
	 * @var int
	*/
	const TYPE_DECIMAL = 3;

	/**
	 * Type: DateTime
	 * 
	 * @var int
	*/
	const TYPE_DATETIME = 4;

	/**
	 * Type: Char
	 * 
	 * @var int
	*/
	const TYPE_CHAR = 5;

	/**
	 * Type: Text
	 * 
	 * @var int
	*/
	const TYPE_TEXT = 6;

	/**
	 * Type: Float
	 * 
	 * @var int
	*/
	const TYPE_FLOAT = 7;

	/**
	 * Type: Boolean
	 * 
	 * @var int
	*/
	const TYPE_BOOLEAN = 8;

	/**
	 * Type: Double
	 * 
	 * @var int
	*/
	const TYPE_DOUBLE = 9;

	/**
	 * Bind Param: Null
	 * 
	 * @var int
	*/
	const BIND_PARAM_NULL = 0;

	/**
	 * Bind Param: Integer
	 * 
	 * @var int
	*/
	const BIND_PARAM_INT = 1;

	/**
	 * Bind Param: String
	 * 
	 * @var int
	*/
	const BIND_PARAM_STR = 2;

	/**
	 * Bind Param: Boolean
	 * 
	 * @var int
	*/
	const BIND_PARAM_BOOL = 5;

	/**
	 * Bind Param: Decimal
	 * 
	 * @var int
	*/
	const BIND_PARAM_DECIMAL = 32;

	/**
	 * Bind: Skip
	 * 
	 * @var int
	*/
	const BIND_SKIP = 1024;

	/**
	 * Column Name
	 * 
	 * @var null|string
	 * @access protected
	*/
	protected $_columnName;

	/**
	 * Schema Name
	 * 
	 * @var null
	 * @access protected
	*/
	protected $_schemaName;

	/**
	 * Type
	 * 
	 * @var null|int
	 * @access protected
	*/
	protected $_type;

	/**
	 * Is Numeric
	 * 
	 * @var boolean
	 * @access protected
	*/
	protected $_isNumeric = false;

	/**
	 * Size
	 * 
	 * @var int
	 * @access protected
	*/
	protected $_size = 0;

	/**
	 * Scale
	 * 
	 * @var int
	 * @access protected
	*/
	protected $_scale = 0;

	/**
	 * Unsigned
	 * 
	 * @var boolean
	 * @access protected
	*/
	protected $_unsigned = false;

	/**
	 * Not Null
	 * 
	 * @var boolean
	 * @access protected
	*/
	protected $_notNull = false;

	/**
	 * Primary
	 * 
	 * @var boolean
	 * @access protected
	*/
	protected $_primary = false;

	/**
	 * Auto Increment
	 * 
	 * @var boolean
	 * @access protected
	*/
	protected $_autoIncrement = false;

	/**
	 * First
	 * 
	 * @var boolean
	 * @access protected
	*/
	protected $_first = false;

	/**
	 * After
	 * 
	 * @var null|string
	 * @access protected
	*/
	protected $_after;

	/**
	 * Bind Type
	 * 
	 * @var int
	 * @access protected
	*/
	protected $_bindType = 2;

	/**
	 * \Phalcon\Db\Column constructor
	 *
	 * @param string $columnName
	 * @param array $definition
	 * @throws Exception
	 */
	public function __construct($columnName, $definition)
	{
		if(is_string($columnName) === false ||
			is_array($definition) === false) {
			throw new Exception('Invalid parameter type.');
		}

		$this->_columnName = $columnName;

		//Get the column type, one of the TYPE_* constants
		if(isset($definition['type']) === true) {
			$this->_type = $definition['type'];
		} else {
			throw new Exception('Column type is required');
		}
		
		$type = (int)$definition['type'];

		//Check if the field is nullable
		if(isset($definition['notNull']) === true) {
			$this->_notNull = $definition['notNull'];
		}

		//Check if the field is primary key
		if(isset($definition['primary']) === true) {
			$this->_primary = $definition['primary'];
		}

		if(isset($definition['size']) === true) {
			$this->_size = $definition['size'];
		}

		//Check if the column has a decimal scale
		if(isset($definition['scale']) === true &&
			($type === 3 || $type === 7 || $type === 9)) {
			$this->_scale = $definition['scale'];
		}

		//Check if the field is unsigned (only MySQL)
		if(isset($definition['unsigned']) === true) {
			$this->_unsigned = $definition['unsigned'];
		}

		//Check if the field is numeric
		if(isset($definition['autoIncrement']) === true) {
			if($type === 0) {
				$this->_autoIncrement = $definition['autoIncrement'];
			} else {
				throw new Exception('Column type cannot be auto-increment');
			}
		}

		//Check if the field is placed at the first position of the table
		if(isset($definition['first']) === true) {
			$this->_first = $definition['first'];
		}

		//Name of the column which is placed before the current field
		if(isset($definition['after']) === true) {
			$this->_after = $definition['after'];
		}

		//The bind type to cast the field when passing it to PDO
		if(isset($definition['bindType']) === true) {
			$this->_bindType = $definition['bindType'];
		}
	}

	/**
	 * Returns schema's table related to column
	 *
	 * @return null
	 */
	public function getSchemaName()
	{
		return $this->_schemaName;
	}

	/**
	 * Returns column name
	 *
	 * @return string
	 */
	public function getName()
	{
		return $this->_columnName;
	}

	/**
	 * Returns column type
	 *
	 * @return int
	 */
	public function getType()
	{
		return $this->_type;
	}

	/**
	 * Returns column size
	 *
	 * @return int|null
	 */
	public function getSize()
	{
		return $this->_size;
	}

	/**
	 * Returns column scale
	 *
	 * @return int|null
	 */
	public function getScale()
	{
		return $this->_scale;
	}

	/**
	 * Returns true if number column is unsigned
	 *
	 * @return boolean
	 */
	public function isUnsigned()
	{
		return $this->_unsigned;
	}

	/**
	 * Not null
	 *
	 * @return boolean
	 */
	public function isNotNull()
	{
		return $this->_notNull;
	}

	/**
	 * Column is part of the primary key?
	 *
	 * @return boolean
	 */
	public function isPrimary()
	{
		return $this->_primary;
	}

	/**
	 * Auto-Increment
	 *
	 * @return boolean
	 */
	public function isAutoIncrement()
	{
		return $this->_autoIncrement;
	}

	/**
	 * Check whether column have an numeric type
	 *
	 * @return boolean
	 */
	public function isNumeric()
	{
		return $this->_isNumeric;
	}

	/**
	 * Check whether column have first position in table
	 *
	 * @return boolean
	 */
	public function isFirst()
	{
		return $this->_first;
	}

	/**
	 * Check whether field absolute to position in table
	 *
	 * @return string|null
	 */
	public function getAfterPosition()
	{
		return $this->_after;
	}

	/**
	 * Returns the type of bind handling
	 *
	 * @return int
	 */
	public function getBindType()
	{
		return $this->_bindType;
	}

	/**
	 * Restores the internal state of a \Phalcon\Db\Column object
	 *
	 * @param array $data
	 * @return \Phalcon\Db\Column
	 * @throws Exception
	 */
	public static function __set_state($data)
	{
		if(is_array($data) === false) {
			throw new Exception('Column state must be an array');
		}

		$columnName = $data['_columnName'];
		$definition = array();

		if(isset($data['_type']) === true) {
			$definition['type'] = $data['_type'];
		}

		if(isset($data['_notNull']) === true) {
			$definition['notNull'] = $data['_notNull'];
		}

		if(isset($data['_primary']) === true) {
			$definition['primary'] = $data['_primary'];
		}

		if(isset($data['_size']) === true) {
			$definition['size'] = $data['_size'];
		}

		if(isset($data['_scale']) === true) {
			$definition['scale'] = $data['_scale'];
		}

		if(isset($data['_unsigned']) === true) {
			$definition['unsigned'] = $data['_unsigned'];
		}

		if(isset($data['_after']) === true) {
			$definition['after'] = $data['_after'];
		}

		if(isset($data['_isNumeric']) === true) {
			$definition['isNumeric'] = $data['_isNumeric'];
		}

		if(isset($data['_first']) === true) {
			$definition['first'] = $data['_first'];
		}

		if(isset($data['_bindType']) === true) {
			$definition['bindType'] = $data['_bindType'];
		}

		return new Column($columnName, $definition);
	}
}