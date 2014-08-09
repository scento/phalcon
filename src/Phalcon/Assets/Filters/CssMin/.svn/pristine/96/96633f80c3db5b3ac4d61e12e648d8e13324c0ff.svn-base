<?php
/**
 * This {@link aCssToken CSS token} represents a CSS comment.
 * 
 * @package		CssMin/Tokens
 * @link		http://code.google.com/p/cssmin/
 * @author		Joe Scylla <joe.scylla@gmail.com>
 * @copyright	2008 - 2011 Joe Scylla <joe.scylla@gmail.com>
 * @license		http://opensource.org/licenses/mit-license.php MIT License
 * @version		3.0.1
 */
class CssCommentToken extends aCssToken
	{
	/**
	 * Comment as Text.
	 * 
	 * @var string
	 */
	public $Comment = "";
	/**
	 * Set the properties of a comment token.
	 * 
	 * @param string $comment Comment including comment delimiters 
	 * @return void
	 */
	public function __construct($comment)
		{
		$this->Comment = $comment;
		}
	/**
	 * Implements {@link aCssToken::__toString()}.
	 * 
	 * @return string
	 */
	public function __toString()
		{
		return $this->Comment;
		}
	}
?>