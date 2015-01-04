<?php
/**
 * Simple
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
use \Phalcon\Mvc\View\Exception;
use \Phalcon\Mvc\View\Engine\Php;

/**
 * Phalcon\Mvc\View\Simple
 *
 * This component allows to render views without hicherquical levels
 *
 *<code>
 * $view = new Phalcon\Mvc\View\Simple();
 * echo $view->render('templates/my-view', array('content' => $html));
 *</code>
 *
 * @see https://github.com/phalcon/cphalcon/blob/1.2.6/ext/mvc/view/simple.c
 */
class Simple extends Injectable implements EventsAwareInterface, InjectionAwareInterface
{
    /**
     * Options
     *
     * @var null|array
     * @access protected
    */
    protected $_options;

    /**
     * Views Directory
     *
     * @var null|string
     * @access protected
    */
    protected $_viewsDir;

    /**
     * Partials Directory
     *
     * @var null|string
     * @access protected
    */
    protected $_partialsDir;

    /**
     * View Parameters
     *
     * @var null|array
     * @access protected
    */
    protected $_viewParams;

    /**
     * Engines
     *
     * @var boolean
     * @access protected
    */
    protected $_engines = false;

    /**
     * Registered Engines
     *
     * @var null|array
     * @access protected
    */
    protected $_registeredEngines;

    /**
     * Active Render Path
     *
     * @var null|string
     * @access protected
    */
    protected $_activeRenderPath;

    /**
     * Content
     *
     * @var null|string
     * @access protected
    */
    protected $_content;

    /**
     * Cache
     *
     * @var boolean|\Phalcon\Cache\BackendInterface
     * @access protected
    */
    protected $_cache = false;

    /**
     * Cache Options
     *
     * @var null|array
     * @access protected
    */
    protected $_cacheOptions;

    /**
     * \Phalcon\Mvc\View constructor
     *
     * @param array|null $options
     */
    public function __construct($options = null)
    {
        if (is_array($options) === true) {
            $this->_options = $options;
        }
    }

    /**
     * Sets views directory. Depending of your platform, always add a trailing slash or backslash
     *
     * @param string $viewsDir
     * @throws Exception
     */
    public function setViewsDir($viewsDir)
    {
        if (is_string($viewsDir) === false) {
            throw new Exception('Invalid parameter type.');
        }

        $this->_viewsDir = $viewsDir;
    }

    /**
     * Gets views directory
     *
     * @return string|null
     */
    public function getViewsDir()
    {
        return $this->_viewsDir;
    }

    /**
     * Register templating engines
     *
     *<code>
     *$this->view->registerEngines(array(
     *  ".phtml" => "Phalcon\Mvc\View\Engine\Php",
     *  ".volt" => "Phalcon\Mvc\View\Engine\Volt",
     *  ".mhtml" => "MyCustomEngine"
     *));
     *</code>
     *
     * @param array $engines
     * @throws Exception
     */
    public function registerEngines($engines)
    {
        if (is_array($engines) === false) {
            throw new Exception('Engines to register must be an array');
        }

        $this->_registeredEngines = $engines;
    }

    /**
     * Loads registered template engines, if none is registered it will use \Phalcon\Mvc\View\Engine\Php
     *
     * @return array
     * @throws Exception
     */
    protected function _loadTemplateEngines()
    {
        //If engines aren't initialized 'engines' is false
        if ($this->_engines === false) {
            $engines = array();

            if (is_array($this->_registeredEngines) === false) {
                //We use Phalcon\Mvc\View\Engine\Php as default
                //@note $this->_dependencyInjector might be null
                $php_engine = new Php($this, $this->_dependencyInjector);

                //Use .phtml as extension for the PHP engine
                $engines['.phtml'] = $php_engine;
            } else {
                if (is_object($this->_dependencyInjector) === false) {
                    throw new Exception('A dependency injector container is required to obtain the application services');
                }

                //Arguments for instantiated engines
                $arguments = array($this, $this->_dependencyInjector);
                foreach ($this->_registeredEngines as $extension => $engine_service) {
                    if (is_object($engine_service) === true) {
                        //Engine can be a closure
                        if ($engine_service instanceof Closure === true) {
                            $engine_object = call_user_func_array($engine_service, $arguments);
                        } else {
                            $engine_object = $engine_service;
                        }
                    } else {
                        //Engine can be a string representing a service in the DI
                        if (is_string($engine_service) === true) {
                            $engine_object = $this->_dependencyInjector->getShared($engine_service, $arguments);
                        } else {
                            throw new Exception('Invalid template engine registration for extension: '.$extension);
                        }
                    }

                    $engines[$extension] = $engine_object;
                }
            }
            $this->_engines = true;
            $this->_registeredEngines = $engines;
        }

        //@note fixed wrong code
        return $this->_registeredEngines;
    }

