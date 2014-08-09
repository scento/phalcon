<?php
/**
 * This {@link aCssMinifierFilter minifier filter} will parse the variable declarations out of @variables at-rule 
 * blocks. The variables will get store in the {@link CssVariablesMinifierPlugin} that will apply the variables to 
 * declaration.
 * 
 * @package		CssMin/Minifier/Filters
 * @link		http://code.google.com/p/cssmin/
 * @author		Joe Scylla <joe.scylla@gmail.com>
 * @copyright	2008 - 2011 Joe Scylla <joe.scylla@gmail.com>
 * @license		http://opensource.org/licenses/mit-license.php MIT License
 * @version		3.0.1
 */
class CssVariablesMinifierFilter extends aCssMinifierFilter
	{
	/**
	 * Implements {@link aCssMinifierFilter::filter()}.
	 * 
	 * @param array $tokens Array of objects of type aCssToken
	 * @return integer Count of added, changed or removed tokens; a return value large than 0 will rebuild the array
	 */
	public function apply(array &$tokens)
		{
		$variables			= array();
		$defaultMediaTypes	= array("all");
		$mediaTypes			= array();
		$remove				= array();
		for($i = 0, $l = count($tokens); $i < $l; $i++)
			{
			// @variables at-rule block found
			if (get_class($tokens[$i]) === "CssAtVariablesStartToken")
				{
				$remove[] = $i;
				$mediaTypes = (count($tokens[$i]->MediaTypes) == 0 ? $defaultMediaTypes : $tokens[$i]->MediaTypes);
				foreach ($mediaTypes as $mediaType)
					{
					if (!isset($variables[$mediaType]))
						{
						$variables[$mediaType] = array();
						}
					}
				// Read the variable declaration tokens
				for($i = $i; $i < $l; $i++)
					{
					// Found a variable declaration => read the variable values
					if (get_class($tokens[$i]) === "CssAtVariablesDeclarationToken")
						{
						foreach ($mediaTypes as $mediaType)
							{
							$variables[$mediaType][$tokens[$i]->Property] = $tokens[$i]->Value;
							}
						$remove[] = $i;
						}
					// Found the variables end token => break;
					elseif (get_class($tokens[$i]) === "CssAtVariablesEndToken")
						{
						$remove[] = $i;
						break;
						}
					}
				}
			}
		// Variables in @variables at-rule blocks
		foreach($variables as $mediaType => $null)
			{
			foreach($variables[$mediaType] as $variable => $value)
				{
				// If a var() statement in a variable value found...
				if (stripos($value, "var") !== false && preg_match_all("/var\((.+)\)/iSU", $value, $m))
					{
					// ... then replace the var() statement with the variable values.
					for ($i = 0, $l = count($m[0]); $i < $l; $i++)
						{
						$variables[$mediaType][$variable] = str_replace($m[0][$i], (isset($variables[$mediaType][$m[1][$i]]) ? $variables[$mediaType][$m[1][$i]] : ""), $variables[$mediaType][$variable]);
						}
					}
				}
			}
		// Remove the complete @variables at-rule block
		foreach ($remove as $i)
			{
			$tokens[$i] = null;
			}
		if (!($plugin = $this->minifier->getPlugin("CssVariablesMinifierPlugin")))
			{
			CssMin::triggerError(new CssError(__FILE__, __LINE__, __METHOD__ . ": The plugin <code>CssVariablesMinifierPlugin</code> was not found but is required for <code>" . __CLASS__ . "</code>"));
			}
		else
			{
			$plugin->setVariables($variables);
			}
		return count($remove);
		}
	}
?>