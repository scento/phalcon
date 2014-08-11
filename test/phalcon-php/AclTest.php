<?php
/**
 * ACL Testsuite
 *
 * @author Wenzel Pünter <wenzel@phelix.me>
*/
class AclTest extends PHPUnit_Framework_TestCase
{
	/**
	 * Simple
	*/
	public function testSimple()
	{
		$acl = new Phalcon\Acl\Adapter\Memory();

		$acl->setDefaultAction(Phalcon\Acl::DENY);
		$this->assertEquals($acl->getDefaultAction(), Phalcon\Acl::DENY);

		$role_a = new Phalcon\Acl\Role('Administrators', 'Super-User role');
		$role_b = new Phalcon\Acl\Role('Guests');
		$acl->addRole($role_a);
		$acl->addRole($role_b);
		$acl->addRole('Designers');
		$this->assertEquals($acl->getRoles(), 
			array(
				$role_a, 
				$role_b, 
				new Phalcon\Acl\Role('Designers')
			));

		$resource = new Phalcon\Acl\Resource('Customers');
		$acl->addResource($resource, 'search');
		$acl->addResource($resource, array('create', 'update'));
		$acl->addResource($resource);
		$acl->addResource('Imprint');
		$this->assertEquals($acl->getResources(), array(
			$resource,
			new Phalcon\Acl\Resource('Imprint')
		));

		$acl->allow('Guests', 'Customers', 'search');
		$acl->allow('Guests', 'Customers', 'create');
		$acl->allow('Designers', 'Imprint', '*');
		$this->assertEquals(
			$acl->isAllowed('Guests', 'Customers', 'edit'), 
			Phalcon\Acl::DENY
		);
		$this->assertEquals(
			$acl->isAllowed('Designers', 'Customers', 'create'),
			Phalcon\Acl::DENY
		);
		$this->assertEquals(
			$acl->isAllowed('Guests', 'Customers', 'search'),
			Phalcon\Acl::ALLOW
		);
		$this->assertEquals(
			$acl->isAllowed('Designers', 'Imprint', 'show'),
			Phalcon\Acl::ALLOW
		);
	}

	/**
	 * Wildcards
	*/
	public function testWildcards()
	{
		$acl = new Phalcon\Acl\Adapter\Memory();

		$acl->setDefaultAction(Phalcon\Acl::DENY);
		$this->assertEquals($acl->getDefaultAction(), Phalcon\Acl::DENY);

		$role_a = new Phalcon\Acl\Role('Administrators');
		$role_g = new Phalcon\Acl\Role('Guests');
		$acl->addRole($role_a);
		$acl->addRole($role_g);
		$this->assertEquals($acl->getRoles(), array(
			$role_a,
			$role_g
		));

		$resource_pub = new Phalcon\Acl\Resource('Public');
		$resource_pri = new Phalcon\Acl\Resource('Private');
		$acl->addResource($resource_pub, 'edit');
		$acl->addResource($resource_pri, array('create', 'update', 'delete'));
		$this->assertEquals($acl->getResources(), array(
			$resource_pub,
			$resource_pri
		));

		$acl->allow('Guests', 'Public', '*');
		$acl->allow('Administrators', '*', '*');

		$this->assertEquals(
			$acl->isAllowed('Guests', 'Public', 'edit'),
			Phalcon\Acl::ALLOW
		);
		$this->assertEquals(
			$acl->isAllowed('Administrators', 'Private', 'create'),
			Phalcon\Acl::ALLOW
		);
		$this->assertEquals(
			$acl->isAllowed('Guests', 'Private', 'update'),
			Phalcon\Acl::DENY
		);
		$this->assertEquals(
			$acl->isAllowed('Administrators', 'Public', 'edit'),
			Phalcon\Acl::ALLOW
		);
		$this->assertEquals(
			$acl->isAllowed('Guests', 'Public', 'edit'),
			Phalcon\Acl::ALLOW
		);
	}

