<?php
/**
 * Macro
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
 * Macro
*/
class Macro extends Phrase
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
		$expression = '#^{%(?:-?)\s*macro\s*([\w]+)\s*\(((?:\w(?:[,]?)\s*)*\))\s*%}(.*){%(?:-?)\s*endmacro\s*%}$#';
		if(preg_match($expression, $this->_statement, $inner) == false) {
			throw new Exception('Invalid macro statement.');
		}

		if(empty($expression[3]) === true) {
			throw new Exception('Unexpected token.');
		}

		$ret = array(
			'type' => 322, //PHVOLT_T_MACRO
			'name' => $inner[1],
			'file' => $this->_path,
			'line' => $this->_line,
			'block_statements' => Tokenizer::run($inner[3])
		);

		//Parse parameters
		if(empty($inner[2]) === false) {
			$params = explode(',', $inner[2]);

			$parameters = array();
			foreach($params as $param)
			{
				$parameters[] = array(
					'variable' => $param,
					'file' => $this->_path,
					'line' => $this->_line
				);
			}

			$ret['params'] = $parameters;
		}

		return $ret;
	}
}