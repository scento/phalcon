<?php
/**
 * URL
 *
 * @author Andres Gutierrez <andres@phalconphp.com>
 * @author Eduar Carvajal <eduar@phalconphp.com>
 * @author Wenzel Pünter <wenzel@phelix.me>
 * @version 1.2.6
 * @package Phalcon
*/
namespace Phalcon\Mvc;

use \Phalcon\Mvc\UrlInterface,
	\Phalcon\DI\InjectionAwareInterface,
	\Phalcon\Mvc\Url\Exception,
	\Phalcon\DiInterface;

/**
 * Phalcon\Mvc\Url
 *
 * This components aids in the generation of: URIs, URLs and Paths
 *
 *<code>
 *
 * //Generate a URL appending the URI to the base URI
 * echo $url->get('products/edit/1');
 *
 * //Generate a URL for a predefined route
 * echo $url->get(array('for' => 'blog-post', 'title' => 'some-cool-stuff', 'year' => '2012'));
 *
 *</code>
 * 
 * @see https://github.com/phalcon/cphalcon/blob/1.2.6/ext/mvc/url.c
 */
class Url implements UrlInterface, InjectionAwareInterface
{
	/**
	 * Dependency Injector
	 * 
	 * @var null|\Phalcon\DiInterface
	 * @access protected
	*/
	protected $_dependencyInjector;

	/**
	 * Base URI
	 * 
	 * @var string|null
	 * @access protected
	*/
	protected $_baseUri;

	/**
	 * Static Base URI
	 * 
	 * @var string|null
	 * @access protected
	*/
	protected $_staticBaseUri;

	/**
	 * Base Path
	 * 
	 * @var string|null
	 * @access protected
	*/
	protected $_basePath;

	/**
	 * Router
	 * 
	 * @var object|null
	 * @access protected
	*/
	protected $_router;

	/**
	 * Sets the DependencyInjector container
	 *
	 * @param \Phalcon\DiInterface $dependencyInjector
	 * @throws Exception
	 */
	public function setDI($dependencyInjector)
	{
		if(is_object($dependencyInjector) === false ||
			$dependencyInjector instanceof DiInterface === false) {
			throw new Exception('The dependency injector must be an Object');
		}

		$this->_dependencyInjector = $dependencyInjector;
	}

	/**
	 * Returns the DependencyInjector container
	 *
	 * @return \Phalcon\DiInterface|null
	 */
	public function getDI()
	{
		return $this->_dependencyInjector;
	}

	/**
	 * Sets a prefix for all the URIs to be generated
	 *
	 *<code>
	 *	$url->setBaseUri('/invo/');
	 *	$url->setBaseUri('/invo/index.php/');
	 *</code>
	 *
	 * @param string $baseUri
	 * @return \Phalcon\Mvc\Url
	 * @throws Exception
	 */
	public function setBaseUri($baseUri)
	{
		if(is_string($baseUri) === false) {
			throw new Exception('Invalid parameter type.');
		}

		$this->_baseUri = $baseUri;

		if(is_null($this->_staticBaseUri) === true) {
			$this->_staticBaseUri = $baseUri;
		}

		return $this;
	}

	/**
	 * Sets a prefix for all static URLs generated
	 *
	 *<code>
	 *	$url->setStaticBaseUri('/invo/');
	 *</code>
	 *
	 * @param string $staticBaseUri
	 * @return \Phalcon\Mvc\Url
	 * @throws Exception
	 */
	public function setStaticBaseUri($staticBaseUri)
	{
		if(is_string($staticBaseUri) === false) {
			throw new Exception('Invalid parameter type.');
		}

		$this->_staticBaseUri = $staticBaseUri;

		return $this;
	}

