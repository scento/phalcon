<?php
/**
 * This {@link aCssMinifierPlugin} will process var-statement and sets the declaration value to the variable value. 
 * 
 * This plugin only apply the variable values. The variable values itself will get parsed by the
 * {@link CssVariablesMinifierFilter}.
 * 
 * Example:
 * <code>
 * @variables
 * 		{
 * 		defaultColor: black;
 * 		}
 * color: var(defaultColor);
 * </code>
 * 
 * Will get converted to:
 * <code>
 * color:black;
 * </code>
 *
 * @package		CssMin/Minifier/Plugins
 * @link		http://code.google.com/p/cssmin/
 * @author		Joe Scylla <joe.scylla@gmail.com>
 * @copyright	2008 - 2011 Joe Scylla <joe.scylla@gmail.com>
 * @license		http://opensource.org/licenses/mit-license.php MIT License
 * @version		3.0.1
 */
class CssVariablesMinifierPlugin extends aCssMinifierPlugin
	{
	/**
	 * Regular expression matching a value.
	 * 
	 * @var string
	 */
	private $reMatch = "/var\((.+)\)/iSU";
	/**
	 * Parsed variables.
	 * 
	 * @var array
	 */
	private $variables = null;
	/**
	 * Returns the variables.
	 * 
	 * @return array
	 */
	public function getVariables()
		{
		return $this->variables;
		}
	/**
	 * Implements {@link aCssMinifierPlugin::minify()}.
	 * 
	 * @param aCssToken $token Token to process
	 * @return boolean Return TRUE to break the processing of this token; FALSE to continue
	 */
	public function apply(aCssToken &$token)
		{
		if (stripos($token->Value, "var") !== false && preg_match_all($this->reMatch, $token->Value, $m))
			{
			$mediaTypes	= $token->MediaTypes;
			if (!in_array("all", $mediaTypes))
				{
				$mediaTypes[] = "all";
				}
			for ($i = 0, $l = count($m[0]); $i < $l; $i++)
				{
				$variable	= trim($m[1][$i]);
				foreach ($mediaTypes as $mediaType)
					{
					if (isset($this->variables[$mediaType], $this->variables[$mediaType][$variable]))
						{
						// Variable value found => set the declaration value to the variable value and return
						$token->Value = str_replace($m[0][$i], $this->variables[$mediaType][$variable], $token->Value);
						continue 2;
						}
					}
				// If no value was found trigger an error and replace the token with a CssNullToken
				CssMin::triggerError(new CssError(__FILE__, __LINE__, __METHOD__ . ": No value found for variable <code>" . $variable . "</code> in media types <code>" . implode(", ", $mediaTypes) . "</code>", (string) $token));
				$token = new CssNullToken();
				return true;
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
	/**
	 * Sets the variables.
	 * 
	 * @param array $variables Variables to set
	 * @return void
	 */
	public function setVariables(array $variables)
		{
		$this->variables = $variables;
		}
	}
?>