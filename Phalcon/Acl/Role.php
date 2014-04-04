<?php
/**
 * ACL Role
 *
 * @author Andres Gutierrez <andres@phalconphp.com>
 * @author Eduar Carvajal <eduar@phalconphp.com>
 * @author Wenzel Pünter <wenzel@phelix.me>
 * @version 1.2.6
 * @package Phalcon
*/
namespace Phalcon\Acl;

use \Phalcon\Acl\RoleInterface,
	\Phalcon\Acl\Exception;

/**
 * Phalcon\Acl\Role
 *
 * This class defines role entity and its description
 *
 * @see https://github.com/phalcon/cphalcon/blob/master/ext/acl/role.c
 */
class Role implements RoleInterface
{
	/**
	 * Name
	 * 
	 * @var string
	 * @access protected
	*/
	protected $_name = null;

	/**
	 * Description
	 * 
	 * @var string|null
	 * @access protected
	*/
	protected $_description = null;

	/**
	 * \Phalcon\Acl\Role description
	 *
	 * @param string $name
	 * @param string $description
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
			throw new Exception('Role name cannot be "*"');
		}

		$this->_name = $name;

		if(is_null($description) === false)
		{
			$this->_description = $description;
		}
	}

	/**
	 * Returns the role name
	 *
	 * @return string
	 */
	public function getName()
	{
		return $this->_name;
	}

	/**
	 * Returns role description
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