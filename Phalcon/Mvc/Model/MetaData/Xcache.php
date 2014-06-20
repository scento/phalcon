<?php
/**
* XCache Adapter
*
* @author Andres Gutierrez <andres@phalconphp.com>
* @author Eduar Carvajal <eduar@phalconphp.com>
* @author Wenzel Pünter <wenzel@phelix.me>
* @version 1.2.6
* @package Phalcon
*/
namespace Phalcon\Mvc\Model\MetaData;

use \Phalcon\Mvc\Model\MetaData,
	\Phalcon\Mvc\Model\MetaDataInterface,
	\Phalcon\Mvc\Model\Exception,
	\Phalcon\DI\InjectionAwareInterface;
	
/**
 * Phalcon\Mvc\Model\MetaData\Xcache
 *
 * Stores model meta-data in the XCache cache. Data will erased if the web server is restarted
 *
 * By default meta-data is stored for 48 hours (172800 seconds)
 *
 * You can query the meta-data by printing xcache_get('$PMM$') or xcache_get('$PMM$my-app-id')
 *
 *<code>
 *	$metaData = new Phalcon\Mvc\Model\Metadata\Xcache(array(
 *		'prefix' => 'my-app-id',
 *		'lifetime' => 86400
 *	));
 *</code>
 * 
 * @see https://github.com/phalcon/cphalcon/blob/1.2.6/ext/mvc/model/metadata/xcache.c
 */
class Xcache extends MetaData implements InjectionAwareInterface, MetaDataInterface
{
	/**
	 * Models: Attributes
	 *
	 * @var int
	*/
	const MODELS_ATTRIBUTES = 0;

	/**
	 * Models: Primary Key
	 *
	 * @var int
	*/
	const MODELS_PRIMARY_KEY = 1;

	/**
	 * Models: Non Primary Key
	 *
	 * @var int
	*/
	const MODELS_NON_PRIMARY_KEY = 2;

	/**
	 * Models: Not Null
	 *
	 * @var int
	*/
	const MODELS_NOT_NULL = 3;

	/**
	 * Models: Data Types
	 *
	 * @var int
	*/
	const MODELS_DATA_TYPES = 4;

	/**
	 * Models: Data Types Numeric
	 *
	 * @var int
	*/
	const MODELS_DATA_TYPES_NUMERIC = 5;

	/**
	 * Models: Date At
	 *
	 * @var int
	*/
	const MODELS_DATE_AT = 6;

	/**
	 * Models: Date In
	 *
	 * @var int
	*/
	const MODELS_DATE_IN = 7;

	/**
	 * Models: Identity Column
	 *
	 * @var int
	*/
	const MODELS_IDENTITY_COLUMN = 8;

	/**
	 * Models: Data Types Bind
	 *
	 * @var int
	*/
	const MODELS_DATA_TYPES_BIND = 9;

	/**
	 * Models: Automatic Default Insert
	 *
	 * @var int
	*/
	const MODELS_AUTOMATIC_DEFAULT_INSERT = 10;

	/**
	 * Models: Automatic Default Update
	 *
	 * @var int
	*/
	const MODELS_AUTOMATIC_DEFAULT_UPDATE = 11;

	/**
	 * Models: Column Map
	 *
	 * @var int
	*/
	const MODELS_COLUMN_MAP = 0;

	/**
	 * Models: Reverse Column Map
	 *
	 * @var int
	*/
	const MODELS_REVERSE_COLUMN_MAP = 1;

	/**
	 * Prefix
	 * 
	 * @var string
	 * @access protected
	*/
	protected $_prefix = '';

	/**
	 * Lifetime
	 * 
	 * @var int
	 * @access protected 
	*/
	protected $_ttl;

	/**
	 * \Phalcon\Mvc\Model\MetaData\Xcache constructor
	 *
	 * @param array|null $options
	 */
	public function __construct($options = null)
	{
		if(is_array($options) === true) {
			if(isset($options['prefix']) === true) {
				$this->_prefix = $options['prefix'];
			}

			if(isset($options['lifetime']) === true) {
				$this->_ttl = $options['lifetime'];
			}
		}

		$this->_metaData = array();
	}

	/**
	 * Reads metadata from XCache
	 *
	 * @param string $key
	 * @return array|null
	 * @throws Exception
	 */
	public function read($key)
	{
		if(is_string($key) === false) {
			throw new Exception('Invalid parameter type.');
		}

		$data = xcache_get('$PMM$'.$this->_prefix.$key);
		if(is_array($data) === true) {
			return $data;
		}
	}

	/**
	 *  Writes the metadata to XCache
	 *
	 * @param string $key
	 * @param array $data
	 * @throws Exception
	 */
	public function write($key, $data)
	{
		if(is_string($key) === false ||
			is_array($data) === false) {
			throw new Exception('Invalid parameter type.');
		}

		xcache_set('$PMM$'.$this->_prefix.$key, $data, $this->_ttl);
	}
}