    /**
     * Tries to render the view with every engine registered in the component
     *
     * @param string $path
     * @param array $params
     * @throws Exception
     */
    protected function _internalRender($path, $params)
    {
        if (is_string($path) === false ||
            is_array($params) === false) {
            throw new Exception('Invalid parameter type.');
        }

        //Call beforeRender if there is an events manager
        if (is_object($this->_eventsManager) === true) {
            $this->_activeRenderPath = $path;

            if ($this->_eventsManager->fire('view:beforeRender', $this) === false) {
                return null;
            }
        }

        //Load the template engines
        $engines = $this->_loadTemplateEngines();

        $not_exists = true;

        //Views are rendered in each engine
        foreach ($engines as $extension => $engine) {
            $view_engine_path = $this->_viewsDir.$extension;

            if (file_exists($view_engine_path) === true) {
                //Call beforeRenderView if there is an events manager aviailable
                if (is_object($this->_eventsManager) === true) {
                    if ($this->_eventsManager->fire('view:beforeRenderView', $this, $view_engine_path) === false) {
                        continue;
                    }
                }

                $engine->render($view_engine_path, $params, true);
                $not_exists = false;

                //Call afterRenderView if there is an events manager available
                if (is_object($this->_eventsManager) === true) {
                    $this->_eventsManager->fire('view:afterRenderView', $this);
                }

                break;
            }
        }

        //Always throw an exception if the view does not exist
        if ($not_exists === true) {
            throw new Exception("View '".$this->_viewsDir."' was not found in the views directory");
        }

        //Call afterRender event
        if (is_object($this->_eventsManager) === true) {
            $this->_eventsManager->fire('view:afterRender', $this);
        }
    }

    /**
     * Renders a view
     *
     * @param string $path
     * @param array|null $params
     * @return string
     * @throws Exception
     */
    public function render($path, $params = null)
    {
        if (is_string($path) === false) {
            throw new Exception('Invalid parameter type.');
        }

        if (is_array($params) === false &&
            is_null($params) === false) {
            throw new Exception('Invalid parameter type.');
        }

        //Create/Get a cache
        $cache = $this->getCache();

        if (is_object($cache) === true) {
            //Check if the cache is started, the first time a cache is started we start
            //the cache
            if ($cache->isStarted() === false) {
                $key = null;
                $lifetime = null;

                if (is_array($this->_cacheOptions) === true) {
                    if (isset($this->_cacheOptions['key']) === true) {
                        $key = $this->_cacheOptions['key'];
                    }

                    if (isset($this->_cacheOptions['lifetime']) === true) {
                        $lifetime = $this->_cacheOptions['lifetime'];
                    }
                }

                //If a cache key is not set we create one using a md5
                if (is_null($key) === true) {
                    $key = md5($path);
                }

                //We start the cache using the key set
                $content = $cache->start($key, $lifetime);
                if (is_null($content) === false) {
                    $this->_content = $content;
                    return $content;
                }
            }
        }

        ob_start();

        //Merge parameters
        if (is_array($params) === true) {
            if (is_array($this->_viewParams) === true) {
                $params = array_merge($this->_viewParams, $params);
            }
        } else {
            $params = $this->_viewParams;
        }

        //internalRender is also reused by partials
        $this->_internalRender($path, $params);

        //Store the data
        if (is_object($cache) === true) {
            if ($cache->isStarted() === true) {
                if ($cache->isFresh() === true) {
                    $cache->save();
                } else {
                    $cache->stop();
                }
            } else {
                $cache->stop();
            }
        }

        ob_end_clean();

        return $this->_content;
    }

