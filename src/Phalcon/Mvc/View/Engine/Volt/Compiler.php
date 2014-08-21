<?php
/**
 * Compiler
 *
 * @author Andres Gutierrez <andres@phalconphp.com>
 * @author Eduar Carvajal <eduar@phalconphp.com>
 * @author Wenzel Pünter <wenzel@phelix.me>
 * @version 1.2.6
 * @package Phalcon
*/
namespace Phalcon\Mvc\View\Engine\Volt;

use \Phalcon\Mvc\View\Engine\Volt\Parser,
	\Phalcon\DI\InjectionAwareInterface,
	\Phalcon\DiInterface,
	\Phalcon\Mvc\ViewInterface,
	\Phalcon\Mvc\View\Exception,
	\Closure,
	\Phalcon\Text;

/**
 * Phalcon\Mvc\View\Engine\Volt\Compiler
 *
 * This class reads and compiles Volt templates into PHP plain code
 *
 *<code>
 *	$compiler = new \Phalcon\Mvc\View\Engine\Volt\Compiler();
 *
 *	$compiler->compile('views/partials/header.volt');
 *
 *	require $compiler->getCompiledTemplatePath();
 *</code>
 * 
 * @see https://github.com/phalcon/cphalcon/blob/1.2.6/ext/mvc/view/engine/volt/compiler.c
 */
class Compiler implements InjectionAwareInterface
{
	/**
	 * Dependency Injector
	 * 
	 * @var null|\Phalcon\DiInterface
	 * @access protected
	*/
	protected $_dependencyInjector;

	/**
	 * View 
	 * 
	 * @var null|\Phalcon\Mvc\ViewInterface
	 * @access protected
	*/
	protected $_view;

	/**
	 * Options
	 * 
	 * @var array
	 * @access protected
	*/
	protected $_options = array();

	/**
	 * Array Helpers
	 * 
	 * @var null|array
	 * @access protected
	*/
	protected $_arrayHelpers;

	/**
	 * Level
	 * 
	 * @var int
	 * @access protected
	*/
	protected $_level = 0;

	/**
	 * Foreach Level
	 * 
	 * @var int
	 * @access protected
	*/
	protected $_foreachLevel = 0;

	/**
	 * Block Level
	 * 
	 * @var int
	 * @access protected
	*/
	protected $_blockLevel = 0;

	/**
	 * Expression Level
	 * 
	 * @var int
	 * @access protected
	*/
	protected $_exprLevel = 0;

	/**
	 * Extended
	 * 
	 * @var boolean
	 * @access protected
	*/
	protected $_extended = false;

	/**
	 * Autoescape
	 * 
	 * @var boolean
	 * @access protected
	*/
	protected $_autoescape = false;

	/**
	 * Extended Blocks
	 * 
	 * @var null|array
	 * @access protected
	*/
	protected $_extendedBlocks;

	/**
	 * Current Block
	 * 
	 * @var null|string
	 * @access protected
	*/
	protected $_currentBlock;

	/**
	 * Blocks
	 * 
	 * @var null|array
	 * @access protected
	*/
	protected $_blocks;

	/**
	 * For-Else-Pointers
	 * 
	 * @var null|array
	 * @access protected
	*/
	protected $_forElsePointers;

	/**
	 * Loop Pointers
	 * 
	 * @var null|array
	 * @access protected
	*/
	protected $_loopPointers;

	/**
	 * Extensions
	 * 
	 * @var null|array
	 * @access protected
	*/
	protected $_extensions;

	/**
	 * Functions
	 * 
	 * @var null|array
	 * @access protected
	*/
	protected $_functions;

	/**
	 * Filters
	 * 
	 * @var null|array
	 * @access protected
	*/
	protected $_filters;

	/**
	 * Macros
	 * 
	 * @var null|array
	 * @access protected
	*/
	protected $_macros;

	/**
	 * Prefix
	 * 
	 * @var null|string
	 * @access protected
	*/
	protected $_prefix;

	/**
	 * Current Path
	 * 
	 * @var null|string
	 * @access protected
	*/
	protected $_currentPath;

	/**
	 * Compiled Template Path
	 * 
	 * @var null|string
	 * @access protected
	*/
	protected $_compiledTemplatePath;

	/**
	 * \Phalcon\Mvc\View\Engine\Volt\Compiler
	 *
	 * @param \Phalcon\Mvc\ViewInterface|null $view
	 * @throws Exception
	 */
	public function __construct($view = null)
	{
		if(is_object($view) === true &&
			$view instanceof ViewInterface === true) {
			$this->_view = $view;
		}
	}

	/**
	 * Sets the dependency injector
	 *
	 * @param \Phalcon\DiInterface $dependencyInjector
	 * @throws Exception
	 */
	public function setDI($dependencyInjector)
	{
		if(is_object($dependencyInjector) === false ||
			$dependencyInjector instanceof DiInterface === false) {
			throw new Exception('Dependency Injector is invalid');
		}

		$this->_dependencyInjector = $dependencyInjector;
	}

	/**
	 * Returns the internal dependency injector
	 *
	 * @return \Phalcon\DiInterface|null
	 */
	public function getDI()
	{
		return $this->_dependencyInjector;
	}

	/**
	 * Sets the compiler options
	 *
	 * @param array $options
	 * @throws Exception
	 */
	public function setOptions($options)
	{
		if(is_array($options) === false) {
			throw new Exception('Options must be an array');
		}

		$this->_options = $options;
	}

	/**
	 * Sets a single compiler option
	 *
	 * @param string $option
	 * @param string $value
	 * @throws Exception
	 */
	public function setOption($option, $value)
	{
		if(is_string($option) === false ||
			is_string($value) === false) {
			throw new Exception('Invalid parameter type.');
		}

		$this->_options[$option] = $value;
	}

	/**
	 * Returns a compiler's option
	 *
	 * @param string $option
	 * @return string|null
	 * @throws Exception
	 */
	public function getOption($option)
	{
		if(is_string($option) === false) {
			throw new Exception('Invalid parameter type.');
		}

		if(isset($this->_options[$option]) === true) {
			return $this->_options[$option];
		}
	}

	/**
	 * Returns the compiler options
	 *
	 * @return array
	 */
	public function getOptions()
	{
		return $this->_options;
	}

	/**
	 * Fires an event to registered extensions
	 *
	 * @param string $name
	 * @param array|null $arguments
	 * @return string|null
	 * @throws Exception
	 */
	public function fireExtensionEvent($name, $arguments = null)
	{
		if(is_string($name) === false ||
			(is_array($arguments) === false &&
				is_null($arguments) === false)) {
			throw new Exception('Invalid parameter type.');
		}

		if(is_array($this->_extensions) === true) {
			foreach($this->_extensions as $extension) {
				//Check if the extension implements the required event name
				if(method_exists($extension, $name) === true) {
					$callObject = array($extension, $name);
					if(is_array($arguments) === true) {
						$status = call_user_func_array($callObject, $arguments);
					} else {
						$status = call_user_func($callObject);
					}

					//The extension processed something when the status is a string
					if(is_string($status) === true) {
						return $status;
					}
				}
			}
		}
	}

	/**
	 * Registers a Volt's extension
	 *
	 * @param object $extension
	 * @return \Phalcon\Mvc\View\Engine\Volt\Compiler
	 * @throws Exception
	 */
	public function addExtension($extension)
	{
		if(is_object($extension) === false) {
			throw new Exception('The extension is not valid');
		}

		//Initialize the extension
		if(method_exists($extension, 'initialize') === true) {
			$extension->initialize($this);
		}

		if(is_array($this->_extensions) === false) {
			$this->_extensions = array($extension);
		} else {
			$this->_extensions[] = $extension;
		}

		return $this;
	}

	/**
	 * Returns the list of extensions registered in Volt
	 *
	 * @return array|null
	 */
	public function getExtensions()
	{
		return $this->_extensions;
	}

