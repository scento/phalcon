<?php
/**
 * INI Adapter
 *
 * @author Andres Gutierrez <andres@phalconphp.com>
 * @author Eduar Carvajal <eduar@phalconphp.com>
 * @author Wenzel Pünter <wenzel@phelix.me>
 * @version 1.2.6
 * @package Phalcon
 */
namespace Phalcon\Config\Adapter;

use \ArrayAccess,
	\Countable,
	\Phalcon\Config,
	\Phalcon\Config\Exception;

/**
 * Phalcon\Config\Adapter\Ini
 *
 * Reads ini files and converts them to Phalcon\Config objects.
 *
 * Given the next configuration file:
 *
 *<code>
 *[database]
 *adapter = Mysql
 *host = localhost
 *username = scott
 *password = cheetah
 *dbname = test_db
 *
 *[phalcon]
 *controllersDir = "../app/controllers/"
 *modelsDir = "../app/models/"
 *viewsDir = "../app/views/"
 *</code>
 *
 * You can read it as follows:
 *
 *<code>
 *	$config = new Phalcon\Config\Adapter\Ini("path/config.ini");
 *	echo $config->phalcon->controllersDir;
 *	echo $config->database->username;
 *</code>
 *
 * @see https://github.com/phalcon/cphalcon/blob/1.2.6/ext/config/adapter/ini.c
 */
class Ini extends Config
{
	/**
	 * \Phalcon\Config\Adapter\Ini constructor
	 *
	 * @param string $filePath
	 * @throws Exception
	 */
	public function __construct($filePath)
	{
		$array = array();
		if (is_string($filePath) === false) {
			throw new Exception('Invalid parameter type.');
		}

		$d = parse_ini_file($filePath, true);

		if ($d === false) {
			throw new Exception('Configuration file '.$filePath.' can\'t be loaded');
		}

		foreach($d as $section => $directives) {
			if (!is_array($directives) || empty($directives)) {
				$array[$section] = $directives;
			} else {
				foreach ($directives as $key => $value) {
					if (strpos($key, '.') !== false) {
						!isset($array[$section]) && $array[$section] = array();
						$array[$section] = self::_parseKey($array[$section], $key, $value);
					} else {
						$array[$section][$key] = $value;
					}
				}
			}
		}

		parent::__construct($array);
	}

	/**
	 * recursive parse key
	 *
	 * <code>
	 * $r = self::_parseKey(array(), 'a.b.c', 1);
	 * $r = array(
	 *		'a' => array(
	 *			'b' => array(
	 *				'c' => 1
	 *			)
	 *		)
	 * );
	 *
	 * $r = self::_parseKey(array(), 'a..', 1);
	 * $r = array(
	 *		'a' => array(
	 *			0 => array(
	 *				0 => 1
	 *			)
	 *		)
	 * );
	 *
	 * </code>
	 */
	private static function _parseKey($config, $key, $value) {
		if (strpos($key, '.') !== false) {
			list($k, $v) = explode('.', $key, 2);

			$k = isset($k{0}) ? $k : 0;
			$v = isset($v{0}) ? $v : 0;

			if (!isset($config[$k])) {
				$config[$k] = array();
			}

			$config[$k] = self::_parseKey($config[$k], $v, $value);

		} else {
			$config[$key] = $value;
		}

		return $config;
	}
}