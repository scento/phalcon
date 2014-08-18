<?php
/**
 * Volt
 *
 * @author Andres Gutierrez <andres@phalconphp.com>
 * @author Eduar Carvajal <eduar@phalconphp.com>
 * @author Wenzel Pünter <wenzel@phelix.me>
 * @version 1.2.6
 * @package Phalcon
*/
namespace Phalcon\Mvc\View\Engine;

use \Phalcon\Mvc\View\Engine,
	\Phalcon\Mvc\View\EngineInterface,
	\Phalcon\Mvc\View\Exception,
	\Phalcon\DI\InjectionAwareInterface,
	\Phalcon\Events\EventsAwareInterface,
	\Phalcon\Mvc\View\Engine\Volt\Compiler,
	\Traversable;

/**
 * Phalcon\Mvc\View\Engine\Volt
 *
 * Designer friendly and fast template engine for PHP written in C
 * 
 * @see https://github.com/phalcon/cphalcon/blob/1.2.6/ext/mvc/view/engine/volt.c
 */
class Volt extends Engine implements InjectionAwareInterface, EventsAwareInterface, EngineInterface
{
	/**
	 * Options
	 * 
	 * @var null|array
	 * @access protected
	*/
	protected $_options;

	/**
	 * Compiler
	 * 
	 * @var null|\Phalcon\Mvc\View\Engine\Volt\Compiler
	 * @access protected
	*/
	protected $_compiler;

	/**
	 * Set Volt's options
	 *
	 * @param array $options
	 * @throws Exception
	 */
	public function setOptions($options)
	{
		if(is_array($options) === false) {
			throw new Exception('Options parameter must be an array');
		}

		$this->_options = $options;
	}

	/**
	 * Return Volt's options
	 *
	 * @return array|null
	 */
	public function getOptions()
	{
		return $this->_options;
	}

	/**
	 * Returns the Volt's compiler
	 *
	 * @return \Phalcon\Mvc\View\Engine\Volt\Compiler
	 */
	public function getCompiler()
	{
		if(is_object($this->_compiler) === false) {
			$compiler = new Compiler();

			//Pass the IoC to the compiler only if it's an object
			if(is_object($this->_dependencyInjector) === true) {
				$compiler->setDi($this->_dependencyInjector);
			}

			//Pass the options to the compiler only if they're an array
			if(is_array($this->_options) === true) {
				$compiler->setOptions($this->_options);
			}

			$this->_compiler = $compiler;
		}

		return $this->_compiler;
	}

	/**
	 * Renders a view using the template engine
	 *
	 * @param string $templatePath
	 * @param array $params
	 * @param boolean|null $mustClean
	 * @throws Exception
	 */
	public function render($templatePath, $params, $mustClean = null)
	{
		if(is_string($templatePath) === false ||
			(is_array($params) === false && is_null($params) === false)) {
			throw new Exception('Invalid parameter type.');
		}

		if(is_null($mustClean) === true) {
			$mustClean = false;
		} elseif(is_bool($mustClean) === false) {
			throw new Exception('Invalid parameter type.');
		}

		//The compilation process is done by Phalcon\Mvc\View\Engine\Volt\Compiler
		$compiler = $this->getCompiler();
		$compiler->compile($templatePath);
		$compiled_template_path = $compiler->getCompiledTemplatePath();

		//Export the variables
		if(is_array($params) === true) {
			foreach($params as $key => $value) {
				$$key = $value;
			}
		}

		require($compiled_template_path);

		if($mustClean === true) {
			$this->_view->setContent(ob_get_contents());
		}
	}

	/**
	 * Length filter. If an array/object is passed a count is performed otherwise a strlen/mb_strlen
	 *
	 * @param mixed $item
	 * @return int
	 */
	public function length($item)
	{
		if(is_object($item) === true ||
			is_array($item) === true) {
			return count($item);
		} else {
			if(function_exists('mb_strlen') === true) {
				return mb_strlen($item);
			} else {
				return strlen($item);
			}
		}
	}

