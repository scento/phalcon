<?php
/**
 * Parser
 *
 * @author Wenzel Pünter <wenzel@phelix.me>
 * @version 1.2.6
 * @package Phalcon
*/
namespace Phalcon\Mvc\View\Engine\Volt;

use \Phalcon\Mvc\View\Engine\Volt\Parser\Exception;

/**
 * Parser
*/
class Parser
{
	/**
	 * Volt Code
	 * 
	 * @var string
	 * @access protected
	*/
	protected $_volt;

	/**
	 * File Path
	 * 
	 * @var string|null
	 * @access protected
	*/
	protected $_path;

	/**
	 * Intermediate Representation
	 * 
	 * @var array
	 * @access protected
	*/
	protected $_intermediate;

	/**
	 * Create a new parser of the volt expression.
	 * 
	 * @param string $volt
	 * @param string|null $path
	*/
	public function __construct($volt, $path = null)
	{
		$this->setVolt($volt);
		$this->setPath($path);
		$this->_intermediate = array();
	}

	/**
	 * Set File Path
	 * 
	 * @param string|null $path
	 * @throws Exception
	*/
	public function setPath($path)
	{
		if(is_string($path) === false &&
			is_null($path) === false) {
			throw new Exception('Invalid parameter type.');
		}

		if($path === 'eval code') {
			$path = null;
		}

		$this->_path = $path;
	}

	/**
	 * Get File Path
	 * 
	 * @return string|null
	*/
	public function getPath()
	{
		return $this->_path;
	}

	/**
	 * Set Volt Code
	 * 
	 * @param string $code
	 * @throws Exception
	*/
	public function setVolt($code)
	{
		if(is_string($code) === false) {
			throw new Exception('Invalid parameter type.');
		}

		$this->_volt = $code;
		$this->_intermediate = array();
	}

	/**
	 * Get Volt Code
	 * 
	 * @return string
	*/
	public function getVolt()
	{
		return $this->_volt;
	}

	/**
	 * Get intermediate representation of the
	 * volt code
	 * 
	 * @return array
	*/
	public function getIntermediate()
	{
		$intermediate = Tokenizer::run($this->_volt, $this->_path);
		$this->_intermediate = $intermediate;
		return $intermediate;
	}
}