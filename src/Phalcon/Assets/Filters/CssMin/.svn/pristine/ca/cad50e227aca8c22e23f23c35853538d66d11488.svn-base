<?php
/**
 * This {@link aCssMinifierPlugin} will convert a color value in rgb notation to hexadecimal notation.
 * 
 * Example:
 * <code>
 * color: rgb(200,60%,5);
 * </code>
 * 
 * Will get converted to:
 * <code>
 * color:#c89905;
 * </code>
 *
 * @package		CssMin/Minifier/Plugins
 * @link		http://code.google.com/p/cssmin/
 * @author		Joe Scylla <joe.scylla@gmail.com>
 * @copyright	2008 - 2011 Joe Scylla <joe.scylla@gmail.com>
 * @license		http://opensource.org/licenses/mit-license.php MIT License
 * @version		3.0.1
 */
class CssConvertRgbColorsMinifierPlugin extends aCssMinifierPlugin
	{
	/**
	 * Regular expression matching the value.
	 * 
	 * @var string
	 */
	private $reMatch = "/rgb\s*\(\s*([0-9%]+)\s*,\s*([0-9%]+)\s*,\s*([0-9%]+)\s*\)/iS";
	/**
	 * Implements {@link aCssMinifierPlugin::minify()}.
	 * 
	 * @param aCssToken $token Token to process
	 * @return boolean Return TRUE to break the processing of this token; FALSE to continue
	 */
	public function apply(aCssToken &$token)
		{
		if (stripos($token->Value, "rgb") !== false && preg_match($this->reMatch, $token->Value, $m))
			{
			for ($i = 1, $l = count($m); $i < $l; $i++)
				{
				if (strpos("%", $m[$i]) !== false)
					{
					$m[$i] = substr($m[$i], 0, -1);
					$m[$i] = (int) (256 * ($m[$i] / 100));
					}
				$m[$i] = str_pad(dechex($m[$i]),  2, "0", STR_PAD_LEFT);
				}
			$token->Value = str_replace($m[0], "#" . $m[1] . $m[2] . $m[3], $token->Value);
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