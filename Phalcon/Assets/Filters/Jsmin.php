<?php
/**
 * JSMin Filter
 *
 * @author Andres Gutierrez <andres@phalconphp.com>
 * @author Eduar Carvajal <eduar@phalconphp.com>
 * @author Wenzel Pünter <wenzel@phelix.me>
 * @version 1.2.6
 * @package Phalcon
*/
namespace Phalcon\Assets\Filters;

use \Phalcon\Assets\Exception,
	Phalcon\Assets\Filters\Helper\JShrink;

/**
 * Phalcon\Assets\Filters\Jsmin
 *
 * Deletes the characters which are insignificant to JavaScript. Comments will be removed. Tabs will be
 * replaced with spaces. Carriage returns will be replaced with linefeeds.
 * Most spaces and linefeeds will be removed.
 * 
 * @see https://github.com/phalcon/cphalcon/blob/1.2.6/ext/assets/filters/jsmin.c
 */
class Jsmin
{
	/**
	 * Filters the content using JSMIN
	 *
	 * @param string $content
	 * @return null|string
	 * @throws Exception
	 */
	public function filter($content)
	{
		if(is_string($content) === false) {
			throw new Exception('Invalid parameter type.');
		}

		try {
			return JShrink::minify($content);
		} catch(\Exception $e) {
			return null;
		}
	}
}