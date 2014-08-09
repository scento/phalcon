<?php
/**
 * This {@link aCssToken CSS token} represents a @import at-rule.
 * 
 * @package		CssMin/Tokens
 * @link		http://code.google.com/p/cssmin/
 * @author		Joe Scylla <joe.scylla@gmail.com>
 * @copyright	2008 - 2011 Joe Scylla <joe.scylla@gmail.com>
 * @license		http://opensource.org/licenses/mit-license.php MIT License
 * @version		3.0.1.b1 (2001-02-22)
 */
class CssAtImportToken extends aCssToken
	{
	/**
	 * Import path of the @import at-rule.
	 * 
	 * @var string
	 */
	public $Import = "";
	/**
	 * Media types of the @import at-rule.
	 * 
	 * @var array
	 */
	public $MediaTypes = array();
	/**
	 * Set the properties of a @import at-rule token.
	 * 
	 * @param string $import Import path
	 * @param array $mediaTypes Media types
	 * @return void
	 */
	public function __construct($import, $mediaTypes)
		{
		$this->Import		= $import;
		$this->MediaTypes	= $mediaTypes ? $mediaTypes : array();
		}
	/**
	 * Implements {@link aCssToken::__toString()}.
	 * 
	 * @return string
	 */
	public function __toString()
		{
		return "@import \"" . $this->Import . "\"" . (count($this->MediaTypes) > 0 ? " "  . implode(",", $this->MediaTypes) : ""). ";";
		}
	}
?>