<?php
/**
 * Loader
 *
 * @author Andres Gutierrez <andres@phalconphp.com>
 * @author Eduar Carvajal <eduar@phalconphp.com>
 * @author Wenzel Pünter <wenzel@phelix.me>
 * @version 1.2.6
 * @package Phalcon
*/
namespace Phalcon;

use \Phalcon\Events\EventsAwareInterface,
	\Phalcon\Events\ManagerInterface,
	\Phalcon\Loader\Exception as LoaderException,
	\Phalcon\Text;

/**
 * Phalcon\Loader
 *
 * This component helps to load your project classes automatically based on some conventions
 *
 *<code>
 * //Creates the autoloader
 * $loader = new Phalcon\Loader();
 *
 * //Register some namespaces
 * $loader->registerNamespaces(array(
 *   'Example\Base' => 'vendor/example/base/',
 *   'Example\Adapter' => 'vendor/example/adapter/',
 *   'Example' => 'vendor/example/'
 * ));
 *
 * //register autoloader
 * $loader->register();
 *
 * //Requiring this class will automatically include file vendor/example/adapter/Some.php
 * $adapter = Example\Adapter\Some();
 *</code>
 * 
 * @see https://github.com/phalcon/cphalcon/blob/1.2.6/ext/loader.c
 */
class Loader implements EventsAwareInterface
{
	/**
	 * Events Manager
	 * 
	 * @var Phalcon\Events\ManagerInterface|null
	 * @access protected
	*/
	protected $_eventsManager;

	/**
	 * Found Path
	 * 
	 * @var string|null
	 * @access protected
	*/
	protected $_foundPath;

	/**
	 * Checked Path
	 * 
	 * @var string|null
	 * @access protected
	*/
	protected $_checkedPath;

	/**
	 * Prefixes
	 * 
	 * @var array|null
	 * @access protected
	*/
	protected $_prefixes;

	/**
	 * Classes
	 * 
	 * @var array|null
	 * @access protected
	*/
	protected $_classes;

	/**
	 * Extensions
	 * 
	 * @var array|null
	 * @access protected
	*/
	protected $_extensions;

	/**
	 * Namespaces
	 * 
	 * @var array|null
	 * @access protected
	*/
	protected $_namespaces;

	/**
	 * Directories
	 * 
	 * @var array|null
	 * @access protected
	*/
	protected $_directories; 

	/**
	 * Registered
	 * 
	 * @var boolean
	 * @access protected
	*/
	protected $_registered = false;

	/**
	 * \Phalcon\Loader constructor
	 */
	public function __construct()
	{
		$this->_extensions = array('php');
	}

	/**
	 * Sets the events manager
	 *
	 * @param \Phalcon\Events\ManagerInterface $eventsManager
	 * @throws \Phalcon\Loader\Exception
	 */
	public function setEventsManager($eventsManager)
	{
		if(is_object($eventsManager) === false || 
			$eventsManager instanceof ManagerInterface === false) {
			throw new LoaderException('Invalid parameter type.');
		}

		$this->_eventsManager = $eventsManager;
	}

	/**
	 * Returns the internal event manager
	 *
	 * @return \Phalcon\Events\ManagerInterface|null
	 */
	public function getEventsManager()
	{
		return $this->_eventsManager;
	}

	/**
	 * Sets an array of extensions that the loader must try in each attempt to locate the file
	 *
	 * @param array $extensions
	 * @return \Phalcon\Loader
	 * @throws \Phalcon\Loader\Exception
	 */
	public function setExtensions($extensions)
	{
		if(is_array($extensions) === false) {
			throw new LoaderException('Parameter extension must be an array');
		}

		$this->_extensions = $extensions;

		return $this;
	}

	/**
	 * Return file extensions registered in the loader
	 *
	 * @return null|array
	 */
	public function getExtensions()
	{
		return $this->_extensions;
	}

