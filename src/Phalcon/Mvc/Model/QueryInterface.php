<?php
/**
 * Query Interface
 *
 * @author Andres Gutierrez <andres@phalconphp.com>
 * @author Eduar Carvajal <eduar@phalconphp.com>
 * @version 1.2.6
 * @package Phalcon
*/
namespace Phalcon\Mvc\Model;

/**
 * Phalcon\Mvc\Model\QueryInterface initializer
 *
 * @see https://github.com/phalcon/cphalcon/blob/1.2.6/ext/mvc/model/queryinterface.c
 */
interface QueryInterface
{
    /**
     * \Phalcon\Mvc\Model\Query constructor
     *
     * @param string $phql
     */
    public function __construct($phql);

    /**
     * Parses the intermediate code produced by \Phalcon\Mvc\Model\Query\Lang generating another
     * intermediate representation that could be executed by \Phalcon\Mvc\Model\Query
     *
     * @return array
     */
    public function parse();

    /**
     * Executes a parsed PHQL statement
     *
     * @param array|null $bindParams
     * @param array|null $bindTypes
     * @return mixed
     */
    public function execute($bindParams = null, $bindTypes = null);
}
