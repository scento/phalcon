<?php
/**
 * ACL Resource
 *
 * @author Andres Gutierrez <andres@phalconphp.com>
 * @author Eduar Carvajal <eduar@phalconphp.com>
 * @author Wenzel Pünter <wenzel@phelix.me>
 * @version 0.1
 * @package Phalcon
*/
namespace Phalcon\Acl;

use \Phalcon\Acl\ResourceInterface,
	\Phalcon\Acl\Exception;

/**
 * Phalcon\Acl\Resource
 *
 * This class defines resource entity and its description
 * 
 * @see https://github.com/phalcon/cphalcon/blob/master/ext/acl/resource.c
 */
class Resource implements ResourceInterface
{
	/**
	 * Name
	 * 
	 * @var null|string
	 * @access protected
	*/
	protected $_name = null;

	/**
	 * Description
	 * 
	 * @var null|string
	 * @access protected
	*/
	protected $_description = null;

	/**
	 * \Phalcon\Acl\Resource constructor
	 *
	 * @param string $name
	 * @param string|null $description
	 * @throws Exception
	 */
	public function __construct($name, $description = null)
	{
		if(is_string($name) === false)
		{
			throw new Exception('Invalid parameter type.');
		}

		if(is_string($description) === false && is_null($description) === false)
		{
			throw new Exception('Invalid parameter type.');
		}

		if($name === '*')
		{
			throw new Exception('Resource name cannot be "*"');
		}

		$this->_name = $name;

		if(is_null($description) === false)
		{
			$this->_description = $description;
		}
	}

	/**
	 * Returns the resource name
	 *
	 * @return string
	 */
	public function getName()
	{
		return $this->_name;
	}

	/**
	 * Returns resource description
	 *
	 * @return string|null
	 */
	public function getDescription()
	{
		return $this->_description;
	}

	/**
	 * Magic method __toString
	 *
	 * @return string
	 */
	public function __toString()
	{
		return $this->_name;
	}
}
