<?php
/**
 * Formatter
 *
 * @author Andres Gutierrez <andres@phalconphp.com>
 * @author Eduar Carvajal <eduar@phalconphp.com>
 * @author Wenzel Pünter <wenzel@phelix.me>
 * @version 1.2.6
 * @package Phalcon
*/
namespace Phalcon\Logger;

/**
 * Phalcon\Logger\Formatter
 *
 * This is a base class for logger formatters
 *
 * @see https://github.com/phalcon/cphalcon/blob/1.2.6/ext/logger/formatter.c
 */
abstract class Formatter
{
    /**
     * Returns the string meaning of a logger constant
     *
     * @param integer $type
     * @return string
     */
    public function getTypeString($type)
    {
        $lut = array('EMERGENCY', 'CRITICAL', 'ALERT', 'ERROR',
            'WARNING', 'NOTICE', 'INFO', 'DEBUG', 'CUSTOM', 'SPECIAL');

        $type = (int)$type;
        if ($type >= 0 && $type < 10) {
            return $lut[$type];
        }

        return 'CUSTOM';
    }
}