	/**
	 * Checks if the needle is included in the haystack
	 *
	 * @param mixed $needle
	 * @param string|array $haystack
	 * @return boolean
	 * @throws Exception
	 */
	public function isIncluded($needle, $haystack)
	{
		if(is_array($haystack) === true) {
			return in_array($needle, $haystack);
		} elseif(is_string($haystack) === true) {
			//@note: Additional parameter validation
			if(is_scalar($needle) === false) {
				throw new Exception('Invalid parameter type.');
			}

			if(function_exists('mb_strpos') === true) {
				return mb_strpos($haystack, $needle);
			}

			return strpos($haystack, $needle);
		}

		throw new Exception('Invlid haystack');
	}

	/**
	 * Performs a string conversion
	 *
	 * @param string $text
	 * @param string $from
	 * @param string $to
	 * @return string
	 * @throws Exception
	 */
	public function convertEncoding($text, $from, $to)
	{
		if(is_string($text) === false ||
			is_string($from) === false ||
			is_string($to) === false) {
			throw new Exception('Invalid parameter type.');
		}

		//Try to use utf8_encode if conversion is 'latin1' to 'utf8'
		if($from === 'latin1' && $to === 'utf8') {
			return utf8_encode($text);
		}

		//Try to use utf8_decode if conversion is 'utf8' to 'latin1'
		if($to === 'latin1' && $from === 'utf8') {
			return utf8_decode($text);
		}

		//Fallback to mb_convert_encoding
		if(function_exists('mb_convert_encoding') === true) {
			return mb_convert_encoding($text, $to, $from); //@note we changed $from and $to, since they were in the wrong order
		}

		//Fallback to iconv
		if(function_exists('iconv') === true) {
			return iconv($from, $to, $text);
		}

		//There are not enough extensions available
		throw new Exception("Any of 'mbstring' or 'iconv' is required to perform the charset conversion");
	}

	/**
	 * Extracts a slice from a string/array/traversable object value
	 *
	 * @param string|array|\Traversable $value
	 * @param int $start
	 * @param null|int $end
	 * @return array|string
	 * @throws Exception
	 */
	public function slice($value, $start, $end = null)
	{
		if(is_int($start) === false) {
			throw new Exception('Invalid parameter type.');
		}

		if(is_null($end) === false &&
			is_int($end) === false) {
			throw new Exception('Invalid parameter type.');
		}

		//Objects must implement a Traversable interface
		if(is_object($value) === true &&
			$value instanceof Traversable) {
			$slice = array();

			if(is_null($end) === true) {
				$end = count($value);
			}

			$position = 0;
			$value->rewind();

			while($value->valid() === true) {
				if($position >= $start && $position <= $end) {
					$slice[] = $value->current();
				}

				$value->next();
				$position++;
			}

			return $slice;
		} elseif(is_array($value) === true) {
			//Calculating the slice length
			if(is_null($end) === false) {
				$length = ($end - $start) + 1;
			} else {
				$length = null;
			}

			//Use array_slice on arrays
			return array_slice($value, $start, $length);

		} elseif(is_string($value) === true) {
			//Calculating the slice length
			if(is_null($end) === false) {
				$length = ($end - $start) + 1;
			} else {
				$length = null;
			}

			//Use mb_substr if available
			if(function_exists('mb_substr') === true) {
				if(is_null($length) === false) {
					return mb_substr($value, $start, $length);
				}

				return mb_substr($value, $start);
			}

			//Use the standard substr function
			if(is_null($length) === false) {
				return substr($value, $start, $length);
			}

			return substr($value, $start);
		} else {
			throw new Exception('Invalid parameter type.');
		}
	}

	/**
	 * Sorts an array
	 *
	 * @param array $value
	 * @return array
	 * @throws Exception
	 */
	public function sort($value)
	{
		if(is_array($value) === false) {
			throw new Exception('Invalid parameter type.');
		}

		return asort($value);
	}
}