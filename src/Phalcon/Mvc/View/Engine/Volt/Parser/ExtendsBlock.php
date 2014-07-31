<?php
/**
 * Extends Block
 *
 * @author Wenzel Pünter <wenzel@phelix.me>
 * @version 1.2.6
 * @package Phalcon
*/
namespace Phalcon\Mvc\View\Engine\Volt\Parser;

use Phalcon\Mvc\View\Engine\Volt\Parser\Exception,
	Phalcon\Mvc\View\Engine\Volt\Parser\Phrase,
	Phalcon\Mvc\View\Engine\Volt\Tokenizer;

/**
 * Extends Block
*/
class ExtendsBlock extends Phrase
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
		$expression = '#^{%\s*extends\s*"([\' /.\w\-]+)"\s*%}$#';
		if(preg_match($expression, $this->_statement, $inner) == false) {
			throw new Exception('Invalid extends statement.');
		}

		return array(
			'type' => 310, //PHVOLT_T_EXTENDS
			'path' => $inner[1],
			'file' => $this->_path,
			'line' => $this->_line
		);
	}
}