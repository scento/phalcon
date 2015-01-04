<?php
/**
 * Engine
 *
 * @author Andres Gutierrez <andres@phalconphp.com>
 * @author Eduar Carvajal <eduar@phalconphp.com>
 * @author Wenzel Pünter <wenzel@phelix.me>
 * @version 1.2.6
 * @package Phalcon
*/
namespace Phalcon\Mvc\View;

use \Phalcon\DI\Injectable;
use \Phalcon\DI\InjectionAwareInterface;
use \Phalcon\Events\EventsAwareInterface;
use \Phalcon\Mvc\ViewInterface;
use \Phalcon\Mvc\View\Exception;

/**
 * Phalcon\Mvc\View\Engine
 *
 * All the template engine adapters must inherit this class. This provides
 * basic interfacing between the engine and the Phalcon\Mvc\View component.
 *
 * @see https://github.com/phalcon/cphalcon/blob/1.2.6/ext/mvc/view/engine.c
 */
abstract class Engine extends Injectable implements EventsAwareInterface, InjectionAwareInterface
{
    /**
     * View
     *
     * @var null|\Phalcon\Mvc\ViewInterface
     * @access protected
    */
    protected $_view;

    /**
     * \Phalcon\Mvc\View\Engine constructor
     *
     * @param \Phalcon\Mvc\ViewInterface $view
     * @param \Phalcon\DiInterface|null $dependencyInjector
     * @throws Exception
     */
    public function __construct($view, $dependencyInjector = null)
    {
        if (is_object($view) === false ||
            $view instanceof ViewInterface === false) {
            throw new Exception('Invalid parameter type.');
        }

        if (is_null($dependencyInjector) === false &&
            is_object($dependencyInjector) === false) {
            throw new Exception('Invalid parameter type.');
        }

        $this->_view = $view;
        $this->_dependencyInjector = $dependencyInjector;
    }

    /**
     * Returns cached ouput on another view stage
     *
     * @return array
     */
    public function getContent()
    {
        return $this->_view->getContent();
    }

    /**
     * Renders a partial inside another view
     *
     * @param string $partialPath
     * @param array|null $params
     * @return string
     * @throws Exception
     */
    public function partial($partialPath, $params = null)
    {
        if (is_string($partialPath) === false) {
            throw new Exception('Invalid parameter type.');
        }

        if (is_array($params) === false &&
            is_null($params) === false) {
            throw new Exception('Invalid parameter type.');
        }

        return $this->_view->partial($partialPath, $params);
    }

    /**
     * Returns the view component related to the adapter
     *
     * @return \Phalcon\Mvc\ViewInterface
     */
    public function getView()
    {
        return $this->_view;
    }
}
