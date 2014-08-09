<?php
/**
 * CssMin Minified-Builder.
 * 
 * @package		CssMin/Tools/Build
 * @link		http://code.google.com/p/cssmin/
 * @author		Joe Scylla <joe.scylla@gmail.com>
 * @copyright	2008 - 2011 Joe Scylla <joe.scylla@gmail.com>
 * @license		http://opensource.org/licenses/mit-license.php MIT License
 * @version		3.0.1
 */
class MinifiedVersionBuilder extends BuildVersionBuilder
	{
	private $parentTarget = "";
	/**
	 * Overwrites {BuildVersionBuilder::__construct()}. Sets the target.
	 * 
	 * @return void
	 */
	public function __construct()
		{
		parent::__construct();
		$this->parentTarget = parent::getTarget();
		$this->target = $this->path . "/minified/CssMin.php";
		}
	/**
	 * Implements {aBuilder::build()}.
	 * 
	 * @return string
	 */
	public function build()
		{
		parent::build();
		$this->source = php_strip_whitespace($this->parentTarget);
		// Remove php delimiters
		$this->source = str_replace(array("<?php", "?>"), "", $this->source);
		// --
		$this->source = "<?php\n" . $this->getComment() . $this->source . "\n?>";
		
		return $this->source;
		}
	}
?>