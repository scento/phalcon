<?php
/**
 * Cssmin Filter
 *
 * @author Andres Gutierrez <andres@phalconphp.com>
 * @author Eduar Carvajal <eduar@phalconphp.com>
 * @author Wenzel Pünter <wenzel@phelix.me>
 * @version 1.2.6
 * @package Phalcon
*/
namespace Phalcon\Assets\Filters;

use \Phalcon\Assets\Exception;

/**
 * Phalcon\Assets\Filters\Cssmin
 *
 * Minify the css - removes comments
 * removes newlines and line feeds keeping
 * removes last semicolon from last property
 * 
 * @see https://github.com/phalcon/cphalcon/blob/1.2.6/ext/assets/filters/cssmin.c
 */
class Cssmin
{
	/**
	 * Filters the content using CSSMIN
	 *
	 * @param string $content
	 * @return string|null
	 */
	public function filter($content)
	{
		try {
			return self::cssmin($content);
		} catch(Exception $e) {
			return null;
		}
	}

	/**
	 * Cssmin
	 * 
	 * @param string $content
	 * @return string
	 * @throws Exception
	*/
	private static function cssmin($content) {
		if(is_string($content) === false) {
			throw new Exception('Style must be a string');
		}
		
		if(empty($content) === true) {
			return $content;
		}

		require(__DIR__.'/CssMin/build/CssMin.php');

		try {
			$minify = new \CssMinifier($content);
			return $minify->getMinified();
		} catch(\Exception $e) {
			if(is_string($e) === true) {
				throw new Exception($e);
			} else {
				throw new Exception('Unknown error');
			}
		}
	}
}