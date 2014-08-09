<?php
/**
 * {@link aCssParserPlugin Parser plugin} for preserve parsing expression() declaration values.
 * 
 * This plugin return no {@link aCssToken CssToken} but ensures that expression() declaration values will get parsed 
 * properly.
 * 
 * @package		CssMin/Parser/Plugins
 * @link		http://code.google.com/p/cssmin/
 * @author		Joe Scylla <joe.scylla@gmail.com>
 * @copyright	2008 - 2011 Joe Scylla <joe.scylla@gmail.com>
 * @license		http://opensource.org/licenses/mit-license.php MIT License
 * @version		3.0.1
 */
class CssExpressionParserPlugin extends aCssParserPlugin
	{
	/**
	 * Count of left braces.
	 * 
	 * @var integer
	 */
	private $leftBraces = 0;
	/**
	 * Count of right braces.
	 * 
	 * @var integer
	 */
	private $rightBraces = 0;
	/**
	 * Implements {@link aCssParserPlugin::getTriggerChars()}.
	 * 
	 * @return array
	 */
	public function getTriggerChars()
		{
		return array("(", ")", ";", "}");
		}
	/**
	 * Implements {@link aCssParserPlugin::getTriggerStates()}.
	 * 
	 * @return array
	 */
	public function getTriggerStates()
		{
		return false;
		}
	/**
	 * Implements {@link aCssParserPlugin::parse()}.
	 * 
	 * @param integer $index Current index
	 * @param string $char Current char
	 * @param string $previousChar Previous char
	 * @return mixed TRUE will break the processing; FALSE continue with the next plugin; integer set a new index and break the processing
	 */
	public function parse($index, $char, $previousChar, $state)
		{
		// Start of expression
		if ($char === "(" && strtolower(substr($this->parser->getSource(), $index - 10, 11)) === "expression(" && $state !== "T_EXPRESSION")
			{
			$this->parser->pushState("T_EXPRESSION");
			$this->leftBraces++;
			}
		// Count left braces
		elseif ($char === "(" && $state === "T_EXPRESSION")
			{
			$this->leftBraces++;
			}
		// Count right braces
		elseif ($char === ")" && $state === "T_EXPRESSION")
			{
			$this->rightBraces++;
			}
		// Possible end of expression; if left and right braces are equal the expressen ends
		elseif (($char === ";" || $char === "}") && $state === "T_EXPRESSION" && $this->leftBraces === $this->rightBraces)
			{
			$this->leftBraces = $this->rightBraces = 0;
			$this->parser->popState();
			return $index - 1;
			}
		else
			{
			return false;
			}
		return true;
		}
	}
?>