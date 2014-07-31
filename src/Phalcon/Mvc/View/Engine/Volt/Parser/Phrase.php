<?php
/**
 * Phrase
 *
 * @author Wenzel Pünter <wenzel@phelix.me>
 * @version 1.2.6
 * @package Phalcon
*/
namespace Phalcon\Mvc\View\Engine\Volt\Parser;

/**
 * Phrase
*/
abstract class Phrase
{
	/**
	 * Statement
	 * 
	 * @var string
	 * @access protected
	*/
	protected $_statement;

	/**
	 * Line
	 * 
	 * @var int|null
	 * @access protected
	*/
	protected $_line;

	/**
	 * Path
	 * 
	 * @var string|null
	 * @access protected
	*/
	protected $_path;

	/**
	 * Constructor
	 * 
	 * @param string $statement
	 * @throws Exception
	*/
	public function __construct($statement)
	{
		if(is_string($statement) === false) {
			throw new Exception('Invalid parameter type.');
		}
		
		$this->_statement = $statement;
	}

	/**
	 * Get Statement
	 * 
	 * @return string
	*/
	public function getStatement()
	{
		return $this->_statement;
	}

	/**
	 * Set Line
	 * 
	 * @param int|null $line
	 * @throws Exception
	*/
	public function setLine($line)
	{
		if(is_int($line) === false &&
			is_null($line) === false) {
			throw new Exception('Invalid parameter type.');
		}

		$this->_line = $line;
	}

	/**
	 * Get Line
	 * 
	 * @return int|null
	*/
	public function getLine()
	{
		return $this->_line;
	}

	/**
	 * Set Path
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

		$this->_path = $path;
	}

	/**
	 * Get Path
	 * 
	 * @return string|null
	*/
	public function getPath()
	{
		return $this->_path;
	}

	/**
	 * Get Intermediate Expression
	 * 
	 * @return array
	*/
	abstract public function getIntermediateExpression();
}