	/**
	 * Register a new function in the compiler
	 *
	 * @param string $name
	 * @param Closure|string $definition
	 * @return \Phalcon\Mvc\View\Engine\Volt\Compiler
	 * @throws Exception
	 */
	public function addFunction($name, $definition)
	{
		if(is_string($name) === false) {
			throw new Exception('The function name must be a string');
		}

		if(is_string($definition) === false &&
			is_callable($definition) === false) {
			throw new Exception('Invalid parameter type.');
		}

		if(is_array($this->_functions) === false) {
			$this->_functions = array();
		}

		$this->_functions[$name] = $definition;

		return $this;
	}

	/**
	 * Register the user registered functions
	 *
	 * @return array|null
	 */
	public function getFunctions()
	{
		return $this->_functions;
	}

	/**
	 * Register a new filter in the compiler
	 *
	 * @param string $name
	 * @param Closure|string $definition
	 * @return \Phalcon\Mvc\View\Engine\Volt\Compiler
	 * @throws Exception
	 */
	public function addFilter($name, $definition)
	{
		if(is_string($name) === false) {
			throw new Exception('The function name must be a string');
		}

		if(is_callable($definition) === false &&
				is_string($definition) === false) {
			throw new Exception('Invalid parameter type.');
		}

		if(is_array($this->_filters) === false) {
			$this->_filters = array();
		}

		$this->_filters[$name] = $definition;

		return $this;
	}

	/**
	 * Register the user registered filters
	 *
	 * @return array|null
	 */
	public function getFilters()
	{
		return $this->_filters;
	}

	/**
	 * Set a unique prefix to be used as prefix for compiled variables
	 *
	 * @param string $prefix
	 * @return \Phalcon\Mvc\View\Engine\Volt\Compiler
	 * @throws Exception
	 */
	public function setUniquePrefix($prefix)
	{
		if(is_string($prefix) === false) {
			throw new Exception('Invalid parameter type.');
		}

		$this->_prefix = $prefix;

		return $this;
	}

	/**
	 * Generates a unique id for a path
	 * 
	 * @param string $path
	 * @return string|null
	*/
	private static function uniquePathKey($path)
	{
		if(is_string($path) === false) {
			return;
		}

		//We cannot access zend_hash_func, so we simply use md5
		//to generate a unique id for a path
		return md5($path);
	}

	/**
	 * Return a unique prefix to be used as prefix for compiled variables and contexts
	 *
	 * @return string
	 * @throws Exception
	 */
	public function getUniquePrefix()
	{
		$prefix = $this->_prefix;

		//If the unique prefix is not set we use a hash using the modified Berstein
		//algorithm
		if($prefix == false) {
			$prefix = self::uniquePathKey($this->_currentPath);
			$this->_prefix = $prefix;
		}

		//The user could use a closure generator
		if(is_object($prefix) === true &&
			$prefix instanceof Closure === true) {
			$prefix = call_user_func_array($prefix, array($this));
			$this->_prefix = $prefix;
		}

		if(is_string($prefix) === false) {
			throw new Exception('The unique compilation prefix is invalid');
		}

		return $prefix;
	}

	/**
	 * Resolves attribute reading
	 *
	 * @param array $expr
	 * @return string
	 * @throws Exception
	 */
	public function attributeReader($expr)
	{
		if(is_array($expr) === false) {
			throw new Exception('Invalid parameter type.');
		}

		$exprCode = '';
		$left = $expr['left'];

		if($left['type'] === 265) {
			$variable = $left['value'];

			//Check if the variable is the loop context
			if($variable === 'loop') {
				$level = $this->_foreachLevel;

				$prefix = $this->getUniquePrefix();
				$exprCode .= '$'.$prefix.$level.'loop';

				if(is_array($this->_loopPointers) === false) {
					$this->_loopPointers = array();
				}

				$this->_loopPointers[$level] = $level;
			} else {
				//Services registered in the dependency injector container are always available
				$dependencyInjector = $this->_dependencyInjector;
				if(is_object($dependencyInjector) === true) {
					if($dependencyInjector->has($variable) === true) {
						$exprCode .= '$this->'.$variable;
					} else {
						$exprCode .= '$'.$variable;
					}
				} else {
					$exprCode .= '$'.$variable;
				}
			}
		} else {
			$leftCode = $this->expression($left);
			if($leftType !== 46) {
				if($leftType !== 350) {
					$exprCode .= '('.$leftCode.')';
				} else {
					$exprCode .= $leftCode;
				}
			} else {
				$exprCode .= $leftCode;
			}
		}

		$exprCode .= '->';
		$right = $expr['right'];
		if($right['type'] === 265) {
			$exprCode .= $right['value'];
		} else {
			$exprCode .= $this->expression($right);
		}

		return $exprCode;
	}

	/**
	 * Resolves function intermediate code into PHP function calls
	 *
	 * @param array $expr
	 * @return string
	 * @throws Exception
	 */
	public function functionCall($expr)
	{
		//Valid filters are always arrays
		if(is_array($expr) === false) {
			throw new Exception('Corrupted function call');
		}

		$code = null;
		$funcArguments = null;
		if(isset($expr['arguments']) === true) {
			$funcArguments = $expr['arguments'];
			$arguments = $this->expression($funcArguments);
		} else {
			$arguments = '';
		}

		$nameExpr = $expr['name'];
		$nameType = $nameExpr['type'];

		//Check if it's a single function
		if($nameType === 265) {
			$name = $nameExpr['value'];

			//Check if any of the registered extensions provide compilation for this function
			$extensions = $this->_extensions;
			if(is_array($extensions) === true) {
				$code = $this->fireExtensionEvent('compileFunction', array($name, $arguments, $funcArguments));
				if(is_string($code) === true) {
					return $code;
				}
			}

			//Check if it's a user-defined function
			$functions = $this->_functions;
			if(is_array($functions) === true) {
				if(isset($functions[$name]) === true) {
					$definition = $functions[$name];

					//Use the string as function
					if(is_string($definition) === true) {
						return $definition.'('.$arguments.')';
					}

					//Execute the function closure returning the compiled definition
					if(is_object($definition) === true &&
						$definition instanceof Closure === true) {
						return call_user_func_array($definition, array($arguments, $funcArguments));
					}

					$line = $expr['line'];
					$file = $expr['file'];
					throw new Exception("Invalid definition for user function '".$name."' in ".$file.' on line '.$line);
				}
			}

			$macros = $this->_macros;

			//Check if the function name is a macro
			if(isset($macros[$name]) === true) {
				return 'vmacro_'.$name.'(array('.$arguments.'))';
			}

			switch($name) {
				case 'get_content':
				case 'content':
					//This function includes the previous rendering stage
					return '$this->getContent()';
					break;
				case 'partial':
					//This function includes views of volt or others template engines dynamically
					return '$this->partial('.$arguments.')';
					break;
				case 'super':
					//This function embedds the parent block in the current block
					$extendedBlocks = $this->_extendedBlocks;
					if(is_array($extendedBlocks) === true) {
						$currentBlock = $this->_currentBlock;
						if(isset($extendedBlocks[$currentBlock]) === true) {
							$exprLevel = $this->_exprLevel;
							$block = $extendedBlocks[$currentBlock];
							if(is_array($block) === true) {
								$code = $this->_statementListOrExtends($block);
								if($exprLevel === 1) {
									$escapedCode = $code;
								} else {
									$escapedCode = addslashes($code);
								}
							} else {
								if($exprLevel === 1) {
									$escapedCode = $block;
								} else {
									$escapedCode = addcslashes($block);
								}
							}

							//If the super() is the first level we don't esacpe it
							if($exprLevel === 1) {
								return $escapedCode;
							}

							return "'".$escapedCode."'";
						}
					}
					return "''";
					break;
			}

			$camelized = Text::camelize($name);
			$method = lcfirst($camelized);

			//Check if it's a method in Phalcon\Tag
			if(method_exists('Phalcon\\Tag', $method) === true) {
				$arrayHelpers = $this->_arrayHelpers;
				if(is_array($arrayHelpers) === false) {
					$arrayHelpers = array(
						'link_to',
						'image',
						'form',
						'select',
						'select_static',
						'submit_button',
						'radio_field',
						'check_field',
						'file_field',
						'hidden_field',
						'password_field',
						'text_area',
						'text_field',
						'date_field',
						'numeric_field',
						'email_field'
					);

					$this->_arrayHelpers = $arrayHelpers;
				}

				if(isset($arrayHelpers[$name]) === true) {
					return '$this->tag->'.$method.'(array('.$arguments.'))';
				}

				return '$this->tag->'.$method.'('.$arguments.')';
			}

			switch($name) {
				case 'url':
					return '$this->url->get('.$arguments.')';
					break;
				case 'static_url':
					return '$this->url->getStatic('.$arguments.')';
					break;
				case 'date':
					return 'date('.$arguments.')';
					break;
				case 'time':
					return 'time()';
					break;
				case 'dump':
					return 'var_dump('.$arguments.')';
					break;
				case 'version':
					return 'Phalcon\\Version::get()';
					break;
				case 'version_id':
					return 'Phalcon\\Version::getId()';
					break;
				case 'constant':
					return 'constant('.$arguments.')';
					break;
			}

			//The function doesn't exists: throw an exception
			throw new Exception("Undefined function '".$name."' in ".$expr['file'].' on line '.$expr['line']);
		}

		return $this->expression($nameExpr).'('.$arguments.')';
	}

