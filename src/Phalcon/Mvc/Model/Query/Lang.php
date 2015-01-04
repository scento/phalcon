<?php
/**
* PHQL Parser
*
* @author Wenzel Pünter <wenzel@phelix.me>
* @version 1.2.6
* @package Phalcon
*/
namespace Phalcon\Mvc\Model\Query;

use \Phalcon\Mvc\Model\Query\Scanner;
use \Phalcon\Mvc\Model\Exception;

/**
 * Phalcon\Mvc\Model\Query\Lang
 *
 * PHQL is implemented as a parser (written in C) that translates syntax in
 * that of the target RDBMS. It allows Phalcon to offer a unified SQL language to
 * the developer, while internally doing all the work of translating PHQL
 * instructions to the most optimal SQL instructions depending on the
 * RDBMS type associated with a model.
 *
 * To achieve the highest performance possible, we wrote a parser that uses
 * the same technology as SQLite. This technology provides a small in-memory
 * parser with a very low memory footprint that is also thread-safe.
 *
 * <code>
 * $intermediate = Phalcon\Mvc\Model\Query\Lang::parsePHQL("SELECT r.* FROM Robots r LIMIT 10");
 * </code>
 *
 * @see https://github.com/phalcon/cphalcon/blob/1.2.6/ext/mvc/model/query/lang.c
 */
abstract class Lang
{
    /**
     * Parses a PHQL statement returning an intermediate representation (IR)
     *
     * @param string $phql
     * @return array|null
     */
    public static function parsePHQL($phql)
    {
        if (is_string($phql) === false) {
            throw new Exception('PHQL statement must be string');
        }

        try {
            //$scanner = new Scanner($phql);
            //@todo implement scanner
        } catch (\Exception $e) {
            return null;
        }
    }
}