	/**
	 * Simple Inheritance
	*/
	public function testInheritanceSimple()
	{
		$acl = new Phalcon\Acl\Adapter\Memory();

		$acl->setDefaultAction(Phalcon\Acl::DENY);
		$this->assertEquals($acl->getDefaultAction(), Phalcon\Acl::DENY);

		$role_a = new Phalcon\Acl\Role('Role A');
		$role_b = new Phalcon\Acl\Role('Role B');

		$acl->addRole($role_a);
		$acl->addRole($role_b, $role_a);
		$this->assertEquals($acl->getRoles(), array(
			$role_a,
			$role_b
		));

		$resource_a = new Phalcon\Acl\Resource('Resource A');
		$resource_b = new Phalcon\Acl\Resource('Resource B');

		$acl->addResource($resource_a);
		$acl->addResource($resource_b);
		$this->assertEquals($acl->getResources(), array(
			$resource_a, 
			$resource_b
		));

		$acl->allow('Role A', 'Resource A', '*');
		$acl->allow('Role B', 'Resource B', '*');

		$this->assertEquals(
			$acl->isAllowed('Role B', 'Resource A', 'action'),
			Phalcon\Acl::ALLOW
		);

		$this->assertEquals(
			$acl->isAllowed('Role A', 'Resource A', 'action'),
			Phalcon\Acl::ALLOW
		);

		$this->assertEquals(
			$acl->isAllowed('Role A', 'Resource B', 'action'),
			Phalcon\Acl::DENY
		);

		$this->assertEquals(
			$acl->isAllowed('Role B', 'Resource B', 'action'),
			Phalcon\Acl::ALLOW
		);
	}

	/**
	 * Multiple Inheritance
	*/
	public function testInheritanceMultiple()
	{
		$acl = new Phalcon\Acl\Adapter\Memory();

		$acl->setDefaultAction(Phalcon\Acl::DENY);
		$this->assertEquals($acl->getDefaultAction(), Phalcon\Acl::DENY);

		$role_a = new Phalcon\Acl\Role('Role A');
		$role_b = new Phalcon\Acl\Role('Role B');
		$role_c = new Phalcon\Acl\Role('Role C');
		$role_d = new Phalcon\Acl\Role('Role D');

		$acl->addRole($role_a);
		$acl->addRole($role_b, $role_a);
		$acl->addRole($role_c, $role_b);
		$acl->addRole($role_d, $role_c);
		$this->assertEquals($acl->getRoles(), array(
			$role_a,
			$role_b,
			$role_c,
			$role_d
		));

		$resource_a = new Phalcon\Acl\Resource('Resource A');
		$resource_b = new Phalcon\Acl\Resource('Resource B');
		$resource_c = new Phalcon\Acl\Resource('Resource C');
		$resource_d = new Phalcon\Acl\Resource('Resource D');

		$acl->addResource($resource_a);
		$acl->addResource($resource_b);
		$acl->addResource($resource_c);
		$acl->addResource($resource_d);
		$this->assertEquals($acl->getResources(), array(
			$resource_a,
			$resource_b,
			$resource_c,
			$resource_d
		));

		$acl->allow('Role A', 'Resource A', '*');
		$acl->allow('Role B', 'Resource B', '*');
		$acl->allow('Role C', 'Resource C', '*');
		$acl->allow('Role D', 'Resource D', '*');

		$this->assertEquals(
			$acl->isAllowed('Role A', 'Resource A', 'action'),
			Phalcon\Acl::ALLOW
		);
		$this->assertEquals(
			$acl->isAllowed('Role A', 'Resource B', 'action'),
			Phalcon\Acl::DENY
		);
		$this->assertEquals(
			$acl->isAllowed('Role A', 'Resource C', 'action'),
			Phalcon\Acl::DENY
		);
		$this->assertEquals(
			$acl->isAllowed('Role A', 'Resource D', 'action'),
			Phalcon\Acl::DENY
		);

		$this->assertEquals(
			$acl->isAllowed('Role B', 'Resource A', 'action'),
			Phalcon\Acl::ALLOW
		);
		$this->assertEquals(
			$acl->isAllowed('Role B', 'Resource B', 'action'),
			Phalcon\Acl::ALLOW
		);
		$this->assertEquals(
			$acl->isAllowed('Role B', 'Resource C', 'action'),
			Phalcon\Acl::DENY
		);
		$this->assertEquals(
			$acl->isAllowed('Role B', 'Resource D', 'action'),
			Phalcon\Acl::DENY
		);

		$this->assertEquals(
			$acl->isAllowed('Role C', 'Resource A', 'action'),
			Phalcon\Acl::ALLOW
		);
		$this->assertEquals(
			$acl->isAllowed('Role C', 'Resource B', 'action'),
			Phalcon\Acl::ALLOW
		);
		$this->assertEquals(
			$acl->isAllowed('Role C', 'Resource C', 'action'),
			Phalcon\Acl::ALLOW
		);
		$this->assertEquals(
			$acl->isAllowed('Role C', 'Resource D', 'action'),
			Phalcon\Acl::DENY
		);

		$this->assertEquals(
			$acl->isAllowed('Role D', 'Resource A', 'action'),
			Phalcon\Acl::ALLOW
		);
		$this->assertEquals(
			$acl->isAllowed('Role D', 'Resource B', 'action'),
			Phalcon\Acl::ALLOW
		);
		$this->assertEquals(
			$acl->isAllowed('Role D', 'Resource C', 'action'),
			Phalcon\Acl::ALLOW
		);
		$this->assertEquals(
			$acl->isAllowed('Role D', 'Resource D', 'action'),
			Phalcon\Acl::ALLOW
		);
	}