	/**
	 * Resolves filter intermediate code into a valid PHP expression
	 *
	 * @param array $test
	 * @param string $left
	 * @return string
	 * @throws Exception
	 */
	public function resolveTest($test, $left)
	{
		//Valid tests are always arrays
		if(is_array($test) === false) {
			throw new Exception('Corrupted test');
		}

		if(is_string($left) === false) {
			throw new Exception('Invalid parameter type.');
		}

		$type = $test['type'];

		//Check if right part is a single identifier
		if($type === 265) {
			switch($test['value']) {
				case 'empty':
					return 'empty('.$left.')';
					break;
				case 'even':
					return '((('.$left.') % 2) == 0)';
					break;
				case 'odd':
					return '((('.$left.') % 2) != 0)';
					break;
				case 'numeric':
					return 'is_numeric('.$left.')';
					break;
				case 'scalar':
					return 'is_scalar('.$left.')';
					break;
				case 'iterable':
					return '(is_array('.$left.') || ('.$left.') instanceof Traversable)';
					break;
			}
		}

		//Check if right part is a function call
		if($type === 350) {
			$testName = $test['name'];
			if(isset($testName['value']) === true) {
				//Checks if a value is divisible by other
				switch($testName['value']) {
					case 'divisibleby':
						return '((('.$left.') % ('.$this->expression($test['arguments']).')) == 0)';
						break;
					case 'sameas':
						return '('.$left.') == ('.$this->expression($test['arguments']).')';
						break;
					case 'type':
						return 'gettype('.$left.') === ('.$this->expression($test['arguments']).')';
						break;
				}
			}
		}

		//Fall back to the equals operator
		return $left.' == '.$this->expression($test);
	}

	/**
	 * Resolves filter intermediate code into PHP function calls
	 *
	 * @param array $filter
	 * @param string $left
	 * @return string
	 * @throws Exception
	 */
	protected function resolveFilter($filter, $left)
	{
		//Valid filters are always arrays
		if(is_array($filter) === false) {
			throw new Exception('Corrupted filter');
		}

		if(is_string($left) === false) {
			throw new Exception('Invalid parameter type.');
		}

		$code = null;
		$type = $filter['type'];

		//Check if the filter is a single identifier
		if($type === 265) {
			$name = $filter['value'];
		} elseif($type === 350) {
			$name = $filter['name']['value'];
		} else {
			//Unknown filter throw an exception
			throw new Exception('Unknown filter type in '.$filter['file'].' on line '.$filter['line']);
		}

		$funcArguments = null;
		$arguments = null;

		//Resolve arguments
		if(isset($filter['arguments']) === true) {
			$file = $filter['file'];
			$line = $filter['line'];
			$funcArguments = $filter['arguments'];

			//'default' filter is not the first argument, improve this!
			if($name === 'default') {
				$resolvedExpr = array('type' => 364, 'value' => $left, 'file' => $file, 'line' => $line);
				$resolvedParam = array('expr' => $resolvedExpr, 'file' >= $file, 'line' => $line);
				array_unshift($funcArguments, $resolvedParam);
			}

			$arguments = $this->expression($funcArguments);
		} else {
			$arguments = $left;
		}

		//Check if any of the registered extensions provide compilation for this filter
		$extensions = $this->_extensions;
		if(is_array($extensions) === true) {
			$code = $this->fireExtensionEvent('compileFilter', array($name, $arguments, $funcArguments));
			if(is_string($code) === true) {
				return $code;
			}
		}

		//Check if it's a user-defined filter
		$filters = $this->_filters;
		if(is_array($filters) === true) {
			if(isset($filters[$name]) === true) {
				$definition = $filters[$name];

				if(is_string($definition) === true) {
					//The definition is a string
					return $definition.'('.$arguments.')';
				} elseif(is_object($definition) === true &&
					$definition instanceof Closure === true) {
					//The definition is a closure
					return call_user_func_array($definition, array($arguments, $funcArguments));
				} else {
					//Invalid filter definition - throw an exception
					throw new Exception("Invalid definition for user filter '".$name."' in ".$filter['file'].' on line '.$filter['line']);
				}
			}
		}

		switch($name) {
			case 'length':
				return '$this->length('.$arguments.')';
				break;
			case 'e':
				return '$this->escaper->escapeHtml('.$arguments.')';
				break;
			case 'escape':
				return '$this->escaper->esacpeHtml('.$arguments.')';
				break;
			case 'escape_css':
				return '$this->escaper->esacpeCss('.$arguments.')';
				break;
			case 'escape_js':
				return '$this->escaper->escapeJs('.$arguments.')';
				break;
			case 'escape_attr':
				return '$this->escaper->escapeHtmlAttr('.$arguments.')';
				break;
			case 'trim':
				return 'trim('.$arguments.')';
				break;
			case 'left_trim':
				return 'ltrim('.$arguments.')';
				break;
			case 'right_trim':
				return 'rtrim('.$arguments.')';
				break;
			case 'striptags':
				return 'strip_tags('.$arguments.')';
				break;
			case 'url_encode':
				return 'urlencode('.$arguments.')';
				break;
			case 'slashes':
				return 'addslashes('.$arguments.')';
				break;
			case 'stripslashes':
				return 'stripslashes('.$arguments.')';
				break;
			case 'nl2br':
				return 'nl2br('.$arguments.')';
				break;
			case 'keys':
				return 'array_keys('.$arguments.')';
				break;
			case 'join':
				return 'join('.$arguments.')';
				break;
			case 'lowercase':
			case 'lower':
				return 'Phalcon\\Text::lower('.$arguments.')';
				break;
			case 'uppercase':
			case 'upper':
				return 'Phalcon\\Text::upper('.$arguments.')';
				break;
			case 'capitalize':
				return 'ucwords('.$arguments.')';
				break;
			case 'sort':
				return '$this->sort('.$arguments.')';
				break;
			case 'json_encode':
				return 'json_encode('.$arguments.')';
				break;
			case 'json_decode':
				return 'json_decode('.$arguments.')';
				break;
			case 'format':
				return 'sprintf('.$arguments.')';
				break;
			case 'abs':
				return 'abs('.$arguments.')';
				break;
			case 'slice':
				return '$this->slice('.$arguments.')';
				break;
			case 'default':
				return '(empty('.$left.') ? ('.$arguments.') : ('.$left.'))';
				break;
			case 'convert_encoding':
				return '$this->convertEncoding('.$arguments.')';
				break;
		}

		throw new Exception('Unknown filter "'.$name.'" in '.$filter['file'].' on line '.$filter['line']);
	}

