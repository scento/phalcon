<?php
/**
 * Abstract definition of a parser plugin.
 * 
 * Every parser plugin have to extend this class. A parser plugin contains the logic to parse one or aspects of a 
 * stylesheet.
 * 
 * @package		CssMin/Parser/Plugins
 * @link		http://code.google.com/p/cssmin/
 * @author		Joe Scylla <joe.scylla@gmail.com>
 * @copyright	2008 - 2011 Joe Scylla <joe.scylla@gmail.com>
 * @license		http://opensource.org/licenses/mit-license.php MIT License
 * @version		3.0.1
 */
abstract class aCssParserPlugin
	{
	/**
	 * Plugin configuration.
	 * 
	 * @var array
	 */
	protected $configuration = array();
	/**
	 * The CssParser of the plugin.
	 * 
	 * @var CssParser
	 */
	protected $parser = null;
	/**
	 * Plugin buffer.
	 * 
	 * @var string
	 */
	protected $buffer = "";
	/**
	 * Constructor.
	 * 
	 * @param CssParser $parser The CssParser object of this plugin.
	 * @param array $configuration Plugin configuration [optional]
	 * @return void
	 */
	public function __construct(CssParser $parser, array $configuration = null)
		{
		$this->configuration	= $configuration;
		$this->parser			= $parser;
		}
	/**
	 * Returns the array of chars triggering the parser plugin.
	 * 
	 * @return array
	 */
	abstract public function getTriggerChars();
	/**
	 * Returns the array of states triggering the parser plugin or FALSE if every state will trigger the parser plugin.
	 * 
	 * @return array
	 */
	abstract public function getTriggerStates();
	/**
	 * Parser routine of the plugin.
	 * 
	 * @param integer $index Current index
	 * @param string $char Current char
	 * @param string $previousChar Previous char
	 * @return mixed TRUE will break the processing; FALSE continue with the next plugin; integer set a new index and break the processing
	 */
	abstract public function parse($index, $char, $previousChar, $state);
	}
?>