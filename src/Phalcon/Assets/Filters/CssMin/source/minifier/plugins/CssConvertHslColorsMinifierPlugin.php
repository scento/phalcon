<?php
/**
 * This {@link aCssMinifierPlugin} will convert a color value in hsl notation to hexadecimal notation.
 * 
 * Example:
 * <code>
 * color: hsl(232,36%,48%);
 * </code>
 * 
 * Will get converted to:
 * <code>
 * color:#4e5aa7;
 * </code>
 * 
 * @package		CssMin/Minifier/Plugins
 * @link		http://code.google.com/p/cssmin/
 * @author		Joe Scylla <joe.scylla@gmail.com>
 * @copyright	2008 - 2011 Joe Scylla <joe.scylla@gmail.com>
 * @license		http://opensource.org/licenses/mit-license.php MIT License
 * @version		3.0.1
 */
class CssConvertHslColorsMinifierPlugin extends aCssMinifierPlugin
	{
	/**
	 * Regular expression matching the value.
	 * 
	 * @var string
	 */
	private $reMatch = "/^hsl\s*\(\s*([0-9]+)\s*,\s*([0-9]+)\s*%\s*,\s*([0-9]+)\s*%\s*\)/iS";
	/**
	 * Implements {@link aCssMinifierPlugin::minify()}.
	 * 
	 * @param aCssToken $token Token to process
	 * @return boolean Return TRUE to break the processing of this token; FALSE to continue
	 */
	public function apply(aCssToken &$token)
		{
		if (stripos($token->Value, "hsl") !== false && preg_match($this->reMatch, $token->Value, $m))
			{
			$token->Value = str_replace($m[0], $this->hsl2hex($m[1], $m[2], $m[3]), $token->Value);
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
	/**
	 * Convert a HSL value to hexadecimal notation.
	 * 
	 * Based on: {@link http://www.easyrgb.com/index.php?X=MATH&H=19#text19}.
	 * 
	 * @param integer $hue Hue
	 * @param integer $saturation Saturation
	 * @param integer $lightness Lightnesss
	 * @return string
	 */
	private function hsl2hex($hue, $saturation, $lightness)
		{
		$hue		= $hue / 360;
		$saturation	= $saturation / 100;
		$lightness	= $lightness / 100;
		if ($saturation == 0)
			{
			$red	= $lightness * 255;
			$green	= $lightness * 255;
			$blue	= $lightness * 255;
			}
		else
			{
			if ($lightness < 0.5 )
				{
				$v2 = $lightness * (1 + $saturation);
				}
			else
				{
				$v2 = ($lightness + $saturation) - ($saturation * $lightness);
				}
			$v1		= 2 * $lightness - $v2;
			$red	= 255 * self::hue2rgb($v1, $v2, $hue + (1 / 3));
			$green	= 255 * self::hue2rgb($v1, $v2, $hue);
			$blue	= 255 * self::hue2rgb($v1, $v2, $hue - (1 / 3));
			}
		return "#" . str_pad(dechex(round($red)), 2, "0", STR_PAD_LEFT) . str_pad(dechex(round($green)), 2, "0", STR_PAD_LEFT) . str_pad(dechex(round($blue)), 2, "0", STR_PAD_LEFT);
		}
	/**
	 * Apply hue to a rgb color value.
	 * 
	 * @param integer $v1 Value 1
	 * @param integer $v2 Value 2
	 * @param integer $hue Hue
	 * @return integer
	 */
	private function hue2rgb($v1, $v2, $hue)
		{
		if ($hue < 0)
			{
			$hue += 1;
			}
		if ($hue > 1)
			{
			$hue -= 1;
			}
		if ((6 * $hue) < 1)
			{
			return ($v1 + ($v2 - $v1) * 6 * $hue);
			}
		if ((2 * $hue) < 1)
			{
			return ($v2);
			}
		if ((3 * $hue) < 2)
			{
			return ($v1 + ($v2 - $v1) * (( 2 / 3) - $hue) * 6);
			}
		return $v1;
		}
	}
?>