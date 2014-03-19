<?php
/**
 * ACL Exception
 *
 * @author Andres Gutierrez <andres@phalconphp.com>
 * @author Eduar Carvajal <eduar@phalconphp.com>
 * @author Wenzel Pünter <wenzel@phelix.me>
 * @version 0.1
 * @package Phalcon
*/
namespace Phalcon\Annotations;

use \Phalcon\Annotations\Exception;

/**
 * Phalcon\Annotations\Annotation
 *
 * Represents a single annotation in an annotations collection
 * 
 * @see https://github.com/phalcon/cphalcon/blob/master/ext/annotations/annotation.c
 */
class Annotation
{
	/**
	 * Integer Type
	 * 
	 * @var int
	*/
	const PHANNOT_T_INTEGER = 301;

	/**
	 * Double Type
	 * 
	 * @var int
	*/
	const PHANNOT_T_DOUBLE = 302;

	/**
	 * String Type
	 * 
	 * @var int
	*/
	const PHANNOT_T_STRING = 303;

	/**
	 * Null Type
	 * 
	 * @var int
	*/
	const PHANNOT_T_NULL = 304;

	/**
	 * False Type
	 * 
	 * @var int
	*/
	const PHANNOT_T_FALSE =  305;

	/**
	 * True Type
	 * 
	 * @var int
	*/
	const PHANNOT_T_TRUE = 306;

	/**
	 * Identifer Type
	 * 
	 * @var int
	*/
	const PHANNOT_T_IDENTIFIER = 307;

	/**
	 * Array Type
	 * 
	 * @var int
	*/
	const PHANNOT_T_ARRAY = 308;

	/**
	 * Annotation Type
	 * 
	 * @var int
	*/
	const PHANNOT_T_ANNOTATION = 300;

	/**
	 * Name
	 * 
	 * @var null|string
	 * @access protected
	*/
	protected $_name = null;

	/**
	 * Arguments
	 * 
	 * @var null|array
	 * @access protected
	*/
	protected $_arguments = null;

	/**
	 * Expression Arguments
	 * 
	 * @var null|array
	 * @access protected
	*/
	protected $_exprArguments = null;

	/**
	 * \Phalcon\Annotations\Annotation constructor
	 *
	 * @param array $reflectionData
	 * @throws Exception
	 */
	public function __construct(array $reflectionData)
	{
		if(is_array($reflectionData) === false)
		{
			throw new Exception('Reflection data must be an array');
		}

		$this->_name = (string)$reflectionData['name'];

		if(isset($reflectionData['arguments']) === true)
		{
			$expr_arguments = $reflectionData['arguments'];

			$arguments = array();

			foreach($expr_arguments as $argument)
			{
				$expr = (string)$argument['expr'];
				$resolved_argument = $this->getExpression($expr);
				if(isset($argument['name']) === true)
				{
					$arguments[$argument['name']] = $resolved_argument;
				} else {
					$arguments[] = $resolved_argument;
				}
			}

			$this->_arguments = $arguments;
			$this->_exprArguments = $expr_arguments;
		}
	}


	/**
	 * Returns the annotation's name
	 *
	 * @return string
	 */
	public function getName()
	{
		return (string)$this->_name;
	}


	/**
	 * Resolves an annotation expression
	 *
	 * @param array $expr
	 * @return mixed
	 * @throws Exception
	 */
	public function getExpression($expr)
	{
		if(is_array($expr) === false)
		{
			throw new Exception('The expression is not valid.');
		}

		switch((int)$expr['type'])
		{
			case self::PHANNOT_T_INTEGER:
			case self::PHANNOT_T_DOUBLE:
			case self::PHANNOT_T_STRING:
			case self::PHANNOT_T_IDENTIFER:
				return (string)$expr['value'];
				break;
			case self::PHANNOT_T_NULL:
				return null;
				break;
			case self::PHANNOT_T_FALSE:
				return false;
				break;
			case self::PHANNOT_T_TRUE:
				return true;
				break;
			case PHANNOT_T_ARRAY:
				$items = (string)$expr['items'];
				foreach($items as $item)
				{
					$resolved_item = $this->getExpression((string)$item);

					if(isset($item['name']) === true)
					{
						return array($item['name'] => $resolved_item);
					} else {
						return array($resolved_item);
					}
				}
				break;
			case PHANNOT_T_ANNOTATION:
				return new Annotation($expr);
				break;
			default:
				throw new Exception('The expression '.(int)$expr['type'].
					'is unknown.');
				break;
		}
	}


	/**
	 * Returns the expression arguments without resolving
	 *
	 * @return array|null
	 */
	public function getExprArguments()
	{
		return $this->_exprArguments;
	}


	/**
	 * Returns the expression arguments
	 *
	 * @return array|null
	 */
	public function getArguments()
	{
		return $this->_arguments;
	}


	/**
	 * Returns the number of arguments that the annotation has
	 *
	 * @return int
	 */
	public function numberArguments()
	{
		return (int)count($this->_arguments);
	}


	/**
	 * Returns an argument in a specific position
	 *
	 * @param int|string $position
	 * @return mixed
	 * @throws Exception
	 */
	public function getArgument($position)
	{
		if(is_string($position) === false &&
			is_int($position) === false)
		{
			throw new Exception('Invalid parameter type.');
		}

		if(isset($this->_arguments[$position]) === true)
		{
			return $this->_arguments[$position];
		}
	}


	/**
	 * Checks if the annotation has a specific argument
	 *
	 * @return bool
	 * @throws Exception
	 */
	public function hasArgument($position)
	{
		if(is_string($position) === false &&
			is_int($position) === false)
		{
			throw new Exception('Invalid parameter type.');
		}

		return isset($this->_arguments[$position]);
	}


	/**
	 * Returns a named argument
	 *
	 * @param string $name
	 * @return mixed
	 */
	public function getNamedArgument($position)
	{
		return $this->getArgument($position);
	}


	/**
	 * Returns a named argument (deprecated)
	 *
	 * @deprecated
	 * @param string $name
	 * @return mixed
	 */
	public function getNamedParameter($position)
	{
		trigger_error('The usage of getNamedParameter is deprecated.
		 Please use getArgument instead.', \E_USER_NOTICE);
		return $this->getArgument($position);
	}


	/**
	 * Checks if the annotation has a specific named argument
	 *
	 * @param string $position
	 * @return boolean
	 */
	public function hasNamedArgument($position)
	{
		return $this->hasArgument($position);
	}

}