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
		$expression = '#^{%[\s]*block[\s]+([\w]+)[\s]*%}(.*){%[\s]*endblock[\s]*%}$#';
		if(preg_match($expression, $this->_statement, $inner) == false) {
			throw new Exception('Invalid block statement.');
		}

		return array(
			'type' => 307, //PHVOLT_T_BLOCK
			'name' => $inner[1],
			'block_statements' => Tokenizer::run($inner[2]),
			'file' => $this->_path,
			'line' => $this->_line
		);
	}
}