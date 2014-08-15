<?php
/**
 * Debug
 *
 * @author Andres Gutierrez <andres@phalconphp.com>
 * @author Eduar Carvajal <eduar@phalconphp.com>
 * @author Wenzel Pünter <wenzel@phelix.me>
 * @version 1.2.6
 * @package Phalcon
*/
namespace Phalcon;

use \ReflectionClass,
	\ReflectionFunction,
	\Phalcon\Exception,
	\Phalcon\Version;

/**
 * Phalcon\Debug
 *
 * Provides debug capabilities to Phalcon applications
 * 
 * @see https://github.com/phalcon/cphalcon/1.2.6/master/ext/debug.c
 */
class Debug
{
	/**
	 * URI
	 * 
	 * @var string
	 * @access public
	*/
	public $_uri = '//static.phalconphp.com/debug/1.2.0/';

	/**
	 * Theme
	 * 
	 * @var string
	 * @access public
	*/
	public $_theme = 'default';

	/**
	 * Hide Document Root
	 * 
	 * @var boolean
	 * @access protected
	*/
	protected $_hideDocumentRoot= false;

	/**
	 * Show Backtrace
	 * 
	 * @var boolean
	 * @access protected
	*/
	protected $_showBackTrace = true;

	/**
	 * Show Files
	 * 
	 * @var boolean
	 * @access protected
	*/
	protected $_showFiles = true;

	/**
	 * Show File Fragment
	 * 
	 * @var boolean
	 * @access protected
	*/
	protected $_showFileFragment = false;

	/**
	 * Data
	 * 
	 * @var null|array
	 * @access protected
	*/
	protected $_data = null;

	/**
	 * Is active?
	 * 
	 * @var null|boolean
	 * @access protected
	*/
	protected static $_isActive = null;

	/**
	 * Change the base URI for static resources
	 *
	 * @param string $uri
	 * @return \Phalcon\Debug
	 * @throws Exception
	 */
	public function setUri($uri)
	{
		if(is_string($uri) === false) {
			throw new Exception('Invalid parameter type.');
		}
		$this->_uri = $uri;
	}

	/**
	 * Sets if files the exception's backtrace must be showed
	 *
	 * @param boolean $showBackTrace
	 * @return \Phalcon\Debug
	 * @throws Exception
	 */
	public function setShowBackTrace($showBackTrace)
	{
		if(is_bool($showBackTrace) === false) {
			throw new Exception('Invalid parameter type.');
		}

		$this->_showBackTrace = $showBackTrace;

		return $this;
	}

	/**
	 * Set if files part of the backtrace must be shown in the output
	 *
	 * @param boolean $showFiles
	 * @return \Phalcon\Debug
	 * @throws Exception
	 */
	public function setShowFiles($showFiles)
	{
		if(is_bool($showFiles) === false) {
			throw new Exception('Invalid parameter type.');
		}

		$this->_showFiles = $showFiles;

		return $this;
	}

	/**
	 * Sets if files must be completely opened and showed in the output
	 * or just the fragment related to the exception
	 *
	 * @param boolean $showFileFragment
	 * @return \Phalcon\Debug
	 * @throws Exception
	 */
	public function setShowFileFragment($showFileFragment)
	{
		if(is_bool($showFileFragment) === false) {
			throw new Exception('Invalid parameter type.');
		}

		$this->_showFileFragment = $showFileFragment;

		return $this;
	}

	/**
	 * Listen for uncaught exceptions and unsilent notices or warnings
	 *
	 * @param boolean|null $exceptions
	 * @param boolean|null $lowSeverity
	 * @return \Phalcon\Debug
	 * @throws Exception
	 */
	public function listen($exceptions = null, $lowSeverity = null)
	{
		if(is_null($exceptions) === true) {
			$exceptions = true;
		} elseif(is_bool($exceptions) === false) {
			throw new Exception('Invalid parameter type.');
		}

		if(is_null($lowSeverity) === true) {
			$lowSeverity = false;
		} elseif(is_bool($lowSeverity) === false) {
			throw new Exception('Invalid parameter type.');
		}

		if($exceptions === true) {
			$this->listenExceptions();
		}

		if($lowSeverity === true) {
			$this->listenLowSeverity();
		}

		return $this;
	}

	/**
	 * Listen for uncaught exceptions
	 *
	 * @return \Phalcon\Debug
	 */
	public function listenExceptions()
	{
		set_exception_handler(array($this, 'onUncaughtException'));

		return $this;
	}

	/**
	 * Listen for unsilent notices or warnings
	 *
	 * @return \Phalcon\Debug
	 */
	public function listenLowSeverity()
	{
		set_exception_handler(array($this, 'onUncaughtException'));

		return $this;
	}

