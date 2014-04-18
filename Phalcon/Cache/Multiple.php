<?php
/**
 * Multiple Backends
 *
 * @author Andres Gutierrez <andres@phalconphp.com>
 * @author Eduar Carvajal <eduar@phalconphp.com>
 * @author Wenzel Pünter <wenzel@phelix.me>
 * @version 1.2.6
 * @package Phalcon
*/
namespace Phalcon\Cache;

use \Phalcon\Cache\Exception;

/**
 * Phalcon\Cache\Multiple
 *
 * Allows to read to chained backends writing to multiple backends
 *
 *<code>
 *   use Phalcon\Cache\Frontend\Data as DataFrontend,
 *       Phalcon\Cache\Multiple,
 *       Phalcon\Cache\Backend\Apc as ApcCache,
 *       Phalcon\Cache\Backend\Memcache as MemcacheCache,
 *       Phalcon\Cache\Backend\File as FileCache;
 *
 *   $ultraFastFrontend = new DataFrontend(array(
 *       "lifetime" => 3600
 *   ));
 *
 *   $fastFrontend = new DataFrontend(array(
 *       "lifetime" => 86400
 *   ));
 *
 *   $slowFrontend = new DataFrontend(array(
 *       "lifetime" => 604800
 *   ));
 *
 *   //Backends are registered from the fastest to the slower
 *   $cache = new Multiple(array(
 *       new ApcCache($ultraFastFrontend, array(
 *           "prefix" => 'cache',
 *       )),
 *       new MemcacheCache($fastFrontend, array(
 *           "prefix" => 'cache',
 *           "host" => "localhost",
 *           "port" => "11211"
 *       )),
 *       new FileCache($slowFrontend, array(
 *           "prefix" => 'cache',
 *           "cacheDir" => "../app/cache/"
 *       ))
 *   ));
 *
 *   //Save, saves in every backend
 *   $cache->save('my-key', $data);
 *</code>
 * 
 * @see https://github.com/phalcon/cphalcon/blob/1.2.6/ext/cache/multiple.c
 */
class Multiple
{
	/**
	 * Backends
	 * 
	 * @var null|array
	 * @access protected
	*/
	protected $_backends = null;

	/**
	 * \Phalcon\Cache\Multiple constructor
	 *
	 * @param \Phalcon\Cache\BackendInterface[]|null $backends
	 * @throws Exception
	 */
	public function __construct($backends = null)
	{
		if(is_null($backends) === true) {
			$this->_backends = array();
		} elseif(is_array($backends) === true) {
			$this->_backends = $backends;
		} else {
			throw new Exception('The backends must be an array');
		}
	}

	/**
	 * Adds a backend
	 *
	 * @param \Phalcon\Cache\BackendInterface $backend
	 * @return \Phalcon\Cache\Multiple
	 * @throws Exception
	 */
	public function push($backend)
	{
		if(is_object($backend) === false ||
			$backend instanceof BackendInterface === false) {
			throw new Exception('The backend is not valid');
		}

		$this->_backends[] = $backend;

		return $this;
	}

	/**
	 * Returns a cached content reading the internal backends
	 *
	 * @param string $keyName
	 * @param int|null $lifetime
	 * @return mixed
	 */
	public function get($keyName, $lifetime = null)
	{
		foreach($this->_backends as $backend) {
			$content = $backend->get($keyName, $lifetime);
			if(is_null($content) === false) {
				return $content;
			}
		}
	}

	/**
	 * Starts every backend
	 *
	 * @param int|string $keyName
	 * @param int|null $lifetime
	 */
	public function start($keyName, $lifetime = null)
	{
		foreach($this->_backends as $backend) {
			$backend->start($keyName, $lifetime);
		}
	}

	/**
	 * Stores cached content into all backends and stops the frontend
	 *
	 * @param string|null $keyName
	 * @param string|null $content
	 * @param int|null $lifetime
	 * @param boolean|null $stopBuffer
	 * @throws Exception
	 */
	public function save($keyName = null, $content = null, $lifetime = null, $stopBuffer = null)
	{
		/* Type check */
		if(is_string($keyName) === false && is_null($keyName) === false) {
			throw new Exception('Invalid parameter type.');
		}

		if(is_string($content) === false && is_null($content) === false) {
			throw new Exception('Invalid parameter type.');
		}

		if(is_int($lifetime) === false && is_null($lifetime) === false) {
			throw new Exception('Invalid parameter type.');
		}

		if(is_null($stopBuffer) === true) {
			$stopBuffer = true;
		} elseif(is_int($stopBuffer) === false) {
			throw new Exception('Invalid parameter type.');
		}

		/* Execution */
		foreach($this->_backends as $bakcend) {
			$backend->save($keyName, $content, $lifetime, $stopBuffer);
		}
	}

	/**
	 * Deletes a value from each backend
	 *
	 * @param int|string $keyName
	 * @throws Exception
	 */
	public function delete($keyName)
	{
		//@note scalar would be more appropriate
		if(is_int($keyName) === false && is_string($keyName) === false) {
			throw new Exception('Invalid parameter type.');
		}

		foreach($this->_backends as $backend) {
			$backend->delete($keyName);
		}
	}

	/**
	 * Checks if cache exists in at least one backend
	 *
	 * @param string|null $keyName
	 * @param int|null $lifetime
	 * @return boolean
	 * @throws Exception
	 */
	public function exists($keyName = null, $lifetime = null)
	{
		if(is_string($keyName) === false && is_null($keyName) === false) {
			throw new Exception('Invalid parameter type.');
		}

		if(is_int($lifetime) === false && is_null($lifetime) === false) {
			throw new Exception('Invalid parameter type.');
		}

		foreach($this->_backends as $backend) {
			if($backend->exists($keyName, $lifetime) === true) {
				return true;
			}
		}

		return false;
	}
}