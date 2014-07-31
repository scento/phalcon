<?php
/**
 * Cache Block
 *
 * @author Wenzel Pünter <wenzel@phelix.me>
 * @version 1.2.6
 * @package Phalcon
*/
namespace Phalcon\Mvc\View\Engine\Volt\Parser;

use Phalcon\Mvc\View\Engine\Volt\Parser\Exception,
	Phalcon\Mvc\View\Engine\Volt\Parser\Phrase,
	Phalcon\Mvc\View\Engine\Volt\Parser\Statement;

/**
 * Cache Block
*/
class Cache extends Phrase
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
		$expression = '#^{%[\s]*cache[\s]+(.*?)([\d]*)[\s]*%}(.*){%[\s]*endcache[\s]*%}$#';
		if(preg_match($expression, $this->_statement, $inner) == false) {
			throw new Exception('Invalid caching statement.');
		}

		$expr = new Statement($inner[0]);
		$expr->setPath($this->_path);
		$expr->setLine($this->_line);

		$ret = array(
			'type' => 314, //PHVOLT_T_CACHE
			'expr' => $expr,
			'file' => $this->_path,
			'line' => $this->_line
		);

		if(empty($inner[2]) === false) {
			$ret['ttl'] = (int)$inner[2];
		}

		return $ret;
	}
}