<?php
/**
 * This {@link aCssMinifierFilter minifier filter} sorts the ruleset declarations of a ruleset by name.
 * 
 * @package		CssMin/Minifier/Filters
 * @link		http://code.google.com/p/cssmin/
 * @author		Rowan Beentje <http://assanka.net>
 * @copyright	Rowan Beentje <http://assanka.net>
 * @license		http://opensource.org/licenses/mit-license.php MIT License
 * @version		3.0.1
 */
class CssSortRulesetPropertiesMinifierFilter extends aCssMinifierFilter
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
		for ($i = 0, $l = count($tokens); $i < $l; $i++)
			{
			// Only look for ruleset start rules
			if (get_class($tokens[$i]) !== "CssRulesetStartToken") { continue; }
			// Look for the corresponding ruleset end
			$endIndex = false;
			for ($ii = $i + 1; $ii < $l; $ii++)
				{
				if (get_class($tokens[$ii]) !== "CssRulesetEndToken") { continue; }
				$endIndex = $ii;
				break;
				}
			if (!$endIndex) { break; }
			$startIndex = $i;
			$i = $endIndex;
			// Skip if there's only one token in this ruleset
			if ($endIndex - $startIndex <= 2) { continue; }
			// Ensure that everything between the start and end is a declaration token, for safety
			for ($ii = $startIndex + 1; $ii < $endIndex; $ii++)
				{
				if (get_class($tokens[$ii]) !== "CssRulesetDeclarationToken") { continue(2); }
				}
			$declarations = array_slice($tokens, $startIndex + 1, $endIndex - $startIndex - 1);
			// Check whether a sort is required
			$sortRequired = $lastPropertyName = false;
			foreach ($declarations as $declaration)	
				{
				if ($lastPropertyName)
					{
					if (strcmp($lastPropertyName, $declaration->Property) > 0)
						{
						$sortRequired = true;
						break;
						}
					}
				$lastPropertyName = $declaration->Property;
				}
			if (!$sortRequired) { continue; }
			// Arrange the declarations alphabetically by name
			usort($declarations, array(__CLASS__, "userDefinedSort1"));
			// Update "IsLast" property
			for ($ii = 0, $ll = count($declarations) - 1; $ii <= $ll; $ii++)
				{
				if ($ii == $ll)
					{
					$declarations[$ii]->IsLast = true;
					}
				else
					{
					$declarations[$ii]->IsLast = false;
					}
				}
			// Splice back into the array.
			array_splice($tokens, $startIndex + 1, $endIndex - $startIndex - 1, $declarations);
			$r += $endIndex - $startIndex - 1;
			}
		return $r;
		}
	/**
	 * User defined sort function.
	 * 
	 * @return integer
	 */
	public static function userDefinedSort1($a, $b)
		{
		return strcmp($a->Property, $b->Property);
		}
	}
?>