	/**
	 * Get URI
	 * 
	 * @param string $path
	 * @see https://github.com/phalcon/cphalcon/blob/1.2.6/ext/kernel/framework/url.c
	*/
	private static function getUri($path)
	{
		if(is_string($path) === false) {
			return '';
		}

		$found = 0;
		$mark = 0;

		if(empty($path) === false) {
			for($i = strlen($path); $i > 0; $i--) {
				$ch = $path[$i - 1];
				if($ch === '/' || $ch === '\\') {
					$found++;

					if($found === 1) {
						$mark = $i - 1;
					} else {
						return substr($path, 0, $mark - $i).chr(0);
					}
				}
			}
		}

		return '';
	}

	/**
	 * Returns the prefix for all the generated urls. By default /
	 *
	 * @return string
	 */
	public function getBaseUri()
	{
		$baseUri = $this->_baseUri;

		if(is_null($this->_baseUri) === true) {
			if(isset($_SERVER['PHP_SELF']) === true) {
				$uri = self::getUri($_SERVER['PHP_SELF']);
			}

			if(is_string($uri) === false) {
				$baseUri = '/';
			} else {
				$baseUri .= '/'.$uri.'/';
			}

			$this->_baseUri = $baseUri;
		}

		return $baseUri;
	}

	/**
	 * Returns the prefix for all the generated static urls. By default /
	 *
	 * @return string
	 */
	public function getStaticBaseUri()
	{
		if(is_null($this->_staticBaseUri) === false) {
			return $this->_staticBaseUri;
		}

		return $this::getBaseUri();
	}

	/**
	 * Sets a base path for all the generated paths
	 *
	 *<code>
	 *	$url->setBasePath('/var/www/htdocs/');
	 *</code>
	 *
	 * @param string $basePath
	 * @return \Phalcon\Mvc\Url
	 * @throws Exception
	 */
	public function setBasePath($basePath)
	{
		if(is_string($basePath) === false) {
			throw new Exception('Invalid parameter type.');
		}

		$this->_basePath = $basePath;
	}

	/**
	 * Returns the base path
	 *
	 * @return string|null
	 */
	public function getBasePath()
	{
		return $this->_basePath;
	}
	
	/**
	 * Replace Marker
	 * 
	 * @param boolean $named
	 * @param string $pattern
	 * @param array $paths
	 * @param array $replacements
	 * @param int $position
	 * @param int $cursor
	 * @param int $marker
	*/
	private static function replaceMarker($pattern, $named, &$paths, &$replacements, &$position, 
		&$cursor, &$marker) {
		$notValid = false;
		/*
		 * $marker: string index of the start char (e.g. "{")
		 * $cursor: string index of the character before end (e.g. "}")
		 * $pattern: string to handle
		 * $named: is named marker?
		 * $replacements: parameter data to use
		*/

		if($named === true) {
			$length = $cursor - $marker - 1;	//Length of the name
			$item = substr($pattern, $marker + 1, $length); //The name
			$cursorVar = $marker + 1;
			$marker = $marker + 1;
			for($j = 0; $j < $length; ++$j) {
				$ch = $pattern[$cursorVar];
				if($ch === "\0") {
					$notValid = true;
					break;
				}
				
				$z = ord($ch);
				if($j === 0 && !(($z >= 97 && $z <= 122) || ($z >= 65 && $z <= 90))) {
					$notValid = true;
					break;
				}
				
				if(($z >= 97 && $z <= 122) || ($z >= 65 && $z <= 90) || ($z >= 48 && 
				$z <= 57) || $ch === '-' || $ch === '_' || $ch === ':') {
					if($ch === ':') {
						$variableLength = $cursorVar - $marker;
						$variable = substr($pattern, $marker, $variableLength);
						break;
					}
				} else {
					$notValid = true;
					break;
				}
				$cursorVar++;
			}
		}
		
		if($notValid === false) {
			if(isset($paths[$position])) {
				if($named === true) {
					if(isset($variable) === true) {
						$item = $variable;
						$length = $variableLength;
					}
					
					if(isset($replacements[$item]) === true) {
						$position++;
						return $replacements[$item];
					}
				} else {
					if(isset($paths[$position]) === true) {
						$zv = $paths[$position];
						if(is_string($zv) === true) {
							if(isset($replacements[$zv]) === true) {
								$position++;
								return $replacements[$zv];
							}
						}
					}
				}
			}
			
			$position++;
		}
		
		return null;
	}