	/**
	 * Inversed Action
	*/
	public function testInversedAction()
	{
		$acl = new Phalcon\Acl\Adapter\Memory();

		$acl->setDefaultAction(Phalcon\Acl::ALLOW);
		$this->assertEquals($acl->getDefaultAction(), Phalcon\Acl::ALLOW);

		$role_a = new Phalcon\Acl\Role('Role A');
		$role_b = new Phalcon\Acl\Role('Role B');
		$acl->addRole($role_a);
		$acl->addRole($role_b);

		$this->assertEquals($acl->getRoles(), array(
			$role_a,
			$role_b
		));

		$resource_a = new Phalcon\Acl\Resource('Resource A');
		$resource_b = new Phalcon\Acl\Resource('Resource B');
		$acl->addResource($resource_a, array('index'));
		$acl->addResource($resource_b, array('index'));

		$this->assertEquals($acl->getResources(), array(
			$resource_a,
			$resource_b
		));

		$acl->deny('Role A', 'Resource A', 'index');
		$acl->deny('Role B', 'Resource A', 'index');
		$acl->deny('Role B', 'Resource B', 'index');

		$this->assertEquals(
			$acl->isAllowed('Role A', 'Resource A', 'index'),
			Phalcon\Acl::DENY
		);

		$this->assertEquals(
			$acl->isAllowed('Role A', 'Resource B', 'index'),
			Phalcon\Acl::ALLOW
		);

		$this->assertEquals(
			$acl->isAllowed('Role B', 'Resource A', 'index'),
			Phalcon\Acl::DENY
		);

		$this->assertEquals(
			$acl->isAllowed('Role B', 'Resource B', 'index'),
			Phalcon\Acl::DENY
		);
	}

	/**
	 * Manual Inherit
	*/
	public function testManualInherit()
	{
		$acl = new Phalcon\Acl\Adapter\Memory();

		$acl->setDefaultAction(Phalcon\Acl::DENY);
		$this->assertEquals($acl->getDefaultAction(), Phalcon\Acl::DENY);

		$role_a = new Phalcon\Acl\Role('Role A');
		$role_b = new Phalcon\Acl\Role('Role B');
		$role_c = new Phalcon\Acl\Role('Role C');
		$acl->addRole($role_a);
		$acl->addRole($role_b);
		$acl->addRole($role_c);
		$this->assertEquals($acl->getRoles(), array(
			$role_a,
			$role_b,
			$role_c
		));

		$resource_a = new Phalcon\Acl\Resource('Resource A');
		$resource_b = new Phalcon\Acl\Resource('Resource B');
		$acl->addResource($resource_a, 'index');
		$acl->addResource($resource_b, array('index'));
		$this->assertEquals($acl->getResources(), array(
			$resource_a,
			$resource_b
		));

		$acl->addInherit('Role B', $role_a);
		$acl->addInherit('Role C', 'Role B');

		$acl->allow('Role A', 'Resource A', 'index');
		$acl->allow('Role B', 'Resource B', 'index');

		$this->assertEquals(
			$acl->isAllowed('Role A', 'Resource A', 'index'),
			Phalcon\Acl::ALLOW
		);
		$this->assertEquals(
			$acl->isAllowed('Role A', 'Resource B', 'index'),
			Phalcon\Acl::DENY
		);
		$this->assertEquals(
			$acl->isAllowed('Role A', 'Resource A', 'anoth'),
			Phalcon\Acl::DENY
		);
		$this->assertEquals(
			$acl->isAllowed('Role B', 'Resource A', 'index'),
			Phalcon\Acl::ALLOW
		);
		$this->assertEquals(
			$acl->isAllowed('Role B', 'Resource B', 'index'),
			Phalcon\Acl::ALLOW
		);
		$this->assertEquals(
			$acl->isAllowed('Role B', 'Resource A', 'another'),
			Phalcon\Acl::DENY
		);
		$this->assertEquals(
			$acl->isAllowed('Role C', 'Resource A', 'index'),
			Phalcon\Acl::ALLOW
		);
		$this->assertEquals(
			$acl->isAllowed('Role C', 'Resource B', 'index'),
			Phalcon\Acl::ALLOW
		);
		$this->assertEquals(
			$acl->isAllowed('Role C', 'Resource A', 'test'),
			Phalcon\Acl::DENY
		);
	}

