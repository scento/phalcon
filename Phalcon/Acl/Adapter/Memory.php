<?php
/**
 * ACL Memory Adapter
 *
 * @author Andres Gutierrez <andres@phalconphp.com>
 * @author Eduar Carvajal <eduar@phalconphp.com>
 * @author Wenzel Pünter <wenzel@phelix.me>
 * @version 1.2.6
 * @package Phalcon
*/
namespace Phalcon\Acl\Adapter;

use \Phalcon\Events\EventsAwareInterface,
	\Phalcon\Acl\Exception,
	\Phalcon\Acl\Adapter,	
	\Phalcon\Acl\AdapterInterface,
	\Phalcon\Acl\RoleInterface,
	\Phalcon\Acl\Role,
	\Phalcon\Acl\Resource,
	\Phalcon\Acl;

/**
 * Phalcon\Acl\Adapter\Memory
 *
 * Manages ACL lists in memory
 *
 *<code>
 *
 *	$acl = new Phalcon\Acl\Adapter\Memory();
 *
 *	$acl->setDefaultAction(Phalcon\Acl::DENY);
 *
 *	//Register roles
 *	$roles = array(
 *		'users' => new Phalcon\Acl\Role('Users'),
 *		'guests' => new Phalcon\Acl\Role('Guests')
 *	);
 *	foreach ($roles as $role) {
 *		$acl->addRole($role);
 *	}
 *
 *	//Private area resources
 *  $privateResources = array(
 *		'companies' => array('index', 'search', 'new', 'edit', 'save', 'create', 'delete'),
 *		'products' => array('index', 'search', 'new', 'edit', 'save', 'create', 'delete'),
 *		'invoices' => array('index', 'profile')
 *	);
 *	foreach ($privateResources as $resource => $actions) {
 *		$acl->addResource(new Phalcon\Acl\Resource($resource), $actions);
 *	}
 *
 *	//Public area resources
 *	$publicResources = array(
 *		'index' => array('index'),
 *		'about' => array('index'),
 *		'session' => array('index', 'register', 'start', 'end'),
 *		'contact' => array('index', 'send')
 *	);
 *  foreach ($publicResources as $resource => $actions) {
 *		$acl->addResource(new Phalcon\Acl\Resource($resource), $actions);
 *	}
 *
 *  //Grant access to public areas to both users and guests
 *	foreach ($roles as $role){
 *		foreach ($publicResources as $resource => $actions) {
 *			$acl->allow($role->getName(), $resource, '*');
 *		}
 *	}
 *
 *	//Grant access to private area to role Users
 *  foreach ($privateResources as $resource => $actions) {
 * 		foreach ($actions as $action) {
 *			$acl->allow('Users', $resource, $action);
 *		}
 *	}
 *
 *</code>
 * 
 * @see https://github.com/phalcon/cphalcon/blob/1.2.6/ext/acl/adapter/memory.c
 */
class Memory extends Adapter implements EventsAwareInterface, AdapterInterface
{
	/**
	 * Positive Access Result
	 * 
	 * @var int
	*/
	const YES = 1;

	/**
	 * Negative Access Result
	 * 
	 * @var int
	*/
	const NO = 0;

	/**
	 * Unknown Access Result
	 * 
	 * @var int
	*/
	const DUNNO = -1;

	/**
	 * Roles Names
	 * 
	 * @var null|array
	 * @access protected
	*/
	protected $_rolesNames = null;

	/**
	 * Roles
	 * 
	 * @var null|array
	 * @access protected
	*/
	protected $_roles = array();

	/**
	 * Resource Names
	 * 
	 * @var null|array
	 * @access protected
	*/
	protected $_resourcesNames = null;

	/**
	 * Resources
	 * 
	 * @var null|array
	 * @access protected
	*/
	protected $_resources = null;

	/**
	 * Access
	 * 
	 * @var null|array
	 * @access protected
	*/
	protected $_access = null;

