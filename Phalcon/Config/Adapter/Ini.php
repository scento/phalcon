<?php 
/**
 * INI Adapter
 *
 * @author Andres Gutierrez <andres@phalconphp.com>
 * @author Eduar Carvajal <eduar@phalconphp.com>
 * @author Wenzel Pünter <wenzel@phelix.me>
 * @version 0.1
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
 * @see https://github.com/phalcon/cphalcon/blob/master/ext/config/adapter/ini.c
 */
class Ini extends Config implements Countable, ArrayAccess
{

	/**
	 * \Phalcon\Config\Adapter\Ini constructor
	 *
	 * @param string $filePath
	 * @throws Exception
	 */
	public function __construct($filePath)
	{
		if(is_string($filePath) === false)
		{
			throw new Exception('Invalid parameter type.');
		}

		$d = parse_ini_file($filePath, true);

		if($d === false)
		{
			throw new Exception('Configuration file '.$filePath.' can\'t be loaded');
		}

		foreach($d as $section => $directives)
		{
			if(is_scalar($directives) === true)
			{
				$this->merge(array($section => $directives));
			} elseif(is_array($directives) === true) {
				foreach($directives as $key => $value)
				{
					if(strpos($key, '.') !== false)
					{
						$data = array();
						$path = explode('.', $key);

						$temp = &$data;
						//Build tree structure
						foreach($path as $key)
						{
							$temp = &$temp[$key];
						}

						$this->merge(array($section => $data));
					} else {
						$this->merge(array($section => array($key => $value)));
					}
				}
			} else {
				throw new Exception('Invalid ini file.');
			}
		}
	}
}