<?php
/**
 * This {@link aCssMinifierPlugin} will convert hexadecimal color value with 6 chars to their 3 char hexadecimal 
 * notation (if possible). 
 * 
 * Example:
 * <code>
 * color: #aabbcc;
 * </code>
 * 
 * Will get converted to:
 * <code>
 * color:#abc;
 * </code>
 * 
 * @package		CssMin/Minifier/Plugins
 * @link		http://code.google.com/p/cssmin/
 * @author		Joe Scylla <joe.scylla@gmail.com>
 * @copyright	2008 - 2011 Joe Scylla <joe.scylla@gmail.com>
 * @license		http://opensource.org/licenses/mit-license.php MIT License
 * @version		3.0.1
 */
class CssCompressColorValuesMinifierPlugin extends aCssMinifierPlugin
	{
	/**
	 * Regular expression matching 6 char hexadecimal color values.
	 * 
	 * @var string
	 */
	private $reMatch = "/\#([0-9a-f]{6})/iS";
	/**
	 * Implements {@link aCssMinifierPlugin::minify()}.
	 * 
	 * @param aCssToken $token Token to process
	 * @return boolean Return TRUE to break the processing of this token; FALSE to continue
	 */
	public function apply(aCssToken &$token)
		{
		if (strpos($token->Value, "#") !== false && preg_match($this->reMatch, $token->Value, $m))
			{
			$value = strtolower($m[1]);
			if ($value[0] == $value[1] && $value[2] == $value[3] && $value[4] == $value[5])
				{
				$token->Value = str_replace($m[0], "#" . $value[0] . $value[2] . $value[4], $token->Value);
				}
			}
		return false;
		}
	/**
	 * Implements {@link aMinifierPlugin::getTriggerTokens()}
	 * 
	 * @return array
	 */
	public function getTriggerTokens()
		{
		return array
			(
			"CssAtFontFaceDeclarationToken",
			"CssAtPageDeclarationToken",
			"CssRulesetDeclarationToken"
			);
		}
	}
?>