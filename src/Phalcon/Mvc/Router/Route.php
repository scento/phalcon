<?php
/**
 * Route
 *
 * @author Andres Gutierrez <andres@phalconphp.com>
 * @author Eduar Carvajal <eduar@phalconphp.com>
 * @author Wenzel Pünter <wenzel@phelix.me>
 * @version 1.2.6
 * @package Phalcon
*/
namespace Phalcon\Mvc\Router;

use \Phalcon\Mvc\Router\RouteInterface,
	\Phalcon\Mvc\Router\Exception,
	\Phalcon\Text;

/**
 * Phalcon\Mvc\Router\Route
 *
 * This class represents every route added to the router
 * 
 * @see https://github.com/phalcon/cphalcon/blob/1.2.6/ext/mvc/router/route.c
 */
class Route implements RouteInterface
{
	/**
	 * Pattern
	 * 
	 * @var null|string
	 * @access protected
	*/
	protected $_pattern;

	/**
	 * Compiled Pattern
	 * 
	 * @var null|string
	 * @access protected
	*/
	protected $_compiledPattern;

	/**
	 * Paths
	 * 
	 * @var null|array
	 * @access protected
	*/
	protected $_paths;

	/**
	 * Methods
	 * 
	 * @var null|array|string
	 * @access protected
	*/
	protected $_methods;

	/**
	 * Hostname
	 * 
	 * @var null|string
	 * @access protected
	*/
	protected $_hostname;

	/**
	 * Converters
	 * 
	 * @var null|array
	 * @access protected
	*/
	protected $_converters;

	/**
	 * ID
	 * 
	 * @var null|int
	 * @access protected
	*/
	protected $_id;

	/**
	 * Name
	 * 
	 * @var null|string
	 * @access protected
	*/
	protected $_name;

	/**
	 * Before Match
	 * 
	 * @var null|callback
	 * @access protected
	*/
	protected $_beforeMatch;

	/**
	 * Unique ID
	 * 
	 * @var null|int
	 * @access protected
	*/
	protected static $_uniqueId;

	/**
	 * \Phalcon\Mvc\Router\Route constructor
	 *
	 * @param string $pattern
	 * @param array|null $paths
	 * @param array|string|null $httpMethods
	 * @throws Exception
	 */
	public function __construct($pattern, $paths = null, $httpMethods = null)
	{
		/* Type check */
		if(is_array($httpMethods) === false &&
			is_string($httpMethods) === false &&
			is_null($httpMethods) === false) {
			throw new Exception('Invalid parameter type.');
		}

		//Configure the route (extract parameters, paths, etc)
		$this->reConfigure($pattern, $paths);

		//Update the HTTP method constraints
		$this->_methods = $httpMethods;

		//Get the unique Id from the static member _uniqueId
		$unique_id = self::$_uniqueId;
		if(is_null($unique_id) === true) {
			$unique_id = 0;
		}

		//TODO: Add a function that increases static members
		$this->_id = $unique_id;
		self::$_uniqueId = $unique_id + 1;
	}

	/**
	 * Replaces placeholders from pattern returning a valid PCRE regular expression
	 *
	 * @param string $pattern
	 * @return string
	 * @throws Exception
	 */
	public function compilePattern($pattern)
	{
		if(is_string($pattern) === false) {
			throw new Exception('Invalid parameter type.');
		}

		$compiled_pattern = $pattern;

		//If a pattern contains ':', maybe there are placeholders to replace
		if(strpos($pattern, ':') !== false) {
			//This is a pattern for valid identifers
			$id_pattern = '/([a-zA-Z0-9\\_\\-]+)';

			//Replace the module part
			if(strpos($pattern, '/:module') !== false) {
				$compiled_pattern = str_replace('/:module', $id_pattern, $compiled_pattern);
			}

			//Replace the controller placeholder
			if(strpos($pattern, '/:controller') !== false) {
				$compiled_pattern = str_replace('/:controller', $id_pattern, $compiled_pattern);
			}

			//Replace the namespace placeholder
			if(strpos($pattern, '/:namespace') !== false) {
				$compiled_pattern = str_replace('/:namespace', $id_pattern, $compiled_pattern);
			}

			//Replace the action placeholder
			if(strpos($pattern, '/:action') !== false) {
				$compiled_pattern = str_replace('/:action', $id_pattern, $compiled_pattern);
			}

			//Replace the params placeholder
			if(strpos($pattern, '/:params') !== false) {
				$compiled_pattern = str_replace('/:params', '(/.*)*', $compiled_pattern);
			}

			//Replace the int placeholder
			if(strpos($pattern, '/:int') !== false) {
				$compiled_pattern = str_replace('/:int', '/([0-9]+)', $compiled_pattern);
			}
		}

		//Check if the pattern has parantheses in order to add the regex delimiters
		if(strpos($compiled_pattern, '(') !== false ||
			strpos($compiled_pattern, '[') !== false) {
			return '#^'.$compiled_pattern.'$#';
		}

		return $compiled_pattern;
	}

