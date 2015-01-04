<?php
/**
* Files Adapter
*
* @author Andres Gutierrez <andres@phalconphp.com>
* @author Eduar Carvajal <eduar@phalconphp.com>
* @author Wenzel Pünter <wenzel@phelix.me>
* @version 1.2.6
* @package Phalcon
*/
namespace Phalcon\Mvc\Model\MetaData;

use \Phalcon\Mvc\Model\MetaData;
use \Phalcon\Mvc\Model\Exception;
use \Phalcon\Mvc\Model\MetaDataInterface;
use \Phalcon\DI\InjectionAwareInterface;

/**
 * Phalcon\Mvc\Model\MetaData\Files
 *
 * Stores model meta-data in PHP files.
 *
 *<code>
 * $metaData = new \Phalcon\Mvc\Model\Metadata\Files(array(
 *    'metaDataDir' => 'app/cache/metadata/'
 * ));
 *</code>
 *
 * @see https://github.com/phalcon/cphalcon/blob/1.2.6/ext/mvc/model/metadata/files.c
 */
class Files extends MetaData implements InjectionAwareInterface, MetaDataInterface
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
     * Metadata Directory
     *
     * @var string
     * @access protected
    */
    protected $_metaDataDir = './';

    /**
     * \Phalcon\Mvc\Model\MetaData\Files constructor
     *
     * @param array|null $options
     */
    public function __construct($options = null)
    {
        if (is_array($options) === true &&
            isset($options['metaDataDir']) === true) {
            $this->_metaDataDir = $options['metaDataDir'];
        }

        $this->_metaData = array();
    }

    /**
     * Replaces directory seperators by the virtual seperator
     *
     * @param string $path
     * @param string $virtualSeperator
     * @throws Exception
    */
    private static function prepareVirtualPath($path, $virtualSeperator)
    {
        if (is_string($path) === false ||
            is_string($virtualSeperator) === false) {
            if (is_string($path) === true) {
                return $path;
            } else {
                return '';
            }
        }

        $virtualStr = '';
        $l = strlen($path);
        for ($i = 0; $i < $l; ++$i) {
            $ch = $path[$i];

            if ($ch === "\0") {
                break;
            }

            if ($ch === '/' || $ch === '\\' || $ch === ':' || ctype_print($ch) === false) {
                $virtualStr .= $virtualSeperator;
            } else {
                $virtualStr .= strtolower($ch);
            }
        }

        return $virtualStr;
    }

    /**
     * Reads meta-data from files
     *
     * @param string $key
     * @return array
     * @throws Exception
     */
    public function read($key)
    {
        if (is_string($key) === false) {
            throw new Exception('Invalid parameter type.');
        }

        $path = $this->_metaDataDir.self::prepareVirtualPath($key, '_').'.php';
        if (file_exists($path) === true) {
            //@note this isn't a very satisfying solution
            $str = file_get_contents($path);
            if ($str === false) {
                throw new Exception('Error while reading file.');
            }

            return eval($str);
        }

        return null;
    }

    /**
     * Writes the meta-data to files
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

        $path = $this->_metaDataDir.self::prepareVirtualPath($key, '_').'.php';
        if (file_put_contents($path, '<?php return '.var_export($data, true).'; ') === false) {
            throw new Exception('Meta-Data directory cannot be written');
        }
    }
}