	/**
	 * Register namespaces and their related directories
	 *
	 * @param array $namespaces
	 * @param boolean|null $merge
	 * @return \Phalcon\Loader
	 * @throws \Phalcon\Loader\Exception
	 */
	public function registerNamespaces($namespaces, $merge = null)
	{
		if(is_null($merge) === true) {
			$merge = false;
		} elseif(is_bool($merge) === false) {
			throw new LoaderException('Invalid parameter type.');
		}

		if(is_array($namespaces) === false) {
			throw new LoaderException('Parameter namespaces must be an array');
		}

		if($merge === true && is_array($this->_namespaces) === true) {
			$this->_namespaces = array_merge($this->_namespaces, $namespaces);
		} else {
			$this->_namespaces = $namespaces;
		}

		return $this;
	}

	/**
	 * Return current namespaces registered in the autoloader
	 *
	 * @return array|null
	 */
	public function getNamespaces()
	{
		return $this->_namespaces;
	}

	/**
	 * Register directories on which "not found" classes could be found
	 *
	 * @param array $prefixes
	 * @param boolean|null $merge
	 * @return \Phalcon\Loader
	 * @throws \Phalcon\Loader\Exception
	 */
	public function registerPrefixes($prefixes, $merge = null)
	{
		if(is_null($merge) === true) {
			$merge = false;
		} elseif(is_bool($merge) === false) {
			throw new LoaderException('Invalid parameter type.');
		}

		if(is_array($prefixes) === false) {
			throw new LoaderException('Parameter prefixes must be an array');
		}

		if($merge === true && is_array($this->_prefixes) === true) {
			$this->_prefixes = array_merge($this->_prefixes, $prefixes);
		} else {
			$this->_prefixes = $prefixes;
		}

		return $this;
	}

	/**
	 * Return current prefixes registered in the autoloader
	 *
	 * @param array|null
	 */
	public function getPrefixes()
	{
		return $this->_prefixes;
	}

	/**
	 * Register directories on which "not found" classes could be found
	 *
	 * @param array $directories
	 * @param boolean|null $merge
	 * @return \Phalcon\Loader
	 * @throws \Phalcon\Loader\Exception
	 */
	public function registerDirs($directories, $merge = null)
	{
		if(is_null($merge) === true) {
			$merge = false;
		} elseif(is_bool($merge) === false) {
			throw new LoaderException('Invalid parameter type.');
		}

		if(is_array($directories) === false) {
			throw new LoaderException('Parameter directories must be an array');
		}

		if($merge === true && is_array($this->_directories) === true) {
			$this->_directories = array_merge($this->_directories, $directories);
		} else {
			$this->_directories = $directories;
		}

		return $this;
	}

	/**
	 * Return current directories registered in the autoloader
	 *
	 * @return array|null
	 */
	public function getDirs()
	{
		return $this->_directories;
	}

	/**
	 * Register classes and their locations
	 *
	 * @param array $classes
	 * @param boolean|null $merge
	 * @return \Phalcon\Loader
	 * @throws \Phalcon\Loader\Exception
	 */
	public function registerClasses($classes, $merge = null)
	{
		if(is_null($merge) === true) {
			$merge = false;
		} elseif(is_bool($marge) === false) {
			throw new LoaderException('Invalid parameter type.');
		}

		if(is_array($classes) === false) {
			throw new LoaderException('Parameter classes must be an array');
		}

		if($merge === true && is_array($this->_classes) === true) {
			$this->_classes = array_merge($this->_classes, $classes);
		} else {
			$this->_classes = $classes;
		}

		return $this;
	}

	/**
	 * Return the current class-map registered in the autoloader
	 *
	 * @return array|null
	 */
	public function getClasses()
	{
		return $this->_classes;
	}

	/**
	 * Register the autoload method
	 *
	 * @return \Phalcon\Loader
	 */
	public function register()
	{
		if($this->_registered === false) {
			spl_autoload_register(array($this, 'autoLoad'));
			$this->_registered = true;
		}

		return $this;
	}

