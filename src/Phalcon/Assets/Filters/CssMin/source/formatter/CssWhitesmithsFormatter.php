<?php
/**
 * {@link aCssFromatter Formatter} returning the CSS source in {@link http://goo.gl/etzLs Whitesmiths indent style}.
 * 
 * @package		CssMin/Formatter
 * @link		http://code.google.com/p/cssmin/
 * @author		Joe Scylla <joe.scylla@gmail.com>
 * @copyright	2008 - 2011 Joe Scylla <joe.scylla@gmail.com>
 * @license		http://opensource.org/licenses/mit-license.php MIT License
 * @version		3.0.1
 */
class CssWhitesmithsFormatter extends aCssFormatter
	{
	/**
	 * Implements {@link aCssFormatter::__toString()}.
	 * 
	 * @return string
	 */
	public function __toString()
		{
		$r				= array();
		$level			= 0;
		for ($i = 0, $l = count($this->tokens); $i < $l; $i++)
			{
			$token		= $this->tokens[$i];
			$class		= get_class($token);
			$indent 	= str_repeat($this->indent, $level);
			if ($class === "CssCommentToken")
				{
				$lines = array_map("trim", explode("\n", $token->Comment));
				for ($ii = 0, $ll = count($lines); $ii < $ll; $ii++)
					{
					$r[] = $indent . (substr($lines[$ii], 0, 1) == "*" ? " " : "") . $lines[$ii];
					}
				}
			elseif ($class === "CssAtCharsetToken")
				{
				$r[] = $indent . "@charset " . $token->Charset . ";";
				}
			elseif ($class === "CssAtFontFaceStartToken")
				{
				$r[] = $indent . "@font-face";
				$r[] = $this->indent . $indent . "{";
				$level++;
				}
			elseif ($class === "CssAtImportToken")
				{
				$r[] = $indent . "@import " . $token->Import . " " . implode(", ", $token->MediaTypes) . ";";
				}
			elseif ($class === "CssAtKeyframesStartToken")
				{
				$r[] = $indent . "@keyframes \"" . $token->Name . "\"";
				$r[] = $this->indent . $indent . "{";
				$level++;
				}
			elseif ($class === "CssAtMediaStartToken")
				{
				$r[] = $indent . "@media " . implode(", ", $token->MediaTypes);
				$r[] = $this->indent . $indent . "{";
				$level++;
				}
			elseif ($class === "CssAtPageStartToken")
				{
				$r[] = $indent . "@page";
				$r[] = $this->indent . $indent . "{";
				$level++;
				}
			elseif ($class === "CssAtVariablesStartToken")
				{
				$r[] = $indent . "@variables " . implode(", ", $token->MediaTypes);
				$r[] = $this->indent . $indent . "{";
				$level++;
				}
			elseif ($class === "CssRulesetStartToken" || $class === "CssAtKeyframesRulesetStartToken")
				{
				$r[] = $indent . implode(", ", $token->Selectors);
				$r[] = $this->indent . $indent . "{";
				$level++;
				}
			elseif ($class == "CssAtFontFaceDeclarationToken"
				|| $class === "CssAtKeyframesRulesetDeclarationToken"
				|| $class === "CssAtPageDeclarationToken"
				|| $class == "CssAtVariablesDeclarationToken"
				|| $class === "CssRulesetDeclarationToken"
				)
				{
				$declaration = $indent . $token->Property . ": ";
				if ($this->padding)
					{
					$declaration = str_pad($declaration, $this->padding, " ", STR_PAD_RIGHT);
					}
				$r[] = $declaration . $token->Value . ($token->IsImportant ? " !important" : "") . ";";
				}
			elseif ($class === "CssAtFontFaceEndToken"
				|| $class === "CssAtMediaEndToken"
				|| $class === "CssAtKeyframesEndToken"
				|| $class === "CssAtKeyframesRulesetEndToken"
				|| $class === "CssAtPageEndToken"
				|| $class === "CssAtVariablesEndToken"
				|| $class === "CssRulesetEndToken"
				)
				{
				$r[] = $indent . "}";
				$level--;
				}
			}
		return implode("\n", $r);
		}
	}
?>