	/**
	 * Role Inherits
	 * 
	 * @var null|array
	 * @access protected
	*/
	protected $_roleInherits = null;

	/**
	 * Access List
	 * 
	 * @var null|array
	 * @access protected
	*/
	protected $_accessList = null;

	/**
	 * \Phalcon\Acl\Adapter\Memory constructor
	 */
	public function __construct()
	{
		$this->_rolesNames = array();
		$this->_roles = array();
		$this->_access = array();
		$this->_roleInherits = array();
		$this->_resources = array();

		$this->_resourcesNames = array('*');
		$this->_accessList = array('*!*');
	}


	/**
	 * Adds a role to the ACL list. Second parameter allows inheriting access data from other existing role
	 *
	 * Example:
	 * <code>
	 * 	$acl->addRole(new \Phalcon\Acl\Role('administrator'), 'consultant');
	 * 	$acl->addRole('administrator', 'consultant');
	 * </code>
	 *
	 * @param \Phalcon\Acl\RoleInterface|string $role
	 * @param object|string|null $accessInherits
	 * @return boolean
	 * @throws Exception
	 */
	public function addRole($role, $accessInherits = null)
	{
		if(is_array($accessInherits) === false && 
			is_string($accessInherits) === false && is_null($accessInherits) === false) {
			throw new Exception('Invalid parameter type.');
		}

		if(is_null($accessInherits) === false 
			&& is_string($accessInherits) === false && is_object($accessInherits) === false)
		{
			throw new Exception('Invalid parameter type.');
		}

		if(is_object($role) === true)
		{
			$role_name = $role->getName();
			$object = $role;
		} elseif(is_string($role) === true) {
			$role_name = $role;
			$object = new Role($role);
		} else {
			throw new Exception('Invalid parameter type.');
		}

		if(isset($this->_rolesNames[$role_name]) === true)
		{
			return false;
		}

		$this->_roles[] = $object;
		$this->_rolesNames[$role_name] = true;
		$this->_access[$role_name.'!*!*'] = $this->_defaultAccess;

		if(is_null($accessInherits) === false)
		{
			return $this->addInherit($role_name, $accessInherits);
		}

		return true;
	}


	/**
	 * Do a role inherit from another existing role
	 *
	 * @param string $roleName
	 * @param string|object $roleToInherit
	 * @return boolean|null
	 * @throws Exception
	 */
	public function addInherit($roleName, $roleToInherit)
	{
		if(is_string($roleName) === false)
		{
			throw new Exception('Invalid parameter type.');
		}

		if(isset($this->_rolesNames[$roleName]) === false)
		{
			throw new Exception('Role \''.$roleName.'\' does not exist in the role list');
		}

		if(is_object($roleToInherit) === true &&
			$roleToInherit instanceof Role === true)
		{
			$role_inherit_name = $roleToInherit->getName();
		} elseif(is_string($roleToInherit) === true)
		{
			$role_inherit_name = $roleToInherit;
		} else {
			throw new Exception('Invalid parameter type.');
		}

		if(isset($this->_rolesNames[$role_inherit_name]) === false)
		{
			throw new Exception('Role \''.$role_inherit_name.'\' (to inherit) does not exist in the role list');
		}

		if($role_inherit_name === $role_name)
		{
			return false;
		}

		if(isset($this->_roleInherits[$role_name]) === false)
		{
			$this->_roleInherits[$role_name] = array();
		}

		$this->_roleInherits[$role_name][] = $role_inherit_name;
	}


	/**
	 * Check whether role exist in the roles list
	 *
	 * @param string $roleName
	 * @return boolean
	 * @throws Exception
	 */
	public function isRole($roleName)
	{
		if(is_string($roleName) === false)
		{
			throw new Exception('Invalid parameter type.');
		}

		return isset($this->_rolesNames[$roleName]);
	}


	/**
	 * Check whether resource exist in the resources list
	 *
	 * @param string $resourceName
	 * @return boolean
	 * @throws Exception
	 */
	public function isResource($resourceName)
	{
		if(is_string($resourceName) === false)
		{
			throw new Exception('Invalid parameter type.');
		}

		return isset($this->_resourcesNames[$resourceName]);
	}