	/**
	 * Events Manager
	*/
	public function testEventsManager()
	{
		$GLOBALS['calledBefore'] = false;
		$GLOBALS['calledAfter'] = false;

		$events = new Phalcon\Events\Manager();
		$events->attach('acl:beforeCheckAccess', function() {
			$GLOBALS['calledBefore'] = true;
			return true;
		});
		$events->attach('acl:afterCheckAccess', function() {
			$GLOBALS['calledAfter'] = true;
			return true;
		});

		$acl = new Phalcon\Acl\Adapter\Memory();
		$acl->setDefaultAction(Phalcon\Acl::DENY);
		$this->assertEquals($acl->getDefaultAction(), Phalcon\Acl::DENY);

		$acl->setEventsManager($events);
		$this->assertEquals(
			$acl->getEventsManager(),
			$events
		);

		$resource = new Phalcon\Acl\Resource('Resource');
		$role = new Phalcon\Acl\Role('Role');
		$acl->addResource($resource, array('index'));
		$acl->addRole($role);
		$acl->allow('Role', 'Resource', 'index');

		$this->assertEquals($acl->getResources(), array(
			$resource
		));
		$this->assertEquals($acl->getRoles(), array(
			$role
		));
		$this->assertEquals($acl->isAllowed('Role', 'Resource', 'index'),
			Phalcon\Acl::ALLOW);
		$this->assertTrue($GLOBALS['calledBefore']);
		$this->assertTrue($GLOBALS['calledAfter']);

		unset($GLOBALS['calledBefore']);
		unset($GLOBALS['calledAfter']);
	}

	/**
	 * Events Manager Interaction
	*/
	public function testEventsManagerInteraction()
	{
		$events = new Phalcon\Events\Manager();
		$self = $this;
		$events->attach('acl:beforeCheckAccess', function($event, $component, $object) use($self) {
			$self->assertEquals($component->getActiveRole(), 'Role');
			$self->assertEquals($component->getActiveResource(), 'Resource');
			$self->assertEquals($component->getActiveAccess(), 'index');
			return false;
		});

		$acl = new Phalcon\Acl\Adapter\Memory();
		$acl->setDefaultAction(Phalcon\Acl::DENY);
		$this->assertEquals($acl->getDefaultAction(), Phalcon\Acl::DENY);

		$acl->setEventsManager($events);
		$this->assertEquals(
			$acl->getEventsManager(),
			$events
		);

		$resource = new Phalcon\Acl\Resource('Resource');
		$role = new Phalcon\Acl\Role('Role');
		$acl->addResource($resource, array('index'));
		$acl->addRole($role);
		$acl->allow('Role', 'Resource', 'index');

		$this->assertEquals($acl->getResources(), array(
			$resource
		));
		$this->assertEquals($acl->getRoles(), array(
			$role
		));
		$this->assertEquals($acl->isAllowed('Role', 'Resource', 'index'),
			false);
	}

	/**
	 * Resource Creation
	*/
	public function testResource()
	{
		$resource_a = new Phalcon\Acl\Resource('Resource A', 'Description');
		$resource_b = new Phalcon\Acl\Resource('Resource B');

		$this->assertEquals(
			$resource_a->getName(),
			'Resource A'
		);
		$this->assertEquals(
			$resource_b->getName(),
			'Resource B'
		);
		$this->assertEquals(
			$resource_a->getDescription(),
			'Description'
		);
		$this->assertEquals(
			$resource_b->getDescription(),
			null
		);
		$this->assertEquals(
			$resource_a,
			'Resource A'
		);
		$this->assertEquals(
			$resource_b,
			'Resource B'
		);
	}

	/**
	 * Role Creation
	*/
	public function testRole()
	{
		$role_a = new Phalcon\Acl\Role('Role A', 'Description');
		$role_b = new Phalcon\Acl\Role('Role B');

		$this->assertEquals(
			$role_a->getName(),
			'Role A'
		);
		$this->assertEquals(
			$role_b->getName(),
			'Role B'
		);
		$this->assertEquals(
			$role_a->getDescription(),
			'Description'
		);
		$this->assertEquals(
			$role_b->getDescription(),
			null
		);
		$this->assertEquals(
			$role_a,
			'Role A'
		);
		$this->assertEquals(
			$role_b,
			'Role B'
		);
	}