	/**
	 * Unregister the autoload method
	 *
	 * @return \Phalcon\Loader
	 */
	public function unregister()
	{
		if($this->_registered === true) {
			spl_autoload_unregister(array($this, 'autoLoad'));
			$this->_registered = false;
		}

		return $this;
	}

	/**
	 * Removes the prefix from a class name, 
	 * removes malicious characters, 
	 * replace namespace seperator by directory seperator
	 * 
	 * @param string $prefix
	 * @param string $className
	 * @param string $virtualSeperator
	 * @param string|null $seperator
	 * @return string|boolean
	 * @see https://github.com/phalcon/cphalcon/blob/1.2.6/ext/kernel/file.c#L213
	*/
	private static function possibleAutoloadFilePath($prefix, $className, $virtualSeperator, $seperator = null)
	{
		if(is_string($prefix) === false ||
			is_string($className) === false ||
			is_string($virtualSeperator) === false ||
			(is_string($seperator) === false &&
			is_null($seperator) === false)) {
			return false;
		}

		$length = strlen($prefix);
		if($length === 0 || $length > strlen($className)) {
			return false;
		}

		if(is_null($seperator) === false && 
			is_string($seperator) === true &&
			$prefix[$length-1] === $seperator[0]) {
			$length--;
		}

		$virtualStr = '';

		$lengthClassName = strlen($className);
		for($i = $length + 1; $i < $lengthClassName; ++$i) {
			$ch = ord($className[$i]);

			//Anticipated end of string
			if($ch === 0) {
				break;
			}

			//Replace namespace seperator by directory seperator (\)
			if($ch === 92) {
				$virtualStr .= $virtualSeperator;
				continue;
			}

			//Basic alphanumeric characters
			if($ch === 95 || // _
				($ch >= 48 && $ch <= 57) || // >="0" && <= "9"
				($ch >= 97 && $ch <= 122) || // >="a" && <= "z"
				($ch >= 65 && $ch <= 90)) { // >= "A" && <= "Z"
				$virtualStr .= $className[$i];
				continue;
			}

			//Multibyte characters?
			if($ch > 127) {
				$virtualStr .= $className[$i];
				continue;
			}
		}

		if(empty($virtualStr) === false) {
			return $virtualStr;
		} else {
			return false;
		}
	}

	/**
	 * Adds a trailing directory seperator if the path doesn't have it
	 * 
	 * @param string $path
	 * @param string $directorySeperator
	 * @return string|null
	 * @see https://github.com/phalcon/cphalcon/blob/1.2.6/ext/kernel/file.c#L106
	*/
	private static function fixPath($path, $directorySeperator)
	{
		if(is_string($path) === false ||
			is_string($directorySeperator) === false) {
			return;
		}

		//@note we assume $directorySeparator is a char and not a string

		$pathLength = strlen($path);

		if(empty($pathLength) === false && 
			empty($directorySeperator) === false &&
			$path[$pathLength-1] !== '\\' &&
			$path[$pathLength-1] !== '/') {
			return $path.$directorySeperator;
		}

		return $path;
	}