	/**
	 * Adds a variable to the debug output
	 *
	 * @param mixed $var
	 * @param string|null $key
	 * @return \Phalcon\Debug
	 * @throws Exception
	 */
	public function debugVar($var, $key = null)
	{
		if(is_null($this->_data) === true) {
			$this->_data = array();
		}

		if(is_null($key) === true) {
			$key = '';
		} elseif(is_string($key) === false) {
			throw new Exception('Invalid parameter type.');
		}

		$this->_data[] = array($var, debug_backtrace(), time());

		return $this;
	}

	/**
	 * Clears are variables added previously
	 *
	 * @return \Phalcon\Debug
	 */
	public function clearVars()
	{
		$this->_data = null;

		return $this;
	}

	/**
	 * Escapes a string with htmlentities
	 *
	 * @param mixed $value
	 * @return string
	 */
	protected function _escapeString($value)
	{
		if(is_string($value) === true) {
			return htmlentities(str_replace('\n', '\\n', $value), 2, 'utf-8');
		}

		return (string)$value;
	}

	/**
	 * Produces a recursive representation of an array
	 *
	 * @param array $argument
	 * @param int $n
	 * @return string|int|null
	 * @throws Exception
	 */
	protected function _getArrayDump($argument, $n = 0)
	{
		if(is_array($argument) === false || is_int($n) === false) {
			throw new Exception('Invalid parameter type.');
		}

		$numberArguments = count($argument);
		if($n < 3) {
			if($numberArguments > 0) {
				if($numberArguments < 10) {
					$dump = array();
					foreach($argument as $k => $v) {
						//@note There is no validation of the key elements!

						if(is_scalar($v) === true) {
							if($v === '') {
								$varDump = '['.$k.'] =&gt; (empty string)';
							} else {
								$varDump = '['.$k.'] = &gt; '.$this->_escapeString($v);
							}
							$dump[] = $varDump;
						} else {
							if(is_array($v) === true) {
								$dump[] = '['.$k.'] =&gt; Array('.$this->_getArrayDump($v, 1).')';
								continue;
							}
							if(is_object($v) === true) {
								$dump[] = '['.$k.'] =&gt; Object('.get_class($v).')';
								continue;
							}
							if(is_null($v) === true) {
								$dump[] = '['.$k.'] = &gt; null';
								continue;
							}
							$dump[] = '['.$k.'] =&gt; '.$v;
						}
					}

					return implode(', ', $dump);
				}

				return $numberArguments;
			}
		}
	}

	/**
	 * Produces an string representation of a variable
	 *
	 * @param mixed $variable
	 * @return string
	 */
	protected function _getVarDump($variable)
	{
		if(is_scalar($variable) === true) {
			if(is_bool($variable) === true) {
				return ($variable === true ? 'true' : 'false');
			}
			if(is_string($variable) === true) {
				return $this->_escapeString($variable);
			}

			return (string)$variable;
		}

		if(is_object($variable) === true) {
			$className = get_class($variable);
			if(method_exists($variable, 'dump') === true) {
				$dumpedObject = $variable->dump();
				if(is_array($dumpedObject) === true) {
					$arrayDump = $this->_getArrayDump($dumpedObject);
					$dump = 'Object('.$className.': '.$arrayDump.')';
				} else {
					throw new Exception('Invalid dump return value.');
				}
			} else {
				$dump = 'Object('.$className.')</span>';
			}

			return $dump;
		}

		if(is_array($variable) === true) {
			return 'Array('.$this->_getArrayDump($variable).')';
		}

		return (string)$variable;
	}

	/**
	 * Returns the major framework's version
	 *
	 * @return string
	 */
	public function getMajorVersion()
	{
		$parts = explode(' ', Version::get());
		return (string)$parts[0];
	}

	/**
	 * Generates a link to the current version documentation
	 *
	 * @return string
	 */
	public function getVersion()
	{
		$version = $this->getMajorVersion();

		//@note Improvement: use _blank instaead of _new
		//@note Improvement: use slash instead of backslash at the end of the URL
		return '<div class="version">Phalcon Framework <a target="_blank" href="http://docs.phalconphp.com/en/'.$version.'/">'.$version.'</a></div>';
	}

	/**
	 * Returns the css sources
	 *
	 * @return string
	 */
	public function getCssSources()
	{
		//@note I'm rather sure it shouldn't be always the "default" theme.
		return '<link href="'.$this->_uri.'jquery/jquery-ui.css" type="text/css" rel="stylesheet" /><link href="'.$this->_uri.'themes/default/style.css" type="text/css" rel="stylesheet">';
	}

