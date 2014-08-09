<?php
/**
 * CSS Parser.
 * 
 * @package		CssMin/Parser
 * @link		http://code.google.com/p/cssmin/
 * @author		Joe Scylla <joe.scylla@gmail.com>
 * @copyright	2008 - 2011 Joe Scylla <joe.scylla@gmail.com>
 * @license		http://opensource.org/licenses/mit-license.php MIT License
 * @version		3.0.1
 */
class CssParser
	{
	/**
	 * Parse buffer.
	 * 
	 * @var string
	 */
	private $buffer = "";
	/**
	 * {@link aCssParserPlugin Plugins}.
	 * 
	 * @var array
	 */
	private $plugins = array();
	/**
	 * Source to parse.
	 * 
	 * @var string
	 */
	private $source = "";
	/**
	 * Current state.
	 * 
	 * @var integer
	 */
	private $state = "T_DOCUMENT";
	/**
	 * Exclusive state.
	 * 
	 * @var string
	 */
	private $stateExclusive = false;
	/**
	 * Media types state.
	 * 
	 * @var mixed
	 */
	private $stateMediaTypes = false;
	/**
	 * State stack.
	 * 
	 * @var array
	 */
	private $states = array("T_DOCUMENT");
	/**
	 * Parsed tokens.
	 * 
	 * @var array
	 */
	private $tokens = array();
	/**
	 * Constructer.
	 * 
	 *  Create instances of the used {@link aCssParserPlugin plugins}.
	 * 
	 * @param string $source CSS source [optional]
	 * @param array $plugins Plugin configuration [optional]
	 * @return void
	 */
	public function __construct($source = null, array $plugins = null)
		{
		$plugins = array_merge(array
			(
			"Comment"		=> true,
			"String"		=> true,
			"Url"			=> true,
			"Expression"	=> true,
			"Ruleset"		=> true,
			"AtCharset"		=> true,
			"AtFontFace"	=> true,
			"AtImport"		=> true,
			"AtKeyframes"	=> true,
			"AtMedia"		=> true,
			"AtPage"		=> true,
			"AtVariables"	=> true
			), is_array($plugins) ? $plugins : array());
		// Create plugin instances
		foreach ($plugins as $name => $config)
			{
			if ($config !== false)
				{
				$class	= "Css" . $name . "ParserPlugin";
				$config = is_array($config) ? $config : array();
				if (class_exists($class))
					{
					$this->plugins[] = new $class($this, $config);
					}
				else
					{
					CssMin::triggerError(new CssError(__FILE__, __LINE__, __METHOD__ . ": The plugin <code>" . $name . "</code> with the class name <code>" . $class . "</code> was not found"));
					}
				}
			}
		if (!is_null($source))
			{
			$this->parse($source);
			}
		}
	/**
	 * Append a token to the array of tokens.
	 * 
	 * @param aCssToken $token Token to append
	 * @return void
	 */
	public function appendToken(aCssToken $token)
		{
		$this->tokens[] = $token;
		}
	/**
	 * Clears the current buffer.
	 * 
	 * @return void
	 */
	public function clearBuffer()
		{
		$this->buffer = "";
		}
	/**
	 * Returns and clear the current buffer.
	 * 
	 * @param string $trim Chars to use to trim the returned buffer
	 * @param boolean $tolower if TRUE the returned buffer will get converted to lower case
	 * @return string
	 */
	public function getAndClearBuffer($trim = "", $tolower = false)
		{
		$r = $this->getBuffer($trim, $tolower);
		$this->buffer = "";
		return $r;
		}
	/**
	 * Returns the current buffer.
	 * 
	 * @param string $trim Chars to use to trim the returned buffer
	 * @param boolean $tolower if TRUE the returned buffer will get converted to lower case
	 * @return string
	 */
	public function getBuffer($trim = "", $tolower = false)
		{
		$r = $this->buffer;
		if ($trim)
			{
			$r = trim($r, " \t\n\r\0\x0B" . $trim);
			}
		if ($tolower)
			{
			$r = strtolower($r);
			}
		return $r;
		}
	/**
	 * Returns the current media types state.
	 * 
	 * @return array
	 */	
	public function getMediaTypes()
		{
		return $this->stateMediaTypes;
		}
	/**
	 * Returns the CSS source.
	 * 
	 * @return string
	 */
	public function getSource()
		{
		return $this->source;
		}
	/**
	 * Returns the current state.
	 * 
	 * @return integer The current state
	 */
	public function getState()
		{
		return $this->state;
		}
	/**
	 * Returns a plugin by class name.
	 * 
	 * @param string $name Class name of the plugin 
	 * @return aCssParserPlugin
	 */
	public function getPlugin($class)
		{
		static $index = null;
		if (is_null($index))
			{
			$index = array();
			for ($i = 0, $l = count($this->plugins); $i < $l; $i++)
				{
				$index[get_class($this->plugins[$i])] = $i;
				}
			}
		return isset($index[$class]) ? $this->plugins[$index[$class]] : false;
		}
	/**
	 * Returns the parsed tokens.
	 * 
	 * @return array
	 */
	public function getTokens()
		{
		return $this->tokens;
		}
	/**
	 * Returns if the current state equals the passed state.
	 * 
	 * @param integer $state State to compare with the current state
	 * @return boolean TRUE is the state equals to the passed state; FALSE if not
	 */
	public function isState($state)
		{
		return ($this->state == $state);
		}
	/**
	 * Parse the CSS source and return a array with parsed tokens.
	 * 
	 * @param string $source CSS source
	 * @return array Array with tokens
	 */
	public function parse($source)
		{
		// Reset
		$this->source = "";
		$this->tokens = array();
		// Create a global and plugin lookup table for trigger chars; set array of plugins as local variable and create 
		// several helper variables for plugin handling
		$globalTriggerChars		= "";
		$plugins				= $this->plugins;
		$pluginCount			= count($plugins);
		$pluginIndex			= array();
		$pluginTriggerStates	= array();
		$pluginTriggerChars		= array();
		for ($i = 0, $l = count($plugins); $i < $l; $i++)
			{
			$tPluginClassName				= get_class($plugins[$i]);
			$pluginTriggerChars[$i]			= implode("", $plugins[$i]->getTriggerChars());
			$tPluginTriggerStates			= $plugins[$i]->getTriggerStates();
			$pluginTriggerStates[$i]		= $tPluginTriggerStates === false ? false : "|" . implode("|", $tPluginTriggerStates) . "|";
			$pluginIndex[$tPluginClassName]	= $i;
			for ($ii = 0, $ll = strlen($pluginTriggerChars[$i]); $ii < $ll; $ii++)
				{
				$c = substr($pluginTriggerChars[$i], $ii, 1);
				if (strpos($globalTriggerChars, $c) === false)
					{
					$globalTriggerChars .= $c;
					}
				}
			}
		// Normalise line endings
		$source			= str_replace("\r\n", "\n", $source);	// Windows to Unix line endings
		$source			= str_replace("\r", "\n", $source);		// Mac to Unix line endings
		$this->source	= $source;
		// Variables
		$buffer			= &$this->buffer;
		$exclusive		= &$this->stateExclusive;
		$state			= &$this->state;
		$c = $p 		= null;
		// --
		for ($i = 0, $l = strlen($source); $i < $l; $i++)
			{
			// Set the current Char
			$c = $source[$i]; // Is faster than: $c = substr($source, $i, 1);
			// Normalize and filter double whitespace characters
			if ($exclusive === false)
				{
				if ($c === "\n" || $c === "\t")
					{
					$c = " ";
					}
				if ($c === " " && $p === " ")
					{
					continue;
					}
				}
			$buffer .= $c;
			// Extended processing only if the current char is a global trigger char
			if (strpos($globalTriggerChars, $c) !== false)
				{
				// Exclusive state is set; process with the exclusive plugin 
				if ($exclusive)
					{
					$tPluginIndex = $pluginIndex[$exclusive];
					if (strpos($pluginTriggerChars[$tPluginIndex], $c) !== false && ($pluginTriggerStates[$tPluginIndex] === false || strpos($pluginTriggerStates[$tPluginIndex], $state) !== false))
						{
						$r = $plugins[$tPluginIndex]->parse($i, $c, $p, $state);
						// Return value is TRUE => continue with next char
						if ($r === true)
							{
							continue;
							}
						// Return value is numeric => set new index and continue with next char
						elseif ($r !== false && $r != $i)
							{
							$i = $r;
							continue;
							}
						}
					}
				// Else iterate through the plugins
				else
					{
					$triggerState = "|" . $state . "|";
					for ($ii = 0, $ll = $pluginCount; $ii < $ll; $ii++)
						{
						// Only process if the current char is one of the plugin trigger chars
						if (strpos($pluginTriggerChars[$ii], $c) !== false && ($pluginTriggerStates[$ii] === false || strpos($pluginTriggerStates[$ii], $triggerState) !== false))
							{
							// Process with the plugin
							$r = $plugins[$ii]->parse($i, $c, $p, $state);
							// Return value is TRUE => break the plugin loop and and continue with next char
							if ($r === true)
								{
								break;
								}
							// Return value is numeric => set new index, break the plugin loop and and continue with next char
							elseif ($r !== false && $r != $i)
								{
								$i = $r;
								break;
								}
							}
						}
					}
				}
			$p = $c; // Set the parent char
			}
		return $this->tokens;
		}
	/**
	 * Remove the last state of the state stack and return the removed stack value.
	 * 
	 * @return integer Removed state value
	 */
	public function popState()
		{
		$r = array_pop($this->states);
		$this->state = $this->states[count($this->states) - 1];
		return $r;
		}
	/**
	 * Adds a new state onto the state stack.
	 * 
	 * @param integer $state State to add onto the state stack.
	 * @return integer The index of the added state in the state stacks
	 */
	public function pushState($state)
		{
		$r = array_push($this->states, $state);
		$this->state = $this->states[count($this->states) - 1];
		return $r;
		}
	/**
	 * Sets/restores the buffer.
	 * 
	 * @param string $buffer Buffer to set
	 * @return void
	 */	
	public function setBuffer($buffer)
		{
		$this->buffer = $buffer;
		}
	/**
	 * Set the exclusive state.
	 * 
	 * @param string $exclusive Exclusive state
	 * @return void
	 */	
	public function setExclusive($exclusive)
		{
		$this->stateExclusive = $exclusive; 
		}
	/**
	 * Set the media types state.
	 * 
	 * @param array $mediaTypes Media types state
	 * @return void
	 */	
	public function setMediaTypes(array $mediaTypes)
		{
		$this->stateMediaTypes = $mediaTypes; 
		}
	/**
	 * Sets the current state in the state stack; equals to {@link CssParser::popState()} + {@link CssParser::pushState()}.
	 * 
	 * @param integer $state State to set
	 * @return integer
	 */
	public function setState($state)
		{
		$r = array_pop($this->states);
		array_push($this->states, $state);
		$this->state = $this->states[count($this->states) - 1];
		return $r;
		}
	/**
	 * Removes the exclusive state.
	 * 
	 * @return void
	 */
	public function unsetExclusive()
		{
		$this->stateExclusive = false;
		}
	/**
	 * Removes the media types state.
	 * 
	 * @return void
	 */
	public function unsetMediaTypes()
		{
		$this->stateMediaTypes = false;
		}
	}
?>