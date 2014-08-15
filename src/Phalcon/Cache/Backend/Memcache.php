<?php
/**
 * Memcache Cache Backend
 *
 * @author Andres Gutierrez <andres@phalconphp.com>
 * @author Eduar Carvajal <eduar@phalconphp.com>
 * @author Wenzel Pünter <wenzel@phelix.me>
 * @version 1.2.6
 * @package Phalcon
*/
namespace Phalcon\Cache\Backend;

use \Phalcon\Cache\Backend,
	\Phalcon\Cache\BackendInterface,
	\Phalcon\Cache\Exception,
	\Phalcon\Text;

/**
 * Phalcon\Cache\Backend\Memcache
 *
 * Allows to cache output fragments, PHP data or raw data to a memcache backend
 *
 * This adapter uses the special memcached key "_PHCM" to store all the keys internally used by the adapter
 *
 *<code>
 *
 * // Cache data for 2 days
 * $frontCache = new Phalcon\Cache\Frontend\Data(array(
 *    "lifetime" => 172800
 * ));
 *
 * //Create the Cache setting memcached connection options
 * $cache = new Phalcon\Cache\Backend\Memcache($frontCache, array(
 *		'host' => 'localhost',
 *		'port' => 11211,
 *  	'persistent' => false
 * ));
 *
 * //Cache arbitrary data
 * $cache->save('my-data', array(1, 2, 3, 4, 5));
 *
 * //Get data
 * $data = $cache->get('my-data');
 *
 *</code>
 * 
 * @see https://github.com/phalcon/cphalcon/blob/1.2.6/ext/cache/backend/memcache.c
 */
class Memcache extends Backend implements BackendInterface
{
	/**
	 * Memcache Instance
	 * 
	 * @var null|\Memcache
	 * @access protected
	*/
	protected $_memcache;

	/**
	 * \Phalcon\Cache\Backend\Memcache constructor
	 *
	 * @param \Phalcon\Cache\FrontendInterface $frontend
	 * @param array|null $options
	 */
	public function __construct($frontend, $options = null)
	{
		if(is_null($options) === true) {
			$options = array();
		}

		if(isset($options['host']) === false) {
			$options['host'] = '127.0.0.1';
		}

		if(isset($options['port']) === false) {
			$options['port'] = '11211';
		}

		if(isset($options['persistent']) === false) {
			$options['persistent'] = false;
		}

		if(isset($options['statsKey']) === false) {
			$options['statsKey'] = '_PHCM';
		}

		parent::__construct($frontend, $options);
	}

	/**
	 * Create internal connection to memcached
	 * 
	 * @throws Exception
	 */
	protected function _connect()
	{
		$memcache = new \Memcache();

		if($this->_options['persistent'] === true) {
			$success = $memcache->pconnect($this->_options['host'], $this->_options['port']);
		} else {
			$success = $memcache->connect($this->_options['host'], $this->_options['port']);
		}

		if($success !== true) {
			throw new Exception('Cannot connect to Memcached server');
		}

		$this->_memcache = $memcache;
	}

	/**
	 * Returns a cached content
	 *
	 * @param int|string $keyName
	 * @param int|null $lifetime
	 * @return mixed
	 * @throws Exception
	 */
	public function get($keyName, $lifetime = null)
	{
		/* Type check */
		if(is_int($keyName) === false && is_string($keyName) === false) {
			throw new Exception('Invalid parameter type.');
		}

		if(is_int($lifetime) === false && is_null($lifetime) === false) {
			throw new Exception('Invalid parameter type.');
		}

		/* Connect if required */
		if(is_object($this->_memcache) === false) {
			$this->_connect();
		}

		/* Get data */
		$this->_lastKey = $this->_prefix.$keyName;
		$cachedContent = $this->_memcache->get($this->_lastKey);
		if($cachedContent === false) {
			return null;
		}
		return $this->_frontend->afterRetrieve($cachedContent);
	}

