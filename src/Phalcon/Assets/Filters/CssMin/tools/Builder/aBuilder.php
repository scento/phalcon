<?php
/**
 * Abstract definition of a CssMin Builder.
 * 
 * @package		CssMin/Tools/Build
 * @link		http://code.google.com/p/cssmin/
 * @author		Joe Scylla <joe.scylla@gmail.com>
 * @copyright	2008 - 2011 Joe Scylla <joe.scylla@gmail.com>
 * @license		http://opensource.org/licenses/mit-license.php MIT License
 * @version		3.0.1
 */
abstract class aBuilder
	{
	/**
	 * Build base path.
	 * 
	 * @var string
	 */
	protected $path = null;
	/**
	 * Array with source files.
	 * 
	 * @var array
	 */
	protected $files = null;
	/**
	 * Main comment extracted from the file 'CssMin.php'.
	 * 
	 * @var string
	 */
	protected $comment = null;
	/**
	 * Builded source.
	 * 
	 * @var string
	 */
	protected $source = null;
	/**
	 * Target file the builded source will get saved to.
	 * 
	 * @var string
	 */
	protected $target = null;
	/**
	 * Constructor.
	 * 
	 * @return void
	 */
	public function __construct()
		{
		$this->path = str_replace("/tools/Builder", "", dirname(__FILE__));
		}
	/**
	 * Builds the source.
	 * 
	 * @return string
	 */
	abstract public function build();
	/**
	 * Returns the main comment.
	 * 
	 * @return string
	 */
	public function getComment()
		{
		if (is_null($this->comment))
			{
			$content = file_get_contents($this->path . "/source/CssMin.php");;
			preg_match("/\/\*.+\*\//sU", $content, $m);
			$this->comment = $m[0];
			}
		return $this->comment;
		}
	/**
	 * Returns the source files.
	 * 
	 * @return array
	 */
	public function getFiles()
		{
		if (is_null($this->files))
			{
			$this->files		= array();
			$paths	= array($this->path . "/source");
			while (list($i, $path) = each($paths))
				{
				foreach (glob($path . "*", GLOB_MARK | GLOB_ONLYDIR | GLOB_NOSORT) as $subDirectory)
					{
					$paths[] = $subDirectory;
					}
				foreach (glob($path . "*.php", 0) as $file)
					{
					$this->files[basename($file)] = $file;
					}
				}
			krsort($this->files);
			}
		return $this->files;
		}
	/**
	 * Returns the builded source.
	 * 
	 * @return string
	 */
	public function getSource()
		{
		return $this->source;
		}
	/**
	 * Returns the target file.
	 * 
	 * @return string
	 */
	public function getTarget()
		{
		return $this->target;
		}
	/**
	 * Saves the builded source into the target file.
	 * 
	 * @return boolean
	 */
	public function save()
		{
		if (file_put_contents($this->target, $this->source))
			{
			return true;
			}
		else
			{
			return false;
			}
		}
	}
?>