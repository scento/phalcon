<?php
/**
 * PHP
 *
 * @author Andres Gutierrez <andres@phalconphp.com>
 * @author Eduar Carvajal <eduar@phalconphp.com>
 * @author Wenzel Pünter <wenzel@phelix.me>
 * @version 1.2.6
 * @package Phalcon
*/
namespace Phalcon\Mvc\View\Engine;

use \Phalcon\Mvc\View\Engine;
use \Phalcon\Mvc\View\EngineInterface;
use \Phalcon\Mvc\View\Exception;
use \Phalcon\DI\InjectionAwareInterface;
use \Phalcon\Events\EventsAwareInterface;

/**
 * Phalcon\Mvc\View\Engine\Php
 *
 * Adapter to use PHP itself as templating engine
 *
 * @see https://github.com/phalcon/cphalcon/blob/1.2.6/ext/mvc/view/engine/php.c
 */
class Php extends Engine implements InjectionAwareInterface, EventsAwareInterface, EngineInterface
{
    /**
     * Renders a view using the template engine
     *
     * @param string $path
     * @param array $params
     * @param boolean|null $mustClean
     * @throws Exception
     */
    public function render($path, $params, $mustClean = null)
    {
        if (is_string($path) === false ||
            (is_array($params) === false && is_null($params) === false)) {
            throw new Exception('Invalid parameter type.');
        }

        if (is_null($mustClean) === true) {
            $mustClean = false;
        } elseif (is_bool($mustClean) === false) {
            throw new Exception('Invalid parameter type.');
        }

        if ($mustClean === true) {
            ob_clean();
        }

        //Create the variables
        if (is_array($params) === true) {
            foreach ($params as $key => $value) {
                $$key = $value;
            }
        }

        //Require the file
        require($path);

        if ($mustClean === true) {
            $this->_view->setContent(ob_get_contents());
        }
    }
}
