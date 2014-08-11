<?php
/**
 * ACL Resource Interface
 *
 * @author Andres Gutierrez <andres@phalconphp.com>
 * @author Eduar Carvajal <eduar@phalconphp.com>
 * @version 1.2.6
 * @package Phalcon
*/
namespace Phalcon\Acl;

/**
 * Phalcon\Acl\RoleInterface initializer
 * 
 * @see https://github.com/phalcon/cphalcon/blob/1.2.6/ext/acl/roleinterface.c
 */
interface RoleInterface
{
	/**
	 * \Phalcon\Acl\Role constructor
	 *
	 * @param string $name
	 * @param string|null $description
	 */
	public function __construct($name, $description = null);

	/**
	 * Returns the role name
	 *
	 * @return string
	 */
	public function getName();

	/**
	 * Returns role description
	 *
	 * @return string|null
	 */
	public function getDescription();

	/**
	 * Magic method __toString
	 *
	 * @return string
	 */
	public function __toString();
}