	/**
	 * Set one or more HTTP methods that constraint the matching of the route
	 *
	 *<code>
	 * $route->via('GET');
	 * $route->via(array('GET', 'POST'));
	 *</code>
	 *
	 * @param string|array $httpMethods
	 * @return \Phalcon\Mvc\Router\Route
	 * @throws Exception
	 */
	public function via($httpMethods)
	{
		if(is_string($httpMethods) === false &&
			is_array($httpMethods) === false) {
			throw new Exception('Invalid parameter type.');
		}

		$this->_methods = $httpMethods;

		return $this;
	}

	/**
	 * Extracts parameters from a string
	 * 
	 * @param string $str
	 * @param array $matches
	 * @throws Exception
	*/
	private static function extractNamedParameters($str, $matches)
	{
		if(is_string($str) === false ||
			strlen($str) <= 0 ||
			is_array($matches) === false) {
			return false;
		}

		$bracket_count = 0;
		$parentheses_count = 0;
		$intermediate = 0;
		$number_matches = 0;
		$regexp_length = 0;
		$not_valid = false;
		$marker = null;
		$variable = null;
		$item = null;
		$regexp = null;
		$cursor_var = null;
		$route_str = '';
		$cursor = 0;

		$l = strlen($str);
		for($i = 0; $i < $l; ++$i) {
			$ch = $str[$i];

			if($ch === "\0") {
				break;
			}

			if($parentheses_count === 0) {
				if($ch === '{') {
					if($bracket_count === 0) {
						$marker = $i;
						$intermediate = 0;
						$not_valid = false;
					}
					++$bracket_count;
				} elseif($ch === '}') {
					--$bracket_count;
					if($intermediate > 0) {
						if($bracket_count === 0) {
							$number_matches++;
							$variable = null;
							$length = $cursor - $marker - 1;
							$item = substr($str, $marker + 1, $length);
							$cursor_var = $marker + 1;
							$marker = $marker + 1;
							for($j = 0; $j < $length; ++$j) {
								$ch = $str[$cursor_var];
								$cha = ord($ch);

								if($ch === "\0") {
									break;
								}

								if($j === 0 && !(($cha >= 97 && $cha <= 122) || ($cha >= 65 && $cha <= 90))) {
									$not_valid = true;
									break;
								}

								if(($cha >= 97 && $cha <= 122) || ($cha >= 65 && $cha <= 90) || ($cha >= 48 && $cha <= 57) || $ch === '-' || $ch === '_' || $ch === ':') {
									if($ch === ':') {
										$regexp_length = $length - $j - 1;
										$variable_length = $cursor_var - $marker;
										$variable = substr($str, $marker, $variable_length);
										$regexp = substr($str, $cursor_var + 1, $regexp_length);
										break;
									}
								} else {
									$not_valid = true;
									break;
								}

								$cursor_var++;
							}

							if($not_valid === false) {
								$tmp = $number_matches;
								if(isset($variable) === true) {
									if($regexp_length > 0) {
										if($regexp === null) {
											throw new Exception('Invalid assumption.');
										}

										//Check if we need to add parentheses to the expressions
										$found_pattern = 0;
										for($k = 0; $k < $regexp_length; ++$k) {
											if($regexp[$k] === "\0") {
												break;
											}

											if($found_pattern === false) {
												if($regexp[$k] === '(') {
													$found_pattern = 1;
												}
											} else {
												if($regexp[$k] === ')') {
													$found_pattern = 2;
													break;
												}
											}
										}

										if($found_pattern !== 2) {
											$route_str .= '('.$regexp.')';
										} else {
											$route_str .= $regexp;
										}
										$matches[$variable] = $tmp;
									}
								} else {
									$route_str .= '([^/]*)';
									$matches[$item] = $tmp;
								}
							}
						} else {
							$route_str .= '{'.$item.'}';
						}

						$cursor++;
						continue;
					}
				}
			}

			if($bracket_count === 0) {
				if($ch === '(') {
					$parentheses_count++;
				} else {
					if($ch === ')') {
						$parentheses_count--;
						if($parentheses_count === 0) {
							$number_matches++;
						}
					}
				}
			}

			if($bracket_count > 0) {
				$intermediate++;
 			} else {
 				$route_str .= $ch;
 			}

 			$cursor++;
		}

		return $route_str;
	}