	/**
	 * Adds a resource to the ACL list
	 *
	 * Access names can be a particular action, by example
	 * search, update, delete, etc or a list of them
	 *
	 * Example:
	 * <code>
	 * //Add a resource to the the list allowing access to an action
	 * $acl->addResource(new \Phalcon\Acl\Resource('customers'), 'search');
	 * $acl->addResource('customers', 'search');
	 *
	 * //Add a resource  with an access list
	 * $acl->addResource(new \Phalcon\Acl\Resource('customers'), array('create', 'search'));
	 * $acl->addResource('customers', array('create', 'search'));
	 * </code>
	 *
	 * @param \Phalcon\Acl\Resource|string $resource
	 * @param array|string|null $accessList
	 * @return boolean
	 * @throws Exception
	 */
	public function addResource($resource, $accessList = null)
	{
		if(is_object($resource) === true)
		{
			$resource_name = $resource->getName();
			$object = $resource;
		} elseif(is_string($resource) === true) {
			$resource_name = $resource;
			$object = new Resource($resource_name);
		} else {
			throw new Exception('Invalid parameter type.');
		}

		if(isset($this->_resourcesNames[$resource_name]) === false)
		{
			$this->_resources[] = $object;
			$this->_resourcesNames[$resource_name] = true;
		}

		return $this->addResourceAccess($resource_name, $accessList);	
	}


	/**
	 * Adds access to resources
	 *
	 * @param string $resourceName
	 * @param array|string $accessList
	 * @throws Exception
	 */
	public function addResourceAccess($resourceName, $accessList)
	{
		if(is_string($resourceName) === false)
		{
			throw new Exception('Invalid parameter type.');
		}

		if(isset($this->_resourcesNames[$resourceName]) === false)
		{
			throw new Exception('Resource \''.$resourceName.'\' does not exist in ACL');
		}

		if(is_array($accessList) === true) {
			foreach($accessList as $access_name)
			{
				$key = $resourceName.'!'.$access_name;
				if(isset($this->_accessList[$key]) === false)
				{
					$this->_accessList[$key] = true;
				}
			}
		} elseif(is_string($accessList) === true) {
			$key = $resourceName.'!'.$accessList;
			if(isset($this->_accessList[$key]) === false)
			{
				$this->_accessList[$key] = true;
			}
		} else {
			throw new Exception('Invalid parameter type.');
		}

		return true;
	}


	/**
	 * Removes an access from a resource
	 *
	 * @param string $resourceName
	 * @param array|string $accessList
	 * @throws Exception
	 */
	public function dropResourceAccess($resourceName, $accessList)
	{
		if(is_string($resourceName) === false)
		{
			throw new Exception('Invalid parameter type.');
		}

		if(is_array($accessList) === true) {
			foreach($accessList as $access_name)
			{
				unset($this->_accessList[$resourceName.'!'.$access_name]);
			}
		} elseif(is_string($accessList) === true) {
			unset($this->_accessList[$resourceName.'!'.$accessList]);
		} else {
			throw new Exception('Invalid parameter type.');
		}
	}


