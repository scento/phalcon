<?php
/**
* This {@link aCssToken CSS token} represents the start of a @page at-rule block.
 *
 * @package		CssMin/Tokens
 * @link		http://code.google.com/p/cssmin/
 * @author		Joe Scylla <joe.scylla@gmail.com>
 * @copyright	2008 - 2011 Joe Scylla <joe.scylla@gmail.com>
 * @license		http://opensource.org/licenses/mit-license.php MIT License
 * @version		3.0.1
 */
class CssAtPageStartToken extends aCssAtBlockStartToken
	{
	/**
	 * Selector.
	 * 
	 * @var string
	 */
	public $Selector = "";
	/**
	 * Sets the properties of the @page at-rule.
	 * 
	 * @param string $selector Selector
	 * @return void
	 */
	public function __construct($selector = "")
		{
		$this->Selector = $selector;
		}
	/**
	 * Implements {@link aCssToken::__toString()}.
	 * 
	 * @return string
	 */
	public function __toString()
		{
		return "@page" . ($this->Selector ? " " . $this->Selector : "") . "{";
		}
	}
?>