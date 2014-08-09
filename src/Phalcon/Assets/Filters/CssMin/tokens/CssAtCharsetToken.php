<?php
/**
 * This {@link aCssToken CSS token} represents a @charset at-rule.
 * 
 * @package		CssMin/Tokens
 * @link		http://code.google.com/p/cssmin/
 * @author		Joe Scylla <joe.scylla@gmail.com>
 * @copyright	2008 - 2011 Joe Scylla <joe.scylla@gmail.com>
 * @license		http://opensource.org/licenses/mit-license.php MIT License
 * @version		3.0.1
 */
class CssAtCharsetToken extends aCssToken
	{
	/**
	 * Charset of the @charset at-rule.
	 * 
	 * @var string
	 */
	public $Charset = "";
	/**
	 * Set the properties of @charset at-rule token. 
	 * 
	 * @param string $charset Charset of the @charset at-rule token
	 * @return void
	 */
	public function __construct($charset)
		{
		$this->Charset = $charset;
		}
	/**
	 * Implements {@link aCssToken::__toString()}.
	 * 
	 * @return string
	 */
	public function __toString()
		{
		return "@charset " . $this->Charset . ";";
		}
	}
?>