	/**
	 * Resolves an expression node in an AST volt tree
	 *
	 * @param array $expr
	 * @return string
	 * @throws Exception
	 */
	public function expression($expr)
	{
		if(is_array($expr) === false) {
			throw new Exception('Corrupted expression');
		}

		$exprCode = null;
		$this->_exprLevel++;

		//Check if any of the registered extensions provide compilation for this
		//expression
		$extensions = $this->_extensions;

		while(true) {
			if(is_array($extensions) === true) {
				$exprCode = $this->fireExtensionEvent('resolveExpression', array($expr));
				if(is_string($exprCode) === true) {
					break;
				}
			}

			if(isset($expr['type']) === false) {
				$items = array();
				foreach($expr as $singleExpr) {
					$singleExprExpr = $singleExpr['expr'];
					$singleExprCode = $this->expression($singleExprExpr);
					if(isset($singleExpr['name']) === true) {
						$items[] = "'".$singleExpr['name']."' => ".$singleExprCode;
					} else {
						$items[] = $singleExprCode;
					}
				}

				$exprCode = implode(', ', $items);
				break;
			}

			$type = $expr['type'];

			//Attribute reading needs special handling
			if($type === 46) {
				$exprCode = $this->attributeReader($expr);
				break;
			}

			//Left part of expression is always resolved
			$leftCode = null;
			if(isset($expr['left']) === true) {
				$left = $expr['left'];
				$leftCode = $this->expression($left);
			}

			//Operator 'is' also needs special handling
			if($type === 311) {
				$rightCode = $expr['right'];
				$exprCode = $this->resolveTest($rightCode, $leftCode);
				break;
			}

			//We don't resolve the right expression for filters
			if($type === 124) {
				$rightCode = $expr['right'];
				$exprCode = $this->resolveFilter($rightCode, $leftCode);
				break;
			}

			//From here, right part of expression is always resolved
			$rightCode = null;
			if(isset($expr['right']) === true) {
				$right = $expr['right'];
				$rightCode = $this->expression($right);
			}

			$exprCode = null;

			switch((int)$type) {
				case 33:
					$exprCode = '!'.$rightCode;
					break;
				case 42:
					$exprCode = $leftCode.' * '.$rightCode;
					break;
				case 43:
					$exprCode = $leftCode.' + '.$rightCode;
					break;
				case 45:
					$exprCode = $leftCode.' - '.$rightCode;
					break;
				case 47:
					$exprCode = $leftCode.' / '.$rightCode;
					break;
				case 37:
					$exprCode = $leftCode.' % '.$rightCode;
					break;
				case 60:
					$exprCode = $leftCode.' < '.$rightCode;
					break;
				case 61:
				case 62:
					$exprCode = $leftCode.' > '.$rightCode;
					break;
				case 126:
					$exprCode = $leftCode.' . '.$rightCode;
					break;
				case 278:
					$exprCode = 'pow('.$leftCode.', '.$rightCode.')';
					break;
				case 360:
					if(isset($expr['left']) === true) {
						$exprCode = 'array('.$leftCode.')';
					} else {
						$exprCode = 'array()';
					}
					break;
				case 258:
				case 259:
				case 364:
					$exprCode = $expr['value'];
					break;
				case 260:
					$exprCode = "'".str_replace("'", "\\'", $expr['value']);
					break;
				case 261:
					$exprCode = 'null';
					break;
				case 262:
					$exprCode = 'false';
					break;
				case 263:
					$exprCode = 'true';
					break;
				case 265:
					$exprCode = '$'.$expr['value'];
					break;
				case 266:
					$exprCode = $leftCode.' && '.$rightCode;
					break;
				case 267:
					$exprCode = $leftCode.' || '.$rightCode;
					break;
				case 270:
					$exprCode = $leftCode.' <= '.$rightCode;
					break;
				case 271:
					$exprCode = $leftCode.' >= '.$rightCode;
					break;
				case 272:
					$exprCode = $leftCode.' == '.$rightCode;
					break;
				case 273:
					$exprCode = $leftCode.' != '.$rightCode;
					break;
				case 274:
					$exprCode = $leftCode.' === '.$rightCode;
					break;
				case 275:
					$exprCode = $leftCode.' !== '.$rightCode;
					break;
				case 276:
					$exprCode = 'range('.$leftCode.', '.$rightCode.')';
					break;
				case 350:
					$exprCode = $this->functionCall($expr);
					break;
				case 356:
					$exprCode = '('.$leftCode.')';
					break;
				case 361:
					$exprCode = $leftCode.'['.$rightCode.']';
					break;
				case 365:
					//Evaluate the start part of the slice
					if(isset($expr['start']) === true) {
						$startCode = $this->expression($expr['start']);
					} else {
						$startCode = 'null';
					}

					//Evaluate the end of the slice
					if(isset($expr['end']) === true) {
						$endCode = $this->expression($expr['end']);
					} else {
						$endCode = 'null';
					}

					$exprCode = '$this->slice('.$leftCode.', '.$startCode.', '.$endCode.')';
					break;
				case 362:
					$exprCode = '!isset('.$leftCode.')';
					break;
				case 363:
					$exprCode = 'isset('.$leftCode.')';
					break;
				case 309:
					$exprCode = '$this->isIncluded('.$leftCode.', '.$rightCode.')';
					break;
				case 369:
					$exprCode = '!$this->isIncluded('.$leftCode.', '.$rightCode.')';
					break;
				case 366:
					$exprCode = '('.$this->expression($expr['ternary']).' ? '.$leftCode.' : '.$rightCode.' )';
					break;
				case 367:
					$exprCode = '-'.$rightCode;
					break;
				case 368:
					$exprCode = '+'.$rightCode;
					break;
				default:
					throw new Exception('Unknown expression '.$type.' in '.$expr['file'].' on line '.$expr['line']);
					break;
			}

			break;
		}

		$this->_exprLevel--;
		return $exprCode;
	}

	/**
	 * Compiles a block of statements
	 *
	 * @param array $statements
	 * @return string|array
	 * @throws Exception
	 */
	protected function _statementListOrExtends($statements)
	{
		if(is_array($statements) === false) {
			return $statements;
		}

		//If all elements in the statement list are arrays, we resolve this as a
		//statementList
		$isStatementList = true;
		if(isset($statement['type']) === false) {
			foreach($statements as $statement) {
				if(is_array($statement) === false) {
					$isStatementList = false;
					break;
				}
			}
		}

		//Resolve the statement list as normal
		if($isStatementList === true) {
			return $this->_statementList($statements);
		}

		//Is an array, but not a statement list?
		return $statements;
	}

