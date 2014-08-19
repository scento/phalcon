<?php
/**
 * View
 *
 * @author Andres Gutierrez <andres@phalconphp.com>
 * @author Eduar Carvajal <eduar@phalconphp.com>
 * @author Wenzel Pünter <wenzel@phelix.me>
 * @version 1.2.6
 * @package Phalcon
*/
namespace Phalcon\Mvc;

use \Closure,
	\Phalcon\DI\Injectable,
	\Phalcon\DI\InjectionAwareInterface,
	\Phalcon\Events\EventsAwareInterface,
	\Phalcon\Mvc\ViewInterface,
	\Phalcon\Mvc\View\Exception,
	\Phalcon\Mvc\View\Engine\Php,
	\Phalcon\Cache\BackendInterface;

/**
 * Phalcon\Mvc\View
 *
 * Phalcon\Mvc\View is a class for working with the "view" portion of the model-view-controller pattern.
 * That is, it exists to help keep the view script separate from the model and controller scripts.
 * It provides a system of helpers, output filters, and variable escaping.
 *
 * <code>
 * //Setting views directory
 * $view = new Phalcon\Mvc\View();
 * $view->setViewsDir('app/views/');
 *
 * $view->start();
 * //Shows recent posts view (app/views/posts/recent.phtml)
 * $view->render('posts', 'recent');
 * $view->finish();
 *
 * //Printing views output
 * echo $view->getContent();
 * </code>
 * 
 * @see https://github.com/phalcon/cphalcon/blob/1.2.6/ext/mvc/view.c
 */
class View extends Injectable implements EventsAwareInterface, InjectionAwareInterface, ViewInterface
{
	/**
	 * Level: Main Layout
	 * 
	 * @var int
	*/
	const LEVEL_MAIN_LAYOUT = 5;

	/**
	 * Level: After Template
	 * 
	 * @var int
	*/
	const LEVEL_AFTER_TEMPLATE = 4;

	/**
	 * Level: Layout
	 * 
	 * @var int
	*/
	const LEVEL_LAYOUT = 3;

	/**
	 * Level: Before Template
	 * 
	 * @var int
	*/
	const LEVEL_BEFORE_TEMPLATE = 2;

	/**
	 * Level: Action View
	 * 
	 * @var int
	*/
	const LEVEL_ACTION_VIEW = 1;

	/**
	 * Level: No Render
	 * 
	 * @var int
	*/
	const LEVEL_NO_RENDER = 0;

	/**
	 * Options
	 * 
	 * @var null|array
	 * @access protected
	*/
	protected $_options;

	/**
	 * Base Path
	 * 
	 * @var string
	 * @access protected
	*/
	protected $_basePath = '';

	/**
	 * Content
	 * 
	 * @var string|null
	 * @access protected
	*/
	protected $_content = '';

	/**
	 * Render Level
	 * 
	 * @var int
	 * @access protected
	*/
	protected $_renderLevel = 5;

	/**
	 * Disabled Levels
	 * 
	 * @var null|array
	 * @access protected
	*/
	protected $_disabledLevels;

	/**
	 * View Params
	 * 
	 * @var null|array
	 * @access protected
	*/
	protected $_viewParams;

	/**
	 * Layout
	 * 
	 * @var null|string
	 * @access protected
	*/
	protected $_layout;

	/**
	 * Layouts Dir
	 * 
	 * @var string
	 * @access protected
	*/
	protected $_layoutsDir = '';

	/**
	 * Partials Dir
	 * 
	 * @var string
	 * @access protected
	*/
	protected $_partialsDir = '';

	/**
	 * Views Dir
	 * 
	 * @var null|string
	 * @access protected
	*/
	protected $_viewsDir;

	/**
	 * Templates Before
	 * 
	 * @var null|array
	 * @access protected
	*/
	protected $_templatesBefore;

	/**
	 * Templates After
	 * 
	 * @var null|array
	 * @access protected
	*/
	protected $_templatesAfter;

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
	 * Main View
	 * 
	 * @var string
	 * @access protected
	*/
	protected $_mainView = 'index';

