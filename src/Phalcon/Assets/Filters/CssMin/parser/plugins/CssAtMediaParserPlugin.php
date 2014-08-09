<?php
/**
 * {@link aCssParserPlugin Parser plugin} for parsing @media at-rule block.
 * 
 * Found @media at-rule blocks will add a {@link CssAtMediaStartToken} and {@link CssAtMediaEndToken} to the parser. 
 * This plugin will also set the the current media types using {@link CssParser::setMediaTypes()} and
 * {@link CssParser::unsetMediaTypes()}.
 *
 * @package		CssMin/Parser/Plugins
 * @link		http://code.google.com/p/cssmin/
 * @author		Joe Scylla <joe.scylla@gmail.com>
 * @copyright	2008 - 2011 Joe Scylla <joe.scylla@gmail.com>
 * @license		http://opensource.org/licenses/mit-license.php MIT License
 * @version		3.0.1
 */
class CssAtMediaParserPlugin extends aCssParserPlugin
	{
	/**
	 * Implements {@link aCssParserPlugin::getTriggerChars()}.
	 * 
	 * @return array
	 */
	public function getTriggerChars()
		{
		return array("@", "{", "}");
		}
	/**
	 * Implements {@link aCssParserPlugin::getTriggerStates()}.
	 * 
	 * @return array
	 */
	public function getTriggerStates()
		{
		return array("T_DOCUMENT", "T_AT_MEDIA::PREPARE", "T_AT_MEDIA");
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
		if ($char === "@" && $state === "T_DOCUMENT" && strtolower(substr($this->parser->getSource(), $index, 6)) === "@media")
			{
			$this->parser->pushState("T_AT_MEDIA::PREPARE");
			$this->parser->clearBuffer();
			return $index + 6;
			}
		elseif ($char === "{" && $state === "T_AT_MEDIA::PREPARE")
			{
			$mediaTypes = array_filter(array_map("trim", explode(",", $this->parser->getAndClearBuffer("{"))));
			$this->parser->setMediaTypes($mediaTypes);
			$this->parser->setState("T_AT_MEDIA");
			$this->parser->appendToken(new CssAtMediaStartToken($mediaTypes));
			}
		elseif ($char === "}" && $state === "T_AT_MEDIA")
			{
			$this->parser->appendToken(new CssAtMediaEndToken());
			$this->parser->clearBuffer();
			$this->parser->unsetMediaTypes();
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