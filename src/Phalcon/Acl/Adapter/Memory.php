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

use \Phalcon\Events\EventsAwareInterface;
use \Phalcon\Acl\Exception;
use \Phalcon\Acl\Adapter;
use \Phalcon\Acl\AdapterInterface;
use \Phalcon\Acl\RoleInterface;
use \Phalcon\Acl\Role;
use \Phalcon\Acl\ResourceInterface;
use \Phalcon\Acl\Resource;
use \Phalcon\Acl;

/**
 * Phalcon\Acl\Adapter\Memory
 *
 * Manages ACL lists in memory
 *
 *<code>
 *
 *  $acl = new Phalcon\Acl\Adapter\Memory();
 *
 *  $acl->setDefaultAction(Phalcon\Acl::DENY);
 *
 *  //Register roles
 *  $roles = array(
 *      'users' => new Phalcon\Acl\Role('Users'),
 *      'guests' => new Phalcon\Acl\Role('Guests')
 *  );
 *  foreach ($roles as $role) {
 *      $acl->addRole($role);
 *  }
 *
 *  //Private area resources
 *  $privateResources = array(
 *      'companies' => array('index', 'search', 'new', 'edit', 'save', 'create', 'delete'),
 *      'products' => array('index', 'search', 'new', 'edit', 'save', 'create', 'delete'),
 *      'invoices' => array('index', 'profile')
 *  );
 *  foreach ($privateResources as $resource => $actions) {
 *      $acl->addResource(new Phalcon\Acl\Resource($resource), $actions);
 *  }
 *
 *  //Public area resources
 *  $publicResources = array(
 *      'index' => array('index'),
 *      'about' => array('index'),
 *      'session' => array('index', 'register', 'start', 'end'),
 *      'contact' => array('index', 'send')
 *  );
 *  foreach ($publicResources as $resource => $actions) {
 *      $acl->addResource(new Phalcon\Acl\Resource($resource), $actions);
 *  }
 *
 *  //Grant access to public areas to both users and guests
 *  foreach ($roles as $role){
 *      foreach ($publicResources as $resource => $actions) {
 *          $acl->allow($role->getName(), $resource, '*');
 *      }
 *  }
 *
 *  //Grant access to private area to role Users
 *  foreach ($privateResources as $resource => $actions) {
 *      foreach ($actions as $action) {
 *          $acl->allow('Users', $resource, $action);
 *      }
 *  }
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
     * @var array
     * @access protected
    */
    protected $_rolesNames;

    /**
     * Roles
     *
     * @var array
     * @access protected
    */
    protected $_roles;

    /**
     * Resource Names
     *
     * @var array
     * @access protected
    */
    protected $_resourcesNames;

    /**
     * Resources
     *
     * @var array
     * @access protected
    */
    protected $_resources;

    /**
     * Access
     *
     * @var array
     * @access protected
    */
    protected $_access;

    /**
     * Role Inherits
     *
     * @var array
     * @access protected
    */
    protected $_roleInherits;

    /**
     * Access List
     *
     * @var array
     * @access protected
    */
    protected $_accessList;

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

        $this->_resourcesNames = array('*' => true);
        $this->_accessList = array('*!*' => true);
    }

    /**
     * Adds a role to the ACL list. Second parameter allows inheriting access data from other existing role
     *
     * Example:
     * <code>
     *  $acl->addRole(new \Phalcon\Acl\Role('administrator'), 'consultant');
     *  $acl->addRole('administrator', 'consultant');
     * </code>
     *
     * @param \Phalcon\Acl\RoleInterface|string $role
     * @param \Phalcon\Acl\RoleInterface|string|null $accessInherits
     * @return boolean
     * @throws Exception
     */
    public function addRole($role, $accessInherits = null)
    {
        if (is_object($role) === true &&
            $role instanceof RoleInterface === true) {
            $roleName = $role->getName();
            $object = $role;
        } elseif (is_string($role) === true) {
            $roleName = $role;
            $object = new Role($role);
        } else {
            throw new Exception('Invalid parameter type.');
        }

        if (isset($this->_rolesNames[$roleName]) === true) {
            return false;
        }

        $this->_roles[] = $object;
        $this->_rolesNames[$roleName] = true;
        $this->_access[$roleName.'!*!*'] = $this->_defaultAccess;

        if (is_null($accessInherits) === false) {
            return $this->addInherit($roleName, $accessInherits);
        }

        return true;
    }

    /**
     * Do a role inherit from another existing role
     *
     * @param string $roleName
     * @param string|\Phalcon\Acl\RoleInterface $roleToInherit
     * @return boolean
     * @throws Exception
     */
    public function addInherit($roleName, $roleToInherit)
    {
        if (is_string($roleName) === false) {
            throw new Exception('Invalid parameter type.');
        }

        if (isset($this->_rolesNames[$roleName]) === false) {
            throw new Exception("Role '".$roleName."' does not exist in the role list");
        }

        //Determine roleInheritName
        if (is_object($roleToInherit) === true &&
            $roleToInherit instanceof RoleInterface === true) {
            $roleInheritName = $roleToInherit->getName();
        } elseif (is_string($roleToInherit) === true) {
            $roleInheritName = $roleToInherit;
        } else {
            throw new Exception('Invalid parameter type.');
        }

        //Check if the role to inherit is valid
        if (isset($this->_rolesNames[$roleInheritName]) === false) {
            throw new Exception("Role '".$roleInheritName."' (to inherit) does not exist in the role list");
        }

        if ($roleInheritName === $roleName) {
            return false;
        }

        if (isset($this->_roleInherits[$roleName]) === false) {
            $this->_roleInherits[$roleName] = array();
        }

        $this->_roleInherits[$roleName][] = $roleInheritName;
        return true;
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
        if (is_string($roleName) === false) {
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
        if (is_string($resourceName) === false) {
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
     * @param \Phalcon\Acl\ResourceInterface|string $resource
     * @param array|string|null $accessList
     * @return boolean
     * @throws Exception
     */
    public function addResource($resource, $accessList = null)
    {
        if (is_object($resource) === true &&
            $resource instanceof ResourceInterface === true) {
            $resourceName = $resource->getName();
            $object = $resource;
        } elseif (is_string($resource) === true) {
            $resourceName = $resource;
            $object = new Resource($resource);
        } else {
            throw new Exception('Invalid parameter type.');
        }

        if (isset($this->_resourcesNames[$resourceName]) === false) {
            $this->_resources[] = $object;
            $this->_resourcesNames[$resourceName] = true;
        }

        return $this->addResourceAccess($resourceName, $accessList);
    }

    /**
     * Adds access to resources
     *
     * @param string $resourceName
     * @param array|string|null $accessList
     * @return boolean
     * @throws Exception
     */
    public function addResourceAccess($resourceName, $accessList)
    {
        if (is_string($resourceName) === false) {
            throw new Exception('Invalid parameter type.');
        }

        if (isset($this->_resourcesNames[$resourceName]) === false) {
            throw new Exception("Resource '".$resourceName."' does not exist in ACL");
        }

        if (is_array($accessList) === true) {
            foreach ($accessList as $accessName) {
                $key = $resourceName.'!'.$accessName;
                if (isset($this->_accessList[$key]) === false) {
                    $this->_accessList[$key] = true;
                }
            }
        } elseif (is_string($accessList) === true) {
            $key = $resourceName.'!'.$accessList;
            if (isset($this->_accessList[$key]) === false) {
                $this->_accessList[$key] = true;
            }
        } elseif (is_null($accessList) === false) {
            //@note null can be passed by addResource() and is not handled
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
        if (is_string($resourceName) === false) {
            throw new Exception('Invalid parameter type.');
        }

        if (is_array($accessList) === true) {
            foreach ($accessList as $accessName) {
                unset($this->_accessList[$resourceName.'!'.$accessName]);
            }
        } elseif (is_string($accessList) === true) {
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
        if (is_string($roleName) === false ||
            is_string($resourceName) === false ||
            is_int($action) === false) {
            throw new Exception('Invalid parameter type.');
        }

        if (isset($this->_rolesNames[$roleName]) === false) {
            throw new Exception('Role "'.$roleName.'" does not exist in ACL');
        }

        if (isset($this->_resourcesNames[$resourceName]) === false) {
            throw new Exception('Resource "'.$resourceName.'" does not exist in ACL');
        }

        if (is_array($access) === true) {
            foreach ($access as $accessName) {
                $accessKey = $resourceName.'!'.$accessName;
                if (isset($this->_accessList[$accessKey]) === false) {
                    throw new Exception("Access '".$accessName."' does not exist in resource '".$resourceName."'");
                }
            }

            foreach ($access as $accessName) {
                $accessKey = $roleName.'!'.$resourceName.'!'.$accessName;
                $this->_access[$accessKey] = $action;
                if ($accessName !== '*') {
                    $accessKeyAll = $roleName.'!'.$resourceName.'!*';
                    if (isset($this->_access[$accessKeyAll]) === false) {
                        $this->_access[$accessKeyAll] = $this->_defaultAccess;
                    }
                }
            }
        } elseif (is_string($access) === true) {
            if ($access !== '*') {
                $accessKey = $resourceName.'!'.$access;
                if (isset($this->_accessList[$accessKey]) === false) {
                    throw new Exception("Access '".$access."' does not exist in resource '".$resourceName."'");
                }
            }

            $accessKey = $roleName.'!'.$resourceName.'!'.$access;
            //Define the access action for the specified accessKey
            $this->_access[$accessKey] = $action;

            if ($access !== '*') {
                $accessKey = $roleName.'!'.$resourceName.'!*';

                //If there is no default action for all the rest actions on the resource set the
                //default one
                if (isset($this->_access[$accessKey]) === false) {
                    $this->_access[$accessKey] = $this->_defaultAccess;
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
     * @param string|array $access
     */
    public function deny($roleName, $resourceName, $access)
    {
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
     * @throws Exception
    */
    private static function _checkInheritance($role, $resource, $access, $accessList, $roleInherits)
    {
        if (is_string($role) === false ||
            is_string($resource) === false ||
            is_string($access) === false ||
            is_array($accessList) === false) {
            throw new Exception('Invalid parameter type.');
        }

        if (isset($roleInherits[$role]) === true) {
            $inheritedRoles = $roleInherits[$role];
            if (is_array($inheritedRoles) === false) {
                return self::DUNNO;
            }
        } else {
            return self::DUNNO;
        }

        $accessKey = null;
        foreach ($inheritedRoles as $parentRole) {
            $result = self::DUNNO;

            $accessKey = $parentRole.'!'.$resource.'!'.$access;
            if (isset($accessList[$accessKey]) === true) {
                $result = ($accessList[$accessKey] == true ? self::YES : self::NO);
                break;
            }

            $accessKey = null;

            $result = self::_checkInheritance($parentRole, $resource, $access, $accessList, $roleInherits);
            if ($result !== self::DUNNO) {
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
     * @param string $role
     * @param string $resource
     * @param string $access
     * @return boolean
     * @throws Exception
     */
    public function isAllowed($role, $resource, $access)
    {
        if (is_string($role) === false ||
            is_string($resource) === false ||
            is_string($access) === false) {
            throw new Exception('Invalid parameter type.');
        }

        $this->_activeRole = $role;
        $this->_activeResource = $resource;
        $this->_activeAccess = $access;

        //Call the events manager
        if (is_object($this->_eventsManager) === true) {
            $status = $this->_eventsManager->fire('acl:beforeCheckAccess', $this);
            if ($status === false) {
                return $status;
            }
        }

        //Check if the role exists
        if (isset($this->_rolesNames[$role]) === false) {
            return $this->_defaultAccess;
        }

        $accessKey = $role.'!'.$resource.'!'.$access;

        //Check if there is a direct combination for role-resource-access
        if (isset($this->_access[$accessKey]) === true) {
            $allowAccess = ($this->_access[$accessKey] == true ? self::YES : self::NO);
        } else {
            $allowAccess = self::DUNNO;
        }

        //Check in the inherits roles
        if ($allowAccess === self::DUNNO) {
            $allowAccess = self::_checkInheritance($role, $resource, $access, $this->_access, $this->_roleInherits);
        }

        //If access wasn't found yet, try role-resource-*
        if ($allowAccess === self::DUNNO) {
            $accessKey = $role.'!'.$resource.'!*';

            //In the direct role
            if (isset($this->_access[$accessKey]) === true) {
                $allowAccess = ($this->_access[$accessKey] == true ? self::YES : self::NO);
            } else {
                $allowAccess = self::DUNNO;
            }

            //Check in inherits roles
            if ($allowAccess === self::DUNNO) {
                $allowAccess = self::_checkInheritance($role, $resource, '*', $this->_access, $this->_roleInherits);
            }
        }

        //If access wasn't found yet, try role-*-*
        if ($allowAccess === self::DUNNO) {
            $accessKey = $role.'!*!*';

            //Try in the direct role
            if (isset($this->_access[$accessKey]) === true) {
                $allowAccess = ($this->_access[$accessKey] == true ? self::YES : self::NO);
            } else {
                $allowAccess = self::DUNNO;
            }

            if ($allowAccess === self::DUNNO) {
                $allowAccess = self::_checkInheritance($role, '*', '*', $this->_access, $this->_roleInherits);
            }
        }

        if ($allowAccess === self::DUNNO) {
            $haveAccess = false;
        } else {
            $haveAccess = (self::YES === $allowAccess ? true : false);
        }

        //Set accessGranted to false if $allowAccess is DUNNO
        $this->_accessGranted = $haveAccess;

        if (is_object($this->_eventsManager) === true) {
            $this->_eventsManager->fire('acl:afterCheckAccess', $this, $haveAccess);
        }

        return $haveAccess;
    }

    /**
     * Return an array with every role registered in the list
     *
     * @return array
     */
    public function getRoles()
    {
        return $this->_roles;
    }

    /**
     * Return an array with every resource registered in the list
     *
     * @return array
     */
    public function getResources()
    {
        return $this->_resources;
    }
}