	/**
	 * Replace Paths
	 * 
	 * @param string $pattern
	 * @param array $paths
	 * @param array $replacements
	 * @return string|boolean
	 * @throws Exception
	 * @see https://github.com/phalcon/cphalcon/blob/1.2.6/ext/kernel/framework/router.c
	*/
	private static function replacePaths($pattern, $paths, $replacements)
	{
		if(is_string($pattern) === false ||
			is_array($replacements) === false ||
			is_array($paths) === false) {
			throw new Exception('Invalid arguments supplied for phalcon_replace_paths()');
		}


		$l = strlen($pattern);

		if($l <= 0) {
			return false;
		}

		if($pattern[0] === '/') {
			$i = 1;
		} else {
			$i = 0;
		}

		if(empty($paths) === true) {
			return substr($pattern, 1);
		}

		$cursor = 1;		//Cursor for $pattern; Ignoring the first character
		$marker = null;
		$bracketCount = 0;
		$parenthesesCount = 0;
		$intermediate = 0;
		$ch = null;
		$routeStr = '';
		$position = 1;
		$lookingPlaceholder = false;

		for($i = 1; $i < $l; ++$i) {
			$ch = $pattern[$cursor];
			if($ch === "\0") {
				break;
			}

			if($parenthesesCount === 0 && $lookingPlaceholder === false) {
				if($ch === '{') {
					if($bracketCount === 0) {
						$marker = $cursor;
						$intermediate = 0;
					}
					++$bracketCount;
				} else {
					if($ch === '}') {
						--$bracketCount;
						if($intermediate > 0) {
							if($bracketCount === 0) {
								$replace = self::replaceMarker($pattern, true, $paths, $replacements, $position, $cursor, $marker);
								if(isset($replace) === true) {
									if(is_string($replace) === false) {
										$replace = (string)$replace;
									}

									$routeStr .= $replace;
								}
								++$cursor;
								continue;
							}
						}
					}
				}
			}

			if($bracketCount === 0 && $lookingPlaceholder === false) {
				if($ch === '(') {
					if($parenthesesCount === 0) {
						$marker = $cursor;
						$intermediate = 0;
					}
					++$parenthesesCount;
				} else {
					if($ch === ')') {
						--$parenthesesCount;
						if($intermediate > 0) {
							if($parenthesesCount === 0) {
								$replace = self::replaceMarker($pattern, false, $paths, $replacements, $position, $cursor, $marker);
								
								if(isset($replace) === true) {
									if(is_string($replace) === false) {
										$replace = (string)$replace;
									}

									$routeStr .= $replace;
								}
								++$cursor;
								continue;
							}
						}
					}
				}
			}

			if($bracketCount === 0 && $parenthesesCount === 0) {
				if($lookingPlaceholder === true) {
					if($intermediate > 0) {
						$chord = ord($ch);
						if($chord < 97 || $chord > 122 || $i === ($l - 1)) {
							$replace = self::replaceMarker($pattern, false, $paths, $replacements, $position, $cursor, $marker);
							if(isset($replace) === true) {
								if(is_string($replace) === false) {
									$replace = (string)$replace;
								}

								$routeStr .= $replace;
							}

							$lookingPlaceholder = false;
							continue;
						}
					}
				} else {
					if($ch === ':') {
						$lookingPlaceholder = true;
						$marker = $cursor;
						$intermediate = 0;
					}
				}
			}

			if($bracketCount > 0 || $parenthesesCount > 0 ||
				$lookingPlaceholder === true) {
				++$intermediate;
			} else {
				$routeStr .= $ch;
			}

			++$cursor;
		}

		return $routeStr;
	}

