<?php
/**
 * {@link aCssParserPlugin Parser plugin} for parsing @import at-rule.
 * 
 * If a @import at-rule was found this plugin will add a {@link CssAtImportToken} to the parser.
 * 
 * @package		CssMin/Parser/Plugins
 * @link		http://code.google.com/p/cssmin/
 * @author		Joe Scylla <joe.scylla@gmail.com>
 * @copyright	2008 - 2011 Joe Scylla <joe.scylla@gmail.com>
 * @license		http://opensource.org/licenses/mit-license.php MIT License
 * @version		3.0.1
 */
class CssAtImportParserPlugin extends aCssParserPlugin
	{
	/**
	 * Implements {@link aCssParserPlugin::getTriggerChars()}.
	 * 
	 * @return array
	 */
	public function getTriggerChars()
		{
		return array("@", ";", ",", "\n");
		}
	/**
	 * Implements {@link aCssParserPlugin::getTriggerStates()}.
	 * 
	 * @return array
	 */
	public function getTriggerStates()
		{
		return array("T_DOCUMENT", "T_AT_IMPORT");
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
		if ($char === "@" && $state === "T_DOCUMENT" && strtolower(substr($this->parser->getSource(), $index, 7)) === "@import")
			{
			$this->parser->pushState("T_AT_IMPORT");
			$this->parser->clearBuffer();
			return $index + 7;
			}
		elseif (($char === ";" || $char === "\n") && $state === "T_AT_IMPORT")
			{
			$this->buffer = $this->parser->getAndClearBuffer(";");
			$pos = false;
			foreach (array(")", "\"", "'") as $needle)
				{
				if (($pos = strrpos($this->buffer, $needle)) !== false)
					{
					break;
					}
				}
			$import = substr($this->buffer, 0, $pos + 1);
			if (stripos($import, "url(") === 0)
				{
				$import = substr($import, 4, -1);
				}
			$import = trim($import, " \t\n\r\0\x0B'\"");
			$mediaTypes = array_filter(array_map("trim", explode(",", trim(substr($this->buffer, $pos + 1), " \t\n\r\0\x0B{"))));
			if ($pos)
				{
				$this->parser->appendToken(new CssAtImportToken($import, $mediaTypes));
				}
			else
				{
				CssMin::triggerError(new CssError(__FILE__, __LINE__, __METHOD__ . ": Invalid @import at-rule syntax", $this->parser->buffer));
				}
			$this->parser->popState();
			}
		else
			{
			return false;
			}
		return true;
		}
	}
?>