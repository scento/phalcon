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
 * Phalcon\Acl\ResourceInterface initializer
 * 
 * @see https://github.com/phalcon/cphalcon/blob/1.2.6/ext/acl/resourceinterface.c
 */
interface ResourceInterface
{
	/**
	 * \Phalcon\Acl\ResourceInterface constructor
	 *
	 * @param string $name
	 * @param string $description
	 */
	public function __construct($name, $description = null);

	/**
	 * Returns the resource name
	 *
	 * @return string
	 */
	public function getName();

	/**
	 * Returns resource description
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