	/**
	 * Makes the work of autoload registered classes
	 *
	 * @param string $className
	 * @return boolean
	 */
	public function autoLoad($className)
	{
		$eventsManager = $this->_eventsManager;
		if(is_object($eventsManager) === true) {
			$eventsManager->fire('loader:beforeCheckClass', $this, $className);
		}

		/* First we check for static paths */
		if(is_array($this->_classes) === true &&
			isset($this->_classes[$className]) === true) {

			$filePath = $this->_classes[$className];
			if(is_object($eventsManager) === true) {
				$this->_foundPath = $filePath;
				$eventsManager->fire('loader:pathFound', $this, $filePath);
			}

			require_once($filePath);
			return true;
		}

		$extensions = $this->_extensions;

		/* Checking in namespaces */
		if(is_array($this->_namespaces) === true) {
			foreach($this->_namespaces as $nsPrefix => $directory) {
				//The class name must start with the current namespace
				if(Text::startsWith($className, $nsPrefix) === true) {
					//Get the possible file path
					$fileName = self::possibleAutoloadFilePath($nsPrefix, $className, \DIRECTORY_SEPARATOR, null);
					if($fileName !== false) {
						//Add a trailing directory separator is the user forgot to do that
						$fixedDirectory = self::fixPath($directory, \DIRECTORY_SEPARATOR);

						foreach($extensions as $extension) {
							$filePath = $fixedDirectory.$fileName.'.'.$extension;

							//Check if an events manager is available
							if(is_object($eventsManager) === true) {
								$this->_checkedPath = $filePath;
								$eventsManager->fire('loader:beforeCheckPath', $this);
							}

							//This is probably a good path, let's check if the file exists
							if(file_exists($filePath) === true) {
								if(is_object($eventsManager) === true) {
									$this->_foundPath = $filePath;

									$eventsManager->fire('loader:pathFound', $this, $filePath);
								}

								require_once($filePath);

								//Return true means success
								return true;
							}
						}
					}
				}
			}
		}

		/* Checking in prefixes */
		$prefixes = $this->_prefixes;
		if(is_array($prefixes) === true) {
			foreach($prefixes as $prefix => $directory) {
				//The class name starts with the prefix?
				if(Text::startsWith($className, $prefix) === true) {
					//Get the possible file path
					$fileName = self::possibleAutoloadFilePath($fileName, $prefix, $className, \DIRECTORY_SEPARATOR, '_');
					if($fileName !== false) {
						//Add a trailing directory separator is the user forgot to do that
						$fixedDirectory = self::fixPath($directory, \DIRECTORY_SEPARATOR);

						foreach($extensions as $extension) {
							$filePath = $fixedDirectory.$fileName.'.'.$extension;

							if(is_object($eventsManager) === true) {
								$this->_checkedPath = $filePath;
								$eventsManager->fire('loader:beforeCheckPath', $this, $filePath);
							}

							if(file_exists($filePath) === true) {
								//Call 'pathFound' event
								if(is_object($eventsManager) === true) {
									$this->_foundPath = $filePath;
									$eventsManager->fire('loader:pathFound', $this, $filePath);
								}

								require_once($filePath);

								return true;
							}
						}
					}
				}
			}
		}

		//Change the pseudo-separator by the directory separator in the class name
		$dsClassName = str_replace('_', \DIRECTORY_SEPARATOR, $className);

		//And change the namespace separator by directory separator too
		$nsClassName = str_replace('\\', \DIRECTORY_SEPARATOR, $dsClassName);

		/* Checking in directories */
		$directories = $this->_directories;
		if(is_array($directories) === true) {
			foreach($directories as $directory) {
				//Add a trailing directory separator if the user forgot to do that
				$fixedDirectory = self::fixPath($directory, \DIRECTORY_SEPARATOR);

				foreach($extensions as $extension) {
					//Create a possible path for the file
					$filePath = $fixedDirectory.$nsClassName.'.'.$extension;

					if(is_object($eventsManager) === true) {
						$this->_checkedPath = $filePath;
						$eventsManager->fire('loader:beforeCheckPath', $this, $filePath);
					}

					//Check in every directory if the class exists here
					if(file_exists($filePath) === true) {
						//Call 'pathFound' event
						if(is_object($eventsManager) === true) {
							$this->_foundPath = $filePath;
							$eventsManager->fire('loader:pathFound', $this, $filePath);
						}

						require_once($filePath);

						//Returning true means success
						return true;
					}
				}
			}
		}

		//Call 'afterCheckClass' event
		if(is_object($eventsManager) === true) {
			$eventsManager->fire('loader:afterCheckClass', $this, $className);
		}

		//Cannot find the class - return false
		return false;
	}

	/**
	 * Get the path when a class was found
	 *
	 * @return string|null
	 */
	public function getFoundPath()
	{
		return $this->_foundPath;
	}

	/**
	 * Get the path the loader is checking for a path
	 *
	 * @return string|null
	 */
	public function getCheckedPath()
	{
		return $this->_checkedPath;
	}
}