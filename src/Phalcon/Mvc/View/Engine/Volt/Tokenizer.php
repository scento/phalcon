<?php
/**
 * Tokenizer
 *
 * @author Wenzel Pünter <wenzel@phelix.me>
 * @version 1.2.6
 * @package Phalcon
*/
namespace Phalcon\Mvc\View\Engine\Volt;

use \Phalcon\Mvc\View\Engine\Volt\Parser\Exception,
	\Phalcon\Mvc\View\Engine\Volt\Parser\Evaluation,
	\Phalcon\Mvc\View\Engine\Volt\Parser\Statement,
	\Phalcon\Mvc\View\Engine\Volt\Parser\Raw;

/**
 * Tokenizer
*/
class Tokenizer
{
	/**
	 * Tokenize expression
	 * 
	 * @param string $expression
	 * @param string|null $path
	 * @return array
	 * @throws Exception
	*/
	public static function run($expression)
	{
		if(is_string($expression) === false ||
			(is_string($path) === false &&
				is_null($path) === false)) {
			throw new Exception('Invalid parameter type.');
		}

		$flags = \PREG_SPLIT_NO_EMPTY|\PREG_SPLIT_OFFSET_CAPTURE|PREG_SPLIT_DELIM_CAPTURE;
		$matches = preg_split('#(")|((\{\{)|(\}\}))#', $expression, -1, $flags);

		$statements = 0;
		$evaluations = 0;
		$comments = 0;
		$in_quotes = false;
		$in_single_quotes = false;
		$line = 1;
		
		$buffer = '';
		$ret = array();

		foreach($matches as $match)
		{
			$line += substr_count($match[0], "\n");
			switch($match[0])
			{
				case '{#':
					if($in_quotes === false &&
						$in_single_quotes === false) {
						if($comments === 0) {
							$raw = new Raw($buffer);
							$raw->setLine($line);
							$raw->setPath($path);
							$ret[] = $raw->getIntermediate();
							$buffer = '';
						}

						$comments++;
					}
					break;
				case '#}':
					if($in_quotes === false &&
						$in_single_quotes === false) {
						$comments--;

						if($comments < 0) {
							throw new Exception('Unexpected token.');
						} elseif($comments === 0) {
							$buffer = '';
						}
					}
					break;
				case '{{':
					if($in_quotes === false &&
						$in_single_quotes === false) {
						if($statements === 0) {
							$raw = new Raw($buffer);
							$raw->setLine($line);
							$raw->setPath($path);
							$ret[] = $raw->getIntermediate();
							$buffer = '';
						}

						$statements++;
					}
					break;
				case '}}':
					if($in_quotes === false &&
						$in_single_quotes === false) {
						$statements--;

						if($statements < 0) {
							throw new Exception('Unexpected token.');
						} elseif($statements === 0) {
							$statement = new Statement($buffer);
							$statement->setLine($line);
							$statement->setPath($path);
							$ret[] = $statement->getIntermediate();
							$buffer = '';
						}
					}
					break;
				case '{%':
					if($in_quotes === false &&
						$in_single_quotes === false) {
						if($evaluations === 0) {
							$raw = new Raw($buffer);
							$raw->setLine($line);
							$raw->setPath($path);
							$ret[] = $raw->getIntermediate();
							$buffer = '';
						}

						$evaluations++;
					}
					break;
				case '%}':
					if($in_quotes === false &&
						$in_single_quotes === false) {
						$evaluations--;

						if($evaluations < 0) {
							throw new Exception('Unexpected token.');
						} elseif($evaluations < 0) {
							$evaluation = new Evaluation($buffer);
							$evaluation->setLine($line);
							$evaluation->setPath($path);
							$ret[] = $evaluation->getIntermediate();
							$buffer = '';
						}
					}
					break;
				case '"':
					if($in_single_quotes === false) {
						$in_quotes = ($in_quotes === true ? false : true);
					}
					$buffer .= $match[0];
					break;
				case "'":
					if($in_quotes === false) {
						$in_single_quotes = ($in_single_quotes === true ? false : true);
					}
					$buffer .= $match[0];
					break;
				default:
					$buffer .= $match[0];
					break;
			}
		}

		return $ret;
	}
}