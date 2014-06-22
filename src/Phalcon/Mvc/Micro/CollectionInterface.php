<?php
/**
 * Collection Interface
 *
 * @author Andres Gutierrez <andres@phalconphp.com>
 * @author Eduar Carvajal <eduar@phalconphp.com>
 * @version 1.2.6
 * @package Phalcon
*/
namespace Phalcon\Mvc\Micro;

/**
 * Phalcon\Mvc\Micro\CollectionInterface initializer
 * 
 * @see https://github.com/phalcon/cphalcon/blob/1.2.6/ext/mvc/micro/collectioninterface.c
 */
interface CollectionInterface
{
	/**
	 * Sets a prefix for all routes added to the collection
	 *
	 * @param string $prefix
	 * @return \Phalcon\Mvc\Micro\Collection
	 */
	public function setPrefix($prefix);

	/**
	 * Returns the collection prefix if any
	 *
	 * @return string
	 */
	public function getPrefix();

	/**
	 * Returns the registered handlers
	 *
	 * @return array
	 */
	public function getHandlers();

	/**
	 * Sets the main handler
	 *
	 * @param mixed $handler
	 * @param boolean|null $lazy
	 * @return \Phalcon\Mvc\Micro\Collection
	 */
	public function setHandler($handler, $lazy = null);

	/**
	 * Sets if the main handler must be lazy loaded
	 *
	 * @param boolean $lazy
	 * @return \Phalcon\Mvc\Micro\Collection
	 */
	public function setLazy($lazy);

	/**
	 * Returns if the main handler must be lazy loaded
	 *
	 * @return boolean
	 */
	public function isLazy();

	/**
	 * Returns the main handler
	 *
	 * @return mixed
	 */
	public function getHandler();

	/**
	 * Maps a route to a handler
	 *
	 * @param string $routePattern
	 * @param callable $handler
	 * @return \Phalcon\Mvc\Router\RouteInterface
	 */
	public function map($routePattern, $handler);

	/**
	 * Maps a route to a handler that only matches if the HTTP method is GET
	 *
	 * @param string $routePattern
	 * @param callable $handler
	 * @return \Phalcon\Mvc\Router\RouteInterface
	 */
	public function get($routePattern, $handler);

	/**
	 * Maps a route to a handler that only matches if the HTTP method is POST
	 *
	 * @param string $routePattern
	 * @param callable $handler
	 * @return \Phalcon\Mvc\Router\RouteInterface
	 */
	public function post($routePattern, $handler);

	/**
	 * Maps a route to a handler that only matches if the HTTP method is PUT
	 *
	 * @param string $routePattern
	 * @param callable $handler
	 * @return \Phalcon\Mvc\Router\RouteInterface
	 */
	public function put($routePattern, $handler);

	/**
	 * Maps a route to a handler that only matches if the HTTP method is PATCH
	 *
	 * @param string $routePattern
	 * @param callable $handler
	 * @return \Phalcon\Mvc\Router\RouteInterface
	 */
	public function patch($routePattern, $handler);

	/**
	 * Maps a route to a handler that only matches if the HTTP method is HEAD
	 *
	 * @param string $routePattern
	 * @param callable $handler
	 * @return \Phalcon\Mvc\Router\RouteInterface
	 */
	public function head($routePattern, $handler);

	/**
	 * Maps a route to a handler that only matches if the HTTP method is DELETE
	 *
	 * @param string $routePattern
	 * @param callable $handler
	 * @return \Phalcon\Mvc\Router\RouteInterface
	 */
	public function delete($routePattern, $handler);

	/**
	 * Maps a route to a handler that only matches if the HTTP method is OPTIONS
	 *
	 * @param string $routePattern
	 * @param callable $handler
	 * @return \Phalcon\Mvc\Router\RouteInterface
	 */
	public function options($routePattern, $handler);
}