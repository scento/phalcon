<?php
/**
 * Memory Cache Backend
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
 * Phalcon\Cache\Backend\Memory
 *
 * Stores content in memory. Data is lost when the request is finished
 *
 *<code>
 *	//Cache data
 *	$frontCache = new Phalcon\Cache\Frontend\Data();
 *
 *  $cache = new Phalcon\Cache\Backend\Memory($frontCache);
 *
 *	//Cache arbitrary data
 *	$cache->save('my-data', array(1, 2, 3, 4, 5));
 *
 *	//Get data
 *	$data = $cache->get('my-data');
 *
 *</code>
 * 
 * @see https://github.com/phalcon/cphalcon/blob/1.2.6/ext/cache/backend/memory.c
 */
class Memory extends Backend implements BackendInterface
{
	/**
	 * Data
	 * 
	 * @var array
	 * @access protected
	*/
	protected $_data = array();

	/**
	 * Returns a cached content
	 *
	 * @param string|null $keyName
	 * @param int|null $lifetime
	 * @return mixed
	 * @throws Exception
	 */
	public function get($keyName, $lifetime = null)
	{
		if(is_null($keyName) === true) {
			$keyName = $this->_lastKey;
		} elseif(is_string($keyName) === true) {
			$keyName = $this->_prefix.$keyName;
		} else {
			throw new Exception('Invalid parameter type.');
		}

		if(is_int($lifetime) === false && is_null($lifetime) === false) {
			throw new Exception('Invalid parameter type.');
		}

		if(isset($this->_data[$keyName]) === false) {
			return null;
		}

		$content = $this->_data[$keyName];
		if(is_null($content) === true) {
			return null;
		}

		return $this->_frontend->afterRetrieve($content);
	}

	/**
	 * Stores cached content into the backend and stops the frontend
	 *
	 * @param string|null $keyName
	 * @param string|null $content
	 * @param int|null $lifetime
	 * @param boolean|null $stopBuffer
	 * @throws Exception
	 */
	public function save($keyName = null, $content = null, $lifetime = null, $stopBuffer = null)
	{
		/* Input processing */
		if(is_null($keyName) === true) {
			$keyName = $this->_lastKey;
		} elseif(is_string($keyName) === true) {
			$keyName = $this->_prefix.$keyName;
		} else {
			throw new Exception('Invalid parameter type.');
		}

		if(isset($keyName) === false) {
			throw new Exception('The cache must be started first');
		}

		if(is_null($content) === true) {
			$content = $this->_frontend->getContent();
		} elseif(is_string($content) === false) {
			throw new Exception('Invalid parameter type.');
		}

		if(is_null($stopBuffer) === true) {
			$stopBuffer = true;
		}

		/* Store data */
		$preparedContent = $this->_frontend->beforeStore($content);
		$this->_data[$keyName] = $preparedContent;

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
	 * @param string $keyName
	 * @return boolean
	 * @throws Exception
	 */
	public function delete($keyName)
	{
		if(is_string($keyName) === false) {
			throw new Exception('Invalid parameter type.');
		}

		$keyName = $this->_prefix.$keyName;

		if(isset($this->_data[$keyName]) === true) {
			unset($this->_data[$keyName]);
			return true;
		}

		return false;
	}

	/**
	 * Query the existing cached keys
	 *
	 * @param string|null $prefix
	 * @return array
	 */
	public function queryKeys($prefix = null)
	{
		if(is_null($prefix) === false) {
			$prefix = (string)$prefix;
		}

		if(is_array($this->_data) === true) {
			if(is_null($prefix) === true) {
				return array_keys($this->_data);
			}

			$result = array();
			foreach($this->_data as $key => $value) {
				if(Text::startsWith($key, $prefix) === true) {
					$result[] = $key;
				}
			}

			return $result;
		}

		//@note The default implementation returns NULL
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
			throw new Exeception('Invalid parameter type.');
		}

		if(is_null($lifetime) === false && is_int($lifetime) === false) {
			throw new Exception('Invalid parameter type.');
		}

		/* Key check */
		if(isset($keyName) === true) {
			return isset($this->_data[$keyName]);
		}

		return false;
	}
}