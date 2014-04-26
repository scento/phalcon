<?php
/**
 * igbinary Cache Frontend
 *
 * @author Andres Gutierrez <andres@phalconphp.com>
 * @author Eduar Carvajal <eduar@phalconphp.com>
 * @author Wenzel Pünter <wenzel@phelix.me>
 * @version 1.2.6
 * @package Phalcon
*/
namespace Phalcon\Cache\Frontend;

use \Phalcon\Cache\Frontend\Data,
	\Phalcon\Cache\FrontendInterface;

/**
 * Phalcon\Cache\Frontend\Igbinary
 *
 * Allows to cache native PHP data in a serialized form using igbinary extension
 *
 *<code>
 *
 *	// Cache the files for 2 days using Igbinary frontend
 *	$frontCache = new Phalcon\Cache\Frontend\Igbinary(array(
 *		"lifetime" => 172800
 *	));
 *
 *	// Create the component that will cache "Igbinary" to a "File" backend
 *	// Set the cache file directory - important to keep the "/" at the end of
 *	// of the value for the folder
 *	$cache = new Phalcon\Cache\Backend\File($frontCache, array(
 *		"cacheDir" => "../app/cache/"
 *	));
 *
 *	// Try to get cached records
 *	$cacheKey  = 'robots_order_id.cache';
 *	$robots    = $cache->get($cacheKey);
 *	if ($robots === null) {
 *
 *		// $robots is null due to cache expiration or data do not exist
 *		// Make the database call and populate the variable
 *		$robots = Robots::find(array("order" => "id"));
 *
 *		// Store it in the cache
 *		$cache->save($cacheKey, $robots);
 *	}
 *
 *	// Use $robots :)
 *	foreach ($robots as $robot) {
 *		echo $robot->name, "\n";
 *	}
 *</code>
 * 
 * @see https://github.com/phalcon/cphalcon/blob/1.2.6/ext/cache/frontend/igbinary.c
 * @note This class does not implements FrontendInterface
 */
class Igbinary extends Data implements FrontendInterface
{
	/**
	 * Serializes data before storing them
	 *
	 * @param mixed $data
	 * @return string
	 */
	public function beforeStore($data)
	{
		return igbinary_serialize($data);
	}

	/**
	 * Unserializes data after retrieval
	 *
	 * @param mixed $data
	 * @return mixed
	 */
	public function afterRetrieve($data)
	{
		return igbinary_unserialize($data);
	}
}