	/**
	 * Controller Name
	 * 
	 * @var null|string
	 * @access protected
	*/
	protected $_controllerName;

	/**
	 * Action Name
	 * 
	 * @var null|string
	 * @access protected
	*/
	protected $_actionName;

	/**
	 * Params
	 * 
	 * @var null|string
	 * @access protected
	*/
	protected $_params;

	/**
	 * Pick View
	 * 
	 * @var null|array
	 * @access protected
	*/
	protected $_pickView;

	/**
	 * Cache
	 * 
	 * @var null|\Phalcon\Cache\BackendInterface
	 * @access protected
	*/
	protected $_cache;

	/**
	 * Cache Level
	 * 
	 * @var int
	 * @access protected
	*/
	protected $_cacheLevel = 0;

	/**
	 * Active Render Path
	 * 
	 * @var null|string
	 * @access protected
	*/
	protected $_activeRenderPath;

	/**
	 * Dsiabeld
	 * 
	 * @var boolean
	 * @access protected
	*/
	protected $_disabled = false;

	/**
	 * \Phalcon\Mvc\View constructor
	 *
	 * @param array|null $options
	 */
	public function __construct($options = null)
	{
		if(is_array($options) === true) {
			$this->_options = $options;
		}
	}

	/**
	 * Sets views directory. Depending of your platform, always add a trailing slash or backslash
	 *
	 * @param string $viewsDir
	 * @return \Phalcon\Mvc\View
	 * @throws Exception
	 */
	public function setViewsDir($viewsDir)
	{
		if(is_string($viewsDir) === false) {
			throw new Exception('The views directory must be a string');
		}

		$this->_viewsDir = $viewsDir;

		return $this;
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
	 * Sets the layouts sub-directory. Must be a directory under the views directory. Depending of your platform, always add a trailing slash or backslash
	 *
	 *<code>
	 * $view->setLayoutsDir('../common/layouts/');
	 *</code>
	 *
	 * @param string $layoutsDir
	 * @return \Phalcon\Mvc\View
	 * @throws Exception
	 */
	public function setLayoutsDir($layoutsDir)
	{
		if(is_string($layoutsDir) === false) {
			throw new Exception('Invalid parameter type.');
		}

		$this->_layoutsDir = $layoutsDir;

		return $this;
	}

	/**
	 * Gets the current layouts sub-directory
	 *
	 * @return string
	 */
	public function getLayoutsDir()
	{
		return $this->_layoutsDir;
	}

	/**
	 * Sets a partials sub-directory. Must be a directory under the views directory. Depending of your platform, always add a trailing slash or backslash
	 *
	 *<code>
	 * $view->setPartialsDir('../common/partials/');
	 *</code>
	 *
	 * @param string $partialsDir
	 * @return \Phalcon\Mvc\View
	 * @throws Exception
	 */
	public function setPartialsDir($partialsDir)
	{
		if(is_string($partialsDir) === false) {
			throw new Exception('Invalid parameter type.');
		}

		$this->_partialsDir = $partialsDir;

		return $this;
	}

	/**
	 * Gets the current partials sub-directory
	 *
	 * @return string
	 */
	public function getPartialsDir()
	{
		return $this->_partialsDir;
	}

	/**
	 * Sets base path. Depending of your platform, always add a trailing slash or backslash
	 *
	 * <code>
	 * 	$view->setBasePath(__DIR__ . '/');
	 * </code>
	 *
	 * @param string $basePath
	 * @return \Phalcon\Mvc\View
	 */
	public function setBasePath($basePath)
	{
		if(is_string($basePath) === false) {
			throw new Exception('Invalid parameter type.');
		}

		$this->_basePath = $basePath;

		return $this;
	}

	/**
	 * Sets the render level for the view
	 *
	 * <code>
	 * 	//Render the view related to the controller only
	 * 	$this->view->setRenderLevel(View::LEVEL_LAYOUT);
	 * </code>
	 *
	 * @param int $level
	 * @return \Phalcon\Mvc\View
	 * @throws Exception
	 */
	public function setRenderLevel($level)
	{
		if(is_int($level) === false) {
			throw new Exception('Invalid parameter type.');
		}

		$this->_renderLevel = $level;

		return $this;
	}

	/**
	 * Disables a specific level of rendering
	 *
	 *<code>
	 * //Render all levels except ACTION level
	 * $this->view->disableLevel(View::LEVEL_ACTION_VIEW);
	 *</code>
	 *
	 * @param int|array $level
	 * @return \Phalcon\Mvc\View
	 * @throws Exception
	 */
	public function disableLevel($level)
	{
		if(is_array($level) === true) {
			$this->_disabledLevels = $level;
		} elseif(is_int($level) === true) {
			if(is_array($this->_disabledLevels) === false) {
				$this->_disabledLevels = array();
			}

			$this->_disabledLevels[$level] = false;
		} else {
			throw new Exception('Invalid parameter type.');
		}

		return $this;
	}

	/**
	 * Sets default view name. Must be a file without extension in the views directory
	 *
	 * <code>
	 * 	//Renders as main view views-dir/base.phtml
	 * 	$this->view->setMainView('base');
	 * </code>
	 *
	 * @param string $viewPath
	 * @return \Phalcon\Mvc\View
	 * @throws Exception
	 */
	public function setMainView($viewPath)
	{
		if(is_string($viewPath) === false) {
			throw new Exception('Invalid parameter type.');
		}

		$this->_mainView = $viewPath;

		return $this;
	}

	/**
	 * Returns the name of the main view
	 *
	 * @return string
	 */
	public function getMainView()
	{
		return $this->_mainView;
	}

	/**
	 * Change the layout to be used instead of using the name of the latest controller name
	 *
	 * <code>
	 * 	$this->view->setLayout('main');
	 * </code>
	 *
	 * @param string $layout
	 * @return \Phalcon\Mvc\View
	 * @throws Exception
	 */
	public function setLayout($layout)
	{
		if(is_string($layout) === false) {
			throw new Exception('Invalid parameter type.');
		}

		$this->_layout = $layout;

		return $this;
	}

	/**
	 * Returns the name of the main view
	 *
	 * @return string|null
	 */
	public function getLayout()
	{
		return $this->_layout;
	}

	/**
	 * Appends template before controller layout
	 *
	 * @param string|array $templateBefore
	 * @return \Phalcon\Mvc\View
	 * @throws Exception
	 */
	public function setTemplateBefore($templateBefore)
	{
		if(is_string($templateBefore) === true) {
			$this->_templatesBefore = array($templateBefore);
		} elseif(is_array($templateBefore) === true) {
			$this->_templatesBefore = $templateBefore;
		} else {
			throw new Exception('Invalid parameter type.');
		}

		return $this;
	}

	/**
	 * Resets any template before layouts
	 *
	 * @return \Phalcon\Mvc\View
	 */
	public function cleanTemplateBefore()
	{
		$this->_templatesBefore = null;

		return $this;
	}

	/**
	 * Appends template after controller layout
	 *
	 * @param string|array $templateAfter
	 * @return \Phalcon\Mvc\View
	 * @throws Exception
	 */
	public function setTemplateAfter($templateAfter)
	{
		if(is_string($templateAfter) === true) {
			$this->_templatesAfter = array($templateAfter);
		} elseif(is_array($templateAfter) === true) {
			$this->_templatesAfter = $templateAfter;
		} else {
			throw new Exception('Invalid parameter type.');
		}

		return $this;
	}

	/**
	 * Resets any template before layouts
	 *
	 * @return \Phalcon\Mvc\View
	 */
	public function cleanTemplateAfter()
	{
		$this->_templatesAfter = null;

		return $this;
	}

	/**
	 * Adds parameters to views (alias of setVar)
	 *
	 *<code>
	 *	$this->view->setParamToView('products', $products);
	 *</code>
	 *
	 * @param string $key
	 * @param mixed $value
	 * @return \Phalcon\Mvc\View
	 * @throws Exception
	 */
	public function setParamToView($key, $value)
	{
		if(is_string($key) === false) {
			throw new Exception('Invalid parameter type.');
		}

		if(is_array($this->_viewParams) === false) {
			$this->_viewParams = array();
		}

		$this->_viewParams[$key] = $value;

		return $this;
	}

	/**
	 * Set all the render params
	 *
	 *<code>
	 *	$this->view->setVars(array('products' => $products));
	 *</code>
	 *
	 * @param array $params
	 * @param boolean|null $merge
	 * @return \Phalcon\Mvc\View
	 * @throws Exception
	 */
	public function setVars($params, $merge = null)
	{
		if(is_array($params) === false) {
			throw new Exception('The render parameters must be an array');
		}

		if(is_null($merge) === true) {
			$merge = true;
		} elseif(is_bool($merge) === false) {
			throw new Exception('Invalid parameter type.');
		}

		if($merge === true) {
			if(is_array($this->_viewParams) === true) {
				$this->_viewParams = array_merge($this->_viewParams, $params);
			} else {
				$this->_viewParams = $params;
			}
		} else {
			$this->_viewParams = $params;
		}

		return $this;
	}

	/**
	 * Set a single view parameter
	 *
	 *<code>
	 *	$this->view->setVar('products', $products);
	 *</code>
	 *
	 * @param string $key
	 * @param mixed $value
	 * @return \Phalcon\Mvc\View
	 * @throws Exception
	 */
	public function setVar($key, $value)
	{
		if(is_string($key) === false) {
			throw new Exception('Invalid parameter type.');
		}

		if(is_array($this->_viewParams) === false) {
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
		if(is_string($key) === false) {
			throw new Exception('Invalid parameter type.');
		}

		if(isset($this->_viewParams[$key]) === true) {
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
	 * Gets the name of the controller rendered
	 *
	 * @return string|null
	 */
	public function getControllerName()
	{
		return $this->_controllerName;
	}

	/**
	 * Gets the name of the action rendered
	 *
	 * @return string|null
	 */
	public function getActionName()
	{
		return $this->_actionName;
	}

	/**
	 * Gets extra parameters of the action rendered
	 *
	 * @return array|null
	 */
	public function getParams()
	{
		return $this->_params;
	}

	/**
	 * Starts rendering process enabling the output buffering
	 *
	 * @return \Phalcon\Mvc\View
	 */
	public function start()
	{
		$this->_content = null;
		ob_start();

		return $this;
	}

	/**
	 * Loads registered template engines, if none is registered it will use \Phalcon\Mvc\View\Engine\Php
	 *
	 * @return array
	 * @throws Exception
	 */
	protected function _loadTemplateEngines()
	{
		//If the engines aren't initialized 'engines' is false
		if($this->_engines === false) {
			$engines = array();

			if(is_array($this->_registeredEngines) === false) {
				//We use Phalcon\Mvc\View\Engine\Php as default
				$engines['.phtml'] = new Php($this, $this->_dependencyInjector);
			} else {
				if(is_object($this->_dependencyInjector) === false) {
					throw new Exception('A dependency injector container is required to obtain the application services');
				}

				$arguments = array($this, $this->_dependencyInjector);
				foreach($this->_registeredEngines as $extension => $engine_service) {
					if(is_object($engine_service) === true) {
						//Engine can be a closure
						if($engine_service instanceof Closure) {
							$engine_object = call_user_func_array($engine_service, $arguments);
						} else {
							$engine_object = $engine_service;
						}
					} elseif(is_string($engine_service) === true) {
						//Engine can be a string representing a service in the DI
						$engine_object = $this->_dependencyInjector->getShared($engine_service, $arguments);
					} else {
						throw new Exception('Invalid template engine registration for extension: '.$extension);
					}

					$engines[$extension] = $engine_object;
				}
			}

			$this->_registeredEngines = $engines;
			$this->_engines = true;
		}

		return $this->_registeredEngines;
	}

	/**
	 * Checks whether view exists on registered extensions and render it
	 *
	 * @param array $engines
	 * @param string $viewPath
	 * @param boolean $silence
	 * @param boolean $mustClean
	 * @param \Phalcon\Cache\BackendInterface|null $cache
	 * @throws Exception
	 */
	protected function _engineRender($engines, $viewPath, $silence, $mustClean, $cache = null)
	{
		if(is_array($engines) === false ||
			is_string($viewPath) === false ||
			is_bool($silence) === false ||
			is_bool($mustClean) === false) {
			throw new Exception('Invalid parameter type.');
		}

		$views_dir_path = $this->_basePath.$this->_viewsDir.$viewPath;
		$not_exists = true;

		if(is_object($cache) === true &&
			$cache instanceof BackendInterface === true) {
			if($this->_renderLevel >= $this->_cacheLevel) {
				//Check if the cache is started, the first time a cache is started we start the
				//cache
				if($cache->isStarted() === false) {
					$key = null;
					$lifetime = null;

					//Check if the user has defined different options to the default
					if(is_array($this->_options) === true) {
						if(isset($this->_options['cache']) === true) {
							if(is_array($this->_options['cache']) === true) {
								if(isset($this->_options['cache']['key']) === true) {
									$key = $this->_options['cache']['key'];
								}

								if(isset($this->_options['cache']['lifetime']) === true) {
									$lifetime = $this->_options['cache']['lifetime'];
								}
							}
						}
					}

					//If a cache key is not set we create one using a md5
					if(is_null($key) === true) {
						$key = md5($viewPath);
					}

					//We start the cache using the key set
					$cached_view = $cache->start($key, $lifetime);
					if(is_null($cached_view) === false) {
						$this->_content = $cached_view;
						return null;
					}
				}

				//This method only returns true if the cache has not expired
				if($cache->isFresh() === false) {
					return null;
				}
			}
		}

		//Views are rendered in each engine
		foreach($engines as $extension => $engine) {
			$view_engine_path = $views_dir_path.$extension;

			if(file_exists($view_engine_path) === true) {
				//Call beforeRenderView if there is a events manager available
				if(is_object($this->_eventsManager) === true) {
					$this->_activeRenderPath = $view_engine_path;
					if($this->_eventsManager->fire('view:beforeRenderView', $this, $view_engine_path) === false) {
						continue;
					}
				}

				$engine->render($view_engine_path, $this->_viewParams, $mustClean);

				$not_exists = false;
				//Call afterRenderView if there is a events manager available
				if(is_object($this->_eventsManager) === true) {
					$this->_eventsManager->fire('view:afterRenderView', $this);
				}

				break;
			}
		}

		if($not_exists === true) {
			//Notify about not found views
			if(is_object($this->_eventsManager) === true) {
				$this->_activeRenderPath = $view_engine_path;
				$this->_eventsManager->fire('view:notFoundView', $this);
			}

			if($silence === false) {
				throw new Exception("View '".$views_dir_path."' was not found in the views directory");
			}
		}
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
	 * @return \Phalcon\Mvc\View
	 * @throws Exception
	 */
	public function registerEngines($engines)
	{
		if(is_array($engines) === false) {
			throw new Exception('Engines to register must be an array');
		}

		$this->_registeredEngines = $engines;

		return $this;
	}

	/**
	 * Executes render process from dispatching data
	 *
	 *<code>
	 * //Shows recent posts view (app/views/posts/recent.phtml)
	 * $view->start()->render('posts', 'recent')->finish();
	 *</code>
	 *
	 * @param string $controllerName
	 * @param string $actionName
	 * @param array|null $params
	 * @return boolean|null
	 * @throws Execption
	 */
	public function render($controllerName, $actionName, $params = null)
	{
		if(is_string($controllerName) === false ||
			is_string($actionName) === false) {
			throw new Exception('Invalid parameter type.');
		}

		if(is_array($params) === false &&
			is_null($params) === false) {
			throw new Exception('Invalid parameter type.');
		}

		//If the view is disalbed, we simply update the buffer from any output produced in 
		//the controller
		if($this->_disabled !== false) {
			$this->_content = ob_get_contents();
			return false;
		}

		$this->_controllerName = $controllerName;
		$this->_actionName = $actionName;
		$this->_params = $params;

		//Check if there is a layouts directory set
		$layouts_dir = $this->_layoutsDir;
		if(isset($layouts_dir) === false) {
			$layouts_dir = 'layouts/';
		}

		//Check if the user has defined a custom layout
		$layout_name = $this->_layout;
		if(isset($layout_name) === false) {
			$layout_name = $controllerName;
		}

		//Load the template engines
		$engines = $this->_loadTemplateEngines();

		//Check if the user has picked a view different that the automatic
		if(is_null($this->_pickView) === true) {
			$render_view = $controllerName.'/'.$actionName;
		} else { //@note better check for array type here!
			//The 'picked' view is an array, where the first element is the controller and the
			//second the action
			$render_view = $this->_pickView[0];
			if(isset($this->_pickView[1]) === true) {
				$layout_name = $this->_pickView[1];
			}
		}

		$cache = null;

		//Start the cache if there is a cache level enabled
		if($this->_cacheLevel > 0) {
			$cache = $this->getCache();
		}

		//Call beforeRender if there is an events manager
		if(is_object($this->_eventsManager) === true) {
			if($this->_eventsManager->fire('view:beforeRender', $this) === false) {
				return false;
			}
		}

		//Get the current content in the buffer maybe some output from the controller
		$this->_content = ob_get_contents();

		//Disabled levels allow to avoid an specific level of rendering
		//Render level will tell us when to stop
		if($this->_renderLevel > self::LEVEL_NO_RENDER) {
			//Insert view related to action
			if($this->_renderLevel >= self::LEVEL_ACTION_VIEW && isset($this->_disabledLevels[self::LEVEL_ACTION_VIEW]) === false) {
				$this->_engineRender($engines, $render_view, true, true, $cache);
			}

			//Insert templates before layout
			if($this->_renderLevel >= self::LEVEL_BEFORE_TEMPLATE && isset($this->_disabledLevels[self::LEVEL_BEFORE_TEMPLATE]) === false &&
				is_array($this->_templatesBefore) === true) {
				//Templates before must be an array
				foreach($this->_templatesBefore as $templateBefore) {
					$this->_engineRender($engines, $layouts_dir.$templateBefore, false, true, $cache);
				}
			}

			//Insert controller layout
			if($this->_renderLevel >= self::LEVEL_LAYOUT) {
				if(isset($this->_disabledLevels[self::LEVEL_LAYOUT]) === false) {
					$this->_engineRender($engines, $layouts_dir.$layout_name, true, true, $cache);
				}
			}

			//Inserts templates after layout
			if($this->_renderLevel >= self::LEVEL_AFTER_TEMPLATE &&
				isset($this->_disabledLevels[self::LEVEL_AFTER_TEMPLATE]) === false) {
					//Templates after must be an array
				if(is_array($this->_templatesAfter) === true) {
					foreach($this->_templatesAfter as $template_after) {
						$this->_engineRender($engines, $layouts_dir.$template_after, false, true, $cache);
					}
				}
			}

			//Inserts main view
			if($this->_renderLevel >= self::LEVEL_MAIN_LAYOUT && isset($this->_disabledLevels[self::LEVEL_MAIN_LAYOUT]) === false) {
				$this->_engineRender($engines, $this->_mainView, true, true, $cache);
			}

			//Store the data in the cache
			if(is_object($cache) === true) {
				if($cache->isStarted() === true) {
					if($cache->isFresh() === true) {
						$cache->save();
					} else {
						$cache->stop();
					}
				} else {
					$cache->stop();
				}
			}
		}

		//Call afterRender event
		if(is_object($this->_eventsManager) === true) {
			$this->_eventsManager->fire('view:afterRender', $this);
		}

		return null;
	}

	/**
	 * Choose a different view to render instead of last-controller/last-action
	 *
	 * <code>
	 * class ProductsController extends \Phalcon\Mvc\Controller
	 * {
	 *
	 *    public function saveAction()
	 *    {
	 *
	 *         //Do some save stuff...
	 *
	 *         //Then show the list view
	 *         $this->view->pick("products/list");
	 *    }
	 * }
	 * </code>
	 *
	 * @param string|array $renderView
	 * @return \Phalcon\Mvc\View
	 * @throws Exception
	 */
	public function pick($renderView)
	{
		if(is_array($renderView) === true) {
			$pick_view = $renderView;
		} else {
			$layout = null;
			if(strpos($renderView, '/') !== false) {
				$parts = explode('/', $renderView);
				$layout = $parts[0];
			}

			$pick_view = $render_view;
			if(is_null($layout) === false) {
				$pick_view[] = $layout;
			}
		}

		$this->_pickView;

		return $this;
	}

	/**
	 * Renders a partial view
	 *
	 * <code>
	 * 	//Show a partial inside another view
	 * 	$this->partial('shared/footer');
	 * </code>
	 *
	 * <code>
	 * 	//Show a partial inside another view with parameters
	 * 	$this->partial('shared/footer', array('content' => $html));
	 * </code>
	 *
	 * @param string $partialPath
	 * @param array|null $params
	 * @throws Exception
	 */
	public function partial($partialPath, $params = null)
	{
		if(is_string($partialPath) === false) {
			throw new Exception('Invalid parameter type.');
		}

		//If the developer passes an array of variables we create a new virtual symbol table
		if(is_array($params) === true) {
			$view_params = $this->_viewParams;
			//Merge or assign the new params as parameters
			if(is_array($view_params) === true) {
				$params = array_merge($view_params, $params);
			}

			//Update the parameters with the name ones
			$this->_viewParams = $params;
		} elseif(is_null($params) === false) {
			throw new Exception('Invalid parameter type.');
		}

		//Partials are looked up under the partials directory
		$real_path = $this->_partialsDir.$partialPath;

		//We need to check if the engines are loaded first, this method could be called
		//outside of 'render'
		$engines = $this->_loadTemplateEngines();

		//Call engine render, this checks in every registered engine for the partial
		$this->_engineRender($engines, $real_path, false, false, false);

		//Now we need to restore the original view parameters
		if(isset($view_params) === true) {
			$this->_viewParams = $view_params;
		}
	}

	/**
	 * Perform the automatic rendering returning the output as a string
	 *
	 * <code>
	 * 	$template = $this->view->getRender('products', 'show', array('products' => $products));
	 * </code>
	 *
	 * @param string $controllerName
	 * @param string $actionName
	 * @param array|null $params
	 * @param mixed $configCallback
	 * @return string
	 * @throws Exception
	 */
	public function getRender($controllerName, $actionName, $params = null, $configCallback = null)
	{
		if(is_string($controllerName) === false ||
			is_string($actionName) === false) {
			throw new Exception('Invalid parameter type.');
		}

		if(is_array($params) === false &&
			is_null($params) === false) {
			throw new Exception('Invalid parameter type.');
		}

		//We must clone the current view to keep the old state
		$view = clone $this;

		//The component must be reset to its defaults
		$view->reset();

		//Set the render variables
		if(is_array($params) === true) {
			$view->setVars($params);
		}

		//Perform extra configuration over the cloned object
		if(is_object($configCallback) === true) {
			$status = call_user_func_array($configCallback, array($view));
		}

		//Start the output buffering
		$view->start();

		//Perform the render passing only the controller and action
		$view->render($controllerName, $actionName);

		//Stop the output buffering
		ob_end_clean();

		//Get the passed content
		return $view->getContent();
	}

	/**
	 * Finishes the render process by stopping the output buffering
	 *
	 * @return \Phalcon\Mvc\View
	 */
	public function finish()
	{
		ob_end_clean();

		return $this;
	}

	/**
	 * Create a \Phalcon\Cache based on the internal cache options
	 *
	 * @return \Phalcon\Cache\BackendInterface
	 * @throws Exception
	 */
	protected function _createCache()
	{
		if(is_object($this->_dependencyInjector) === false) {
			throw new Exception('A dependency injector container is required to obtain the view cache services');
		}

		$cache_service = 'viewCache';

		if(is_array($this->_options) === true) {
			if(isset($this->_options['cache']) === true) {
				$cache_options = $this->_options['cache'];
				if(is_array($cache_options) === true) {
					if(isset($cache_options['service']) === true) {
						$cache_service = $cache_options['service'];
					}
				}
			}
		}

		//@note $cache_service can be null
		//The injected service must be an object
		$view_cache = $this->_dependencyInjector->getShared($cache_service);
		if(is_object($view_cache) === false) {
			throw new Exception('The injected caching service is invalid');
		}

		return $view_cache;
	}

	/**
	 * Check if the component is currently caching the output content
	 *
	 * @return boolean
	 */
	public function isCaching()
	{
		return (0 < $this->_cacheLevel ? true : false);
	}

	/**
	 * Returns the cache instance used to cache
	 *
	 * @return \Phalcon\Cache\BackendInterface
	 */
	public function getCache()
	{
		if(isset($this->_cache) === true) {
			if(is_object($this->_cache) === false) {
				$this->_cache = $this->_createCache();
			}
		} else {
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
	 * @return \Phalcon\Mvc\View
	 * @throws Exception
	 */
	public function cache($options = null)
	{
		if(is_null($options) === true) {
			$options = true;
		}

		if(is_array($options) === true) {
			$view_options = $this->_options;
			if(is_array($view_options) === false) {
				$view_options = array();
			}

			//Get the default cache options
			if(isset($view_options['cache']) === true) {
				$cache_options = $view_options['cache'];
			} else {
				$cache_options = array();
			}

			foreach($options as $key => $value) {
				$cache_options[$key] = $value;
			}

			//Check if the user has defined a default cache level or uses 5 as default
			if(isset($cache_options['level']) === true) {
				$this->_cacheLevel = $cache_options['level'];
			} else {
				$this->_cacheLevel = 5;
			}

			$view_options['cache'] = $cache_options;
			$this->_options = $view_options;
		} elseif(is_bool($options) === true) {
			//If 'options' isn't an arary we enable the cache with the default options
			$this->_cacheLevel = ($options === true ? 5 : 0);
		} else {
			throw new Exception('Invalid parameter type.');
		}

		return $this;
	}

	/**
	 * Externally sets the view content
	 *
	 *<code>
	 *	$this->view->setContent("<h1>hello</h1>");
	 *</code>
	 *
	 * @param string $content
	 * @return \Phalcon\Mvc\View
	 * @throws Exception
	 */
	public function setContent($content)
	{
		if(is_string($content) === false) {
			throw new Exception('Content must be a string');
		}

		$this->_content = $content;

		return $this;
	}

	/**
	 * Returns cached output from another view stage
	 *
	 * @return string
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
	 * Disables the auto-rendering process
	 *
	 * @return \Phalcon\Mvc\View
	 */
	public function disable()
	{
		$this->_disabled = true;

		return $this;
	}

	/**
	 * Enables the auto-rendering process
	 *
	 * @return \Phalcon\Mvc\View
	 */
	public function enable()
	{
		$this->_disabled = false;
		return $this;
	}

	/**
	 * Resets the view component to its factory default values
	 *
	 * @return \Phalcon\Mvc\View
	 */
	public function reset()
	{
		$this->_disabled = false;
		$this->_engines = false;
		$this->_cache = null;
		$this->_renderLevel = 5;
		$this->_cacheLevel = 0;
		$this->_content = null;
		$this->_templatesBefore = null;
		$this->_templatesAfter = null;

		return $this;
	}

	/**
	 * Magic method to pass variables to the views
	 *
	 *<code>
	 *	$this->view->products = $products;
	 *</code>
	 *
	 * @param string $key
	 * @param mixed $value
	 * @throws Exception
	 */
	public function __set($key, $value)
	{
		if(is_string($key) === false) {
			throw new Exception('Invalid parameter type.');
		}

		if(is_array($this->_viewParams) === false) {
			$this->_viewParams = array();
		}

		$this->_viewParams[$key] = $value;
	}

	/**
	 * Magic method to retrieve a variable passed to the view
	 *
	 *<code>
	 *	echo $this->view->products;
	 *</code>
	 *
	 * @param string $key
	 * @return mixed
	 * @throws Exception
	 */
	public function __get($key)
	{
		if(is_string($key) === false) {
			throw new Exception('Invalid parameter type.');
		}

		if(isset($this->_viewParams[$key]) === true) {
			return $this->_viewParams[$key];
		}

		return null;
	}
}