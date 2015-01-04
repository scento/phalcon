<?php
/**
* Session Adapter
*
* @author Andres Gutierrez <andres@phalconphp.com>
* @author Eduar Carvajal <eduar@phalconphp.com>
* @author Wenzel Pünter <wenzel@phelix.me>
* @version 1.2.6
* @package Phalcon
*/
namespace Phalcon\Mvc\Model\MetaData;

use \Phalcon\Mvc\Model\MetaData;
use \Phalcon\Mvc\Model\MetaDataInterface;
use \Phalcon\Mvc\Model\Exception;
use \Phalcon\DI\InjectionAwareInterface;

/**
 * Phalcon\Mvc\Model\MetaData\Session
 *
 * Stores model meta-data in session. Data will erased when the session finishes.
 * Meta-data are permanent while the session is active.
 *
 * You can query the meta-data by printing $_SESSION['$PMM$']
 *
 *<code>
 * $metaData = new Phalcon\Mvc\Model\Metadata\Session(array(
 *    'prefix' => 'my-app-id'
 * ));
 *</code>
 *
 * @see https://github.com/phalcon/cphalcon/blob/1.2.6/ext/mvc/model/metadata/session.c
 * @note This class works directly with the $_SESSION variable. It might be useful to use
 * Phalcon abstraction classes, which allow some extended session handeling.
 */
class Session extends MetaData implements InjectionAwareInterface, MetaDataInterface
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
     * \Phalcon\Mvc\Model\MetaData\Session constructor
     *
     * @param array|null $options
     */
    public function __construct($options = null)
    {
        if (is_array($options) === true &&
            isset($options['prefix']) === true) {
            $this->_prefix = $options['prefix'];
        }

        $this->_metaData = array();
    }

    /**
     * Reads meta-data from $_SESSION
     *
     * @param string $key
     * @return array|null
     * @throws Exception
     */
    public function read($key)
    {
        if (is_string($key) === false) {
            throw new Exception('Invalid parameter type.');
        }

        $prefixKey = '$PMM$'.$this->_prefix;
        if (isset($_SESSION[$prefixKey]) === true &&
            isset($_SESSION[$prefixKey][$key])) {
            return $_SESSION[$prefixKey][$key];
        }
    }

    /**
     * Writes the meta-data to $_SESSION
     *
     * @param string $key
     * @param array $data
     * @throws Exception
     */
    public function write($key, $data)
    {
        if (is_string($key) === false ||
            is_array($data) === false) {
            throw new Exception('Invalid parameter type.');
        }

        $prefixKey = '$PMM$'.$this->_prefix;

        if (is_array($_SESSION[$prefixKey]) === false) {
            $_SESSION[$prefixKey] = array();
        }

        $_SESSION[$prefixKey][$key] = $data;
    }
}