	/**
	 * Compiles a 'foreach' intermediate code representation into plain PHP code
	 *
	 * @param array $statement
	 * @param boolean|null $extendsMode
	 * @return string
	 * @throws Exception
	 */
	public function compileForeach($statement, $extendsMode = null)
	{
		if(is_null($extendsMode) === true) {
			$extendsMode = false;
		} elseif(is_bool($extendsMode) === false) {
			throw new Exception('Invalid parameter type.');
		}

		if(is_array($statement) === false ||
			isset($statement['expr']) === false) {
			throw new Exception('Corrupted statement');
		}

		$compilation = '';
		$this->_foreachLevel++;

		$prefix = $this->getUniquePrefix;
		$level = $this->_foreachLevel;

		//prefix_level is used to prefix every temporal veriable
		$prefixLevel = $prefix.$level;

		//Evaluate common expressions
		$expr = $statement['expr'];
		$exprCode = $this->expression($expr);

		//Process the block statements
		$blockStatements = $statement['block_statements'];

		$forElse = false;
		if(is_array($blockStatements) === true) {
			foreach($blockStatements as $bstatement) {
				if(is_array($bstatement) === false) {
					break;
				}

				//Check if the statement is valid
				if(isset($bstatement['type']) === false) {
					break;
				}

				if($bstatement['type'] === 321) {
					$compilation .= '<?php $'.$prefixLevel.'iterated = false; ?>';
					$forElse = $prefixLevel;
					$this->_forElsePointers[$level] = $forElse;
					break;
				}
			}
		}

		//Process statement blocks
		$code = $this->_statementList($blockStatements, $extendsMode);
		$loopContext = $this->_loopPointers;

		//Generate the loop context for the 'foreach'
		if(isset($loopContext[$level]) === true) {
			$compilation .= '<?php $'.$prefixLevel.'iterator = '.$exprCode.'; ';
			$compilation .= '$'.$prefixLevel.'incr = 0; ';
			$compilation .= '$'.$prefixLevel.'loop = new stdClass(); ';
			$compilation .= '$'.$prefixLevel.'loop->length = count($'.$prefixLevel.'iterator); ';
			$compilation .= '$'.$prefixLevel.'loop->index = 1; ';
			$compilation .= '$'.$prefixLevel.'loop->index0 = 1; ';
			$compilation .= '$'.$prefixLevel.'loop->revindex = $'.$prefixLevel.'loop->length; ';
			$compilation .= '$'.$prefixLevel.'loop->revindex0 = $'.$prefixLevel.'loop->length - 1; ?>';

			$iterator = '$'.$prefixLevel.'iterator';
		} else {
			$iterator = $exprCode;
		}

		//Foreach statement
		$variable = $statement['variable'];

		//Check if a 'key' variable needs to be calculated
		if(isset($statement['key']) === true) {
			$key = $statement['key'];
			$compilation .= '<?php foreach ('.$iterator.' as $'.$key.' => $'.$variable.') { ';
		} else {
			$compilation .= '<?php foreach('.$iterator.' as $'.$variable.') { ';
		}

		//Check for an 'if' expr in the block
		if(isset($statement['if_expr']) === true) {
			$ifExpr = $statement['if_expr'];
			$compilation .= 'if ('.$this->expression($ifExpr).') { ?>';
		} else {
			$compilation .= '?>';
		}

		//Generate the loop context inside the cycle
		if(isset($loopContext[$level]) === true) {
			$compilation .= '<?php $'.$prefixLevel.'loop->first = ($'.$prefixLevel.'incr == 0); ';
			$compilation .= '$'.$prefixLevel.'loop->index = $'.$prefixLevel.'incr + 1; ';
			$compilation .= '$'.$prefixLevel.'loop->index0 = $'.$prefixLevel.'incr; ';
			$compilation .= '$'.$prefixLevel.'loop->revindex = $'.$prefixLevel.'loop->length - $'.$prefixLevel.'incr; ';
			$compilation .= '$'.$prefixLevel.'loop->revindex0 = $'.$prefixLevel.'loop->length - ($'.$prefixLevel.'incr + 1); ';
			$compilation .= '$'.$prefixLevel.'loop->last = ($'.$prefixLevel.'incr == ($'.$prefixLevel.'loop->length - 1)); ?>';
		}

		//Update the forelse var if it's iterated at least one time
		if(is_string($forElse) === true) {
			$compilation .= '<?php $'.$forElse.'iterated = true; ?>';
		}

		//Append the internal block compilation
		$compilation .= $code;
		if(isset($statement['if_expr']) === true) {
			$compilation .= '<?php } ?>';
		}

		if(is_string($forElse) === true) {
			$compilation .= '<?php } ?>';
		} else {
			if(isset($loopContext[$level]) === true) {
				$compilation .= '<?php $'.$prefixLevel.'incr++; } ?>';
			} else {
				$compilation .= '<?php } ?>';
			}
		}

		$this->_foreachLevel--;

		return $compilation;
	}

	/**
	 * Generates a 'forelse' PHP code
	 *
	 * @return string|null
	 */
	public function compileForElse()
	{
		$level = $this->_foreachLevel;
		$forElsePointers = $this->_forElsePointers;

		if(isset($forElsePointers[$level]) === true) {
			$prefix = $forElsePointers.$level;
			$loopContext = $this->_loopPointers;

			if(isset($loopContext[$level]) === true) {
				$compilation = '<?php $'.$prefix.'incr++; } if (!$'.$prefix.'iterated) { ?>';
			} else {
				$compilation = '<?php } if (!$'.$prefix.'iterated) { ?>';
			}

			return $compilation;
		}
	}

	/**
	 * Compiles a 'if' statement returning PHP code
	 *
	 * @param array $statement
	 * @param boolean|null $extendsMode
	 * @return string
	 * @throws Exception
	 */
	public function compileIf($statement, $extendsMode = null)
	{
		if(is_null($extendsMode) === true) {
			$extendsMode = false;
		} elseif(is_bool($extendsMode) === false) {
			throw new Exception('Invalid parameter type.');
		}

		$compilation = '';

		//A valid expression is required
		if(is_array($statement) === false ||
			isset($statement['expr']) === false) {
			throw new Exception('Corrupted statement');
		}

		$expr = $statement['expr'];
		$exprCode = $this->expression($expr);

		//'If' statement
		$compilation .= '<?php if ('.$exprCode.') { ?>';
		$blockStatements = $statement['true_statements'];

		//Process statements in the 'true' block
		$compilation .= $this->_statementList($blockStatements, $extendsMode);

		//Check for a 'else'/'elseif' block
		if(isset($statement['false_statements']) === true) {
			$compilation .= '<?php } else { ?>';

			//Process statements in the 'false' block
			$blockStatements = $statement['false_statements'];
			$compilation .= $this->_statementList($blockStatements, $extendsMode);
		}

		return $compilation.'<?php } ?>';
	}

	/**
	 * Compiles a 'elseif' statement returning PHP code
	 *
	 * @param array $statement
	 * @return string
	 * @throws Exception
	 */
	public function compileElseIf($statement)
	{
		if(is_array($statement) === false ||
			isset($statement['expr']) === false) {
			throw new Exception('Corrupted statement');
		}

		//'elseif' statement
		return '<?php } elseif ('.$this->expression($statement['expr']).') } ?>';
	}

	/**
	 * Compiles a 'cache' statement returning PHP code
	 *
	 * @param array $statement
	 * @param boolean|null $extendsMode
	 * @return string
	 * @throws Exception
	 */
	public function compileCache($statement, $extendsMode = null)
	{
		if(is_null($extendsMode) === true) {
			$extendsMode = false;
		} elseif(is_bool($extendsMode) === false) {
			throw new Exception('Invalid parameter type.');
		}

		if(is_array($statement) === false ||
			isset($statement['expr']) === false) {
			throw new Exception('Corrupted statement');
		}

		$compilation = '';

		$expr = $statement['expr'];
		$exprCode = $this->expression($expr);

		//Cache statement
		$compilation .= '<?php $_cache['.$exprCode."] = $this->di->get('viewCache'); ";

		if(isset($statement['lifetime']) === true) {
			$lifetime = $statement['lifetime'];
			$compilation .= '$_cacheKey['.$exprCode.']';
			$compilation .= ' = $_cache['.$exprCode.']->start('.$exprCode.', '.$lifetime.'); ';
		} else {
			$compilation .= '$_cacheKey['.$exprCode.'] = $_cache['.$exprCode.']->start('.$exprCode.'); ';
		}

		$compilation .= 'if ($_cacheKey['.$exprCode.'] === null) { ?>';

		//Get the code in the block
		$blockStatements = $statement['block_statements'];
		$compilation .= $this->_statementList($blockStatements, $extendsMode);

		//Check if the cache has a lifetime
		if(isset($statement['lifetime']) === true) {
			//$lifetime exists from the code block starting @1531
			$compilation .= '<?php $_cache['.$exprCode.']->save('.$exprCode.', null, '.$lifetime.'); ';
			$compilation .= '} else { echo $_cacheKey['.$exprCode.']; } ?>';
		} else {
			$compilation .= '<?php $_cache['.$exprCode.']->save('.$exprCode.'); } else { echo $_cacheKey['.$exprCode.']; } ?>';
		}

		return $compilation;
	}

	/**
	 * Compiles a '{{' '}}' statement returning PHP code
	 *
	 * @param array $statement
	 * @return string
	 * @throws Exception
	 */
	public function compileEcho($statement)
	{
		//A valid expression is required
		if(is_array($statement) === false ||
			isset($statement['expr']) === false) {
			throw new Exception('Corrupted statement');
		}

		$compilation = '';

		//Evaluate common expressions
		$expr = $statement['expr'];
		$exprCode = $this->expressions($expr);
		$exprType = $expr['type'];

		if($exprType === 350) {
			$name = $expr['name'];
			$nameType = $name['type'];
			if($nameType === 265) {
				$nameValue = $name['value'];

				//super() is a function however the return of this function must be output as it
				//is
				if($nameValue === 'super') {
					return $exprCode;
				}
			}
		}

		//Echo statement
		if($this->_autoescape === true) {
			return '<?php echo $this->escaper->escapeHtml('.$exprCode.'); ?>';
		} else {
			return '<?php echo '.$exprCode.'; ?>';
		}
	}