	/**
	 * Allows or denies the access to a resource
	 *
	 * @param string $roleName
	 * @param string $resourceName
	 * @param string|array $access
	 * @param int $action
	 * @throws Exception
	 */
	protected function _allowOrDeny($roleName, $resourceName, $access, $action)
	{
		if(is_string($roleName) === false || is_string($resourceName) === false 
			|| is_int($action) === false)
		{
			throw new Exception('Invalid parameter type.');
		}

		if(isset($this->_rolesNames[$roleName]) === false)
		{
			throw new Exception('Role "'.$roleName.'" does not exist in ACL');
		}

		if(isset($this->_resourceNames[$resourceName]) === false)
		{
			throw new Exception('Resource "'.$resourceName.'" does not exist in ACL');
		}

		if(is_array($access) === true) {
			foreach($access as $name)
			{
				if(isset($this->_accessList[$resourceName.'!'.$name]) === false)
				{
					throw new Exception('Access \''.$name.'\' does not exist in resource \''.$resourceName.'\'');
				}

				$key = $roleName.'!'.$resourceName.'!'.$name;
				if(is_string($name) !== '*')
				{
					$key = $roleName.'!'.$resourceName.'!*';
					if(isset($this->_access[$key]) === false)
					{
						$this->_access[$key] = $this->_defaultAccess;
					}
				}
			}

		} elseif(is_string($access) === true) {
			if($access !== '*')
			{
				$key = $resourceName.'!'.$access;
				if(isset($this->_accessList[$key]) === false)
				{
					throw new Exception('Access \''.$access.'\' does not exist in resource \''.$resourceName.'\'');
				}
			}

			$this->_access[$roleName.'!'.$resourceName.'!'.$access] = $action;

			if($access !== '*')
			{
				$key = $roleName.'!'.$resourceName.'!*';

				if(isset($this->_access[$key]) === false)
				{
					$this->_access[$key] = $this->_defaultAccess;
				}
			}
		} else {
			throw new Exception('Invalid parameter type.');
		}
	}


	/**
	 * Allow access to a role on a resource
	 *
	 * You can use '*' as wildcard
	 *
	 * Example:
	 * <code>
	 * //Allow access to guests to search on customers
	 * $acl->allow('guests', 'customers', 'search');
	 *
	 * //Allow access to guests to search or create on customers
	 * $acl->allow('guests', 'customers', array('search', 'create'));
	 *
	 * //Allow access to any role to browse on products
	 * $acl->allow('*', 'products', 'browse');
	 *
	 * //Allow access to any role to browse on any resource
	 * $acl->allow('*', '*', 'browse');
	 * </code>
	 *
	 * @param string $roleName
	 * @param string $resourceName
	 * @param string|array $access
	 */
	public function allow($roleName, $resourceName, $access)
	{
		if(is_string($roleName) === false || is_string($resourceName) === false)
		{
			throw new Exception('Invalid parameter type.');
		}

		return $this->_allowOrDeny($roleName, $resourceName, $access, Acl::ALLOW);
	}


	/**
	 * Deny access to a role on a resource
	 *
	 * You can use '*' as wildcard
	 *
	 * Example:
	 * <code>
	 * //Deny access to guests to search on customers
	 * $acl->deny('guests', 'customers', 'search');
	 *
	 * //Deny access to guests to search or create on customers
	 * $acl->deny('guests', 'customers', array('search', 'create'));
	 *
	 * //Deny access to any role to browse on products
	 * $acl->deny('*', 'products', 'browse');
	 *
	 * //Deny access to any role to browse on any resource
	 * $acl->deny('*', '*', 'browse');
	 * </code>
	 *
	 * @param string $roleName
	 * @param string $resourceName
	 * @param mixed $access
	 * @return boolean
	 */
	public function deny($roleName, $resourceName, $access)
	{
		if(is_string($roleName) === false || is_string($resourceName) === false)
		{
			throw new Exception('Invalid parameter type.');
		}

		return $this->_allowOrDeny($roleName, $resourceName, $access, Acl::DENY);
	}

	/**
	 * Check whether a role is allowed to access an action from a resource in 
	 * inherited roles.
	 * 
	 * @param string $role
	 * @param string $resource
	 * @param string $access
	 * @param array $accessList
	 * @param array|null $roleInherits
	 * @return int
	*/
	private static function _checkInheritance($role, $resource, $access, $accessList, $roleInherits)
	{
		if(isset($roleInherits[$role]) === true)
		{
			$inherited_roles = $roleInherits[$role];
			if(is_array($inherited_roles) === false)
			{
				return self::DUNNO;
			}
		} else {
			return self::DUNNO;
		}

		foreach($inherited_roles as $parent_role)
		{
			$result = self::DUNNO;

			$access_key = $parent_role.'!'.$resource.'!'.$access;
			if(isset($accessList[$access_key]) === true)
			{
				$result = (isset($accessList[$access_key]) === true ? self::YES : self::NO);
				break;
			}

			$result = self::_checkInheritance($parent_role, $resource, $access, $accessList, $roleInherits);
			if($result !== self::DUNNO)
			{
				break;
			}
		}

		return $result;
	}

