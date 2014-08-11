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
 * @see https://github.com/phalcon/cphalcon/blob/1.2.6/ext/acl/adapter.c
*/
abstract class Adapter implements EventsAwareInterface
{
	/**
	 * Events Manager
	 * 
	 * @var ManagerInterface|null
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
	 * @var string|null
	 * @access protected
	*/
	protected $_activeRole = null;

	/**
	 * Active Resources
	 * 
	 * @var string|null
	 * @access protected
	*/
	protected $_activeResource = null;

	/**
	 * Active Access
	 * 
	 * @var string|null
	 * @access protected
	*/
	protected $_activeAccess = null;

	/**
	 * Sets the events manager
	 *
	 * @param \Phalcon\Events\ManagerInterface $eventsManager
	 * @throws Exception
	 */
	public function setEventsManager($eventsManager)
	{
		if(is_object($eventsManager) === false ||
			$eventsManager instanceof ManagerInterface === false) {
			throw new Exception('Invalid parameter type.');
		}
		
		$this->_eventsManager = $eventsManager;
	}

	/**
	 * Returns the internal event manager
	 *
	 * @return \Phalcon\Events\ManagerInterface|null
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
		if(is_int($defaultAccess) === false) {
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