	/**
	 * Adapter::setEventsManager parameter validation
	*/
	public function testAdapterEventsManagerParameter()
	{
		$adapter = new Phalcon\Acl\Adapter\Memory();
		$exceptions = 0;

		//string
		try {$adapter->setEventsManager('test');} 
		catch(Phalcon\Acl\Exception $e) {$exceptions++;}

		//int
		try {$adapter->setEventsManager(123);}
		catch(Phalcon\Acl\Exception $e) {$exceptions++;}

		//double
		try {$adapter->setEventsManager(123.456);}
		catch(Phalcon\Acl\Exception $e) {$exceptions++;}

		//array
		try {$adapter->setEventsManager(array('test'));}
		catch(Phalcon\Acl\Exception $e) {$exceptions++;}

		//empty array
		try {$adapter->setEventsManager(array());}
		catch(Phalcon\Acl\Exception $e) {$exceptions++;}

		//wrong object
		try {$adapter->setEventsManager(new \stdClass());}
		catch(Phalcon\Acl\Exception $e) {$exceptions++;}

		//null
		try {$adapter->setEventsManager(null);}
		catch(Phalcon\Acl\Exception $e) {$exceptions++;}

		//boolean
		try {$adapter->setEventsManager(false);}
		catch(Phalcon\Acl\Exception $e) {$exceptions++;}

		$this->assertEquals($exceptions, 8);
	}

	/**
	 * Adapter::setDefaultAction parameter validation
	*/
	public function testAdapterDefaultActionParameter()
	{
		$adapter = new Phalcon\Acl\Adapter\Memory();
		$exceptions = 0;

		//string
		try {$adapter->setDefaultAction('test');} 
		catch(Phalcon\Acl\Exception $e) {$exceptions++;}

		//double
		try {$adapter->setDefaultAction(123.456);}
		catch(Phalcon\Acl\Exception $e) {$exceptions++;}

		//array
		try {$adapter->setDefaultAction(array('test'));}
		catch(Phalcon\Acl\Exception $e) {$exceptions++;}

		//empty array
		try {$adapter->setDefaultAction(array());}
		catch(Phalcon\Acl\Exception $e) {$exceptions++;}

		//object
		try {$adapter->setDefaultAction(new \stdClass());}
		catch(Phalcon\Acl\Exception $e) {$exceptions++;}

		//null
		try {$adapter->setDefaultAction(null);}
		catch(Phalcon\Acl\Exception $e) {$exceptions++;}

		//boolean
		try {$adapter->setDefaultAction(false);}
		catch(Phalcon\Acl\Exception $e) {$exceptions++;}

		$this->assertEquals($exceptions, 7);
	}

	/**
	 * Resource::__construct parameter validation
	*/
	public function testResourceParameter()
	{
		$exceptions = 0;

		//int
		try {$res = new Phalcon\Acl\Resource(123);}
		catch(Phalcon\Acl\Exception $e) {$exceptions++;}

		//double
		try {$res = new Phalcon\Acl\Resource(123.456);}
		catch(Phalcon\Acl\Exception $e) {$exceptions++;}

		//array
		try {$res = new Phalcon\Acl\Resource(array(123));}
		catch(Phalcon\Acl\Exception $e) {$exceptions++;}

		//empty array
		try {$res = new Phalcon\Acl\Resource(array());}
		catch(Phalcon\Acl\Exception $e) {$exceptions++;}

		//boolean
		try {$res = new Phalcon\Acl\Resource(false);}
		catch(Phalcon\Acl\Exception $e) {$exceptions++;}

		//object
		try {$res = new Phalcon\Acl\Resource(new \stdClass);}
		catch(Phalcon\Acl\Exception $e) {$exceptions++;}

		//null
		try {$res = new Phalcon\Acl\Resource(null);}
		catch(Phalcon\Acl\Exception $e) {$exceptions++;}

		//star
		try {$res = new Phalcon\Acl\Resource('*');}
		catch(Phalcon\Acl\Exception $e) {$exceptions++;}

		$this->assertEquals($exceptions, 8);

		$exceptions = 0;

		//int
		try {$res = new Phalcon\Acl\Resource('Resource', 123);}
		catch(Phalcon\Acl\Exception $e) {$exceptions++;}

		//double
		try {$res = new Phalcon\Acl\Resource('Resource', 123.456);}
		catch(Phalcon\Acl\Exception $e) {$exceptions++;}

		//array
		try {$res = new Phalcon\Acl\Resource('Resource', array(123));}
		catch(Phalcon\Acl\Exception $e) {$exceptions++;}

		//empty array
		try {$res = new Phalcon\Acl\Resource('Resource', array());}
		catch(Phalcon\Acl\Exception $e) {$exceptions++;}

		//boolean
		try {$res = new Phalcon\Acl\Resource('Resource', false);}
		catch(Phalcon\Acl\Exception $e) {$exceptions++;}

		//object
		try {$res = new Phalcon\Acl\Resource('Resource', new \stdClass);}
		catch(Phalcon\Acl\Exception $e) {$exceptions++;}

		$this->assertEquals($exceptions, 6);
	}