	/**
	 * Compiles a 'include' statement returning PHP code
	 *
	 * @param array $statement
	 * @return string
	 * @throws Exception
	 */
	public function compileInclude($statement)
	{
		if(is_array($statement) === false ||
			isset($statement['path']) === false) {
			throw new Exception('Corrupted statement');
		}

		$compilation = null;
		$pathExpr = $statement['path'];
		$exprType = $pathExpr['type'];

		//If the path is a string try to make a static compilation
		if($exprType === 260) {
			//Static compilation cannot be performed if the user passed extra parameters
			if(isset($statement['params']) === false) {
				//Get the static path
				$path = $pathExpr['value'];

				$view = $this->_view;
				if(is_object($view) === true) {
					$viewsDir = $view->getViewsDir();
					$finalPath = $viewsDir.$path;
				} else {
					$finalPath = $path;
				}

				$extended = false;

				//Clone the original compiler
				$subCompiler = clone $this;

				//Perform a subcompilation of the included file
				$compilation = $subCompiler->compile($finalPath, $extended);

				//If the compilation doesn't return anything we include the compiled path
				if(is_null($compilation) === true) {

					//Use file_get_contents to respect the openbase_dir directive
					$compilation = file_get_contents($subCompiler->getCompiledTemplatePath());
				}

				return $compilation;
			}
		}

		//Resolve the path's expression
		$path = $this->expression($pathExpr);
		if(isset($statement['params']) === false) {
			return '<?php $this->partial('.$path.'); ?>';
		}

		$exprParams = $statement['params'];
		$params = $this->expression($exprParams);
		return '<?php $this->partial('.$path.', '.$params.'); ?>';
	}

	/**
	 * Compiles a 'set' statement returning PHP code
	 *
	 * @param array $statement
	 * @return string
	 * @throws Exception
	 */
	public function compileSet($statement)
	{
		//A valid assignment list is required
		if(is_array($statement) === false ||
			isset($statement['assignments']) === false) {
			throw new Exception('Corrupted statement');
		}

		$compilation = '<?php';

		//A single set can have several assignments
		$assignments = $statement['assignments'];
		foreach($assignments as $assignment) {
			$expr = $assignment['expr'];
			$exprCode = $this->expression($expr);

			//Set statement
			$variable = $assignment['variable'];

			//Generate the right operator
			switch((int)$assignment['op']) {
				case 281:
					$compilation .= ' $'.$variable.' += '.$exprCode.';';
					break;
				case 282:
					$compilation .= ' $'.$variable.' -= '.$exprCode.';';
					break;
				case 283:
					$compilation .= ' $'.$variable.' *= '.$exprCode.';';
					break;
				case 284:
					$compilation .= ' $'.$variable.' /= '.$exprCode.';';
					break;
				default:
					$compilation .= ' $'.$variable.' = '.$exprCode.';';
					break;
			}
		}

		return $compilation.' ?>';
	}

	/**
	 * Compiles a 'do' statement returning PHP code
	 *
	 * @param array $statement
	 * @return string
	 * @throws Exception
	 */
	public function compileDo($statement)
	{
		if(is_array($statement) === false ||
			isset($statement['expr']) === false) {
			throw new Exception('Corrupted statement');
		}

		$expr = $statement['expr'];
		$exprCode = $this->expression($expr);

		//'Do' statement
		return '<?php '.$exprCode.'; ?>';
	}

	/**
	 * Compiles a 'return' statement returning PHP code
	 *
	 * @param array $statement
	 * @return string
	 * @throws Exception
	 */
	public function compileReturn($statement)
	{
		if(is_array($statement) === false ||
			isset($statement['expr']) === false) {
			throw new Exception('Corrupted statement');
		}

		return '<?php return '.$this->expression($statement['expr']).'; ?>';
	}

	/**
	 * Compiles a 'autoescape' statement returning PHP code
	 *
	 * @param array $statement
	 * @param boolean $extendsMode
	 * @return string
	 * @throws Exception
	 */
	public function compileAutoEscape($statement, $extendsMode)
	{
		if(is_array($statement) === false ||
			isset($statement['enable']) === fasle) {
			throw new Exception('Corrupted statement');
		}

		if(is_bool($extendsMode) === false) {
			throw new Exception('Invalid parameter type.');
		}

		//'autoescape' mode
		$oldAutoescape = $this->_autoescape;
		$this->_autoescape = $statement['enable'];

		$compilation = $this->_statementList($statement['block_statements'], $extendsMode);
		$this->_autoescape = $oldAutoescape;

		return $compilation;
	}

	/**
	 * Compiles macros
	 *
	 * @param array $statement
	 * @param boolean $extendsMode
	 * @return string
	 * @throws Exception
	 */
	public function compileMacro($statement, $extendsMode)
	{
		if(is_array($statement) === false ||
			isset($statement['name']) === false) {
			throw new Exception('Corrupted statement');
		}

		$name = $statement['name'];
		$macros = $this->_macros;

		//Check if the macro is already defined
		if(isset($macros[$name]) === true) {
			throw new Exception('Macro "'.$name.'" is already defined');
		} else {
			//Register the macro
			$this->_macros[$name] = $name;
		}

		$code = '<?php function vmacro_';
		if(isset($statement['parameters']) === false) {
			$code .= $name.'() { ?>';
		} else {
			//Parameters are always received as an array
			$code .= $name.'($__p) {';
			$parameters = $statement['parameters'];
			foreach($parameters as $position => $parameters) {
				$variableName = $parameter['variable'];
				$code .= 'if (isset($__p['.$position.'])) { ';
				$code .= '$'.$variableName.' = $__p['.$position.'];';
				$code .= ' } else { ';
				$code .= "if (isset(\$__p['".$variable."'])) { ";
				$code .= '$'.$variableName." = \$__p['".$variable."'];";
				$code .= ' } else { ';
				$code .= 'throw new \\Phalcon\\Mvc\\View\\Exception("Macro '.$name.' was called without parameter: '.$variableName.'"); ';
				$code .= ' } } ';
			}

			$code .= ' ?>';
		}

		//Block statements are allowed
		if(isset($statement['block_statements']) === true) {
			//Get block statements
			$blockStatements = $statement['block_statements'];

			$code .= $this->_statementList($blockStatements, $extendsMode).'<?php } ?>';
		} else {
			$code .= '<?php } ?>';
		}

		return $code;
	}

	/**
	 * Compiles calls to macros
	 *
	 * @param array $statement
	 * @param boolean $extendsMode
	 * @return null
	 */
	public function compileCall($statement, $extendsMode)
	{

	}

