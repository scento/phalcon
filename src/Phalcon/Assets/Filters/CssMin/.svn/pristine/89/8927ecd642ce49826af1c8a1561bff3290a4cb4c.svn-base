<?php
/**
 * Abstract formatter definition.
 * 
 * Every formatter have to extend this class.
 * 
 * @package		CssMin/Formatter
 * @link		http://code.google.com/p/cssmin/
 * @author		Joe Scylla <joe.scylla@gmail.com>
 * @copyright	2008 - 2011 Joe Scylla <joe.scylla@gmail.com>
 * @license		http://opensource.org/licenses/mit-license.php MIT License
 * @version		3.0.1
 */
abstract class aCssFormatter
	{
	/**
	 * Indent string.
	 * 
	 * @var string
	 */
	protected $indent = "    ";
	/**
	 * Declaration padding.
	 * 
	 * @var integer
	 */
	protected $padding = 0;
	/**
	 * Tokens.
	 * 
	 * @var array
	 */
	protected $tokens = array();
	/**
	 * Constructor.
	 * 
	 * @param array $tokens Array of CssToken
	 * @param string $indent Indent string [optional]
	 * @param integer $padding Declaration value padding [optional]
	 */
	public function __construct(array $tokens, $indent = null, $padding = null)
		{
		$this->tokens	= $tokens;
		$this->indent	= !is_null($indent) ? $indent : $this->indent;
		$this->padding	= !is_null($padding) ? $padding : $this->padding;
		}
	/**
	 * Returns the array of aCssToken as formatted string.
	 * 
	 * @return string
	 */
	abstract public function __toString();
	}
?>