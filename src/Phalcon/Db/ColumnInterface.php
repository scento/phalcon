<?php
/**
 * Column Interface
 *
 * @author Andres Gutierrez <andres@phalconphp.com>
 * @author Eduar Carvajal <eduar@phalconphp.com>
 * @version 1.2.6
 * @package Phalcon
*/
namespace Phalcon\Db;

/**
 * Phalcon\Db\ColumnInterface initializer
 * 
 * @see https://github.com/phalcon/cphalcon/blob/1.2.6/ext/db/columninterface.c
 */
interface ColumnInterface
{
	/**
	 * \Phalcon\Db\ColumnInterface constructor
	 *
	 * @param string $columnName
	 * @param array $definition
	 */
	public function __construct($columnName, $definition);

	/**
	 * Returns schema's table related to column
	 *
	 * @return string
	 */
	public function getSchemaName();

	/**
	 * Returns column name
	 *
	 * @return string
	 */
	public function getName();

	/**
	 * Returns column type
	 *
	 * @return int
	 */
	public function getType();

	/**
	 * Returns column size
	 *
	 * @return int
	 */
	public function getSize();

	/**
	 * Returns column scale
	 *
	 * @return int
	 */
	public function getScale();

	/**
	 * Returns true if number column is unsigned
	 *
	 * @return boolean
	 */
	public function isUnsigned();

	/**
	 * Not null
	 *
	 * @return boolean
	 */
	public function isNotNull();

	/**
	 * Column is part of the primary key?
	 *
	 * @return boolean
	 */
	public function isPrimary();

	/**
	 * Auto-Increment
	 *
	 * @return boolean
	 */
	public function isAutoIncrement();

	/**
	 * Check whether column have an numeric type
	 *
	 * @return boolean
	 */
	public function isNumeric();

	/**
	 * Check whether column have first position in table
	 *
	 * @return boolean
	 */
	public function isFirst();

	/**
	 * Check whether field absolute to position in table
	 *
	 * @return string
	 */
	public function getAfterPosition();

	/**
	 * Returns the type of bind handling
	 *
	 * @return int
	 */
	public function getBindType();

	/**
	 * Restores the internal state of a \Phalcon\Db\Column object
	 *
	 * @param array $data
	 * @return \Phalcon\Db\ColumnInterface
	 */
	public static function __set_state($data);
}