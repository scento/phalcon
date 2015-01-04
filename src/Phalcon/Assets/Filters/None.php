<?php
/**
 * None Filter
 *
 * @author Andres Gutierrez <andres@phalconphp.com>
 * @author Eduar Carvajal <eduar@phalconphp.com>
 * @author Wenzel Pünter <wenzel@phelix.me>
 * @version 1.2.6
 * @package Phalcon
*/
namespace Phalcon\Assets\Filters;

/**
 * Phalcon\Assets\Filters\None
 *
 * Returns the content without make any modification to the original source
 *
 * @see https://github.com/phalcon/cphalcon/blob/1.2.6/ext/assets/filters/none.c
 */
class None
{
    /**
     * Returns the content without be touched
     *
     * @param string $content
     * @return $content
     */
    public function filter($content)
    {
        return $content;
    }
}