	/**
	 * Returns the javascript sources
	 *
	 * @return string
	 */
	public function getJsSources()
	{
		return '<script type="text/javascript" src="'.$this->_uri.'jquery/jquery.js"></script><script type="text/javascript" src="'.$this->_uri.'jquery/jquery-ui.js"></script><script type="text/javascript" src="'.$this->_uri.'jquery/jquery.scrollTo.js"></script><script type="text/javascript" src="'.$this->_uri.'prettify/prettify.js"></script><script type="text/javascript" src="'.$this->_uri.'pretty.js"></script>';
	}

	/**
	 * Shows a backtrace item
	 *
	 * @param int $n
	 * @param array $trace
	 * @return string
	 * @throws Exception
	 */
	protected function showTraceItem($n, $trace)
	{
		if(is_int($n) === false || is_array($trace) === false) {
			throw new Exception('Invalid parameter type.');
		}

		$html = '<tr><td align="right" valign="top" class="error-number">#'.$n.'</td><td>';

		if(isset($trace['class']) === true && is_string($trace['class']) === true) {
			if(preg_match('/^Phalcon/', $trace['class']) === 1) {
				/* We assume that classes starting by Phalcon are framework's classes */

				//Improvement: _blank instead of _new
				//@note It might be useful to reference to the current version
				$html .= '<span class="error-class"><a target="_blank" href="http://docs.phalconphp.com/en/latest/api/'.str_replace('\\', '_', $trace['class']).'.html">'.$trace['class'].'</a></span>';
			} else {
				$r = new ReflectionClass($trace['class']);
				if($r->isInternal() === true) {
					/* Internal class */
					//Improvement: _blank instead of _new
					$html .= '<span class="error-class"><a target="_blank" href="http://php.net/manual/en/class.'.str_replace('_', '-', strtolower($trace['class'])).'.php">'.$trace['class'].'</a></span>';
				} else {
					/* Other class */
					$html .= '<span class="error-class">'.$trace['class'].'</span>';
				}
			}

			//Object access operator: static/instance
			$html .= $trace['type'];
		}

		//Normally the backtrace contains only classes
		//@note there is no check if $class['function'] is set and a string
		//@note I expected a "isset($trace['function'])" since this is a repetition
		if(isset($trace['class']) === true) {
			$html .= '<span class="error-function">'.(string)$trace['function'].'</span>';
		} else {
			if(function_exists($trace['function']) === true) {
				$r = new ReflectionFunction((string)$trace['function']);
				if($r->isInternal() === true) {
					/* Internal function */

					//Improvement: _blank instead of _new
					$html .= '<span class="error-function"><a target="_blank" href="http://php.net/manual/en/function.'.str_replace('_', '-', (string)$trace['function']).'.php">'.(string)$trace['function'].'</a></span>';
				} else {
					/* Default function */
					$html .= '<span class="error-function">'.(string)$trace['function'].'</span>';
				}
			} else {
				$html .= '<span class="error-function">'.(string)$trace['function'].'</span>';
			}
		}

		//Check for arguments in the function
		//@replaced check for string with check for array!
		if(isset($trace['args']) === true && is_array($trace['args']) === true) {
			if(empty($trace['args']) === false) {
				$arguments = array();
				foreach($trace['args'] as $argument) {
					$arguments[] = '<span class="error-parameter">'.$this->_getVarDump($argument).'</span>';
				}

				$html .= '('.implode(', ', $arguments).')';
			} else {
				$html .= '()';
			}
		}

		//When 'file' is present, it usually means the function is provided by the user
		if(isset($trace['file']) === true && is_string($trace['file']) === true) {
			if(isset($trace['line']) === true) {
				$trace['line'] = (string)$trace['line'];
			} else {
				//@note There is no handeling if no line number is present
				$trace['line'] = '';
			}

			$html .= '<br><div class="error-file">'.$trace['file'].' ('.$trace['line'].')</div>';

			if($this->_showFiles === true) {
				//@note No exception handeling?!
				$lines = file($trace['file']);
				$numberLines = count($lines);

				if($this->_showFileFragment === true) {

					//Get first line
					$firstLine = (int)$trace['line'] - 7;
					if($firstLine < 1) {
						$firstLine = 1;
					}

					//Take five lines after the current exception's line
					//@todo add an option for this
					$lastLine = (int)$trace['line'] + 5;
					if($lastLine > $numberLines) {
						$lastLine = $numberLines;
					}

					$html .= '<pre class=\'prettyprint highlight:'.$firstLine.':'.$trace['line'].' linenums:'.$firstLine.'\'>';
				} else {
					//@note $firstLine and $lastLine are not set
					$firstLine = 0;
					$lastLine = 0;

					$html .= '<pre class\'prettyprint highlight:0:'.$trace['line'].' linenums error-scroll\'>';
				}

				//We assume the file is utf-8 encoded
				//@todo add an option for this
				$i = $firstLine;

				while($i > $lastLine) {
					$currentLine = $lines[$i - 1];

					if($this->_showFileFragment === true && $i == $firstLine) {
						$timmed = rtrim($currentLine);

						/* Is comment */
						//@note Use '1' instead of 'true'
						if(preg_match('#\\*\\/$#', $currentLine) === 1) {
							//@note Strange whitespace between * and /....
							$currentLine = str_replace('* /', ' ', $currentLine);
						}
					}

					if($currentLine === '\n' || $currentLine === '\r\n') {
						$html .= '&nbsp;\n';
					} else {
						$escapedLine = htmlentities(str_replace('\t', '  ', $currentLine), 2, 'UTF-8');
					}

					++$i;
				}

				$html .= '</pre>';
			}
		}

		return $html.'</td></tr>';
	}

