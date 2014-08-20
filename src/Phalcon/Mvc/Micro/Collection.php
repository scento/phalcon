<?php
/**
 * Micro Collection
 *
 * @author Andres Gutierrez <andres@phalconphp.com>
 * @author Eduar Carvajal <eduar@phalconphp.com>
 * @author Wenzel Pünter <wenzel@phelix.me>
 * @version 1.2.6
 * @package Phalcon
*/
namespace Phalcon\Mvc\Micro;

use \Phalcon\Mvc\Micro\CollectionInterface,
	\Phalcon\Mvc\Micro\Exception;

/**
 * Phalcon\Mvc\Micro\Collection
 *
 * Groups Micro-Mvc handlers as controllers
 *
 *<code>
 *
 * $app = new Phalcon\Mvc\Micro();
 *
 * $collection = new Phalcon\Mvc\Micro\Collection();
 *
 * $collection->setHandler(new PostsController());
 *
 * $collection->get('/posts/edit/{id}', 'edit');
 *
 * $app->mount($collection);
 *
 *</code>
 *
 * @see https://github.com/phalcon/cphalcon/blob/1.2.6/ext/mvc/micro/collection.c
 */
class Collection implements CollectionInterface
{
	/**
	 * Prefix
	 * 
	 * @var null|string
	 * @access protected
	*/
	protected $_prefix;

	/**
	 * Lazy
	 * 
	 * @var null|boolean
	 * @access protected
	*/
	protected $_lazy;

	/**
	 * Handler
	 * 
	 * @var null|mixed
	 * @access protected
	*/
	protected $_handler;

	/**
	 * Handlers
	 * 
	 * @var null|array
	 * @access protected
	*/
	protected $_handlers;
	
	/**
	 * Add a hander to the group
	 * 
	 * @param string|array $method
	 * @param string $routePattern
	 * @param mixed $handler
	*/
	private function addToMap($method, $routePattern, $handler)
	{
		if(is_array($this->_handlers) === false) {
			$this->_handlers = array();
		}

		$this->_handlers[] = array($method, $routePattern, $handler);
	}

	/**
	 * Sets a prefix for all routes added to the collection
	 *
	 * @param string $prefix
	 * @return \Phalcon\Mvc\Micro\CollectionInterface
	 * @throws Exception
	 */
	public function setPrefix($prefix)
	{
		if(is_string($prefix) === false) {
			throw new Exception('Invalid parameter type.');
		}

		$this->_prefix = $prefix;

		return $this;
	}

	/**
	 * Returns the collection prefix if any
	 *
	 * @return string|null
	 */
	public function getPrefix()
	{
		return $this->_prefix;
	}

	/**
	 * Returns the registered handlers
	 *
	 * @return array|null
	 */
	public function getHandlers()
	{
		return $this->_handlers;
	}

	/**
	 * Sets the main handler
	 *
	 * @param mixed $handler
	 * @param boolean|null $lazy
	 * @return \Phalcon\Mvc\Micro\CollectionInterface
	 * @throws Exception
	 */
	public function setHandler($handler, $lazy = null)
	{
		if(is_null($lazy) === true) {
			$lazy = false;
		} elseif(is_bool($lazy) === false) {
			throw new Exception('Invalid parameter type.');
		}

		$this->_handler = $handler;
		$this->_lazy = $lazy;

		return $this;
	}

	/**
	 * Sets if the main handler must be lazy loaded
	 *
	 * @param boolean $lazy
	 * @return \Phalcon\Mvc\Micro\CollectionInterface
	 * @throws Exception
	 */
	public function setLazy($lazy)
	{
		if(is_bool($lazy) === false) {
			throw new Exception('Invalid parameter type.');
		}

		$this->_lazy = $lazy;

		return $this;
	}

	/**
	 * Returns if the main handler must be lazy loaded
	 *
	 * @return boolean|null
	 */
	public function isLazy()
	{
		return $this->_lazy;
	}

	/**
	 * Returns the main handler
	 *
	 * @return mixed
	 */
	public function getHandler()
	{
		return $this->_handler;
	}