	/**
	 * Check whether a role is allowed to access an action from a resource
	 *
	 * <code>
	 * //Does andres have access to the customers resource to create?
	 * $acl->isAllowed('andres', 'Products', 'create');
	 *
	 * //Do guests have access to any resource to edit?
	 * $acl->isAllowed('guests', '*', 'edit');
	 * </code>
	 *
	 * @param  string $role
	 * @param  string $resource
	 * @param  string $access
	 * @return boolean
	 */
	public function isAllowed($role, $resource, $access)
	{
		if(is_string($role) === false || is_string($resource) === false ||
			is_string($access) === false)
		{
			throw new Exception('Invalid parameter type.');
		}

		$this->_activeRole = $role;
		$this->_activeResource = $resource;
		$this->_activeAccess = $access;

		//Call the events manager
		if(is_object($this->_eventsManager) === true)
		{
			$status = $this->_eventsManager->fire('acl:beforeCheckAccess', $this);
			if($status == false)
			{
				return $status;
			}
		}

		//Check if the role exists
		if(isset($this->_rolesNames[$role]) === false)
		{
			return $this->_defaultAccess;
		}

		$access_key = $role.'!'.$resource.'!'.$access;

		//Check if there is a direct combination for role-resource-access
		if(isset($this->_access[$access_key]) === true)
		{
			$allow_access = ($this->_access[$access_key] === true ? self::YES : self::NO);
		} else {
			$allow_access = self::DUNNO;
		}

		//Check in the inherits roles
		if($allow_access === self::DUNNO)
		{
			$allow_access = $this->_checkInheritance($role, $resource, $access, $this->_access, $this->_roleInherits);
		}

		//If access wasn't found yet, try role-resource-*
		if($allow_access === self::DUNNO)
		{
			$access_key = $role.'!'.$resource.'!*';

			if(isset($this->_access[$access_key]) === true)
			{
				$allow_access = ($this->_access[$access_key] === true ? self::YES : self::NO);
			} else {
				$allow_access = self::DUNNO;
			}

			//Check in inherits roles
			if($allow_access === self::DUNNO)
			{
				$allow_access = $this->_checkInheritance($role, $resource, '*', $this->_access, $this->_roleInherits);
			}
		}

		//If access wasn't found yet, try role-*-*
		if($allow_access === self::DUNNO)
		{
			$access_key = $role.'!*!*';

			if(isset($this->_access[$access_key]) === true)
			{
				$allow_access = ($this->_access[$access_key] === true ? self::YES : self::NO);
			} else {
				$allow_access = self::DUNNO;
			}

			if($allow_access === self::DUNNO)
			{
				$allow_access = $this->_checkInheritance($role, '*', '*', $this->_access, $this->_roleInherits);
			}
		}

		//Set accessGranted to false if $allow_access is DUNNO
		$this->_accessGranted = ($allow_access === self::DUNNO ? false : (self::YES == $allow_access));

		if(is_object($this->_eventsManager) === true)
		{
			$this->_eventsManager->fire('acl:afterCheckAccess', $this, $this->_accessGranted);
		}

		return $this->_accessGranted;
	}


	/**
	 * Return an array with every role registered in the list
	 *
	 * @return array[\Phalcon\Acl\Role]
	 */
	public function getRoles()
	{
		return $this->_roles;
	}


	/**
	 * Return an array with every resource registered in the list
	 *
	 * @return array[\Phalcon\Acl\Resource]
	 */
	public function getResources()
	{
		return $this->_resources;
	}

}