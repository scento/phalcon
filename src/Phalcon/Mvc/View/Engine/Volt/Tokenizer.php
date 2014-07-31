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
	\Phalcon\Mvc\View\Engine\Volt\Parser\Statement,
	\Phalcon\Mvc\View\Engine\Volt\Parser\Raw,
	\Phalcon\Mvc\View\Engine\Volt\Parser\Block,
	\Phalcon\Mvc\View\Engine\Volt\Parser\Autoescape,
	\Phalcon\Mvc\View\Engine\Volt\Parser\Cache,
	\Phalcon\Mvc\View\Engine\Volt\Parser\Macro;

/**
 * Tokenizer
*/
class Tokenizer
{
	/**
	 * Generate representation of phrase
	 * 
	 * @param string $type
	 * @param string $buffer
	 * @param int $line
	 * @param string $path
	 * @return array
	*/
	private static function createObject($type, $buffer, $line, $path)
	{
		switch($type) {
			case 'raw':
				$obj = new Raw($buffer);
				break;
			case 'statement':
				$obj = new Statement($buffer);
				break;
			case 'autoescape':
				$obj = new Autoescape($buffer);
				break;
			case 'block':
				$obj = new Block($buffer);
				break;
			case 'cache':
				$obj = new Cache($buffer);
				break;
			case 'macro':
				$obj = new Macro($buffer);
		}
		$obj->setLine($line);
		$obj->setPath($path);
		return $obj->getIntermediate();
	}

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
		$regexp = '({%(?:-?)\s*macro\s*([\w]+)\s*\(((?:\w(?:[,]?)\s*)*\))\s*%})|({%(?:-?)\s*endmacro\s*%})|({%\s*endcache\s*%})|({%\s*cache\s+(.*)\s*([\d]*)\s*%})|({%\s*autoescape\s+(true|false)\s*%})|({%\s*endautoescape\s*%})|({%\s*block\s+[\w]+\s*%})|({%\s*endblock\s*%})|(["\'])|({{)|(}})|({\#)|(\#})';
		$matches = preg_split($regexp, $expression, -1, $flags);

		$statements = 0;
		$comments = 0;
		$autoescape = 0;

		$block = false;
		$macro = false;
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
					//Open Comment
					if($in_quotes === false &&
						$in_single_quotes === false) {
						if($comments === 0) {
							$raw[] = self::createObject('raw', $buffer, $line, $path);
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
							//Delete comment
							$buffer = '';
						}
					}
					break;
				case '{{':
					//Open Statement
					if($in_quotes === false &&
						$in_single_quotes === false) {
						if($statements === 0) {
							$ret[] = self::createObject('raw', $buffer, $line, $path);
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
							$ret[] = self::createObject('statement', $buffer, $line, $path);
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
						/* Special Expressions */
						$block_matches = array();
						if(preg_match('#{%\s*block\s+[\w]+\s*%}#', $match[0], $block_matches) != false) {
							//Check for {% block NAME %}
							if($block === true) {
								throw new Exception('Embedding blocks into other blocks is not supported');
							}

							$ret[] = self::createObject('raw', $buffer, $line, $path);
							$buffer = '';

							$block = true;
						} elseif(preg_match('#{%\s*endblock\s*%}#', $match[0]) != false) {
							//Check for {% endblock %}
							if($block === false) {
								throw new Exception('Unexpected token.');
							}

							$ret[] = self::createObject('block', $buffer, $line, $path);
							$buffer = '';
							$block = false;

						} elseif(preg_match('#{%\s*autoescape\s+(true|false)\s*%}#', $match[0]) != false) {
							//Check for {% autoescape BOOL %}
							if($autoescape === 0) {
								$ret[] = self::createObject('raw', $buffer, $line, $path);
								$buffer = '';
							}

							$autoescape++;
						} elseif(preg_match('#{%\s*endautoescape\s*%}#', $match[0]) != false) {
							//Check for {% endautoescape %}
							$autoescape--;

							if($autoescape < 0) {
								throw new Exception('Unexpected token.');
							} elseif($autoescape === 0) {
								$ret[] = self::createObject('autoescape', $buffer, $line, $path);
								$buffer = '';
							}

						} elseif(preg_match('#{%[\s]*cache[\s]+(.*?)([\d]*)[\s]*%}#', $match[0]) != false) {
							//Check for {% cache EXPR INT %}
							if($cache === 0) {
								$ret[] = self::createObject('raw', $buffer, $line, $path);
								$buffer = '';
							}

							$cache++;
						} elseif(preg_match('#{%[\s]*endcache[\s]*%}#', $match[0]) != false) {
							//Check for {% endcache %}
							$cache--;

							if($cache < 0) {
								throw new Exception('Unexpected token.');
							} elseif($cache === 0) {
								$ret[] = self::createObject('cache', $buffer, $line, $path);
								$buffer = '';
							}

						} elseif(preg_match('#{%(?:-?)\s*macro\s*([\w]+)\s*\(((?:\w(?:[,]?)\s*)*\))\s*%}#', $match[0]) != false) {
							//Check for {% macro NAME(PARAMS) %}
							if($macro === true) {
								throw new Exception('Embedding macros into other macros is not allowed');
							}


							$ret[] = self::createObject('raw', $buffer, $line, $path);
							$buffer = '';

							$block = true;
						} elseif(preg_match('#({%(?:-?)\s*endmacro\s*%})#', $match[0]) != false) {
							//Check for {% endmacro %}
							if($macro === false) {
								throw new Exception('Unexpected token.');
							}

							$ret[] = self::createObject('macro', $buffer, $line, $path);
							$buffer = '';
							$block = false;
						}
					}

					break;
			}
			
			$buffer .= $match[0];
		}

		return $ret;
	}
}