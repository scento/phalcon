<?php
/**
 * This {@link aCssMinifierFilter minifier filter} import external css files defined with the @import at-rule into the 
 * current stylesheet. 
 * 
 * @package		CssMin/Minifier/Filters
 * @link		http://code.google.com/p/cssmin/
 * @author		Joe Scylla <joe.scylla@gmail.com>
 * @copyright	2008 - 2011 Joe Scylla <joe.scylla@gmail.com>
 * @license		http://opensource.org/licenses/mit-license.php MIT License
 * @version		3.0.1
 */
class CssImportImportsMinifierFilter extends aCssMinifierFilter
	{
	/**
	 * Array with already imported external stylesheets.
	 * 
	 * @var array
	 */
	private $imported = array();
	/**
	 * Implements {@link aCssMinifierFilter::filter()}.
	 * 
	 * @param array $tokens Array of objects of type aCssToken
	 * @return integer Count of added, changed or removed tokens; a return value large than 0 will rebuild the array
	 */
	public function apply(array &$tokens)
		{
		if (!isset($this->configuration["BasePath"]) || !is_dir($this->configuration["BasePath"]))
			{
			CssMin::triggerError(new CssError(__FILE__, __LINE__, __METHOD__ . ": Base path <code>" . ($this->configuration["BasePath"] ? $this->configuration["BasePath"] : "null"). "</code> is not a directory"));
			return 0;
			}
		for ($i = 0, $l = count($tokens); $i < $l; $i++)
			{
			if (get_class($tokens[$i]) === "CssAtImportToken")
				{
				$import = $this->configuration["BasePath"] . "/" . $tokens[$i]->Import;
				// Import file was not found/is not a file
				if (!is_file($import))
					{
					CssMin::triggerError(new CssError(__FILE__, __LINE__, __METHOD__ . ": Import file <code>" . $import. "</code> was not found.", (string) $tokens[$i]));
					}
				// Import file already imported; remove this @import at-rule to prevent recursions
				elseif (in_array($import, $this->imported))
					{
					CssMin::triggerError(new CssError(__FILE__, __LINE__, __METHOD__ . ": Import file <code>" . $import. "</code> was already imported.", (string) $tokens[$i]));
					$tokens[$i] = null;
					}
				else
					{
					$this->imported[] = $import;
					$parser = new CssParser(file_get_contents($import));
					$import = $parser->getTokens();
					// The @import at-rule has media types defined requiring special handling
					if (count($tokens[$i]->MediaTypes) > 0 && !(count($tokens[$i]->MediaTypes) == 1 && $tokens[$i]->MediaTypes[0] == "all"))
						{
						$blocks = array();
						/*
						 * Filter or set media types of @import at-rule or remove the @import at-rule if no media type is matching the parent @import at-rule
						 */
						for($ii = 0, $ll = count($import); $ii < $ll; $ii++)
							{
							if (get_class($import[$ii]) === "CssAtImportToken")
								{
								// @import at-rule defines no media type or only the "all" media type; set the media types to the one defined in the parent @import at-rule
								if (count($import[$ii]->MediaTypes) == 0 || (count($import[$ii]->MediaTypes) == 1 && $import[$ii]->MediaTypes[0] == "all"))
									{
									$import[$ii]->MediaTypes = $tokens[$i]->MediaTypes;
									}
								// @import at-rule defineds one or more media types; filter out media types not matching with the  parent @import at-rule
								elseif (count($import[$ii]->MediaTypes > 0))
									{
									foreach ($import[$ii]->MediaTypes as $index => $mediaType)
										{
										if (!in_array($mediaType, $tokens[$i]->MediaTypes))
											{
											unset($import[$ii]->MediaTypes[$index]);
											}
										}
									$import[$ii]->MediaTypes = array_values($import[$ii]->MediaTypes);
									// If there are no media types left in the @import at-rule remove the @import at-rule
									if (count($import[$ii]->MediaTypes) == 0)
										{
										$import[$ii] = null;
										}
									}
								}
							}
						/*
						 * Remove media types of @media at-rule block not defined in the @import at-rule
						 */
						for($ii = 0, $ll = count($import); $ii < $ll; $ii++)
							{
							if (get_class($import[$ii]) === "CssAtMediaStartToken")
								{
								foreach ($import[$ii]->MediaTypes as $index => $mediaType)
									{
									if (!in_array($mediaType, $tokens[$i]->MediaTypes))
										{
										unset($import[$ii]->MediaTypes[$index]);
										}
									$import[$ii]->MediaTypes = array_values($import[$ii]->MediaTypes);
									}
								}
							}
						/*
						 * If no media types left of the @media at-rule block remove the complete block
						 */
						for($ii = 0, $ll = count($import); $ii < $ll; $ii++)
							{
							if (get_class($import[$ii]) === "CssAtMediaStartToken")
								{
								if (count($import[$ii]->MediaTypes) === 0)
									{
									for ($iii = $ii; $iii < $ll; $iii++)
										{
										if (get_class($import[$iii]) === "CssAtMediaEndToken")
											{
											break;
											}
										}
									if (get_class($import[$iii]) === "CssAtMediaEndToken")
										{
										array_splice($import, $ii, $iii - $ii + 1, array());
										$ll = count($import);
										}
									}
								}
							}
						/*
						 * If the media types of the @media at-rule equals the media types defined in the @import 
						 * at-rule remove the CssAtMediaStartToken and CssAtMediaEndToken token
						 */ 
						for($ii = 0, $ll = count($import); $ii < $ll; $ii++)
							{
							if (get_class($import[$ii]) === "CssAtMediaStartToken" && count(array_diff($tokens[$i]->MediaTypes, $import[$ii]->MediaTypes)) === 0)
								{
								for ($iii = $ii; $iii < $ll; $iii++)
									{
									if (get_class($import[$iii]) == "CssAtMediaEndToken")
										{
										break;
										}
									}
								if (get_class($import[$iii]) == "CssAtMediaEndToken")
									{
									unset($import[$ii]);
									unset($import[$iii]);
									$import = array_values($import);
									$ll = count($import);
									}
								}
							}
						/**
						 * Extract CssAtImportToken and CssAtCharsetToken tokens
						 */
						for($ii = 0, $ll = count($import); $ii < $ll; $ii++)
							{
							$class = get_class($import[$ii]);
							if ($class === "CssAtImportToken" || $class === "CssAtCharsetToken")
								{
								$blocks = array_merge($blocks, array_splice($import, $ii, 1, array()));
								$ll = count($import);
								}
							}
						/*
						 * Extract the @font-face, @media and @page at-rule block
						 */
						for($ii = 0, $ll = count($import); $ii < $ll; $ii++)
							{
							$class = get_class($import[$ii]);
							if ($class === "CssAtFontFaceStartToken" || $class === "CssAtMediaStartToken" || $class === "CssAtPageStartToken" || $class === "CssAtVariablesStartToken")
								{
								for ($iii = $ii; $iii < $ll; $iii++)
									{
									$class = get_class($import[$iii]);
									if ($class === "CssAtFontFaceEndToken" || $class === "CssAtMediaEndToken" || $class === "CssAtPageEndToken" || $class === "CssAtVariablesEndToken")
										{
										break;
										}
									}
								$class = get_class($import[$iii]);
								if (isset($import[$iii]) && ($class === "CssAtFontFaceEndToken" || $class === "CssAtMediaEndToken" || $class === "CssAtPageEndToken" || $class === "CssAtVariablesEndToken"))
									{
									$blocks = array_merge($blocks, array_splice($import, $ii, $iii - $ii + 1, array()));
									$ll = count($import);
									}
								}
							}
						// Create the import array with extracted tokens and the rulesets wrapped into a @media at-rule block
						$import = array_merge($blocks, array(new CssAtMediaStartToken($tokens[$i]->MediaTypes)), $import, array(new CssAtMediaEndToken()));
						}
					// Insert the imported tokens
					array_splice($tokens, $i, 1, $import);
					// Modify parameters of the for-loop
					$i--;
					$l = count($tokens);
					}
				}
			}
		}
	}
?>