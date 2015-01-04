<?php
/**
 * Dispatcher
 *
 * @author Andres Gutierrez <andres@phalconphp.com>
 * @author Eduar Carvajal <eduar@phalconphp.com>
 * @author Wenzel Pünter <wenzel@phelix.me>
 * @version 1.2.6
 * @package Phalcon
*/
namespace Phalcon\Mvc;

use \Phalcon\Dispatcher as DefaultDispatcher;
use \Phalcon\Events\EventsAwareInterface;
use \Phalcon\DI\InjectionAwareInterface;
use \Phalcon\DispatcherInterface as PhalconDispatcherInterface;
use \Phalcon\Mvc\DispatcherInterface;
use \Phalcon\Mvc\Dispatcher\Exception;

/**
 * Phalcon\Mvc\Dispatcher
 *
 * Dispatching is the process of taking the request object, extracting the module name,
 * controller name, action name, and optional parameters contained in it, and then
 * instantiating a controller and calling an action of that controller.
 *
 *<code>
 *
 *  $di = new Phalcon\DI();
 *
 *  $dispatcher = new Phalcon\Mvc\Dispatcher();
 *
 *  $dispatcher->setDI($di);
 *
 *  $dispatcher->setControllerName('posts');
 *  $dispatcher->setActionName('index');
 *  $dispatcher->setParams(array());
 *
 *  $controller = $dispatcher->dispatch();
 *
 *</code>
 *
 * @see https://github.com/phalcon/cphalcon/blob/1.2.6/ext/mvc/dispatcher.c
 */
class Dispatcher extends DefaultDispatcher implements EventsAwareInterface, InjectionAwareInterface, DispatcherInterface, PhalconDispatcherInterface
{
    /**
     * Exception: No Dependency Injector
     *
     * @var int
    */
    const EXCEPTION_NO_DI = 0;

    /**
     * Exception: Cyclic Routing
     *
     * @var int
    */
    const EXCEPTION_CYCLIC_ROUTING = 1;

    /**
     * Exception: Handler Not Found
     *
     * @var int
    */
    const EXCEPTION_HANDLER_NOT_FOUND = 2;

    /**
     * Exception: Invalid Handler
     *
     * @var int
    */
    const EXCEPTION_INVALID_HANDLER = 3;

    /**
     * Exception: Invalid Parameters
     *
     * @var int
    */
    const EXCEPTION_INVALID_PARAMS = 4;

    /**
     * Exception: Action Not Found
     *
     * @var int
    */
    const EXCEPTION_ACTION_NOT_FOUND = 5;

    /**
     * Handler Suffix
     *
     * @var string
     * @access protected
    */
    protected $_handlerSuffix = 'Controller';

    /**
     * Default Handler
     *
     * @var string
     * @access protected
    */
    protected $_defaultHandler = 'index';

    /**
     * Default Action
     *
     * @var string
     * @access protected
    */
    protected $_defaultAction = 'index';

    /**
     * Sets the default controller suffix
     *
     * @param string $controllerSuffix
     * @throws Exception
     */
    public function setControllerSuffix($controllerSuffix)
    {
        if (is_string($controllerSuffix) === false) {
            throw new Exception('Invalid parameter type.');
        }

        $this->_handlerSuffix = $controllerSuffix;
    }

    /**
     * Sets the default controller name
     *
     * @param string $controllerName
     * @throws Exception
     */
    public function setDefaultController($controllerName)
    {
        if (is_string($controllerName) === false) {
            throw new Exception('Invalid parameter type.');
        }

        $this->_defaultHandler = $controllerName;
    }

    /**
     * Sets the controller name to be dispatched
     *
     * @param string $controllerName
     * @param bool|null $isExact
     * @throws Exception
     */
    public function setControllerName($controllerName, $isExact = null)
    {
        if (is_bool($isExact) === true && $isExact === true) {
            $this->_handlerName = '\\'.$controllerName;
            $this->_isExactHandler = true;
        } else {
            $this->_handlerName = $controllerName;
            $this->_isExactHandler = false;
        }
    }

    /**
     * Gets last dispatched controller name
     *
     * @return string|null
     */
    public function getControllerName()
    {
        if ($this->_isExactHandler === false) {
            return $this->_handlerName;
        }

        if (is_string($this->_handlerName) === true && strlen($this->_handlerName) > 1) {
            return substr($this->_handlerName, 1);
        }
    }

    /**
     * Throws an internal exception
     *
     * @param string $message
     * @param int|null $exceptionCode
     * @return boolean|null
     * @throws Exception
     */
    protected function _throwDispatchException($message, $exceptionCode = null)
    {
        /* Type check */
        if (is_string($message) === false) {
            throw new Exception('Invalid parameter type.');
        }

        if (is_null($exceptionCode) === true) {
            $exceptionCode = 0;
        } elseif (is_int($exceptionCode) === false) {
            throw new Exception('Invalid parameter type.');
        }

        /* Get dependency injector */
        if (is_object($this->_dependencyInjector) === false) {
            throw new Exception("A dependency injection container is required to access the 'response' service", 0);
        }

        $response = $this->_dependencyInjector->getShared('response');

        //Dispatcher exceptions automatically send 404 status
        $response->setStatusCode(404, 'Not Found');

        //Create the real exception
        $exception = new Exception($message, $exceptionCode);
        if (is_object($this->_eventsManager) === true) {
            if ($this->_eventsManager->fire('dispatch:beforeException', $this, $exception) === false) {
                return false;
            }
        }

        //Throw the exception if it wasn't handled
        throw $exception;
    }

    /**
     * Handles a user exception
     *
     * @param \Exception $exception
     * @throws Exception
     * @return boolean|null
     */
    protected function _handleException($exception)
    {
        if (is_object($exception) === false ||
            $exception instanceof \Exception === false) {
            throw new Exception('Invalid parameter type.');
        }

        if (is_object($this->_eventsManager) === true) {
            if ($this->_eventsManager->fire('dispatch:beforeException', $this, $exception) === false) {
                return false;
            }
        }
    }

    /**
     * Possible controller class name that will be located to dispatch the request
     *
     * @return string|null
     */
    public function getControllerClass()
    {
        return $this->getHandlerName();
    }

    /**
     * Returns the lastest dispatched controller
     *
     * @return \Phalcon\Mvc\ControllerInterface|null
     */
    public function getLastController()
    {
        return $this->_lastHandler;
    }

    /**
     * Returns the active controller in the dispatcher
     *
     * @return \Phalcon\Mvc\ControllerInterface|null
     */
    public function getActiveController()
    {
        return $this->_activeHandler;
    }
}
