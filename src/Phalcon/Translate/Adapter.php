<?php
/**
 * Translate Adapter
 *
 * @author Andres Gutierrez <andres@phalconphp.com>
 * @author Eduar Carvajal <eduar@phalconphp.com>
 * @author Wenzel Pünter <wenzel@phelix.me>
 * @version 1.2.6
 * @package Phalcon
*/
namespace Phalcon\Translate;

use \ArrayAccess;
use \Phalcon\Translate\Exception;

/**
 * Phalcon\Translate\Adapter
 *
 * Base class for Phalcon\Translate adapters
 *
 * @see https://github.com/phalcon/cphalcon/blob/1.2.6/ext/translate/adapter.c
 */
abstract class Adapter implements ArrayAccess
{
    /**
     * Returns the translation string of the given key
     *
     * @param string $translateKey
     * @param array|null $placeholders
     * @return string
     */
    public function _($translateKey, $placeholders = null)
    {
        return $this->query($translateKey, $placeholders);
    }

    /**
     * Sets a translation value
     *
     * @param string $offset
     * @param string $value
     * @throws Exception
     */
    public function offsetSet($offset, $value)
    {
        throw new Exception('Translate is an immutable ArrayAccess object');
    }

    /**
     * Check whether a translation key exists
     *
     * @param string $translateKey
     * @return boolean
     */
    public function offsetExists($translateKey)
    {
        return $this->exists($translateKey);
    }

    /**
     * Unsets a translation from the dictionary
     *
     * @param string $offset
     * @throws Exception
     */
    public function offsetUnset($offset)
    {
        throw new Exception('Translate is an immutable ArrayAccess object');
    }

    /**
     * Returns the translation related to the given key
     *
     * @param string $translateKey
     * @return string
     */
    public function offsetGet($translateKey)
    {
        return $this->query($translateKey, null);
    }
}
