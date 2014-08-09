<?php
/**
 * {@link aCssParserPlugin Parser plugin} for parsing @charset at-rule.
 * 
 * If a @charset at-rule was found this plugin will add a {@link CssAtCharsetToken} to the parser.
 *
 * @package		CssMin/Parser/Plugins
 * @link		http://code.google.com/p/cssmin/
 * @author		Joe Scylla <joe.scylla@gmail.com>
 * @copyright	2008 - 2011 Joe Scylla <joe.scylla@gmail.com>
 * @license		http://opensource.org/licenses/mit-license.php MIT License
 * @version		3.0.1
 */
class CssAtCharsetParserPlugin extends aCssParserPlugin
	{
	/**
	 * Implements {@link aCssParserPlugin::getTriggerChars()}.
	 * 
	 * @return array
	 */
	public function getTriggerChars()
		{
		return array("@", ";", "\n");
		}
	/**
	 * Implements {@link aCssParserPlugin::getTriggerStates()}.
	 * 
	 * @return array
	 */
	public function getTriggerStates()
		{
		return array("T_DOCUMENT", "T_AT_CHARSET");
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
		if ($char === "@" && $state === "T_DOCUMENT" && strtolower(substr($this->parser->getSource(), $index, 8)) === "@charset")
			{
			$this->parser->pushState("T_AT_CHARSET");
			$this->parser->clearBuffer();
			return $index + 8;
			}
		elseif (($char === ";" || $char === "\n") && $state === "T_AT_CHARSET")
			{
			$charset = $this->parser->getAndClearBuffer(";");
			$this->parser->popState();
			$this->parser->appendToken(new CssAtCharsetToken($charset));
			}
		else
			{
			return false;
			}
		return true;
		}
	}
?>