<?php
/**
 * FirePHP Formatter
 *
 * @author Andres Gutierrez <andres@phalconphp.com>
 * @author Eduar Carvajal <eduar@phalconphp.com>
 * @author Wenzel Pünter <wenzel@phelix.me>
 * @version 1.2.6
 * @package Phalcon
*/
namespace Phalcon\Logger\Formatter;

use \Phalcon\Logger\Formatter;
use \Phalcon\Logger\FormatterInterface;
use \Phalcon\Logger\Exception;

/**
 * Phalcon\Logger\Formatter\Firephp
 *
 * Formats messages so that they can be sent to FirePHP
 *
 * @see https://github.com/phalcon/cphalcon/blob/1.2.6/ext/logger/formatter/firephp.c
 */
class Firephp extends Formatter implements FormatterInterface
{
    /**
     * Show Backtrace
     *
     * @var boolean
     * @access protected
    */
    protected $_showBacktrace = true;

    /**
     * Returns the string meaning of a logger constant
     *
     * @param int $type
     * @return string
     */
    public function getTypeString($type)
    {
        $lut = array('ERROR', 'ERROR', 'WARN', 'ERROR', 'WARN', 'INFO', 'INFO', 'LOG', 'INFO', 'LOG');

        $type = (int)$type;
        if ($type > 0 && $type < 10) {
            return $lut[$type];
        }

        return 'CUSTOM';
    }

    /**
     * Get _showBacktrace member variable
     *
     * @return boolean
    */
    public function getShowBacktrace()
    {
        return $this->_showBacktrace;
    }

    /**
     * Set _showBacktrace member variable
     *
     * @param boolean $show
     * @throws Exception
    */
    public function setShowBacktrace($show)
    {
        if (is_bool($show) === false) {
            throw new Exception('Invalid parameter type.');
        }

        $this->_showBacktrace = $show;
    }

    /**
     * Applies a format to a message before sending it to the log
     *
     * @param string $message
     * @param int $type
     * @param int $timestamp
     * @return string
     * @throws Exception
     */
    public function format($message, $type, $timestamp)
    {
        if (is_string($message) === false ||
            is_int($type) === false ||
            is_int($timestamp) === false) {
            throw new Exception('Invalid parameter type.');
        }

        if ($this->_showBacktrace === true) {
            $backtrace = debug_backtrace(\DEBUG_BACKTRACE_IGNORE_ARGS);
        }

        if (is_array($backtrace) === true) {
            foreach ($backtrace as $key => $value) {
                if (is_array($value) === true) {
                    if (isset($value['file']) === false) {
                        /**
                         * Here we need to skip the latest calls into Phalcon's core.
                         * Calls to Zend internal functions will have "file" index not set.
                         * We remove these entries from the array.
                         */
                        unset($backtrace[$key]);
                    } else {
                        /*
                         * Remove args and object indices. They usually give
                         * too much information; this is not suitable to send
                         * in the HTTP headers
                         */
                        unset($value['args']);
                        unset($value['object']);
                    }
                }
            }

            /*
             * Now we need to renumber the hash table because we removed several
             * heading elements. If we don't do this, json_encode() will convert
             * this array to a JavaScript object which is an unwanted side effect
             */
            $backtrace = array_values($backtrace);
        }

        /**
         * The result will looks like this:
         *
         * array(
         *     array('Type' => 'message type', 'Label' => 'message'),
         *     array('backtrace' => array(backtrace goes here)
         * )
         */
        $meta = array('Type' => $this->getTypeString($type), 'Label' => $message);

        if (is_array($backtrace) === true) {
            foreach ($backtrace as $trace) {
                if (isset($meta['File']) === false &&
                    array_key_exists('file', $trace) === true) {
                    $meta['File'] = $trace['file'];
                }

                if (isset($meta['Line']) === false &&
                    array_key_exists('line', $trace) === true) {
                    $meta['Line'] = $trace['line'];
                }

                if (isset($meta['Line']) === true &&
                    isset($meta['File']) === true) {
                    break;
                }
            }
        }

        $body = array();
        if ($this->_showBacktrace === true) {
            $body['backtrace'] = $backtrace;
        }

        $payload = array($meta, $body);
        //@note no result check
        $encoded = json_encode($payload);
        unset($payload);

        return (string)strlen($encoded).'|'.$encoded.'|';
    }
}
