<?php
/**
 * Paginator Adapter Interface
 *
 * @author Andres Gutierrez <andres@phalconphp.com>
 * @author Eduar Carvajal <eduar@phalconphp.com>
 * @version 1.2.6
 * @package Phalcon
*/
namespace Phalcon\Paginator;

/**
 * Phalcon\Paginator\AdapterInterface initializer
 *
 * @see https://github.com/phalcon/cphalcon/blob/1.2.6/ext/paginator/adapterinterface.c
 */
interface AdapterInterface
{
    /**
     * \Phalcon\Paginator\AdapterInterface constructor
     *
     * @param array $config
     */
    public function __construct($config);

    /**
     * Set the current page number
     *
     * @param int $page
     */
    public function setCurrentPage($page);

    /**
     * Returns a slice of the resultset to show in the pagination
     *
     * @return stdClass
     */
    public function getPaginate();
}