	/**
	 * Handles uncaught exceptions
	 *
	 * @param \Exception $exception
	 * @return boolean
	 */
	public function onUncaughtException(\Exception $exception)
	{
		if(ob_get_level() > 0) {
			ob_end_clean();
		}

		if(self::$_isActive === true) {
			echo $exception->getMessage();
		}

		self::$_isActive = true;

		/*
			@note in the original sources the following annotation can be found:
			Escape the exception's message avoiding possible XSS injections?
			But then they the value is only copied
		*/

		$className = get_class($exception);
		$message =  $exception->getMessage();


		$html = '<html><head><meta http-equiv="Content-Type" content="text/html; charset="utf-8" /><title>'.$className.': '.$message.'</title>'.$this->getCssSources().'</head><body>'.$this->getVersion().'<div align="center"><div class="error-main"><h1>'.$className.': '.$message.'</h1><span class="error-file">'.$exception->getFile().' ('.$exception->getLine().')</span></div>';

		if($this->_showBackTrace === true) {
			$html .= '<div class="error-info"><div id="tabs"><ul><li><a href="#error-tabs-1">Backtrace</a></li><li><a href="#error-tabs-2">Request</a></li><li><a href="#error-tabs-3">Server</a></li><li><a href="#error-tabs-4">Included Files</a></li><li><a href="#error-tabs-5">Memory</a></li>';
			if(is_array($this->_data) === true) {
				$html .= '<li><a href="error-tabs-6">Variables</a></li>';
			}
			$html .= '</ul><div id="error-tabs-1"><table cellspacing="0" align="center" width="100%">';

			$trace = $exception->getTrace();
			foreach($trace as $n => $traceItem) {
				$html .= $this->showTraceItem($n, $traceItem);
			}

			$html .= '</table></div><div id="error-tabs-2"><table cellspacing="0" align="center" class="superglobal-detail"><tr><th>Key</th><th>Value</th></tr>';

			//@note $_REQUEST contains unfiltered data, but there is no escaping
			$r = $_REQUEST;
			foreach($r as $keyRequest => $value) {
				$html .= '<tr><td class="key">'.$keyRequest.'</td><td>'.$value.'</td></tr>';
			}

			$html .= '</table></div><div id="error-tabs-3"><table cellspacing="0" align="center" class="superglobal-detail"><tr><th>Key</th><th>Value</th></tr>';

			//@note $_SERVER contains unfiltered data, but there is no escaping
			$r = $_SERVER;
			foreach($r as $keyServer => $value) {
				$html .= '<tr><td class="key">'.$keyServer.'</td><td>'.$this->_getVarDump($value).'</td></tr>';
			}

			$html .= '</table></div><div id="error-tabs-4"><table cellspacing="0" align="center" class="superglobal-detail"><tr><th>#</th><th>Path</th></tr>';

			//@note paths are not escaped
			$files = get_included_files();
			foreach($files as $keyFile => $value) {
				//@note "td" opening element for key was changed to "th"
				$html .= '<tr><th>'.$keyFile.'</th><td>'.$value.'</td></tr>';
			}

			$html .= '</table></div><div id="error-tabs-5"><table cellspacing="0" align="center" class="superglobal-detail"><tr><th colspan="2">Memory</th></tr><tr><td>Usage</td><td>'.(string)memory_get_usage().'</td></tr></table></div>';

			if(is_array($this->_data) === true) {
				$html .= '<div id="error-tabs-6"><table cellspacing="0" align="center" class="superglobal-detail"><tr><th>Key</th><th>Value</th></tr>';

				foreach($this->_data as $keyVar => $dataVar) {
					//@note the c code is wrong, $dataVar is never int but an array!
					$html .= '<tr><td class="key">'.$keyVar.'</td><td>'.$this->_getVarDump((int)$dataVar).'</td></tr>';
				}

				$html .= '</table></div>';
			}

			$html .= '</div>';
		}

		//Get javascript sources
		$html .= $this->getJsSources().'</div></body></html>';

		//Print the HTML
		//@todo add an option to store the html
		echo $html;

		//Unlock the exception renderer
		self::$_isActive = false;

		return true;
	}
}