<?php
/**
 * Reference Interface
 *
 * @author Andres Gutierrez <andres@phalconphp.com>
 * @author Eduar Carvajal <eduar@phalconphp.com>
 * @version 1.2.6
 * @package Phalcon
*/
namespace Phalcon\Db;

/**
 * Phalcon\Db\ReferenceInterface initializer
 * 
 * @see https://github.com/phalcon/cphalcon/blob/1.2.6/ext/db/referenceinterface.c
 */
interface ReferenceInterface
{
	/**
	 * \Phalcon\Db\ReferenceInterface constructor
	 *
	 * @param string $referenceName
	 * @param array $definition
	 */
	public function __construct($referenceName, $definition);

	/**
	 * Gets the index name
	 *
	 * @return string
	 */
	public function getName();

	/**
	 * Gets the schema where referenced table is
	 *
	 * @return string
	 */
	public function getSchemaName();

	/**
	 * Gets the schema where referenced table is
	 *
	 * @return string
	 */
	public function getReferencedSchema();

	/**
	 * Gets local columns which reference is based
	 *
	 * @return array
	 */
	public function getColumns();

	/**
	 * Gets the referenced table
	 *
	 * @return string
	 */
	public function getReferencedTable();

	/**
	 * Gets referenced columns
	 *
	 * @return array
	 */
	public function getReferencedColumns();

	/**
	 * Restore a \Phalcon\Db\Reference object from export
	 *
	 * @param array $data
	 * @return \Phalcon\Db\ReferenceInterface
	 */
	public static function __set_state($data);
}