	/**
	 * Role::__construct parameter validation
	*/
	public function testRoleParameter()
	{
		$exceptions = 0;

		//int
		try {$res = new Phalcon\Acl\Role(123);}
		catch(Phalcon\Acl\Exception $e) {$exceptions++;}

		//double
		try {$res = new Phalcon\Acl\Role(123.456);}
		catch(Phalcon\Acl\Exception $e) {$exceptions++;}

		//array
		try {$res = new Phalcon\Acl\Role(array(123));}
		catch(Phalcon\Acl\Exception $e) {$exceptions++;}

		//empty array
		try {$res = new Phalcon\Acl\Role(array());}
		catch(Phalcon\Acl\Exception $e) {$exceptions++;}

		//boolean
		try {$res = new Phalcon\Acl\Role(false);}
		catch(Phalcon\Acl\Exception $e) {$exceptions++;}

		//object
		try {$res = new Phalcon\Acl\Role(new \stdClass);}
		catch(Phalcon\Acl\Exception $e) {$exceptions++;}

		//null
		try {$res = new Phalcon\Acl\Role(null);}
		catch(Phalcon\Acl\Exception $e) {$exceptions++;}

		//star
		try {$res = new Phalcon\Acl\Role('*');}
		catch(Phalcon\Acl\Exception $e) {$exceptions++;}

		$this->assertEquals($exceptions, 8);

		$exceptions = 0;

		//int
		try {$res = new Phalcon\Acl\Role('Role', 123);}
		catch(Phalcon\Acl\Exception $e) {$exceptions++;}

		//double
		try {$res = new Phalcon\Acl\Role('Role', 123.456);}
		catch(Phalcon\Acl\Exception $e) {$exceptions++;}

		//array
		try {$res = new Phalcon\Acl\Role('Role', array(123));}
		catch(Phalcon\Acl\Exception $e) {$exceptions++;}

		//empty array
		try {$res = new Phalcon\Acl\Role('Role', array());}
		catch(Phalcon\Acl\Exception $e) {$exceptions++;}

		//boolean
		try {$res = new Phalcon\Acl\Role('Role', false);}
		catch(Phalcon\Acl\Exception $e) {$exceptions++;}

		//object
		try {$res = new Phalcon\Acl\Role('Role', new \stdClass);}
		catch(Phalcon\Acl\Exception $e) {$exceptions++;}

		$this->assertEquals($exceptions, 6);
	}

	/**
	 * Memory::_allowOrDeny parameter validation
	*/
	public function testMemoryAllowOrDenyParameter()
	{
		$acl = new Phalcon\Acl\Adapter\Memory();

		$tests = array(
			'int' => 123,
			'double' => 123.456,
			'array' => array('test'),
			'empty_array' => array(),
			'bool' => false,
			'object' => new \stdClass(),
			'string' => 'test',
			'null' => null
		);

		$exceptions = 0;
		foreach($tests as $name => $test) {
			if($name === 'string') {continue;}
			foreach($tests as $second) {
				foreach($tests as $third) {
					try {$acl->allow($test, $second, $third);}
					catch(Phalcon\Acl\Exception $e) {$exceptions++;}
				}
			}
		}
		$this->assertEquals($exceptions, 448);


		$exceptions = 0;
		foreach($tests as $name => $test) {
			if($name === 'string') {continue;}
			foreach($tests as $second) {
				foreach($tests as $third) {
					try {$acl->allow($second, $test, $third);}
					catch(Phalcon\Acl\Exception $e) {$exceptions++;}
				}
			}
		}
		$this->assertEquals($exceptions, 448);


		$exceptions = 0;
		foreach($tests as $name => $test) {
			if($name === 'string' ||
				$name === 'array' ||
				$name === 'empty_array') {continue;}
			foreach($tests as $second) {
				foreach($tests as $third) {
					try {$acl->allow($second, $third, $test);}
					catch(Phalcon\Acl\Exception $e) {$exceptions++;}
				}
			}
		}
		$this->assertEquals($exceptions, 320);
	}

