<?php
/**
 * XCache Cache Backend
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
 * Phalcon\Cache\Backend\Xcache
 *
 * Allows to cache output fragments, PHP data and raw data using an XCache backend
 *
 *<code>
 *	//Cache data for 2 days
 *	$frontCache = new Phalcon\Cache\Frontend\Data(array(
 *		'lifetime' => 172800
 *	));
 *
 *  $cache = new Phalcon\Cache\Backend\Xcache($frontCache, array(
 *      'prefix' => 'app-data'
 *  ));
 *
 *	//Cache arbitrary data
 *	$cache->save('my-data', array(1, 2, 3, 4, 5));
 *
 *	//Get data
 *	$data = $cache->get('my-data');
 *
 *</code>
 * 
 * @see https://github.com/phalcon/cphalcon/blob/1.2.6/ext/cache/backend/xcache.c
 */
class Xcache extends Backend implements BackendInterface
{
	/**
	 * \Phalcon\Cache\Backend\Xcache constructor
	 *
	 * @param \Phalcon\Cache\FrontendInterface $frontend
	 * @param array|null $options
	 */
	public function __construct($frontend, $options = null)
	{
		if(is_array($options) === false) {
			$options = array();
		}

		if(isset($options['statsKey']) === true) {
			$options['statsKey'] = '_PHCX';
		}

		parent::__construct($frontend, $options);
	}

	/**
	 * Returns cached content
	 *
	 * @param string $keyName
	 * @param int|null $lifetime
	 * @return mixed
	 * @throws Exception
	 */
	public function get($keyName, $lifetime = null)
	{
		/* Type check */
		if(is_string($keyName) === false) {
			throw new Exception('Invalid parameter type.');
		}

		if(is_int($lifetime) === false && is_null($lifetime) === false) {
			throw new Exception('Invalid parameter type.');
		}

		/* Get content */
		$this->_lastKey = '_PHCX'.$this->_prefix.$keyName;

		$content = xcache_get($this->_lastKey);
		if(is_null($content) === true) {
			return null;
		}

		return $this->_frontend->afterRetrieve($content);
	}

	/**
	 * Stores cached content into the XCache backend and stops the frontend
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
		if(is_null($keyName) === true) {
			$keyName = $this->_lastKey;
			if(isset($keyName) === false) {
				throw new Exception('The cache must be started first');
			}
		} elseif(is_string($keyName) === true) {
			$keyName = '_PHCX'.$this->_prefix.$keyName;
		} else {
			throw new Exception('Invalid parameter type.');
		}

		if(is_null($content) === true) {
			$content = $this->_frontend->getContent();
		} elseif(is_string($content) === false) {
			throw new Exception('Invalid parameter type.');
		}

		if(is_null($lifetime) === true) {
			$lifetime = $this->_lastLifetime;
			if(is_null($lifetime) === true) {
				$lifetime = $this->_frontend->getLifetime();
			}
		} elseif(is_int($lifetime) === false) {
			throw new Exception('Invalid parameter type.');
		}

		if(is_null($stopBuffer) === true) {
			$stopBuffer = true;
		} elseif(is_bool($stopBuffer) === false) {
			throw new Exception('Invalid parameter type.');
		}

		/* Store */
		$preparedContent = $this->_frontend->beforeStore($content);

		$success = xcache_set($keyName, $preparedContent, $lifetime);
		$isBuffering = $this->_frontend->isBuffering();

		if($stopBuffer === true) {
			$this->_frontend->stop();
		}

		$this->_started = false;

		/*
		 * xcache_set() could fail because of Out of Memory condition. I don't think it is
		 * appropriate to throw an exception here (like
		 * Phalcon\Cache\Backend\Memcache::save() does): first, to be consistent with
		 * Phalcon\Cache\Backend\Apc::save(), second, because xCache is usually given much
		 * less RAM than memcached
		 */
		if($success === true) {
			/** 
			 * xcache_list() is available only to the administrator (unless XCache was
			 * patched). We have to update the list of the stored keys.
			 */
			$keys = xcache_get($this->_options['statsKey']);
			if(is_array($keys) === false) {
				$keys = array();
			}

			$keys[$keyName] = $lifetime;

			xcache_set($this->_options['statsKey'], $keys, 0);
		}
	}

	/**
	 * Deletes a value from the cache by its key
	 *
	 * @param string $keyName
	 * @return boolean
	 * @throws Exception
	 */
	public function delete($keyName)
	{
		if(is_string($keyName) === false) {
			throw new Exception('Invalid parameter type.');
		}

		$keyName = '_PHCX'.$this->_prefix.$keyName;

		$success = xcache_unset($keyName);

		$keys = xcache_get($this->_options['statsKey']);
		if(is_array($keys) === true) {
			xcache_set($this->_options['statsKey'], $keys, 0);
		}

		return $success;
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
		if(is_null($prefix) === true) {
			$prefix = '_PHCX';
		} elseif(is_string($prefix) === true) {
			$prefix = '_PHCX'.$prefix;
		} else {
			throw new Exception('Invalid parameter type.');
		}

		/*
		*Get the key from XCache (we cannot use xcache_list() as it is available only to
	 	* the administrator)
	 	*/
		$keys = xcache_get($this->_options['statsKey']);
		if(is_array($keys) === true) {
			$prefixedKeys = array();
			foreach($keys as $key => $ttl) {
				if(Text::startsWith($key, $prefix) === false) {
					continue;
				}

				$prefixedKeys[] = substr($key, 5);
			}

			return $prefixedKeys;
		}

		return array();
	}

	/**
	 * Checks if the cache entry exists and has not expired
	 *
	 * @param string|null $keyName
	 * @param int|null $lifetime
	 * @return boolean
	 * @throws Exception
	 */
	public function exists($keyName = null, $lifetime = null)
	{
		if(is_null($keyName) === true) {
			$keyName = $this->_lastKey;
		} elseif(is_string($keyName) === true) {
			$keyName = '_PHCX'.$this->_prefix.$keyName;
		} else {
			throw new Exception('Invalid parameter type.');
		}

		if(is_null($lifetime) === false && is_int($lifetime) === false) {
			throw new Exception('Invalid parameter type.');
		}

		if(isset($keyName) === true) {
			return xcache_isset($keyName);
		}

		return false;
	}
}