	/**
	 * Traverses a statement list compiling each of its nodes
	 *
	 * @param array $statements
	 * @param boolean|null $extendsMode
	 * @return string
	 * @throws Exception
	 */
	protected function _statementList($statements, $extendsMode)
	{
		if(is_null($extendsMode) === true) {
			$extendsMode = false;
		} elseif(is_bool($extendsMode) === false) {
			throw new Exception('Invalid parameter type.');
		}

		if(is_array($statements) === false) {
			throw new Exception('Invalid parameter type.');
		}

		if(count($statements) === 0) {
			return '';
		}

		//Increase the statement recursion level in extends mode
		$extended = $this->_extended;
		$extensions = $this->_extensions;
		$compilation = '';

		$blockMode = ($extended == true || $extendsMode == true);
		if($blockMode === true) {
			$this->_blockLevel++;
		}

		$this->_level++;

		foreach($statements as $statement) {
			//All statements must be arrays
			if(is_array($statement) === false) {
				throw new Exception('Corrupted statement');
			}

			//Check if the statement is valid
			if(isset($statement['type']) === false) {
				throw new Exception('Invalid statement in '.$statement['file'].' on line '.$statement['line']);
			}

			//Check if extensions have implemented custom compilations for this statement
			if(is_array($extensions) === true) {
				$tempCompilation = $this->fireExtensionEvent('compileStatement', array($statement));
				if(is_string($tempCompilation) === true) {
					$compilation .= $tempCompilation;
					continue;
				}
			}

			//Compile the statement according to the statement's type
			switch((int)$statement['type']) {
				case 357:
					//Raw output statement
					$compilation .= $statement['value'];
					break;
				case 300:
					$compilation .= $this->compileIf($statement, $extendsMode);
					break;
				case 302:
					$compilation .= $this->compileElseIf($statement);
					break;
				case 304:
					$compilation .= $this->compileForeach($statement, $extendsMode);
					break;
				case 306:
					$compilation .= $this->compileSet($statement);
					break;
				case 359:
					$compilation .= $this->compileEcho($statement);
					break;
				case 307:
					//Block statement
					$blockName = $statement['name'];
					if(isset($statement['block_statements']) === true) {
						$blockStatements = $statement['block_statements'];
					} else {
						$blockStatements = null;
					}

					$blocks = $this->_blocks;
					if($blockMode === true) {
						if(is_array($blocks) === false) {
							$blocks = array();
						}

						//Create an unnamed block
						if(is_null($compilation) === false) {
							$blocks[] = $compilation;
							$compilation = null;
						}

						//In extends mode we add the block statements to the block
						$blocks[$blockName] = $blockStatements;
						$this->_blocks = $blocks;
					} elseif(is_array($blockStatements) === true) {
						$compilation .= $this->_statementList($blockStatements, $extendsMode);
					}
					break;
				case 310:
					//Extends statement
					$path = $statement['path'];
					$view = $this->_view;

					if(is_object($view) === true) {
						$viewsDir = $view->getViewsDir();
						$finalPath = $viewsDir.$path;
					} else {
						$finalPath = $path;
					}

					$extended = true;

					//Perform a subcompilation of the extended file
					$subCompiler = clone $this;

					$tempCompilation = $subCompiler->compile($finalPath, $extended);

					//If the compilation doesn't return anything we include the compiled path
					if(is_null($tempCompilation) === true) {
						$compiledPath = $subCompiler->getCompiledTemplatePath();
						$tempCompilation = file_get_contents($compiledPath);
					}

					$this->_extends = true;
					$this->_extendedBlocks = $tempCompilation;
					$blockMode = $extended;
					break;
				case 313:
					$compilation .= $this->compileInclude($statement);
					break;
				case 314:
					$compilation .= $this->compileCache($statement, $extendsMode);
					break;
				case 316:
					$compilation .= $this->compileDo($statement);
					break;
				case 327:
					$compilation .= $this->compileReturn($statement);
					break;
				case 317:
					$compilation .= $this->compileAutoEscape($statement, $extendsMode);
					break;
				case 319:
					$compilation .= '<?php continue; ?>';
					break;
				case 320:
					$compilation .= '<?php break; ?>';
					break;
				case 321:
					$compilation .= $this->compileForElse();
					break;
				case 322:
					$compilation .= $this->compileMacro($statement, $extendsMode);
					break;
				case 325:
					$compilation .= $this->compileCall($statement, $extendsMode);
					break;
				case 358:
					//Empty statement
					break;
				default:
					throw new Exception('Unknown statement '.$type.' in '.$statement['file'].' on line '.$statement['line']);
					break;
			}
		}

		//Reduce the statment level nesting
		if($blockMode === true) {
			if($this->_blockLevel === 1 && is_null($compilation) === false) {
				$this->_blocks[] = $compilation;
			}

			$this->_blockLevel--;
		}

		$this->_level--;

		return $compilation;
	}

	/**
	 * Compiles a Volt source code returning a PHP plain version
	 *
	 * @param string $viewCode
	 * @param boolean|null $extendsMode
	 * @return string
	 * @throws Exception
	 */
	protected function _compileSource($viewCode, $extendsMode = null)
	{
		if(is_null($extendsMode) === true) {
			$extendsMode = false;
		} elseif(is_bool($extendsMode) === false) {
			throw new Exception('Invalid parameter type.');
		}

		if(is_string($viewCode) === false) {
			throw new Exception('Invalid parameter type.');
		}

		$currentPath = $this->_currentPath;

		$parser = new Parser($viewCode, $currentPath);
		$intermediate = $parser->getIntermediate();

		//The parser must return a valid array
		if(is_array($intermediate) === true) {
			$compilation = $this->_statementList($intermediate, $extendsMode);

			//Check if the template is extending another
			$extended = $this->_extended;
			if($extended === true) {
				//Multiple inheritance is allowed
				if($extendsMode === true) {
					$finalCompilation = array();
				} else {
					$finalCompilation = null;
				}

				$blocks = $this->_blocks;
				$extendedBlocks = $this->_extendedBlocks;
				foreach($extendedBlocks as $name => $block) {
					//If name is a string then it is a block name
					if(is_string($name) === true) {
						if(is_array($block) === true) {
							if(isset($blocks[$name]) === true) {
								//The block is set in the local template
								$localBlock = $blocks[$name];
								$this->_currentBlock = $name;
								$blockCompilation = $this->_statementList($localBlock);
							} else {
								//The block is not set local only in the extended template
								$blockCompilation = $this->_statementList($block);
							}
						} else {
							if(isset($blocks[$name]) === true) {
								//The block is set in the local template
								$localBlock = $blocks[$name];
								$this->_currentBlock = $name;
								$blockCompilation = $this->_statementList($localBlock);
							} else {
								$blockCompilation = $block;
							}
						}

						if($extendsMode === true) {
							$finalCompilation[$name] = $blockCompilation;
						} else {
							$finalCompilation .= $blockCompilation;
						}
					} else {
						//Here the block is already compiled text
						if($extendsMode === true) {
							$finalCompilation[] = $block;
						} else {
							$finalCompilation .= $block;
						}
					}
				}

				return $finalCompilation;
			}

			if($extendsMode === true) {
				//In extends mode we return the template blocks instead of the compilation
				return $this->_blocks;
			}

			return $compilation;
		}

		throw new Exception('Invalid intermediate representation');
	}

	/**
	 * Compiles a template into a string
	 *
	 *<code>
	 * echo $compiler->compileString('{{ "hello world" }}');
	 *</code>
	 *
	 * @param string $viewCode
	 * @param boolean|null $extendsMode
	 * @return string
	 * @throws Exception
	 */
	public function compileString($viewCode, $extendsMode = null)
	{
		if(is_null($extendsMode) === true) {
			$extendsMode = false;
		} elseif(is_bool($extendsMode) === false) {
			throw new Exception('Invalid parameter type.');
		}

		if(is_string($viewCode) === false) {
			throw new Exception('The code must be string');
		}

		$this->_currentPath = 'eval code';
		return $this->_compileSource($viewCode, $extendsMode);
	}

	/**
	 * Compiles a template into a file forcing the destination path
	 *
	 *<code>
	 *	$compiler->compile('views/layouts/main.volt', 'views/layouts/main.volt.php');
	 *</code>
	 *
	 * @param string $path
	 * @param string $compiledPath
	 * @param boolean|null $extendsMode
	 * @return string|array
	 * @throws Exception
	 */
	public function compileFile($path, $compiledPath, $extendsMode = null)
	{
		if(is_string($path) === false ||
			is_string($compiledPath) === false) {
			throw new Exception('Invalid parameter type.');
		}

		if(is_null($extendsMode) === true) {
			$extendsMode = false;
		} elseif(is_bool($extendsMode) === false) {
			throw new Exception('Invalid parameter type.');
		}

		if($path === $extendsMode) {
			throw new Exception('Template path and compilation template path cannot be the same');
		}

		//Check if the template exists
		if(file_exists($path) === false) {
			throw new Exception('Template file '.$path.' does not exist');
		}

		//Always use file_get_contents instead of reading the file directly, this respects the
		//open_basedir directive
		$viewCode = file_get_contents($path);
		if($viewCode === false) {
			throw new Exception('Template file '.$path.' could not be opened');
		}

		$this->_currentPath = $path;
		$compilation = $this->_compileSource($viewCode, $extendsMode);

		//We store the file serialized if it's an array of blocks
		if(is_array($compilation) === true) {
			$finalCompilation = serialize($compilation);
		} else {
			$finalCompilation = $compilation;
		}

		//Always use file_put_contents to write files instead of writing the files directly,
		//this respects the open_basedir directive
		if(file_put_contents($compiledPath, $finalCompilation) === false) {
			throw new Exception("Volt directly can't be written");
		}

		return $compilation;
	}

