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
	\Phalcon\Mvc\View\Engine\Volt\Parser\Raw,
	\Phalcon\Mvc\View\Engine\Volt\Parser\Block;

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
		$regexp = '({%\s*autoescape\s+(true|false)\s*%})|({%\s*endautoescape\s*%})|({%\s*block\s+[\w]+\s*%})|({%\s*endblock\s*%})|(["\'])|({{)|(}})|({%)|(%})|({\#)|(\#})';
		$matches = preg_split($regexp, $expression, -1, $flags);

		$statements = 0;
		$evaluations = 0;
		$comments = 0;
		$block = false;
		$in_quotes = false;
		$in_single_quotes = false;
		$line = 1;
		$autoescape = 0;
		
		$buffer = '';
		$ret = array();

		foreach($matches as $match)
		{
			$line += substr_count($match[0], "\n");
			switch($match[0])
			{
				case '{#':
					//Open Comment
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
					//Close Comment
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
					//Open Statement
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
					//Close Statement
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
					//Open Evaluation
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
					//Close Evaluation
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
					//Open/Close String
					if($in_single_quotes === false) {
						$in_quotes = ($in_quotes === true ? false : true);
					}
					break;
				case "'":
					//Open/Close String
					if($in_quotes === false) {
						$in_single_quotes = ($in_single_quotes === true ? false : true);
					}
					break;
				default:
					if($in_quotes === false &&
						$in_single_quotes === false) {
						$block_matches = array();
						if(preg_match('#{%\s*block\s+[\w]+\s*%}#', $match[0], $block_matches) != false) {
							//Check for {% block NAME %}
							if($block === true) {
								throw new Exception('Embedding blocks into other blocks is not supported');
							}

							$raw = new Raw($buffer);
							$raw->setLine($line);
							$raw->setPath($path);
							$ret[] = $raw->getIntermediate();
							$buffer = '';

							$block = true;
						} elseif(preg_match('#{%\s*endblock\s*%}#', $match[0]) != false) {
							//Check for {% endblock %}
							if($block === false) {
								throw new Exception('Unexpected token.');
							}

							$block = false;
							$block_name = null;
							$object = new Block($buffer);
							$object->setLine($line);
							$object->setPath($path);
							$ret[] = $object->getIntermediate();
							$buffer = '';
						} elseif(preg_match('#({%\s*autoescape\s+(true|false)\s*%})#', $match[0]) != false) {
							//Check for {% autoescape BOOL %}
							if($autoescape === 0) {
								$raw = new Raw($buffer);
								$raw->setLine($line);
								$raw->setPath($path);
								$ret[] = $evaluation->getIntermediate();
								$buffer = '';
							}

							$autoescape++;
						} elseif(preg_match('#({%\s*endautoescape\s*%})#', $match[0]) != false) {
							//Check for {% endautoescape %}
							$autoescape--;

							if($autoescape < 0) {
								throw new Exception('Unexpected token.');
							} elseif($autoescape === 0) {
								$object = new Autoescape($buffer);
								$object->setLine($line);
								$object->setPath($path);
								$ret[] = $object->getIntermediate();
							}
						}
					}

					break;
			}
			
			$buffer .= $match[0];
		}

		return $ret;
	}
}