	/**
	 * Stores cached content into the Memcached backend and stops the frontend
	 *
	 * @param int|string|null $keyName
	 * @param string|null $content
	 * @param int|null $lifetime
	 * @param boolean|null $stopBuffer
	 * @throws Exception
	 */
	public function save($keyName = null, $content = null, $lifetime = null, $stopBuffer = null)
	{
		/* Type check */
		if(is_null($keyName) === true) {
			$keyName = $this->_lastKey;
		} elseif(is_string($keyName) === true) {
			$keyName = $this->_prefix.$keyName;
		}

		if(is_null($content) === false) {
			$content = $this->_frontend->getContent();
		}

		if(is_null($lifetime) === true) {
			$lifetime = $this->_frontend->getLifetime();
		} elseif(is_int($lifetime) === false) {
			throw new Exception('Invalid parameter type.');
		}

		if(is_null($stopBuffer) === true) {
			$stopBuffer = true;
		} elseif(is_bool($stopBuffer) === false) {
			throw new Exception('Invalid parameter type.');
		}


		/* Store data */
		if(isset($keyName) !== true) {
			throw new Exception('The cache must be started first');
		}
		
		//Check if a connection is created or make a new one
		if(is_object($this->_memcache) === false) {
			$this->_connect();
		}

		//Prepare the content in the frontend
		$preparedContent = $this->_frontend->beforeStore($content);

		//We store without flags
		if($this->_memcache->set($keyName, $preparedContent, 0, $lifetime) === false) {
			throw new Exception('Failed storing data in memcached');
		}


		/* Stats Key */
		//Update the stats key
		$keys = $this->_memcache->get($this->_options['statsKey']);
		if(is_array($keys) === false) {
			$keys = array();
		}

		if(isset($keys[$keyName]) === false) {
			$keys[$keyName] = $lifetime;
			//@note no return value check
			$this->_memcache->set($this->_options['statsKey'], $keys);
		}


		/* Handle buffer */
		$isBuffering = $this->_frontend->isBuffering();
		if($stopBuffer === true) {
			$this->_frontend->stop();
		}

		if($isBuffering === true) {
			echo $content;
		}

		$this->_started = false;
	}

	/**
	 * Deletes a value from the cache by its key
	 *
	 * @param int|string $keyName
	 * @return boolean
	 * @throws Exception
	 */
	public function delete($keyName)
	{
		/* Type check */
		if(is_int($keyName) === false && is_string($keyName) === false) {
			throw new Exception('Invalid parameter type.');
		}

		if(is_object($this->_memcache) === false) {
			$this->_connect();
		}

		$prefixedKey = $this->_prefix.$keyName;

		/* Update stats key */
		$keys = $this->_memcache->get($this->_options['statsKey']);
		if(is_array($keys) === true) {
			unset($keys[$prefixedKey]);
			$this->_memcache->set($this->_options['statsKey']);
		}

		/* Delete the key from memcached */
		return $this->_memcache->delete($prefixedKey);
	}

	/**
	 * Query the existing cached keys
	 *
	 * @param string|null $prefix
	 * @return array
	 * @throws Exception
	 */
	public function queryKeys($prefix = null)
	{
		if(is_string($prefix) === false && is_null($prefix) === false) {
			throw new Exception('Invalid parameter type.');
		}

		if(is_object($this->_memcache) === false) {
			$this->_connect();
		}

		//Get the key from memcached
		$keys = $this->_memcache->get($this->_options['statsKey']);
		if(is_array($keys) === true) {

			if(isset($prefix) === true) {
				//Use prefix
				$prefixedKeys = array();
				foreach($keys as $key => $ttl) {
					if(Text::startsWith($key, $prefix) === false) {
						continue;
					}

					$prefixedKeys[] = $key;
				}
			} else {
				//Don't use prefix
				$prefixedKeys = array_keys($keys);
			}
		}

		return array();
	}

	/**
	 * Checks if cache exists and it hasn't expired
	 *
	 * @param string|null $keyName
	 * @param int|null $lifetime
	 * @return boolean
	 * @throws Exception
	 */
	public function exists($keyName = null, $lifetime = null)
	{
		/* Type check */
		if(is_null($keyName) === true) {
			$keyName = $this->_lastKey;
		} elseif(is_string($keyName) === true) {
			$keyName = $this->_prefix.$keyName;
		} else {
			throw new Exception('Invalid parameter type.');
		}

		if(is_null($lifetime) === false && is_int($lifetime) === false) {
			throw new Exception('Invalid parameter type.');
		}

		/* Check for key */
		if(isset($keyName) === true) {
			if(is_object($this->_memcache) === false) {
				$this->_connect();
			}

			if($this->_memcache->get($keyName) !== false) {
				return true;
			}
		}

		return false;
	}
}