	/**
	 * Memory::addRole parameter validation
	*/
	public function testMemoryAddRoleParameter()
	{
		$acl = new Phalcon\Acl\Adapter\Memory();

		$tests = array(
			'int' => 123,
			'double' => 123.456,
			'array' => array('test'),
			'empty_array' => array(),
			'bool' => false,
			'object' => new \stdClass(),
			'string' => 'test',
			'null' => null
		);

		$exceptions = 0;
		foreach($tests as $name => $test) {
			if($name === 'string') {continue;}
			foreach($tests as $second) {
				try {$acl->addRole($test, $second);}
				catch(Phalcon\Acl\Exception $e) {$exceptions++;}
			}
		}
		$this->assertEquals($exceptions, 56);


		$exceptions = 0;
		foreach($tests as $name => $test) {
			if($name === 'string' ||
				$name === 'null') {continue;}
			foreach($tests as $second) {
				try {$acl->addRole($second, $test);}
				catch(Phalcon\Acl\Exception $e) {$exceptions++;}
			}
		}
		$this->assertEquals($exceptions, 43);
	}

	/**
	 * Memory::addInherit test
	*/
	public function testMemoryAddInherit()
	{
		$acl = new Phalcon\Acl\Adapter\Memory();
		$acl->addRole('Role');
		$this->assertEquals($acl->addInherit('Role', 'Role'), false);
		$this->assertEquals($acl->addInherit('Role', 
			new Phalcon\Acl\Role('Role')
		), false);


		$this->setExpectedException('Phalcon\Acl\Exception');
		$acl->addInherit('Role', 'Role 2');
	}

	/**
	 * Memory::addInherit parameter validation
	*/
	public function testMemoryAddInheritParameter()
	{
		$acl = new Phalcon\Acl\Adapter\Memory();

		$tests = array(
			'int' => 123,
			'double' => 123.456,
			'array' => array('test'),
			'empty_array' => array(),
			'bool' => false,
			'object' => new \stdClass(),
			'string' => 'test',
			'null' => null
		);

		$exceptions = 0;
		foreach($tests as $name => $test) {
			if($name === 'string') {continue;}
			foreach($tests as $second) {
				try {$acl->addInherit($test, $second);}
				catch(Phalcon\Acl\Exception $e) {$exceptions++;}
			}
		}
		$this->assertEquals($exceptions, 56);


		$exceptions = 0;
		foreach($tests as $name => $test) {
			if($name === 'string') {continue;}
			foreach($tests as $second) {
				try {$acl->addInherit($second, $test);}
				catch(Phalcon\Acl\Exception $e) {$exceptions++;}
			}
		}
		$this->assertEquals($exceptions, 56);
	}

	/**
	 * Memory::isRole parameter validation
	*/
	public function testMemoryIsRoleParameter()
	{
		$acl = new Phalcon\Acl\Adapter\Memory();

		$tests = array(
			'int' => 123,
			'double' => 123.456,
			'array' => array('test'),
			'empty_array' => array(),
			'bool' => false,
			'object' => new \stdClass(),
			'null' => null
		);

		$exceptions = 0;
		foreach($tests as $name => $test) {
			try {$acl->isRole($test);}
			catch(Phalcon\Acl\Exception $e) {$exceptions++;}
		}

		$this->assertEquals($exceptions, 7);
	}

	/**
	 * Memory::isResource parameter validation
	*/
	public function testMemoryIsResourceParameter()
	{
		$acl = new Phalcon\Acl\Adapter\Memory();

		$tests = array(
			'int' => 123,
			'double' => 123.456,
			'array' => array('test'),
			'empty_array' => array(),
			'bool' => false,
			'object' => new \stdClass(),
			'null' => null
		);

		$exceptions = 0;
		foreach($tests as $name => $test) {
			try {$acl->isResource($test);}
			catch(Phalcon\Acl\Exception $e) {$exceptions++;}
		}

		$this->assertEquals($exceptions, 7);
	}

	/**
	 * Memory::addResource parameter validation
	*/
	public function testMemoryAddResourceParameter()
	{
		$acl = new Phalcon\Acl\Adapter\Memory();

		$tests = array(
			'int' => 123,
			'double' => 123.456,
			'array' => array('test'),
			'empty_array' => array(),
			'bool' => false,
			'object' => new \stdClass(),
			'string' => 'test',
			'null' => null
		);

		$exceptions = 0;
		foreach($tests as $name => $test) {
			if($name === 'string') {continue;}
			foreach($tests as $second) {
				try {$acl->addResource($test, $second);}
				catch(Phalcon\Acl\Exception $e) {$exceptions++;}
			}
		}
		$this->assertEquals($exceptions, 56);


		$exceptions = 0;
		foreach($tests as $name => $test) {
			if($name === 'string' ||
				$name === 'array' ||
				$name === 'empty_array' ||
				$name === 'null') {continue;}
			foreach($tests as $second) {
				try {$acl->addResource($second, $test);}
				catch(Phalcon\Acl\Exception $e) {$exceptions++;}
			}
		}
		$this->assertEquals($exceptions, 32);
	}

