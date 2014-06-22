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
	\Phalcon\Loader\Exception,
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
	 * @var null|Phalcon\Events\ManagerInterface
	 * @access protected
	*/
	protected $_eventsManager = null;

	/**
	 * Found Path
	 * 
	 * @var null|string
	 * @access protected
	*/
	protected $_foundPath = null;

	/**
	 * Checked Path
	 * 
	 * @var null|string
	 * @access protected
	*/
	protected $_checkedPath = null;

	/**
	 * Prefixes
	 * 
	 * @var null|array
	 * @access protected
	*/
	protected $_prefixes = null;

	/**
	 * Classes
	 * 
	 * @var null|array
	 * @access protected
	*/
	protected $_classes = null;

	/**
	 * Extensions
	 * 
	 * @var null|array
	 * @access protected
	*/
	protected $_extensions = null;

	/**
	 * Namespaces
	 * 
	 * @var null|array
	 * @access protected
	*/
	protected $_namespaces = null;

	/**
	 * Directories
	 * 
	 * @var null|array
	 * @access protected
	*/
	protected $_directories = null; 

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
	 * @throws Exception
	 */
	public function setEventsManager($eventsManager)
	{
		//@note Improvement: type checking
		if(is_object($eventsManager) === false || 
			$eventsManager instanceof ManagerInterface === false) {
			throw new Exception('Invalid parameter type.');
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
	 */
	public function setExtensions($extensions)
	{
		if(is_array($extensions) === false) {
			throw new Exception('Parameter extension must be an array');
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
	 * @throws Exception
	 */
	public function registerNamespaces($namespaces, $merge = null)
	{
		if($merge === null) {
			$merge = false;
		} elseif(is_bool($merge) === false) {
			throw new Exception('Invalid parameter type.');
		}

		if(is_array($namespaces) === false) {
			throw new Exception('Parameter namespaces must be an array');
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
	 * @throws Exception
	 */
	public function registerPrefixes($prefixes, $merge = null)
	{
		if($merge === null) {
			$merge = false;
		} elseif(is_bool($merge) === false) {
			throw new Exception('Invalid parameter type.');
		}

		if(is_array($prefixes) === false) {
			throw new Exception('Parameter prefixes must be an array');
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
	 * @throws Exception
	 */
	public function registerDirs($directories, $merge = null)
	{
		if($merge === null) {
			$merge = false;
		} elseif(is_bool($merge) === false) {
			throw new Exception('Invalid parameter type.');
		}

		if(is_array($directories) === false) {
			throw new Exception('Parameter directories must be a n array');
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
	 * @param array|null
	 */
	public function getDirs()
	{
		return $this->_directories;
	}

	/**
	 * Register classes and their locations
	 *
	 * @param array $classes
	 * @param boolean $merge
	 * @return \Phalcon\Loader
	 * @throws Exception
	 */
	public function registerClasses($classes, $merge = null)
	{
		if($merge === null) {
			$merge = false;
		} elseif(is_bool($marge) === false) {
			throw new Exception('Invalid parameter type.');
		}

		if(is_array($classes) === false) {
			throw new Exception('Parameter classes must be an array');
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
	 * @param array|null
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
	*/
	private static function possibleAutoloadFilePath($prefix, $className, $virtualSeperator, $seperator = null)
	{
		if(is_string($prefix) === false || is_string($className) === false
		 || is_string($virtualSeperator) === false) {
			return false;
		}

		$l = strlen($prefix);
		if($l === 0 || $l > strlen($className)) {
			return false;
		}

		if(is_null($seperator) === false && is_string($seperator) === true &&
			$prefix[$l-1] === $seperator[0]) {
			$l--;
		}

		$virtual_str = '';

		$lc = strlen($className);
		for($i = $l + 1; $i < $lc; ++$i) {
			$ch = ord($className[$i]);

			//Anticipated end of string
			if($ch === 0) {
				break;
			}

			//Replace namespace seperator by directory seperator
			if($ch === 92) {
				$virtual_str .= $virtualSeperator;
				continue;
			}

			//Basic alphanumeric characters
			if($ch === 95 || ($ch >= 48 && $ch <= 57) || ($ch >= 97 && $ch <= 122) ||
				($ch >= 65 && $ch <= 90)) {
				$virtual_str .= $className[$i];
				continue;
			}

			//Multibyte characters?
			if($ch > 127) {
				$virtual_str .= $className[$i];
				continue;
			}
		}

		if(empty($virtual_str) === false) {
			return $virtual_str;
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
	*/
	private static function fixPath($path, $directorySeperator)
	{
		if(is_string($path) === false || is_string($directorySeperator) === false) {
			return null;
		}

		$pl = strlen($path);

		if($pl > 0 && empty($directorySeperator) === false) {
			if($path[$pl-1] !== '\\' && $path[$pl-1] !== '/') {
				return $path.$directorySeperator;
			}
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
		//Checking in namespaces
		if(is_array($this->_namespaces) === true) {
			foreach($this->_namespaces as $ns_prefix => $directory) {
				//The class name must start with the current namespace
				if(Text::startsWith($className, $ns_prefix, null) === true) {
					//get the possible file path
					$file_name = self::possibleAutoloadFilePath($ns_prefix, $className, \DIRECTORY_SEPARATOR);
					if($file_name !== false) {
						//Add a trailing directory seperator if the user forgot to do that
						$fixed_directory = self::fixPath($directory, \DIRECTORY_SEPARATOR);

						foreach($this->_extensions as $extension) {
							$file_path = $fixed_directory.$file_name.'.'.$extension;

							//Check if a events manager is available
							//@note no class check
							if(is_object($this->_eventsManager) === true) {
								$this->_checkedPath = $file_path;
								$eventsManager->fire('loader:beforeCheckPath', $this);
							}

							//This is probably a good path, lets check if the file exists
							if(file_exists($file_path) === true) {
								if(is_object($this->_eventsManager) === true) {
									$this->_foundPath = $file_path;

									$this->_eventsManager->fire('load:pathFound', $this, $file_path);
								}

								require($file_path);

								//Return true mean success
								return true;
							}
						}
					}
				}
			}
		}

		//Checking in prefixes
		if(is_array($this->_prefixes) === true) {
			foreach($this->_prefixes as $prefix => $directory) {
				//The class name starts with the prefix?
				if(Text::startsWith($className, $prefix, null) === true) {
					//Get the possible file path
					$file_name = self::possibleAutoloadFilePath($prefix, $className, '_');
					if($file_name !== false) {
						//Add a trailing directory seperator if the user forgot to do that
						$fixed_directory = self::fixPath($path, \DIRECTORY_SEPARATOR);
						foreach($this->_extensions as $extension) {
							$file_path = $fixed_directory.$file_name.'.'.$extension;
							if(is_object($this->_eventsManager) === true) {
								$this->_checkedPath = $file_path;

								$this->_eventsManager->fire('load:beforeCheckPath', $this, $file_path);
							}

							if(file_exists($file_path) === true) {
								//Call 'pathFound' event
								if(is_object($this->_eventsManager) === true) {
									$this->_foundPath = $file_path;

									$this->_eventsManager->fire('loader:pathFound', $this, $file_path);
								}

								require($file_path);
								return true;
							}
						}
					}
				}
			}
		}

		//Change the pseudo-seperator by the directory seperator in the class name
		$ds_class_name = str_replace('_', \DIRECTORY_SEPARATOR, $className);

		//And change the namespace seperator by the directory seperator too
		$ns_class_name = str_replace('\\', \DIRECTORY_SEPARATOR, $className);

		//Checking in directories
		if(is_array($this->_directories) === true) {
			foreach($this->_directories as $directory) {
				//Add a trailing directory seperator if the user forgot to do that
				$fixed_directory = self::fixPath($directory, \DIRECTORY_SEPARATOR);
				foreach($this->_extensions as $extension) {
					//Create a possible path for the file
					$file_path = $fixed_directory.$ns_class_name.'.'.$extension;

					if(is_object($this->_eventsManager) === true) {
						$this->_checkedPath = $file_path;
						$this->_eventsManager->fire('loader:beforeCheckPath', $this, $file_path);
					}

					//Check in every directory if the class exists here
					if(file_exists($file_path) === true) {
						//Call 'pathFound' event
						if(is_object($this->_eventsManager) === true) {
							$this->_foundPath = $file_path;
							$this->_eventsManager->fire('loader:pathFound', $this, $file_path);
						}

						require($file_path);

						//Return true meaning success
						return true;
					}
				}
			}
		}

		//Call 'afterCheckClass' event
		if(is_object($this->_eventsManager) === true) {
			$this->_eventsManager->fire('loader:afterCheckClass', $this, $className);
		}

		//Cannot find the class return false
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