<?php
/**
 * Autoescape Block
 *
 * @author Wenzel Pünter <wenzel@phelix.me>
 * @version 1.2.6
 * @package Phalcon
*/
namespace Phalcon\Mvc\View\Engine\Volt\Parser;

use Phalcon\Mvc\View\Engine\Volt\Parser\Exception,
	Phalcon\Mvc\View\Engine\Volt\Parser\Phrase;

/**
 * Autoescape Block
*/
class Autoescape extends Phrase
{
	/**
	 * Get Intermediate
	 * 
	 * @return array
	 * @throws Exception
	*/
	public function getIntermediate()
	{
		$inner = array();
		$expression = '#^{%\s*autoescape\s+(true|false)\s*%}(.*){%[\s]*endautoescape[\s]*%}$#';
		if(preg_match($expression, $this->_statement, $inner) == false) {
			throw new Exception('Invalid autoescape statement.');
		}

		return array(
			'type' => 317, //PHVOLT_T_AUTOESCAPE
			'enable' => ($inner[1] === 'true' ? 1 : 0),
			'block_statements' => Tokenizer::run($inner[2]),
			'file' => $this->_path,
			'line' => $this->_line
		);
	}
}