	/**
	 * Build HTTP Query
	 * 
	 * @param array $params
	 * @param string $sep
	 * @return string
	 * @see https://github.com/phalcon/cphalcon/blob/1.2.6/ext/kernel/string.c
	*/
	private static function httpBuildQuery($params, $sep)
	{
		if(is_array($params) === false ||
			is_string($sep) === false) {
			throw new Exception('Invalid parameter type.');
		}

		$d = '';

		foreach($params as $key => $param) {
			if(isset($key) === false) {
				$d .= $sep.$param;
			} else {
				$d .= $sep.$key.'='.$param;
			}
		}

		return substr($d, strlen($sep));
	}

	/**
	 * Generates a URL
	 *
	 *<code>
	 *
	 * //Generate a URL appending the URI to the base URI
	 * echo $url->get('products/edit/1');
	 *
	 * //Generate a URL for a predefined route
	 * echo $url->get(array('for' => 'blog-post', 'title' => 'some-cool-stuff', 'year' => '2012'));
	 *
	 *</code>
	 *
	 * @param string|array|null $uri
	 * @param array|object|null $args Optional arguments to be appended to the query string
	 * @return string
	 * @throws Exception
	 */
	public function get($uri = null, $args = null)
	{
		if(is_string($uri) === false &&
			is_array($uri) === false &&
			is_null($uri) === false) {
			throw new Exception('Invalid parameter type.');
		}

		if(is_null($args) === false &&
			is_array($args) === false &&
			is_object($args) === false) {
			throw new Exception('Invalid parameter type.');
		}

		$return = '';
		if(is_array($uri) === true) {
			if(isset($uri['for']) === false) {
				throw new Exception('It\'s necessary to define the route name with the parameter "for"');
			}

			$router = $this->_router;

			//Check if the router has not previously set
			if(is_object($router) === false) {
				$dependencyInjector = $this->_dependencyInjector;
				if(is_object($dependencyInjector) === false) {
					throw new Exception('A dependency injector container is required to obtain the "url" service');
				}

				//@note no interface validation
				$this->_router = $dependencyInjector->getShared('router');
				$router = $this->_router;
			}

			$routeName = $uri['for'];

			//Every route is uniquely differenced by a name
			$route = $router->getRouteByName($routeName);
			if(is_object($route) === false) {
				throw new Exception('Cannot obtain a route using the name "'.$routeName.'"');
			}

			//Replace the patterns by its variables
			$return .= $this->_baseUri.self::replacePaths($pattern = $route->getPattern(),
				$route->getReversedPaths(), $uri);
		} else {
			$return .= $this->_baseUri.$uri;
		}

		if(is_null($args) === false) {
			$query = self::httpBuildQuery($args, '&');
			if(is_string($query) === true && empty($query) === false) {
				if(strpos($return, '?') !== false) {
					$return .= '&'.$query;
				} else {
					$return .= '?'.$query;
				}
			}
		}

		return $return;
	}

	/**
	 * Generates a URL for a static resource
	 *
	 * @param string|null $uri
	 * @return string
	 * @throws Exception
	 */
	public function getStatic($uri = null)
	{
		//@note documented 'array' type for uri doesn't make any sence
		//@note added fallback from NULL
		if(is_null($uri) === true) {
			$uri = '';
		} elseif(is_string($uri) === false) {
			throw new Exception('Invalid parameter type.');
		}

		if(is_null($this->_staticBaseUri) === false) {
			return $this->_staticBaseUri.$uri;
		}

		return $this->getBaseUri().$uri;
	}

	/**
	 * Generates a local path
	 *
	 * @param string|null $path
	 * @return string
	 */
	public function path($path = null)
	{
		//@note added NULL fallback
		if(is_null($path) === true) {
			$path = '';
		} elseif(is_string($path) === false) {
			throw new Exception('Invalid parameter type.');
		}

		return $this->_basePath.$path;
	}
}