	/**
	 * Maps a route to a handler
	 *
	 * @param string $routePattern
	 * @param callable $handler
	 * @return \Phalcon\Mvc\Micro\CollectionInterface
	 * @throws Exception
	 */
	public function map($routePattern, $handler)
	{
		if(is_string($routePattern) === false ||
			is_callable($handler) === false) {
			throw new Exception('Invalid parameter type.');
		}

		$this->addToMap(null, $routePattern, $handler);

		return $this;
	}

	/**
	 * Maps a route to a handler that only matches if the HTTP method is GET
	 *
	 * @param string $routePattern
	 * @param callable $handler
	 * @return \Phalcon\Mvc\Micro\CollectionInterface
	 * @throws Exception
	 */
	public function get($routePattern, $handler)
	{
		if(is_string($routePattern) === false ||
			is_callable($handler) === false) {
			throw new Exception('Invalid parameter type.');
		}

		$this->addToMap('GET', $routePattern, $handler);

		return $this;
	}

	/**
	 * Maps a route to a handler that only matches if the HTTP method is POST
	 *
	 * @param string $routePattern
	 * @param callable $handler
	 * @return \Phalcon\Mvc\Micro\CollectionInterface
	 * @throws Exception
	 */
	public function post($routePattern, $handler)
	{
		if(is_string($routePattern) === false ||
			is_callable($handler) === false) {
			throw new Exception('Invalid parameter type.');
		}

		$this->addToMap('POST', $routePattern, $handler);

		return $this;
	}

	/**
	 * Maps a route to a handler that only matches if the HTTP method is PUT
	 *
	 * @param string $routePattern
	 * @param callable $handler
	 * @return \Phalcon\Mvc\Micro\CollectionInterface
	 * @throws Exception
	 */
	public function put($routePattern, $handler)
	{
		if(is_string($routePattern) === false ||
			is_callable($handler) === false) {
			throw new Exception('Invalid parameter type.');
		}

		$this->addToMap('PUT', $routePattern, $handler);

		return $this;
	}

	/**
	 * Maps a route to a handler that only matches if the HTTP method is PATCH
	 *
	 * @param string $routePattern
	 * @param callable $handler
	 * @return \Phalcon\Mvc\Micro\CollectionInterface
	 * @throws Exception
	 */
	public function patch($routePattern, $handler)
	{
		if(is_string($routePattern) === false ||
			is_callable($handler) === false) {
			throw new Exception('Invalid parameter type.');
		}

		$this->addToMap('PATCH', $routePattern, $handler);

		return $this;
	}

	/**
	 * Maps a route to a handler that only matches if the HTTP method is HEAD
	 *
	 * @param string $routePattern
	 * @param callable $handler
	 * @return \Phalcon\Mvc\Micro\CollectionInterface
	 * @throws Exception
	 */
	public function head($routePattern, $handler)
	{
		if(is_string($routePattern) === false ||
			is_callable($handler) === false) {
			throw new Exception('Invalid parameter type.');
		}

		$this->addToMap('HEAD', $routePattern, $handler);

		return $this;
	}

	/**
	 * Maps a route to a handler that only matches if the HTTP method is DELETE
	 *
	 * @param string $routePattern
	 * @param callable $handler
	 * @return \Phalcon\Mvc\Micro\CollectionInterface
	 * @throws Exception
	 */
	public function delete($routePattern, $handler)
	{
		if(is_string($routePattern) === false ||
			is_callable($handler) === false) {
			throw new Exception('Invalid parameter type.');
		}

		$this->addToMap('DELETE', $routePattern, $handler);

		return $this;
	}

	/**
	 * Maps a route to a handler that only matches if the HTTP method is OPTIONS
	 *
	 * @param string $routePattern
	 * @param callable $handler
	 * @return \Phalcon\Mvc\Micro\CollectionInterface
	 * @throws Exception
	 */
	public function options($routePattern, $handler)
	{
		if(is_string($routePattern) === false ||
			is_callable($handler) === false) {
			throw new Exception('Invalid parameter type.');
		}

		$this->addToMap('OPTIONS', $routePattern, $handler);

		return $this;
	}
}