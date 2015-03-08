<?php
/**
 * JSMin Filter
 *
 * @author Andres Gutierrez <andres@phalconphp.com>
 * @author Eduar Carvajal <eduar@phalconphp.com>
 * @author Wenzel Pünter <wenzel@phelix.me>
 * @version 1.2.6
 * @package Phalcon
*/
namespace Phalcon\Assets\Filters;

use \Phalcon\Assets\Exception;
use \JShrink\Minifier;

/**
 * Phalcon\Assets\Filters\Jsmin
 *
 * Deletes the characters which are insignificant to JavaScript. Comments will be removed. Tabs will be
 * replaced with spaces. Carriage returns will be replaced with linefeeds.
 * Most spaces and linefeeds will be removed.
 *
 * @see https://github.com/phalcon/cphalcon/blob/1.2.6/ext/assets/filters/jsmin.c
 */
class Jsmin implements \Phalcon\Assets\FilterInterface
{
    /**
     * Filters the content using JSMIN
     *
     * @param string $content
     * @return null|string
     * @throws Exception
     */
    public function filter($content)
    {
        if (is_string($content) === false) {
            throw new Exception('Script must be a string');
        }

        if (empty($content) === true) {
            return '';
        }
        
        require_once(__DIR__.'/JShrink/src/JShrink/Minifier.php');

        try {
            return PHP_EOL.Minifier::minify($content);
        } catch (\Exception $e) {
            $msg = $e->getMessage();
            if (strpos($msg, 'Unclosed multiline comment at position') === 0) {
                throw new Exception('Unterminated comment.');
            } elseif (strpos($msg, 'Unclosed string at position') === 0) {
                throw new Exception('Unterminated string literal.');
            } elseif (strpos($msg, 'Unclosed regex pattern at position') === 0) {
                throw new Exception('Unterminated Regular Expression literal.');
            } else {
                throw new Exception($e->getMessage());
            }
        }
    }
}
