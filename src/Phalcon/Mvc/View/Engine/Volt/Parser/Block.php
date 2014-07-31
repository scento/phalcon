<?php
/**
 * Block
 *
 * @author Wenzel Pünter <wenzel@phelix.me>
 * @version 1.2.6
 * @package Phalcon
*/
namespace Phalcon\Mvc\View\Engine\Volt\Parser;

use Phalcon\Mvc\View\Engine\Volt\Parser\Exception,
	Phalcon\Mvc\View\Engine\Volt\Parser\Phrase;

/**
 * Block
*/
class Block extends Phrase
{
	/**
	 * Block Name
	 * 
	 * @var string|null
	 * @access private
	*/
	private $_name;

	/**
	 * Set Name
	 * 
	 * @param string|null $name
	 * @throws Exception
	*/
	public function setName($name)
	{
		if(is_string($name) === false &&
			is_null($name) === false) {
			throw new Exception('Invalid parameter type.');
		}

		$this->_name = $name;
	}

	/**
	 * Get Name
	 * 
	 * @return string|null
	*/
	public function getName()
	{
		return $this->_name;
	}

	/**
	 * Get Intermediate
	 * 
	 * @return array
	 * @throws Exception
	*/
	public function getIntermediate()
	{
		if(is_null($this->_name) === true) {
			throw new Exception('Blocks without names are unsupported.');
		}

		$inner = array();
		$expression = '^{%\s*block\s+\w+\s*%}(.*){%\s*endblock\s*%}$';
		if(preg_match($expression, $this->_statement, $inner) === false) {
			throw new Exception('Invalid block statement.');
		}

		return array(
			'type' => 307, //PHVOLT_T_BLOCK
			'name' => $this->getName(),
			'block_statements' => Tokenizer::run($inner[1]),
			'file' => $this->_path,
			'line' => $this->_line
		);
	}
}