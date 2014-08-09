<?php
/**
 * This {@link aCssMinifierFilter minifier filter} will convert @keyframes at-rule block to browser specific counterparts.
 * 
 * @package		CssMin/Minifier/Filters
 * @link		http://code.google.com/p/cssmin/
 * @author		Joe Scylla <joe.scylla@gmail.com>
 * @copyright	2008 - 2011 Joe Scylla <joe.scylla@gmail.com>
 * @license		http://opensource.org/licenses/mit-license.php MIT License
 * @version		3.0.1
 */
class CssConvertLevel3AtKeyframesMinifierFilter extends aCssMinifierFilter
	{
	/**
	 * Implements {@link aCssMinifierFilter::filter()}.
	 * 
	 * @param array $tokens Array of objects of type aCssToken
	 * @return integer Count of added, changed or removed tokens; a return value larger than 0 will rebuild the array
	 */
	public function apply(array &$tokens)
		{
		$r = 0;
		$transformations = array("-moz-keyframes", "-webkit-keyframes");
		for ($i = 0, $l = count($tokens); $i < $l; $i++)
			{
			if (get_class($tokens[$i]) === "CssAtKeyframesStartToken")
				{
				for ($ii = $i; $ii < $l; $ii++)
					{
					if (get_class($tokens[$ii]) === "CssAtKeyframesEndToken")
						{
						break;
						}
					}
				if (get_class($tokens[$ii]) === "CssAtKeyframesEndToken")
					{
					$add	= array();
					$source	= array();
					for ($iii = $i; $iii <= $ii; $iii++)
						{
						$source[] = clone($tokens[$iii]);
						}
					foreach ($transformations as $transformation)
						{
						$t = array();
						foreach ($source as $token)
							{
							$t[] = clone($token);
							}
						$t[0]->AtRuleName = $transformation;
						$add = array_merge($add, $t);
						}
					if (isset($this->configuration["RemoveSource"]) && $this->configuration["RemoveSource"] === true)
						{
						array_splice($tokens, $i, $ii - $i + 1, $add);
						}
					else
						{
						array_splice($tokens, $ii + 1, 0, $add);
						}
					$l = count($tokens);
					$i = $ii + count($add);
					$r += count($add);
					}
				}
			}
		return $r;
		}
	}
?>