	/**
	 * Replaces directory separators by the virtual separator
	 * 
	 * @param string $path
	 * @param string $virualSeparator
	 * @return string
	*/
	private static function prepareVirtualPath($path, $virtualSeparator)
	{
		if(is_string($path) === false ||
			is_string($virtualSeparator) === false) {
			if(is_string($path) === true) {
				return $path;
			} else {
				return '';
			}
		}

		$virtualStr = '';
		$plen = strlen($path);
		for($i = 0; $i < $plen; ++$i) {
			$ch = $path[$i];
			if($ch === "\0") {
				break;
			}

			if($ch === '/' || $ch === '\\' || $ch === ':' || ctype_print($ch) === false) {
				$virtualStr .= $virtualSeparator;
			} else {
				$virtualStr .= strtolower($ch);
			}
		}

		return $virtualStr;
	}

	/**
	 * Compares two file paths returning true if the first mtime is 
	 * greater or equal than the second
	 * 
	 * @param string $filename1
	 * @param string $filename2
	 * @return boolean
	 * @throws Exception
	*/
	private static function compareMtime($filename1, $filename2) {
		if(is_string($filename1) === false || is_string($filename2) === false) {
			throw new Exception('Invalid arguments supplied for compare_mtime()');
		}

		$mtime1 = filemtime($filename1);
		$mtime2 = filemtime($filename2);

		if($mtime1 === false) {
			throw new Exception('mstat failed for '.$filename1);
		}

		if($mtime2 === false) {
			throw new Exception('mstat failed for '.$filename2);	
		}

		return ($mtime1 >= $mtime2);
	}

	/**
	 * Compiles a template into a file applying the compiler options
	 * This method does not return the compiled path if the template was not compiled
	 *
	 *<code>
	 *	$compiler->compile('views/layouts/main.volt');
	 *	require $compiler->getCompiledTemplatePath();
	 *</code>
	 *
	 * @param string $templatePath
	 * @param boolean|null $extendsMode
	 * @return string|array
	 */
	public function compile($templatePath, $extendsMode = null)
	{
		if(is_string($templatePath) === false) {
			throw new Exception('Invalid parameter type.');
		}

		if(is_null($extendsMode) === true) {
			$extendsMode = false;
		} elseif(is_bool($extendsMode) === false) {
			throw new Exception('Invalid parameter type.');
		}

		//Re-initialize some properties already initialized when the object is cloned
		$this->_extended = false;
		$this->_extendedBlocks = false;
		$this->_blocks = null;
		$this->_level = 0;
		$this->_foreachLevel = 0;
		$this->_blockLevel = 0;
		$this->_exprLevel = 0;

		$stat = true;
		$compileAlways = false;
		$compiledPath = '';
		$prefix = null;
		$compiledSeparator = '%%';
		$compiledExtension = '.php';
		$compilation = null;

		$options = $this->_options;
		if(is_array($options) === true) {
			//This makes that templates will always be compiled
			if(isset($options['compileAlways']) === true) {
				$compileAlways = $options['compileAlways'];
				if(is_bool($compileAlways) === false) {
					throw new Exception('compileAlways must be a bool value');
				}
			}

			//Prefix is prepended to the template name
			if(isset($options['prefix']) === true) {
				$prefix = $options['prefix'];
				if(is_string($prefix) === false) {
					throw new Exception('prefix must be a string');
				}
			}

			//Compiled path is a directory where the compiled templates will be located
			if(isset($options['compiledPath']) === true) {
				$compiledPath = $options['compiledPath'];
				if(is_string($compiledPath) === false &&
					is_object($compiledPath) === false) {
					throw new Exception('compiledPath must be a string or a closure');
				}
			}

			//There is no compiled separator by default
			if(isset($options['compiledSeparator']) === true) {
				$compiledSeparator = $options['compiledSeparator'];
				if(is_string($compiledSeparator) === false) {
					throw new Exception('compiledSeparator must be a string');
				}
			}

			//By default the compile extension is .php
			if(isset($options['compiledExtension']) === true) {
				$compiledExtension = $option['compiledExtension'];
				if(is_string($compiledExtension) === false) {
					throw new Exception('compiledExtension must be a string');
				}
			}

			//Stat option assumes the compilation of the file
			if(isset($options['stat']) === true) {
				$stat = $options['stat'];
			}
		}

		if(is_string($compiledPath) === true) {
			//Calculate the template realpath's
			if(empty($compiledPath) === false) {
				$realTemplatePath = realpath($templatePath);

				//Create the virtual path replacing the directory separator by the compiled
				//separator
				$templateSepPath = self::prepareVirtualPath($realTemplatePath, $compiledSeparator);
			} else {
				$templateSepPath = $templatePath;
			}

			//In extends mode we add an additional 'e' suffix to the file
			if($extendsMode === true) {
				$compiledTemplatePath = $compiledPath.$prefix.$templateSepPath.$compiledSeparator.'e'.$compiledSeparator.$compiledExtension;
			} else {
				$compiledTemplatePath = $compiledPath.$prefix.$templateSepPath.$compiledExtension;
			}
		} else {
			//A closure can dynamically compile the path
			if(is_object($compiledPath) === true) {
				if($compiledPath instanceof Closure === true) {
					$compiledTemplatePath = call_user_func_array($compiledPath, array($templatePath, $options, $extendsMode));

					//The closure must return a valid path
					if(is_string($compiledTemplatePath) === false) {
						throw new Exception("compiledPath closure didn't return a valid string");
					}
				} else {
					throw new Exception('compiledPath must be a string or a closure');
				}
			}
			//@note no exception
		}

		//Use the real path to avoid collisions
		$realCompiledPath = $compiledTemplatePath;

		if($compileAlways === true) {
			//Compile always must be used only in the development stage
			$compilation = $this->compileFile($templatePath, $realCompiledPath, $extendsMode);
		} else {
			if($stat === true) {
				if(file_exists($compiledTemplatePath) === true) {
					//Compare modification timestamps to check if the file needs to be recompiled
					if(self::compareMtime($templatePath, $realCompiledPath) === true) {
						$compilation = $this->compileFile($templatePath, $realCompiledPath, $extendsMode);
					} else {
						if($extendsMode === true) {
							//In extends mode we read the file that must contain a serialized array of blocks
							$blocksCode = file_get_contents($realCompiledPath);
							if($blocksCode === false) {
								throw new Exception('Extends compilation file '.$realCompiledPath.' could not be opened');
							}

							//Unserialize the array blocks code
							if($blocksCode == true) {
								$compilation = unserialize($blocksCode);
							} else {
								$compilation = array();
							}
						}
					}
				} else {
					//The file doesn't exists so we compile the php version for the first time
					$compilation = $this->compileFile($templatePath, $realCompiledPath, $extendsMode);
				}
			} else {
				//Stat is off but the compiled file doesn't exists
				if(file_exists($realCompiledPath) === false) {
					throw new Exception('Compiled template file '.$realCompiledPath.' does not exist');
				}
			}
		}

		$this->_compiledTemplatePath = $realCompiledPath;
		return $compilation;
	}

	/**
	 * Returns the path that is currently being compiled
	 *
	 * @return string|null
	 */
	public function getTemplatePath()
	{
		return $this->_currentPath;
	}

	/**
	 * Returns the path to the last compiled template
	 *
	 * @return string|null
	 */
	public function getCompiledTemplatePath()
	{
		return $this->_compiledTemplatePath;
	}

	/**
	 * Parses a Volt template returning its intermediate representation
	 *
	 *<code>
	 *	print_r($compiler->parse('{{ 3 + 2 }}'));
	 *</code>
	 *
	 * @param string $viewCode
	 * @return array
	 * @throws Exception
	 */
	public function parse($viewCode)
	{
		if(is_string($viewCode) === false) {
			throw new Exception('Invalid parameter type.');
		}

		$parser = new Parser($viewCode, 'eval code');
		return $parser->getIntermediate();
	}
}