	/**
	 * Memory::addResourceAccess test
	*/
	public function testMemoryAddResourceAccess()
	{
		$acl = new Phalcon\Acl\Adapter\Memory();
		$acl->addResource('Resource');
		$acl->addResourceAccess('Resource', array('index', 'show'));
		$acl->addResourceAccess('Resource', 'edit');
		$acl->addResourceAccess('Resource', null);

		$acl->addRole('Role');
		$acl->deny('Role', 'Resource', 'edit');
		$this->assertEquals($acl->isAllowed('Role', 'Resource', 'edit'),
			Phalcon\Acl::DENY);
	}

	/**
	 * Memory::addResourceAccess parameter validation
	*/
	public function testMemoryAddResourceAccessParameter()
	{
		$acl = new Phalcon\Acl\Adapter\Memory();

		$tests = array(
			'int' => 123,
			'double' => 123.456,
			'array' => array('test'),
			'empty_array' => array(),
			'bool' => false,
			'object' => new \stdClass(),
			'string' => 'test',
			'null' => null
		);

		$exceptions = 0;
		foreach($tests as $name => $test) {
			if($name === 'string') {continue;}
			foreach($tests as $second) {
				try {$acl->addResourceAccess($test, $second);}
				catch(Phalcon\Acl\Exception $e) {$exceptions++;}
			}
		}
		$this->assertEquals($exceptions, 56);


		$exceptions = 0;
		foreach($tests as $name => $test) {
			if($name === 'string' ||
				$name === 'array' ||
				$name === 'empty_array' ||
				$name === 'null') {continue;}
			foreach($tests as $second) {
				try {$acl->addResourceAccess($second, $test);}
				catch(Phalcon\Acl\Exception $e) {$exceptions++;}
			}
		}
		$this->assertEquals($exceptions, 32);
	}

	/**
	 * Memory::dropResourceAccess test
	*/
	public function testMemoryDropResourceAccess()
	{
		$acl = new Phalcon\Acl\Adapter\Memory();
		$acl->addResource('Resource');
		$acl->addResourceAccess('Resource', array('index', 'show'));
		$acl->addResourceAccess('Resource', 'edit');

		$acl->addRole('Role');
		$acl->deny('Role', 'Resource', 'index');
		$acl->deny('Role', 'Resource', 'edit');
		$this->assertEquals($acl->isAllowed('Role', 'Resource', 'edit'),
			Phalcon\Acl::DENY);

		$exceptions = 0;
		try {
			$acl->dropResourceAccess('Resource', 'edit');
			$acl->addRole('Role 2');
			$acl->deny('Role 2', 'Resource', 'edit');
		} catch(Phalcon\Acl\Exception $e) {
			$exceptions++;
		}

		$acl->addResourceAccess('Resource', 'edit');

		try {
			$acl->dropResourceAccess('Resource', array('edit'));
			$acl->addRole('Role 3');
			$acl->deny('Role 3', 'Resource', 'edit');
		} catch(Phalcon\Acl\Exception $e) {
			$exceptions++;
		}

		$this->assertEquals($exceptions, 2);
	}

	/**
	 * Memory::dropResourceAccess parameter validation
	*/
	public function testMemoryDropResourceAccessParameter()
	{
		$acl = new Phalcon\Acl\Adapter\Memory();

		$tests = array(
			'int' => 123,
			'double' => 123.456,
			'array' => array('test'),
			'empty_array' => array(),
			'bool' => false,
			'object' => new \stdClass(),
			'string' => 'test',
			'null' => null
		);

		$exceptions = 0;
		foreach($tests as $name => $test) {
			if($name === 'string') {continue;}
			foreach($tests as $second) {
				try {$acl->dropResourceAccess($test, $second);}
				catch(Phalcon\Acl\Exception $e) {$exceptions++;}
			}
		}
		$this->assertEquals($exceptions, 56);


		$exceptions = 0;
		foreach($tests as $name => $test) {
			if($name === 'string' ||
				$name === 'array' ||
				$name === 'empty_array'){continue;}
			foreach($tests as $second) {
				try {$acl->dropResourceAccess($second, $test);}
				catch(Phalcon\Acl\Exception $e) {$exceptions++;}
			}
		}
		$this->assertEquals($exceptions, 40);
	}
}