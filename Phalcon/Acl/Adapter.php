<?php
/**
 * ACL Adapter Class
 *
 * @author Andres Gutierrez <andres@phalconphp.com>
 * @author Eduar Carvajal <eduar@phalconphp.com>
 * @author Wenzel Pünter <wenzel@phelix.me>
 * @version 1.2.6
 * @package Phalcon
*/
namespace Phalcon\Acl;

use \Phalcon\Events\EventsAwareInterface,
	\Phalcon\Events\ManagerInterface,
	\Phalcon\Acl\Exception;

/**
 * Phalcon\Acl\Adapter
 *
 * Adapter for Phalcon\Acl adapters
 * 
 * @see https://github.com/phalcon/cphalcon/blob/master/ext/acl/adapter.c
*/
abstract class Adapter implements EventsAwareInterface
{
	/**
	 * Events Manager
	 * 
	 * @var null|ManagerInterface
	 * @access protected
	*/
	protected $_eventsManager = null;

	/**
	 * Default Access
	 * 
	 * @var int
	 * @access protected
	*/
	protected $_defaultAccess = 1;

	/**
	 * Access Granted
	 * 
	 * @var bool
	 * @access protected
	*/
	protected $_accessGranted = false;

	/**
	 * Active Role
	 * 
	 * @var null|string
	 * @access protected
	*/
	protected $_activeRole = null;

	/**
	 * Active Resources
	 * 
	 * @var null|string
	*/
	protected $_activeResource = null;

	/**
	 * Active Access
	 * 
	 * @var null|string
	*/
	protected $_activeAccess = null;

	/**
	 * Sets the events manager
	 *
	 * @param ManagerInterface $eventsManager
	 */
	public function setEventsManager(ManagerInterface $eventsManager)
	{
		$this->_eventsManager = $eventsManager;
	}

	/**
	 * Returns the internal event manager
	 *
	 * @return ManagerInterface|null
	 */
	public function getEventsManager()
	{
		return $this->_eventsManager;
	}

	/**
	 * Sets the default access level (Phalcon\Acl::ALLOW or \Phalcon\Acl::DENY)
	 *
	 * @param int $defaultAccess
	 * @throws Exception
	 */
	public function setDefaultAction($defaultAccess)
	{
		if(is_int($defaultAccess) === false)
		{
			throw new Exception('Invalid parameter type.');
		}

		$this->_defaultAccess = $defaultAccess;
	}

	/**
	 * Returns the default ACL access level
	 *
	 * @return int
	 */
	public function getDefaultAction()
	{
		return $this->_defaultAccess;
	}

	/**
	 * Returns the role which the list is checking if it's allowed to certain resource/access
	 *
	 * @return string|null
	 */
	public function getActiveRole()
	{
		return $this->_activeRole;
	}

	/**
	 * Returns the resource which the list is checking if some role can access it
	 *
	 * @return string|null
	 */
	public function getActiveResource()
	{
		return $this->_activeResource;
	}

	/**
	 * Returns the access which the list is checking if some role can access it
	 *
	 * @return string|null
	 */
	public function getActiveAccess()
	{
		return $this->_activeAccess;
	}
}