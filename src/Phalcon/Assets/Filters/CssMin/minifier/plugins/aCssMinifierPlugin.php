<?php
/**
 * Abstract definition of a minifier plugin class. 
 * 
 * Minifier plugin process the parsed tokens one by one to apply changes to the token. Every minifier plugin has to 
 * extend this class.
 *
 * @package		CssMin/Minifier/Plugins
 * @link		http://code.google.com/p/cssmin/
 * @author		Joe Scylla <joe.scylla@gmail.com>
 * @copyright	2008 - 2011 Joe Scylla <joe.scylla@gmail.com>
 * @license		http://opensource.org/licenses/mit-license.php MIT License
 * @version		3.0.1
 */
abstract class aCssMinifierPlugin
	{
	/**
	 * Plugin configuration.
	 * 
	 * @var array
	 */
	protected $configuration = array();
	/**
	 * The CssMinifier of the plugin.
	 * 
	 * @var CssMinifier
	 */
	protected $minifier = null;
	/**
	 * Constructor.
	 * 
	 * @param CssMinifier $minifier The CssMinifier object of this plugin.
	 * @param array $configuration Plugin configuration [optional]
	 * @return void
	 */
	public function __construct(CssMinifier $minifier, array $configuration = array())
		{
		$this->configuration	= $configuration;
		$this->minifier			= $minifier;
		}
	/**
	 * Apply the plugin to the token.
	 * 
	 * @param aCssToken $token Token to process
	 * @return boolean Return TRUE to break the processing of this token; FALSE to continue
	 */
	abstract public function apply(aCssToken &$token);
	/**
	 * --
	 * 
	 * @return array
	 */
	abstract public function getTriggerTokens();
	}
?>