	/**
	 * Reconfigure the route adding a new pattern and a set of paths
	 *
	 * @param string $pattern
	 * @param array|null|string $paths
	 * @throws Exception
	 */
	public function reConfigure($pattern, $paths = null)
	{
		if(is_string($pattern) === false) {
			throw new Exception('The pattern must be string');
		}

		$original_pattern = $pattern;

		if(is_string($paths) === true) {
			$module_name = null;
			$controller_name = null;
			$action_name = null;

			//Explode the short paths using the :: separator
			$parts = explode('::', $paths);
			$number_parts = count($parts);

			//Create the array paths dynamically
			switch($number_parts) {
				case 3:
					$module_name = $parts[0];
					$controller_name = $parts[1];
					$action_name = $parts[2];
					break;
				case 2:
					$controller_name = $parts[0];
					$action_name = $parts[1];
					break;
				case 1:
					$controller_name = $parts[0];
					break;
				//@note no default
			}

			$route_paths = array();

			//Process module name
			if(is_null($module_name) === false) {
				$route_paths['module'] = $module_name;
			}

			//Process controller name
			if(is_null($controller_name) === false) {
				//Check if we need to obtain the namespace 
				if(strpos($controller_name, '\\') !== false) {
					$class_with_namespace = get_class($controller_name);

					//Extract the real class name from the namespaced class
					//Extract the namespace from the namespaced class
					$pos = strrpos($class_with_namespace, '\\');
					if($pos !== false) {
						$namespace_name = substr($class_with_namespace, 0, $pos);
						$real_class_name = substr($class_with_namespace, $pos);
					} else {
						$real_class_name = $class_with_namespace;
					}

					//Update the namespace
					if(isset($namespace_name) === true) {
						$route_paths['namespace'] = $namespace_name;
					}
				} else {
					$real_class_name = $controller_name;
				}

				//Always pass the controller to lowercase
				$real_class_name = Text::uncamelize($real_class_name);

				//Update the controller path
				$route_paths['controller'] = $real_class_name;
			}

			//Process action name
			if(is_null($action_name) === false) {
				$route_paths['action'] = $action_name;
			}
		} elseif(is_array($paths) === true) {
			$route_paths = $paths;
		} elseif(is_null($paths) === true) {
			$route_paths = array();
		} else {
			throw new Exception('The route contains invalid paths');
		}

		//If the route starts with '#' we assume that it is a regular expression
		if(Text::startsWith($pattern, '#') === false) {
			if(strpos($pattern, '{') !== false) {
				//The route has named parameters so we need to extract them
				$pattern = self::extractNamedParameters($pattern, $route_paths);
			}

			//Transform the route's pattern to a regular expression
			$pattern = $this->compilePattern($pattern);
		}

		//Update member variables
		$this->_pattern = $original_pattern;
		$this->_compiledPattern = $pattern;
		$this->_paths = $route_paths;
	}

	/**
	 * Returns the route's name
	 *
	 * @return string|null
	 */
	public function getName()
	{
		return $this->_name;
	}

