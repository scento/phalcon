<?php
/**
 * {@link aCssParserPlugin Parser plugin} for parsing @keyframes at-rule blocks, rulesets and declarations.
 * 
 * @package		CssMin/Parser/Plugins
 * @link		http://code.google.com/p/cssmin/
 * @author		Joe Scylla <joe.scylla@gmail.com>
 * @copyright	2008 - 2011 Joe Scylla <joe.scylla@gmail.com>
 * @license		http://opensource.org/licenses/mit-license.php MIT License
 * @version		3.0.1
 */
class CssAtKeyframesParserPlugin extends aCssParserPlugin
	{
	/**
	 * @var string Keyword
	 */
	private $atRuleName = "";
	/**
	 * Selectors.
	 * 
	 * @var array
	 */
	private $selectors = array();
	/**
	 * Implements {@link aCssParserPlugin::getTriggerChars()}.
	 * 
	 * @return array
	 */
	public function getTriggerChars()
		{
		return array("@", "{", "}", ":", ",", ";");
		}
	/**
	 * Implements {@link aCssParserPlugin::getTriggerStates()}.
	 * 
	 * @return array
	 */
	public function getTriggerStates()
		{
		return array("T_DOCUMENT", "T_AT_KEYFRAMES::NAME", "T_AT_KEYFRAMES", "T_AT_KEYFRAMES_RULESETS", "T_AT_KEYFRAMES_RULESET", "T_AT_KEYFRAMES_RULESET_DECLARATION");
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
		// Start of @keyframes at-rule block
		if ($char === "@" && $state === "T_DOCUMENT" && strtolower(substr($this->parser->getSource(), $index, 10)) === "@keyframes") 
			{
			$this->atRuleName = "keyframes";
			$this->parser->pushState("T_AT_KEYFRAMES::NAME");
			$this->parser->clearBuffer();
			return $index + 10;
			}
		// Start of @keyframes at-rule block (@-moz-keyframes)
		elseif ($char === "@" && $state === "T_DOCUMENT" && strtolower(substr($this->parser->getSource(), $index, 15)) === "@-moz-keyframes")
			{
			$this->atRuleName = "-moz-keyframes";
			$this->parser->pushState("T_AT_KEYFRAMES::NAME");
			$this->parser->clearBuffer();
			return $index + 15;
			}
		// Start of @keyframes at-rule block (@-webkit-keyframes)
		elseif ($char === "@" && $state === "T_DOCUMENT" && strtolower(substr($this->parser->getSource(), $index, 18)) === "@-webkit-keyframes")
			{
			$this->atRuleName = "-webkit-keyframes";
			$this->parser->pushState("T_AT_KEYFRAMES::NAME");
			$this->parser->clearBuffer();
			return $index + 18;
			}
		// Start of @keyframes rulesets
		elseif ($char === "{" && $state === "T_AT_KEYFRAMES::NAME")
			{
			$name = $this->parser->getAndClearBuffer("{\"'");
			$this->parser->setState("T_AT_KEYFRAMES_RULESETS");
			$this->parser->clearBuffer();
			$this->parser->appendToken(new CssAtKeyframesStartToken($name, $this->atRuleName));
			}
		// Start of @keyframe ruleset and selectors
		if ($char === "," && $state === "T_AT_KEYFRAMES_RULESETS")
			{
			$this->selectors[] = $this->parser->getAndClearBuffer(",{");
			}
		// Start of a @keyframes ruleset
		elseif ($char === "{" && $state === "T_AT_KEYFRAMES_RULESETS")
			{
			if ($this->parser->getBuffer() !== "")
				{
				$this->selectors[] = $this->parser->getAndClearBuffer(",{");
				$this->parser->pushState("T_AT_KEYFRAMES_RULESET");
				$this->parser->appendToken(new CssAtKeyframesRulesetStartToken($this->selectors));
				$this->selectors = array();
				}
			}
		// Start of @keyframes ruleset declaration
		elseif ($char === ":" && $state === "T_AT_KEYFRAMES_RULESET")
			{
			$this->parser->pushState("T_AT_KEYFRAMES_RULESET_DECLARATION");
			$this->buffer = $this->parser->getAndClearBuffer(":;", true);
			}
		// Unterminated @keyframes ruleset declaration
		elseif ($char === ":" && $state === "T_AT_KEYFRAMES_RULESET_DECLARATION")
			{
			// Ignore Internet Explorer filter declarations
			if ($this->buffer === "filter")
				{
				return false;
				}
			CssMin::triggerError(new CssError(__FILE__, __LINE__, __METHOD__ . ": Unterminated @keyframes ruleset declaration", $this->buffer . ":" . $this->parser->getBuffer() . "_"));
			}
		// End of declaration
		elseif (($char === ";" || $char === "}") && $state === "T_AT_KEYFRAMES_RULESET_DECLARATION")
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
			$this->parser->appendToken(new CssAtKeyframesRulesetDeclarationToken($this->buffer, $value, $isImportant));
			// Declaration ends with a right curly brace; so we have to end the ruleset
			if ($char === "}")
				{
				$this->parser->appendToken(new CssAtKeyframesRulesetEndToken());
				$this->parser->popState();
				}
			$this->buffer = "";
			}
		// End of @keyframes ruleset
		elseif ($char === "}" && $state === "T_AT_KEYFRAMES_RULESET")
			{
			$this->parser->clearBuffer();
			
			$this->parser->popState();
			$this->parser->appendToken(new CssAtKeyframesRulesetEndToken());
			}
		// End of @keyframes rulesets
		elseif ($char === "}" && $state === "T_AT_KEYFRAMES_RULESETS")
			{
			$this->parser->clearBuffer();
			$this->parser->popState();
			$this->parser->appendToken(new CssAtKeyframesEndToken());
			}
		else
			{
			return false;
			}
		return true;
		}
	}
?>