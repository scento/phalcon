<?php
/**
 * Mongo Cache Backend
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
	\Mongo,
	\MongoRegex;

/**
 * Phalcon\Cache\Backend\Mongo
 *
 * Allows to cache output fragments, PHP data or raw data to a MongoDb backend
 *
 *<code>
 *
 * // Cache data for 2 days
 * $frontCache = new Phalcon\Cache\Frontend\Base64(array(
 *		"lifetime" => 172800
 * ));
 *
 * //Create a MongoDB cache
 * $cache = new Phalcon\Cache\Backend\Mongo($frontCache, array(
 *		'server' => "mongodb://localhost",
 *      'db' => 'caches',
 *		'collection' => 'images'
 * ));
 *
 * //Cache arbitrary data
 * $cache->save('my-data', file_get_contents('some-image.jpg'));
 *
 * //Get data
 * $data = $cache->get('my-data');
 *
 *</code>
 * 
 * @see https://github.com/phalcon/cphalcon/blob/1.2.6/ext/cache/backend/mongo.c
 */
class Mongo extends Backend implements BackendInterface
{
	/**
	 * Mongo Collection
	 * 
	 * @var null|\MongoCollection
	 * @access protected
	*/
	protected $_collection;

	/**
	 * \Phalcon\Cache\Backend\Mongo constructor
	 *
	 * @param \Phalcon\Cache\FrontendInterface $frontend
	 * @param array|null $options
	 * @throws Exception
	 */
	public function __construct($frontend, $options = null)
	{
		if(is_array($options) === false) {
			$options = array();
		}

		if(isset($options['mongo']) === false && isset($options['server']) === false) {
			throw new Exception('The parameter \'server\' is required');
		}

		if(isset($options['db']) === false) {
			throw new Exception('The parameter \'db\' is required');
		}

		if(isset($options['collection']) === false) {
			throw new Exception("The parameter 'collection' is required");
		}

		parent::__construct($frontend, $options);
	}

	/**
	 * Returns a MongoDb collection based on the backend parameters
	 *
	 * @return MongoCollection
	 * @throws Exception
	 */
	protected function _getCollection()
	{
		if(is_object($this->_collection) === false) {
			if(isset($this->_options['mongo']) === true) {
				//If mongo is defined a valid Mongo object must be passed
				$mongo = $this->_options['mongo'];
				if(is_object($mongo) === false) {
					throw new Exception("The 'mongo' parameter must be a valid Mongo instance");
				}
			} else {
				//Server must be defined otherwise
				if(isset($this->_options['server']) === false || is_string($this->_options['server']) === false) {
					throw new Exception('The backend requires a valid MongoDB connection string');
				}

				$mongo = new Mongo($this->_options['server']);
			}

			if(is_string($this->_options['db']) === false) {
				throw new Exception('The backend requires a valid MongoDb db');
			}
			if(is_string($this->_options['collection']) === false) {
				throw new Exception('The backend requires a valid MongoDB collection');
			}

			//Make the connection and get the collection
			$mongo_database = $mongo->selectDB($this->_options['db']);
			$this->_collection = $mongo_database->selectCollection($this->_options['collection']);
		}

		return $this->_collection;
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
		if(is_int($keyName) === false && is_string($keyName) === false) {
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

		$keyName = $this->_prefix.$keyName;
		$document = $this->_getCollection->findOne(array('key' => $keyName));

		if(is_array($document) === true) {
			//Take the lifetime from the frontend or read it from the set in start()
			if(isset($document['time']) === false) {
				throw new Exception('The cache is corrupted');
			}

			if((time() - $lifetime) < $document['time']) {
				if(isset($document['data']) === false) {
					throw new Exception('The cache is corrupted');
				}

				return $this->_frontend->afterRetrieve($document['data']);
			}
		}

		return null;
	}

	/**
	 * Stores cached content into the Mongo backend and stops the frontend
	 *
	 * @param int|string|null $keyName
	 * @param string|null $content
	 * @param int|null $lifetime
	 * @param boolean|null $stopBuffer
	 * @throws Exception
	 */
	public function save($keyName = null, $content = null, $lifetime = null, $stopBuffer = null)
	{
		/* Input preprocessing */
		if(is_null($keyName) === true) {
			$keyName = $this->_lastKey;
		} elseif(is_string($keyName) === true || is_int($KeyName) === true) {
			$keyName = $this->_prefix.$keyName;
		} else {
			throw new Exception('Invalid parameter type.');
		}

		if(is_null($content) === true) {
			$content = $this->_frontend->getContent();
		} elseif(is_string($content) === false) {
			throw new Exception('Invalid parameter type.');
		}

		if(is_null($lifetime) === true) {
			$lifetime = $this->_frontend->getLifetime();
		} elseif(is_int($lifetime) === false) {
			throw new Exception('Invalid parameter type.');
		}

		if(is_null($stopBuffer) === true) {
			$stopBuffer = true;
		}

		if(isset($keyName) === false) {
			throw new Exception('The cache must be started first');
		}

		/* Store */
		$prepared_content = $this->_frontend->beforeStore($content);
		$collection = $this->_getCollection();
		$ttl = time() + $lifetime;
		$document = $collection->findOne(array('key' => $keyName));
		if(is_array($document) === true) {
			$document['time'] = $ttl;
			$document['data'] = $prepared_content;
			$collection->save($document);
		} else {
			$collection->save(array('key' => $keyName, 'time' => $ttl, 'data' => $prepared_content));
		}

		/* Handle buffer */
		$is_buffering = $this->_frontend->isBuffering();

		if($stopBuffer === true) {
			$this->_frontend->stop();
		}

		if($is_buffering === true) {
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
		if(is_int($keyName) === false &&
			is_string($keyName) === false) {
			throw new Exception('Invalid parameter type.');
		}

		$keyName = $this->_prefix.$keyName;
		$collection = $this->_getCollection();

		$collection->remove(array('key' => $keyName));
		return true;
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
		$collection = $this->_getCollection();

		if(is_string($prefix) === true) {
			$regex = new MongoRegex('/^'.$prefix.'/');
			$conditions = array('key' => $regex);
		} elseif(is_null($prefix) === true) {
			$conditions = array();
		} else {
			throw new Exception('Invalid parameter type.');
		}

		$keys = array();
		$documents = iterator_to_array($collection->find($conditions, array('key')));

		foreach($documents as $key => $document) {
			$keys[] = $document['key'];
		}

		return $keys;
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

		if(is_int($lifetime) === false &&
			is_null($lifetime) === false) {
			throw new Exception('Invalid parameter type.');
		}

		/* Check if key exists */
		if(isset($keyName) === true) {
			$collection = $this->_getCollection();

			//@note there is no check if the document has expired
			return ($collection->count(array('key' => $keyName)) > 0 ? true : false);
		}

		return false;
	}
}