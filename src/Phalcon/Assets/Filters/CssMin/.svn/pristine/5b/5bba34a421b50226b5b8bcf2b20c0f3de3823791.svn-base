<?php
/**
 * CssMin Build-Builder.
 * 
 * @package		CssMin/Tools/Build
 * @link		http://code.google.com/p/cssmin/
 * @author		Joe Scylla <joe.scylla@gmail.com>
 * @copyright	2008 - 2011 Joe Scylla <joe.scylla@gmail.com>
 * @license		http://opensource.org/licenses/mit-license.php MIT License
 * @version		3.0.1
 */
class BuildVersionBuilder extends aBuilder
	{
	/**
	 * Overwrites {aBuilder::__construct()}. Sets the target.
	 * 
	 * @return void
	 */
	public function __construct()
		{
		parent::__construct();
		$this->target = $this->path . "/build/CssMin.php";
		}
	/**
	 * Implements {aBuilder::build()}.
	 * 
	 * @return string
	 */
	public function build()
		{
		$this->source = "";
		// --
		foreach ($this->getFiles() as $file)
			{
			$this->source .= file_get_contents($file);
			}
		// Remove php delimiters
		$this->source = str_replace(array("<?php", "?>"), "", $this->source);
		// Add php delimiters and the main comment and save the file
		$this->source = "<?php\n" . $this->getComment() . $this->source . "\n?>";
		return $this->source;
		}
	}
?>