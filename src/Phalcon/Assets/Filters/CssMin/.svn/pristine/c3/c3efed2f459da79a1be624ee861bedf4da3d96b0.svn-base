<?php
/**
 * CssMin - A (simple) css minifier with benefits
 * 
 * --
 * Copyright (c) 2011 Joe Scylla <joe.scylla@gmail.com>
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 * 
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 * 
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 * --
 * 
 * @package		CssMin
 * @link		http://code.google.com/p/cssmin/
 * @author		Joe Scylla <joe.scylla@gmail.com>
 * @copyright	2008 - 2011 Joe Scylla <joe.scylla@gmail.com>
 * @license		http://opensource.org/licenses/mit-license.php MIT License
 * @version		3.0.1
 */
class CssMin
	{
	/**
	 * Index of classes
	 * 
	 * @var array
	 */
	private static $classIndex = array();
	/**
	 * Parse/minify errors
	 * 
	 * @var array
	 */
	private static $errors = array();
	/**
	 * Verbose output.
	 * 
	 * @var boolean
	 */
	private static $isVerbose = false;
	/**
	 * {@link http://goo.gl/JrW54 Autoload} function of CssMin.
	 * 
	 * @param string $class Name of the class
	 * @return void
	 */
	public static function autoload($class)
		{
		if (isset(self::$classIndex[$class]))
			{
			require(self::$classIndex[$class]);
			}
		}
	/**
	 * Return errors
	 * 
	 * @return array of {CssError}.
	 */
	public static function getErrors()
		{
		return self::$errors;
		}
	/**
	 * Returns if there were errors.
	 * 
	 * @return boolean
	 */
	public static function hasErrors()
		{
		return count(self::$errors) > 0;
		}
	/**
	 * Initialises CssMin.
	 * 
	 * @return void
	 */
	public static function initialise()
		{
		// Create the class index for autoloading or including
		$paths = array(dirname(__FILE__));
		while (list($i, $path) = each($paths))
			{
			$subDirectorys = glob($path . "*", GLOB_MARK | GLOB_ONLYDIR | GLOB_NOSORT);
			if (is_array($subDirectorys))
				{
				foreach ($subDirectorys as $subDirectory)
					{
					$paths[] = $subDirectory;
					}
				}
			$files = glob($path . "*.php", 0);
			if (is_array($files))
				{
				foreach ($files as $file)
					{
					$class = substr(basename($file), 0, -4);
					self::$classIndex[$class] = $file;
					}
				}
			}
		krsort(self::$classIndex);
		// Only use autoloading if spl_autoload_register() is available and no __autoload() is defined (because 
		// __autoload() breaks if spl_autoload_register() is used. 
		if (function_exists("spl_autoload_register") && !is_callable("__autoload"))
			{
			spl_autoload_register(array(__CLASS__, "autoload"));
			}
		// Otherwise include all class files
		else
			{
			foreach (self::$classIndex as $class => $file)
				{
				if (!class_exists($class))
					{
					require_once($file);
					}
				}
			}
		}
	/**
	 * Minifies CSS source.
	 * 
	 * @param string $source CSS source
	 * @param array $filters Filter configuration [optional]
	 * @param array $plugins Plugin configuration [optional]
	 * @return string Minified CSS
	 */
	public static function minify($source, array $filters = null, array $plugins = null)
		{
		self::$errors = array();
		$minifier = new CssMinifier($source, $filters, $plugins);
		return $minifier->getMinified();
		}
	/**
	 * Parse the CSS source.
	 * 
	 * @param string $source CSS source
	 * @param array $plugins Plugin configuration [optional]
	 * @return array Array of aCssToken
	 */
	public static function parse($source, array $plugins = null)
		{
		self::$errors = array();
		$parser = new CssParser($source, $plugins);
		return $parser->getTokens();
		}
	/**
	 * --
	 * 
	 * @param boolean $to
	 * @return boolean
	 */
	public static function setVerbose($to)
		{
		self::$isVerbose = (boolean) $to;
		return self::$isVerbose;
		}
	/**
	 * --
	 * 
	 * @param CssError $error
	 * @return void
	 */
	public static function triggerError(CssError $error)
		{
		self::$errors[] = $error;
		if (self::$isVerbose)
			{
			trigger_error((string) $error, E_USER_WARNING);
			}
		}
	}
// Initialises CssMin
CssMin::initialise();
?>