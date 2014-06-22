<?php
/**
 * Database
 *
 * @author Andres Gutierrez <andres@phalconphp.com>
 * @author Eduar Carvajal <eduar@phalconphp.com>
 * @author Wenzel Pünter <wenzel@phelix.me>
 * @version 1.2.6
 * @package Phalcon
*/
namespace Phalcon;

use \Phalcon\Db\Exception;

/**
 * Phalcon\Db
 *
 * Phalcon\Db and its related classes provide a simple SQL database interface for Phalcon Framework.
 * The Phalcon\Db is the basic class you use to connect your PHP application to an RDBMS.
 * There is a different adapter class for each brand of RDBMS.
 *
 * This component is intended to lower level database operations. If you want to interact with databases using
 * higher level of abstraction use Phalcon\Mvc\Model.
 *
 * Phalcon\Db is an abstract class. You only can use it with a database adapter like Phalcon\Db\Adapter\Pdo
 *
 * <code>
 *
 *try {
 *
 *  $connection = new Phalcon\Db\Adapter\Pdo\Mysql(array(
 *     'host' => '192.168.0.11',
 *     'username' => 'sigma',
 *     'password' => 'secret',
 *     'dbname' => 'blog',
 *     'port' => '3306',
 *  ));
 *
 *  $result = $connection->query("SELECT * FROM robots LIMIT 5");
 *  $result->setFetchMode(Phalcon\Db::FETCH_NUM);
 *  while ($robot = $result->fetch()) {
 *    print_r($robot);
 *  }
 *
 *} catch (Phalcon\Db\Exception $e) {
 *	echo $e->getMessage(), PHP_EOL;
 *}
 *
 * </code>
 * 
 * @see https://github.com/phalcon/cphalcon/1.2.6/master/ext/db.c
 */
abstract class Db
{
	/**
	 * Fetch associative array
	 * 
	 * @var int
	*/
	const FETCH_ASSOC = 1;

	/**
	 * Fetch associative and numeric array
	 * 
	 * @var int
	*/
	const FETCH_BOTH = 2;

	/**
	 * Fetch numeric array
	 * 
	 * @var int
	*/
	const FETCH_NUM = 3;

	/**
	 * Fetch object
	 * 
	 * @var int
	*/
	const FETCH_OBJ = 4;

	/**
	 * Enables/disables options in the Database component
	 *
	 * @param array $options
	 * @throws Exception
	 */
	public static function setup($options)
	{
		if(is_array($options) === false) {
			throw new Exception('Options must be an array');
		}

		if(isset($options['escapeSqlIdentifiers']) === true) {
			$GLOBALS['__phalcon_db__escape_identifiers'] = 
				(bool)$options['escapeSqlIdentifiers'];
		}
	}
}