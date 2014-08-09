<?php
/**
 * This {@link aCssToken CSS token} represents the start of a @keyframes at-rule block.
 *
 * @package		CssMin/Tokens
 * @link		http://code.google.com/p/cssmin/
 * @author		Joe Scylla <joe.scylla@gmail.com>
 * @copyright	2008 - 2011 Joe Scylla <joe.scylla@gmail.com>
 * @license		http://opensource.org/licenses/mit-license.php MIT License
 * @version		3.0.1
 */
class CssAtKeyframesStartToken extends aCssAtBlockStartToken
	{
	/**
	 * Name of the at-rule.
	 * 
	 * @var string
	 */
	public $AtRuleName = "keyframes";
	/**
	 * Name
	 * 
	 * @var string
	 */
	public $Name = "";
	/**
	 * Sets the properties of the @page at-rule.
	 * 
	 * @param string $selector Selector
	 * @return void
	 */
	public function __construct($name, $atRuleName = null)
		{
		$this->Name = $name;
		if (!is_null($atRuleName))
			{
			$this->AtRuleName = $atRuleName;
			}
		}
	/**
	 * Implements {@link aCssToken::__toString()}.
	 * 
	 * @return string
	 */
	public function __toString()
		{
		return "@" . $this->AtRuleName . " \"" . $this->Name . "\"{";
		}
	}
?>