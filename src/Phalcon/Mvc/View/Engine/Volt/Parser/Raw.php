<?php
/**
 * Raw
 *
 * @author Wenzel Pünter <wenzel@phelix.me>
 * @version 1.2.6
 * @package Phalcon
*/
namespace Phalcon\Mvc\View\Engine\Volt\Parser;

use Phalcon\Mvc\View\Engine\Volt\Parser\Exception,
	Phalcon\Mvc\View\Engine\Volt\Parser\Block;

/**
 * Raw
*/
class Raw extends Block
{
	/**
	 * Get Intermediate Expression
	 * 
	 * @return array
	*/
	public function getIntermediateExpression()
	{
		return array(
			'type' => 357, //PHVOLT_T_RAW_FRAGMENT
			'value' => $this->_statement,
			'file' => $this->_path,
			'line' => $this->_line
		);
	}
}