<?php
/**
 * Annotations XCache Adapter
 *
 * @author Andres Gutierrez <andres@phalconphp.com>
 * @author Eduar Carvajal <eduar@phalconphp.com>
 * @author Wenzel Pünter <wenzel@phelix.me>
 * @version 1.2.6
 * @package Phalcon
*/
namespace Phalcon\Annotations\Adapter;

use \Phalcon\Annotations\AdapterInterface;
use \Phalcon\Annotations\Adapter;
use \Phalcon\Annotations\Exception;
use \Phalcon\Annotations\Reflection;

/**
 * Phalcon\Annotations\Adapter\Xcache
 *
 * Stores the parsed annotations to XCache. This adapter is suitable for production
 *
 *<code>
 * $annotations = new \Phalcon\Annotations\Adapter\Xcache();
 *</code>
 *
 * @see https://github.com/phalcon/cphalcon/blob/1.2.6/ext/annotations/adapter/xcache.c
 */
class Xcache extends Adapter implements AdapterInterface
{
    /**
     * Reads parsed annotations from XCache
     *
     * @param string $key
     * @return \Phalcon\Annotations\Reflection|null
     * @throws Exception
     */
    public function read($key)
    {
        if (is_string($key) === false) {
            throw new Exception('Invalid parameter type.');
        }

        $serialized = xcache_get(strtolower('_PHAN'.$key));
        if (is_string($serialized) === true) {
            $unserialized = unserialize($serialized);
            return (is_object($unserialized) === true ? $unserialized : null);
        }
    }

    /**
     * Writes parsed annotations to XCache
     *
     * @param string $key
     * @param \Phalcon\Annotations\Reflection $data
     * @throws Exception
     */
    public function write($key, $data)
    {
        if (is_string($key) === false ||
            is_object($data) === false ||
            $data instanceof Reflection === false) {
            throw new Exception('Invalid parameter type.');
        }

        $serialized = serialize($data);
        xcache_set(strtolower('_PHAN'.$key), $serialized);
    }
}