	/**
	 * Sets the route's name
	 *
	 *<code>
	 * $router->add('/about', array(
	 *     'controller' => 'about'
	 * ))->setName('about');
	 *</code>
	 *
	 * @param string $name
	 * @return \Phalcon\Mvc\Router\Route
	 * @throws Exception
	 */
	public function setName($name)
	{
		if(is_string($name) === false) {
			throw new Exception('Invalid parameter type.');
		}

		$this->_name = $name;

		return $this;
	}

	/**
	 * Sets a callback that is called if the route is matched.
	 * The developer can implement any arbitrary conditions here
	 * If the callback returns false the route is treaded as not matched
	 *
	 * @param callback $callback
	 * @return \Phalcon\Mvc\Router\Route
	 * @throws Exception
	 */
	public function beforeMatch($callback)
	{
		if(is_callable($callback) === false) {
			throw new Exception('Invalid parameter type.');
		}

		$this->_beforeMatch = $callback;

		return $this;
	}

	/**
	 * Returns the 'before match' callback if any
	 *
	 * @return mixed
	 */
	public function getBeforeMatch()
	{
		return $this->_beforeMatch;
	}

	/**
	 * Returns the route's id
	 *
	 * @return int|null
	 */
	public function getRouteId()
	{
		return $this->_id;
	}

	/**
	 * Returns the route's pattern
	 *
	 * @return string|null
	 */
	public function getPattern()
	{
		return $this->_pattern;
	}

	/**
	 * Returns the route's compiled pattern
	 *
	 * @return string|null
	 */
	public function getCompiledPattern()
	{
		return $this->_compiledPattern;
	}

	/**
	 * Returns the paths
	 *
	 * @return array|null
	 */
	public function getPaths()
	{
		return $this->_paths;
	}

	/**
	 * Returns the paths using positions as keys and names as values
	 *
	 * @return array
	 */
	public function getReversedPaths()
	{
		if(is_array($this->_paths) === false) {
			$this->_paths = array();
		}

		$reversed = array();

		foreach($this->_paths as $path => $position) {
			$reversed[$position] = $path;
		}

		return $reversed;
	}

	/**
	 * Sets a set of HTTP methods that constraint the matching of the route (alias of via)
	 *
	 *<code>
	 * $route->setHttpMethods('GET');
	 * $route->setHttpMethods(array('GET', 'POST'));
	 *</code>
	 *
	 * @param string|array $httpMethods
	 * @return \Phalcon\Mvc\Router\Route
	 * @throws Exception
	 */
	public function setHttpMethods($httpMethods)
	{
		if(is_string($httpMethods) === false &&
			is_array($httpMethods) === false) {
			throw new Exception('Invalid parameter type.');
		}

		$this->_methods = $httpMethods;

		return $this;
	}

	/**
	 * Returns the HTTP methods that constraint matching the route
	 *
	 * @return string|array|null
	 */
	public function getHttpMethods()
	{
		return $this->_methods;
	}

	/**
	 * Sets a hostname restriction to the route
	 *
	 *<code>
	 * $route->setHostname('localhost');
	 *</code>
	 *
	 * @param string $hostname
	 * @return \Phalcon\Mvc\Router\Route
	 * @throws Exception
	 */
	public function setHostname($hostname)
	{
		if(is_string($hostname) === false) {
			throw new Exception('Invalid parameter type.');
		}

		$this->_hostname = $hostname;

		return $this;
	}

	/**
	 * Returns the hostname restriction if any
	 *
	 * @return string|null
	 */
	public function getHostname()
	{
		return $this->_hostname;
	}

	/**
	 * Adds a converter to perform an additional transformation for certain parameter
	 *
	 * @param string $name
	 * @param callable $converter
	 * @return \Phalcon\Mvc\Router\Route
	 * @throws Exception
	 */
	public function convert($name, $converter)
	{
		if(is_string($name) === false ||
			is_callable($converter) === false) {
			throw new Exception('Invalid parameter type.');
		}

		if(is_array($this->_converters) === false) {
			$this->_converters = array();
		}

		$this->_converters[$name] = $converter;

		return $this;
	}

	/**
	 * Returns the router converter
	 *
	 * @return array|null
	 */
	public function getConverters()
	{
		return $this->_converters;
	}

	/**
	 * Resets the internal route id generator
	 */
	public static function reset()
	{
		self::$_uniqueId = 0;
	}
}