    /**
     * Renders a partial view
     *
     * <code>
     *  //Show a partial inside another view
     *  $this->partial('shared/footer');
     * </code>
     *
     * <code>
     *  //Show a partial inside another view with parameters
     *  $this->partial('shared/footer', array('content' => $html));
     * </code>
     *
     * @param string $partialPath
     * @param array|null $params
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

        //Start output buffering
        ob_start();

        if (is_array($params) === true) {
            $view_params = $this->_viewParams;
            //Merge or assign the new params as parameters
            if (is_array($view_params) === true) {
                $params = array_merge($view_params, $params);
            }
        }

        //Call engine renderer, this check in every registered engine for the partial
        $this->_internalRender($partialPath, $params);

        //Now we need to restore the original view parameters
        if (is_null($view_params) === false) {
            $this->_viewParams = $view_params;
        }

        ob_end_clean();

        //Output of content to the parent view
        echo $this->_content;
    }

    /**
     * Sets the cache options
     *
     * @param array $options
     * @return \Phalcon\Mvc\View\Simple
     * @throws Exception
     */
    public function setCacheOptions($options)
    {
        if (is_array($options) === false) {
            throw new Exception('Invalid parameter type.');
        }

        $this->_cacheOptions = $options;

        return $this;
    }

    /**
     * Returns the cache options
     *
     * @return array|null
     */
    public function getCacheOptions()
    {
        return $this->_cacheOptions;
    }

    /**
     * Create a \Phalcon\Cache based on the internal cache options
     *
     * @return \Phalcon\Cache\BackendInterface
     * @throws Exception
     */
    protected function _createCache()
    {
        if (is_object($this->_dependencyInjector) === false) {
            throw new Exception('A dependency injector container is required to obtain the view cache services');
        }

        $cache_service = 'viewCache';
        if (is_array($this->_cacheOptions) === true) {
            if (isset($this->_cacheOptions['service']) === true) {
                $cache_service = $this->_cacheOptions['service'];
            }
        }

        //The injected service must be an object
        $view_cache = $this->_dependencyInjector->getShared($cache_service);
        if (is_object($view_cache) === false) {
            //@note no interface validation
            throw new Exception('The injected caching service is invalid');
        }

        return $view_cache;
    }

    /**
     * Returns the cache instance used to cache
     *
     * @return \Phalcon\Cache\BackendInterface|boolean
     */
    public function getCache()
    {
        if (isset($this->_cache) === true &&
            is_object($this->_cache) === false) {
            $this->_cache = $this->_createCache();
        }

        return $this->_cache;
    }

    /**
     * Cache the actual view render to certain level
     *
     *<code>
     *  $this->view->cache(array('key' => 'my-key', 'lifetime' => 86400));
     *</code>
     *
     * @param boolean|array|null $options
     * @return \Phalcon\Mvc\View\Simple
     * @throws Exception
     */
    public function cache($options = null)
    {
        if (is_null($options) === true) {
            $options = true;
        }

        if (is_array($options) === true) {
            $this->_cache = true;
            $this->_cacheOptions = $options;
        } elseif (is_bool($options) === true) {
            $this->_cache = true;
        } elseif (is_null($options) === true) {
            $this->_cache = false;
        } else {
            throw new Exception('Invalid parameter type.');
        }

        return $this;
    }

