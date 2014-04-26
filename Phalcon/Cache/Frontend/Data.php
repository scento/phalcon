<?php
/**
 * Data Cache Frontend
 *
 * @author Andres Gutierrez <andres@phalconphp.com>
 * @author Eduar Carvajal <eduar@phalconphp.com>
 * @author Wenzel Pünter <wenzel@phelix.me>
 * @version 1.2.6
 * @package Phalcon
*/
namespace Phalcon\Cache\Frontend;

use \Phalcon\Cache\FrontendInterface,
	\Phalcon\Cache\Exception;

/**
 * Phalcon\Cache\Frontend\Data
 *
 * Allows to cache native PHP data in a serialized form
 *
 *<code>
 *
 *	// Cache the files for 2 days using a Data frontend
 *	$frontCache = new Phalcon\Cache\Frontend\Data(array(
 *		"lifetime" => 172800
 *	));
 *
 *	// Create the component that will cache "Data" to a "File" backend
 *	// Set the cache file directory - important to keep the "/" at the end of
 *	// of the value for the folder
 *	$cache = new Phalcon\Cache\Backend\File($frontCache, array(
 *		"cacheDir" => "../app/cache/"
 *	));
 *
 *	// Try to get cached records
 *	$cacheKey = 'robots_order_id.cache';
 *	$robots    = $cache->get($cacheKey);
 *	if ($robots === null) {
 *
 *		// $robots is null due to cache expiration or data does not exist
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
 * @see https://github.com/phalcon/cphalcon/blob/1.2.6/ext/cache/frontend/data.c
 */
class Data implements FrontendInterface
{
	/**
	 * Frontend Options
	 * 
	 * @var null|array
	 * @access protected
	*/
	protected $_frontendOptions;

	/**
	 * \Phalcon\Cache\Frontend\Data constructor
	 *
	 * @param array|null $frontendOptions
	 * @throws Exception
	 */
	public function __construct($frontendOptions = null)
	{
		if(is_array($frontendOptions) === false &&
			is_null($frontendOptions) === false) {
			throw new Exception('Invalid parameter type.');
		}

		$this->_frontendOptions = $frontendOptions;
	}

	/**
	 * Returns cache lifetime
	 *
	 * @return int
	 */
	public function getLifetime()
	{
		if(is_array($this->_frontendOptions) === true && 
			isset($this->_frontendOptions['lifetime']) === true) {
			return (int)$this->_frontendOptions['lifetime'];
		}

		return 1;
	}

	/**
	 * Check whether if frontend is buffering output
	 *
	 * @return boolean
	 */
	public function isBuffering()
	{
		return false;
	}

	/**
	 * Starts output frontend. Actually, does nothing
	 */
	public function start()
	{

	}

	/**
	 * Returns output cached content
	 *
	 * @return string
	 */
	public function getContent()
	{

	}

	/**
	 * Stops output frontend
	 */
	public function stop()
	{

	}

	/**
	 * Serializes data before storing them
	 *
	 * @param mixed $data
	 * @return string
	 */
	public function beforeStore($data)
	{
		return serialize($data);
	}

	/**
	 * Unserializes data after retrieval
	 *
	 * @param mixed $data
	 * @return mixed
	 */
	public function afterRetrieve($data)
	{
		return unserialize($data);
	}
}