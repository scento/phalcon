<?php
/**
 * Syslog Formatter
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
 * Phalcon\Logger\Formatter\Syslog
 *
 * Prepares a message to be used in a Syslog backend
 *
 * @see https://github.com/phalcon/cphalcon/blob/1.2.6/ext/logger/formatter/syslog.c
 */
class Syslog extends Formatter implements FormatterInterface
{
    /**
     * Applies a format to a message before sent it to the internal log
     *
     * @param string $message
     * @param int $type
     * @param int $timestamp
     * @return array
     * @throws Exception
     */
    public function format($message, $type, $timestamp)
    {
        if (is_string($message) === false ||
            is_int($type) === false ||
            is_int($timestamp) === false) {
            throw new Exception('Invalid parameter type.');
        }

        return array($type, $message);
    }
}
