<?php
/**
 * Tokenizer
 *
 * @author Wenzel Pünter <wenzel@phelix.me>
 * @version 1.2.6
 * @package Phalcon
*/
namespace Phalcon\Mvc\View\Engine\Volt;

use \Phalcon\Mvc\View\Engine\Volt\Parser\Exception;

/**
 * Tokenizer
*/
class Tokenizer
{
	/**
	 * Tokenize expression
	 * 
	 * @param string $expression
	 * @return array
	*/
	public static function run($expression)
	{
		$expression_length = strlen($expression);
		$ret = '';

		for($i = 0; $i < $expression_length; ++$i)
		{
			//@todo
		}

		return $ret;
	}
}