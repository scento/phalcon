<?php
/**
 * {@link aCssParserPlugin Parser plugin} for parsing @font-face at-rule block with including declarations.
 * 
 * Found @font-face at-rule blocks will add a {@link CssAtFontFaceStartToken} and {@link CssAtFontFaceEndToken} to the 
 * parser; including declarations as {@link CssAtFontFaceDeclarationToken}.
 * 
 * @package		CssMin/Parser/Plugins
 * @link		http://code.google.com/p/cssmin/
 * @author		Joe Scylla <joe.scylla@gmail.com>
 * @copyright	2008 - 2011 Joe Scylla <joe.scylla@gmail.com>
 * @license		http://opensource.org/licenses/mit-license.php MIT License
 * @version		3.0.1
 */
class CssAtFontFaceParserPlugin extends aCssParserPlugin
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
		return array("T_DOCUMENT", "T_AT_FONT_FACE::PREPARE", "T_AT_FONT_FACE", "T_AT_FONT_FACE_DECLARATION");
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
		// Start of @font-face at-rule block
		if ($char === "@" && $state === "T_DOCUMENT" && strtolower(substr($this->parser->getSource(), $index, 10)) === "@font-face")
			{
			$this->parser->pushState("T_AT_FONT_FACE::PREPARE");
			$this->parser->clearBuffer();
			return $index + 10;
			}
		// Start of @font-face declarations
		elseif ($char === "{" && $state === "T_AT_FONT_FACE::PREPARE")
			{
			$this->parser->setState("T_AT_FONT_FACE");
			$this->parser->clearBuffer();
			$this->parser->appendToken(new CssAtFontFaceStartToken());
			}
		// Start of @font-face declaration
		elseif ($char === ":" && $state === "T_AT_FONT_FACE")
			{
			$this->parser->pushState("T_AT_FONT_FACE_DECLARATION");
			$this->buffer = $this->parser->getAndClearBuffer(":", true);
			}
		// Unterminated @font-face declaration
		elseif ($char === ":" && $state === "T_AT_FONT_FACE_DECLARATION")
			{
			// Ignore Internet Explorer filter declarations
			if ($this->buffer === "filter")
				{
				return false;
				}
			CssMin::triggerError(new CssError(__FILE__, __LINE__, __METHOD__ . ": Unterminated @font-face declaration", $this->buffer . ":" . $this->parser->getBuffer() . "_"));
			}
		// End of @font-face declaration
		elseif (($char === ";" || $char === "}") && $state === "T_AT_FONT_FACE_DECLARATION")
			{
			$value = $this->parser->getAndClearBuffer(";}");
			if (strtolower(substr($value, -10, 10)) === "!important")
				{
				$value = trim(substr($value, 0, -10));
				$isImportant = true;
				}
			else
				{
				$isImportant = false;
				}
			$this->parser->popState();
			$this->parser->appendToken(new CssAtFontFaceDeclarationToken($this->buffer, $value, $isImportant));
			$this->buffer = "";
			// --
			if ($char === "}")
				{
				$this->parser->appendToken(new CssAtFontFaceEndToken());
				$this->parser->popState();
				}
			}
		// End of @font-face at-rule block
		elseif ($char === "}" && $state === "T_AT_FONT_FACE")
			{
			$this->parser->appendToken(new CssAtFontFaceEndToken());
			$this->parser->clearBuffer();
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