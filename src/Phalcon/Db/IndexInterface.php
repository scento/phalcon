<?php
/**
 * Index Interface
 *
 * @author Andres Gutierrez <andres@phalconphp.com>
 * @author Eduar Carvajal <eduar@phalconphp.com>
 * @version 1.2.6
 * @package Phalcon
*/
namespace Phalcon\Db;

/**
 * Phalcon\Db\IndexInterface initializer
 *
 * @see https://github.com/phalcon/cphalcon/blob/1.2.6/ext/db/indexinterface.c
 */
interface IndexInterface
{
    /**
     * \Phalcon\Db\Index constructor
     *
     * @param string $indexName
     * @param array $columns
     */
    public function __construct($indexName, $columns);

    /**
     * Gets the index name
     *
     * @return string
     */
    public function getName();

    /**
     * Gets the columns that comprends the index
     *
     * @return array
     */
    public function getColumns();

    /**
     * Restore a \Phalcon\Db\Index object from export
     *
     * @param array $data
     * @return \Phalcon\Db\IndexInterface
     */
    public static function __set_state($data);
}
