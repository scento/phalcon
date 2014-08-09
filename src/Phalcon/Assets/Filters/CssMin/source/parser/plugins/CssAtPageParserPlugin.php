<?php
/**
 * {@link aCssParserPlugin Parser plugin} for parsing @page at-rule block with including declarations.
 * 
 * Found @page at-rule blocks will add a {@link CssAtPageStartToken} and {@link CssAtPageEndToken} to the 
 * parser; including declarations as {@link CssAtPageDeclarationToken}.
 * 
 * @package		CssMin/Parser/Plugins
 * @link		http://code.google.com/p/cssmin/
 * @author		Joe Scylla <joe.scylla@gmail.com>
 * @copyright	2008 - 2011 Joe Scylla <joe.scylla@gmail.com>
 * @license		http://opensource.org/licenses/mit-license.php MIT License
 * @version		3.0.1
 */
class CssAtPageParserPlugin extends aCssParserPlugin
	{
	/**
	 * Implements {@link aCssParserPlugin::getTriggerChars()}.
	 * 
	 * @return array
	 */
	public function getTriggerChars()
		{
		return array("@", "{", "}", ":", ";");
		}
	/**
	 * Implements {@link aCssParserPlugin::getTriggerStates()}.
	 * 
	 * @return array
	 */
	public function getTriggerStates()
		{
		return array("T_DOCUMENT", "T_AT_PAGE::SELECTOR", "T_AT_PAGE", "T_AT_PAGE_DECLARATION");
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
		// Start of @page at-rule block
		if ($char === "@" && $state === "T_DOCUMENT" && strtolower(substr($this->parser->getSource(), $index, 5)) === "@page")
			{
			$this->parser->pushState("T_AT_PAGE::SELECTOR");
			$this->parser->clearBuffer();
			return $index + 5;
			}
		// Start of @page declarations
		elseif ($char === "{" && $state === "T_AT_PAGE::SELECTOR")
			{
			$selector = $this->parser->getAndClearBuffer("{");
			$this->parser->setState("T_AT_PAGE");
			$this->parser->clearBuffer();
			$this->parser->appendToken(new CssAtPageStartToken($selector));
			}
		// Start of @page declaration
		elseif ($char === ":" && $state === "T_AT_PAGE")
			{
			$this->parser->pushState("T_AT_PAGE_DECLARATION");
			$this->buffer = $this->parser->getAndClearBuffer(":", true);
			}
		// Unterminated @font-face declaration
		elseif ($char === ":" && $state === "T_AT_PAGE_DECLARATION")
			{
			// Ignore Internet Explorer filter declarations
			if ($this->buffer === "filter")
				{
				return false;
				}
			CssMin::triggerError(new CssError(__FILE__, __LINE__, __METHOD__ . ": Unterminated @page declaration", $this->buffer . ":" . $this->parser->getBuffer() . "_"));
			}
		// End of @page declaration
		elseif (($char === ";" || $char === "}") && $state == "T_AT_PAGE_DECLARATION")
			{
			$value = $this->parser->getAndClearBuffer(";}");
			if (strtolower(substr($value, -10, 10)) == "!important")
				{
				$value = trim(substr($value, 0, -10));
				$isImportant = true;
				}
			else
				{
				$isImportant = false;
				}
			$this->parser->popState();
			$this->parser->appendToken(new CssAtPageDeclarationToken($this->buffer, $value, $isImportant));
			// --
			if ($char === "}")
				{
				$this->parser->popState();
				$this->parser->appendToken(new CssAtPageEndToken());
				}
			$this->buffer = "";
			}
		// End of @page at-rule block
		elseif ($char === "}" && $state === "T_AT_PAGE")
			{
			$this->parser->popState();
			$this->parser->clearBuffer();
			$this->parser->appendToken(new CssAtPageEndToken());
			}
		else
			{
			return false;
			}
		return true;
		}
	}
?>