    /**
     * Adds parameters to views (alias of setVar)
     *
     *<code>
     *  $this->view->setParamToView('products', $products);
     *</code>
     *
     * @param string $key
     * @param mixed $value
     * @return \Phalcon\Mvc\View\Simple
     * @throws Exception
     */
    public function setParamToView($key, $value)
    {
        if (is_string($key) === false) {
            throw new Exception('Invalid parameter type.');
        }

        if (is_array($this->_viewParams) === false) {
            $this->_viewParams = array();
        }

        $this->_viewParams[$key] = $value;

        return $this;
    }

    /**
     * Set all the render params
     *
     *<code>
     *  $this->view->setVars(array('products' => $products));
     *</code>
     *
     * @param array $params
     * @param boolean|null $merge
     * @return \Phalcon\Mvc\View\Simple
     * @throws Exception
     */
    public function setVars($params, $merge = null)
    {
        if (is_null($merge) === true) {
            $merge = true;
        } elseif (is_bool($merge) === false) {
            throw new Exception('Invalid parameter type.');
        }

        if (is_array($params) === false) {
            throw new Exception('The render parameters must be an array');
        }

        if ($merge === true) {
            if (is_array($this->_viewParams) === true) {
                $params = array_merge($this->_viewParams, $params);
            }

            $this->_viewParams = $params;
        } else {
            $this->_viewParams = $params;
        }

        return $this;
    }

    /**
     * Set a single view parameter
     *
     *<code>
     *  $this->view->setVar('products', $products);
     *</code>
     *
     * @param string $key
     * @param mixed $value
     * @return \Phalcon\Mvc\View\Simple
     * @throws Exception
     */
    public function setVar($key, $value)
    {
        if (is_string($key) === false) {
            throw new Exception('Invalid parameter type.');
        }

        if (is_array($this->_viewParams) === false) {
            $this->_viewParams = array();
        }

        $this->_viewParams[$key] = $value;

        return $this;
    }

    /**
     * Returns a parameter previously set in the view
     *
     * @param string $key
     * @return mixed
     * @throws Exception
     */
    public function getVar($key)
    {
        if (is_string($key) === false) {
            throw new Exception('Invalid parameter type.');
        }

        if (isset($this->_viewParams[$key]) === true) {
            return $this->_viewParams[$key];
        }

        return null;
    }

    /**
     * Returns parameters to views
     *
     * @return array|null
     */
    public function getParamsToView()
    {
        return $this->_viewParams;
    }

    /**
     * Externally sets the view content
     *
     *<code>
     *  $this->view->setContent("<h1>hello</h1>");
     *</code>
     *
     * @param string $content
     * @return \Phalcon\Mvc\View\Simple
     * @throws Exception
     */
    public function setContent($content)
    {
        if (is_string($content) === false) {
            throw new Exception('Content must be a string');
        }

        $this->_content = $content;

        return $this;
    }

    /**
     * Returns cached ouput from another view stage
     *
     * @return string|null
     */
    public function getContent()
    {
        return $this->_content;
    }

    /**
     * Returns the path of the view that is currently rendered
     *
     * @return string|null
     */
    public function getActiveRenderPath()
    {
        return $this->_activeRenderPath;
    }

    /**
     * Magic method to pass variables to the views
     *
     *<code>
     *  $this->view->products = $products;
     *</code>
     *
     * @param string $key
     * @param mixed $value
     * @throws Exception
     */
    public function __set($key, $value)
    {
        if (is_string($key) === false) {
            throw new Exception('Invalid parameter type.');
        }

        if (is_array($this->_viewParams) === false) {
            $this->_viewParams = array();
        }

        $this->_viewParams[$key] = $value;
    }

    /**
     * Magic method to retrieve a variable passed to the view
     *
     *<code>
     *  echo $this->view->products;
     *</code>
     *
     * @param string $key
     * @return mixed
     * @throws Exception
     */
    public function __get($key)
    {
        if (is_string($key) === false) {
            throw new Exception('Invalid parameter type.');
        }

        if (isset($this->_viewParams[$key]) === true) {
            return $this->_viewParams[$key];
        }

        return null;
    }
}
