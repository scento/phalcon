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
	 * Adapter Events Manager Parameter (string)
	*/
	public function testAdapterEventsManagerParameterString()
	{
		$this->setExpectedException('Phalcon\Acl\Exception');
		$adapter = new Phalcon\Acl\Adapter\Memory();
		$adapter->setEventsManager('test');
	}

	/**
	 * Adapter Events Manager Parameter (int)
	*/
	public function testAdapterEventsManagerParameterInt()
	{
		$this->setExpectedException('Phalcon\Acl\Exception');
		$adapter = new Phalcon\Acl\Adapter\Memory();
		$adapter->setEventsManager(123);
	}

	/**
	 * Adapter Events Manager Parameter (double)
	*/
	public function testAdapterEventsManagerParameterDouble()
	{
		$this->setExpectedException('Phalcon\Acl\Exception');
		$adapter = new Phalcon\Acl\Adapter\Memory();
		$adapter->setEventsManager(123.345);
	}

	/**
	 * Adapter Events Manager Parameter (array)
	*/
	public function testAdapterEventsManagerParameterArray()
	{
		$this->setExpectedException('Phalcon\Acl\Exception');
		$adapter = new Phalcon\Acl\Adapter\Memory();
		$adapter->setEventsManager(array('test'));
	}

	/**
	 * Adapter Events Manager Parameter (empty array)
	*/
	public function testAdapterEventsManagerParameterArrayEmpty()
	{
		$this->setExpectedException('Phalcon\Acl\Exception');
		$adapter = new Phalcon\Acl\Adapter\Memory();
		$adapter->setEventsManager(array());
	}

	/**
	 * Adapter Events Manager Parameter (wrong object)
	*/
	public function testAdapterEventsManagerParameterWrongObject()
	{
		$this->setExpectedException('Phalcon\Acl\Exception');
		$adapter = new Phalcon\Acl\Adapter\Memory();
		$adapter->setEventsManager(new \stdClass());
	}

	/**
	 * Adapter Default Action Parameter (string)
	*/
	public function testAdapterDefaultActionParameterString()
	{
		$this->setExpectedException('Phalcon\Acl\Exception');
		$adapter = new Phalcon\Acl\Adapter\Memory();
		$adapter->setDefaultAction('test');
	}

	/**
	 * Adapter Default Action Parameter (double)
	*/
	public function testAdapterDefaultActionParameterDouble()
	{
		$this->setExpectedException('Phalcon\Acl\Exception');
		$adapter = new Phalcon\Acl\Adapter\Memory();
		$adapter->setDefaultAction(123.345);
	}

	/**
	 * Adapter Default Action Parameter (array)
	*/
	public function testDefaultActionManagerParameterArray()
	{
		$this->setExpectedException('Phalcon\Acl\Exception');
		$adapter = new Phalcon\Acl\Adapter\Memory();
		$adapter->setDefaultAction(array('test'));
	}

	/**
	 * Adapter Default Action Parameter (empty array)
	*/
	public function testAdapterDefaultActionParameterArrayEmpty()
	{
		$this->setExpectedException('Phalcon\Acl\Exception');
		$adapter = new Phalcon\Acl\Adapter\Memory();
		$adapter->setDefaultAction(array());
	}

	/**
	 * Adapter Default Action Parameter (object)
	*/
	public function testAdapterDefaultActionParameterWrongObject()
	{
		$this->setExpectedException('Phalcon\Acl\Exception');
		$adapter = new Phalcon\Acl\Adapter\Memory();
		$adapter->setDefaultAction(new \stdClass());
	}

	/**
	 * Resource Parameter: Name (int)
	*/
	public function testResourceParameterNameInt()
	{
		$this->setExpectedException('Phalcon\Acl\Exception');
		$resource = new Phalcon\Acl\Resource(135);
	}

	/**
	 * Resource Parameter: Name (double)
	*/
	public function testResourceParameterNameDouble()
	{
		$this->setExpectedException('Phalcon\Acl\Exception');
		$resource = new Phalcon\Acl\Resource(64.2);
	}

	/**
	 * Resource Parameter: Name (array)
	*/
	public function testResourceParameterNameArray()
	{
		$this->setExpectedException('Phalcon\Acl\Exception');
		$resource = new Phalcon\Acl\Resource(array('test'));
	}

	/**
	 * Resource Parameter: Name (empty array)
	*/
	public function testResourceParameterNameArrayEmpty()
	{
		$this->setExpectedException('Phalcon\Acl\Exception');
		$resource = new Phalcon\Acl\Resource(array());
	}

	/**
	 * Resource Parameter: Name (bool)
	*/
	public function testResourceParameterNameBool()
	{
		$this->setExpectedException('Phalcon\Acl\Exception');
		$resource = new Phalcon\Acl\Resource(false);
	}

	/**
	 * Resource Parameter: Name (object)
	*/
	public function testResourceParameterNameObject()
	{
		$this->setExpectedException('Phalcon\Acl\Exception');
		$resource = new Phalcon\Acl\Resource(new \stdClass());
	}

	/**
	 * Resource Parameter: Name (null)
	*/
	public function testResourceParameterNameNull()
	{
		$this->setExpectedException('Phalcon\Acl\Exception');
		$resource = new Phalcon\Acl\Resource(null);
	}

	/**
	 * Resource Parameter: Name (*)
	*/
	public function testResourceParameterNameStar()
	{
		$this->setExpectedException('Phalcon\Acl\Exception');
		$resource = new Phalcon\Acl\Resource('*');
	}

	/**
	 * Resource Parameter: Description (int)
	*/
	public function testResourceParameterDescriptionInt()
	{
		$this->setExpectedException('Phalcon\Acl\Exception');
		$resource = new Phalcon\Acl\Resource('Name', 123);
	}

	/**
	 * Resource Parameter: Description (double)
	*/
	public function testResourceParameterDescriptionDouble()
	{
		$this->setExpectedException('Phalcon\Acl\Exception');
		$resource = new Phalcon\Acl\Resource('Name', 123.456);
	}

	/**
	 * Resource Parameter: Description (array)
	*/
	public function testResourceParameterDescriptionArray()
	{
		$this->setExpectedException('Phalcon\Acl\Exception');
		$resource = new Phalcon\Acl\Resource('Name', array('test'));
	}

	/**
	 * Resource Parameter: Description (empty array)
	*/
	public function testResourceParameterDescriptionArrayEmpty()
	{
		$this->setExpectedException('Phalcon\Acl\Exception');
		$resource = new Phalcon\Acl\Resource('Name', array());
	}

	/**
	 * Resource Parameter: Description (int)
	*/
	public function testResourceParameterDescriptionBool()
	{
		$this->setExpectedException('Phalcon\Acl\Exception');
		$resource = new Phalcon\Acl\Resource('Name', false);
	}

	/**
	 * Resource Parameter: Description (object)
	*/
	public function testResourceParameterDescriptionObject()
	{
		$this->setExpectedException('Phalcon\Acl\Exception');
		$resource = new Phalcon\Acl\Resource('Name', new \stdClass());
	}

	/**
	 * Role Parameter: Name (int)
	*/
	public function testRoleParameterNameInt()
	{
		$this->setExpectedException('Phalcon\Acl\Exception');
		$resource = new Phalcon\Acl\Role(135);
	}

	/**
	 * Role Parameter: Name (double)
	*/
	public function testRoleParameterNameDouble()
	{
		$this->setExpectedException('Phalcon\Acl\Exception');
		$resource = new Phalcon\Acl\Role(64.2);
	}

	/**
	 * Role Parameter: Name (array)
	*/
	public function testRoleParameterNameArray()
	{
		$this->setExpectedException('Phalcon\Acl\Exception');
		$resource = new Phalcon\Acl\Role(array('test'));
	}

	/**
	 * Role Parameter: Name (empty array)
	*/
	public function testRoleParameterNameArrayEmpty()
	{
		$this->setExpectedException('Phalcon\Acl\Exception');
		$resource = new Phalcon\Acl\Role(array());
	}

	/**
	 * Role Parameter: Name (bool)
	*/
	public function testRoleParameterNameBool()
	{
		$this->setExpectedException('Phalcon\Acl\Exception');
		$resource = new Phalcon\Acl\Role(false);
	}

	/**
	 * Role Parameter: Name (object)
	*/
	public function testRoleParameterNameObject()
	{
		$this->setExpectedException('Phalcon\Acl\Exception');
		$resource = new Phalcon\Acl\Role(new \stdClass());
	}

	/**
	 * Role Parameter: Name (null)
	*/
	public function testRoleParameterNameNull()
	{
		$this->setExpectedException('Phalcon\Acl\Exception');
		$resource = new Phalcon\Acl\Role(null);
	}

	/**
	 * Role Parameter: Name (*)
	*/
	public function testRoleParameterNameStar()
	{
		$this->setExpectedException('Phalcon\Acl\Exception');
		$resource = new Phalcon\Acl\Role('*');
	}

	/**
	 * Role Parameter: Description (int)
	*/
	public function testRoleParameterDescriptionInt()
	{
		$this->setExpectedException('Phalcon\Acl\Exception');
		$resource = new Phalcon\Acl\Role('Name', 123);
	}

	/**
	 * Role Parameter: Description (double)
	*/
	public function testRoleParameterDescriptionDouble()
	{
		$this->setExpectedException('Phalcon\Acl\Exception');
		$resource = new Phalcon\Acl\Role('Name', 123.456);
	}

	/**
	 * Role Parameter: Description (array)
	*/
	public function testRoleParameterDescriptionArray()
	{
		$this->setExpectedException('Phalcon\Acl\Exception');
		$resource = new Phalcon\Acl\Role('Name', array('test'));
	}

	/**
	 * Role Parameter: Description (empty array)
	*/
	public function testRoleParameterDescriptionArrayEmpty()
	{
		$this->setExpectedException('Phalcon\Acl\Exception');
		$resource = new Phalcon\Acl\Role('Name', array());
	}

	/**
	 * Role Parameter: Description (int)
	*/
	public function testRoleParameterDescriptionBool()
	{
		$this->setExpectedException('Phalcon\Acl\Exception');
		$resource = new Phalcon\Acl\Role('Name', false);
	}

	/**
	 * Role Parameter: Description (object)
	*/
	public function testRoleParameterDescriptionObject()
	{
		$this->setExpectedException('Phalcon\Acl\Exception');
		$resource = new Phalcon\Acl\Role('Name', new \stdClass());
	}

	/**
	 * Allow or deny parameter
	*/
	public function testMemoryAllowOrDenyParameterIntFirst()
	{
		$this->setExpectedException('Phalcon\Acl\Exception');
		$acl = new Phalcon\Acl\Adapter\Memory();
		$acl->allow(1234, 1234, 123);
	}

	/**
	 * Allow or deny parameter
	*/
	public function testMemoryAllowOrDenyParameterSecondFirst()
	{
		$this->setExpectedException('Phalcon\Acl\Exception');
		$acl = new Phalcon\Acl\Adapter\Memory();
		$acl->allow('role', 1234, 123);
	}

	/**
	 * Allow or deny parameter
	*/
	public function testMemoryAllowOrDenyParameterIntThird()
	{
		$this->setExpectedException('Phalcon\Acl\Exception');
		$acl = new Phalcon\Acl\Adapter\Memory();
		$acl->allow('role', 'resource', 123);
	}

	/**
	 * Allow or deny parameter
	*/
	public function testMemoryAllowOrDenyParameterArray()
	{
		$this->setExpectedException('Phalcon\Acl\Exception');
		$acl = new Phalcon\Acl\Adapter\Memory();
		$acl->allow(array('test'), array('test'), 'index');
	}

	/**
	 * Allow or deny parameter
	*/
	public function testMemoryAllowOrDenyParameterArrayEmpty()
	{
		$this->setExpectedException('Phalcon\Acl\Exception');
		$acl = new Phalcon\Acl\Adapter\Memory();
		$acl->allow(array(), array(), 'index');
	}

	/**
	 * Allow or deny parameter
	*/
	public function testMemoryAllowOrDenyParameterBool()
	{
		$this->setExpectedException('Phalcon\Acl\Exception');
		$acl = new Phalcon\Acl\Adapter\Memory();
		$acl->allow(false, true, false);
	}

	/**
	 * Allow or deny parameter
	*/
	public function testMemoryAllowOrDenyParameterNull()
	{
		$this->setExpectedException('Phalcon\Acl\Exception');
		$acl = new Phalcon\Acl\Adapter\Memory();
		$acl->allow(null, null, null);
	}

	/**
	 * Allow or deny parameter
	*/
	public function testMemoryAllowOrDenyParameterObject()
	{
		$this->setExpectedException('Phalcon\Acl\Exception');
		$acl = new Phalcon\Acl\Adapter\Memory();
		$acl->allow(new \stdClass(), new \stdClass(), new \stdClass());
	}

	/**
	 * Allow or deny parameter
	*/
	public function testMemoryAllowOrDenyParameterDouble()
	{
		$this->setExpectedException('Phalcon\Acl\Exception');
		$acl = new Phalcon\Acl\Adapter\Memory();
		$acl->allow(123.456, 123.456, 123.456);
	}

	/**
	 * Allow or deny parameter
	*/
	public function testMemoryAllowOrDenyRoleNotExists()
	{
		$this->setExpectedException('Phalcon\Acl\Exception');
		$acl = new Phalcon\Acl\Adapter\Memory();
		$acl->allow('Role', 'Resource', 'Access');
	}

	/**
	 * Allow or deny parameter
	*/
	public function testMemoryAllowOrDenyResourceNotExists()
	{
		$this->setExpectedException('Phalcon\Acl\Exception');

		$role = new Phalcon\Acl\Role('Role');
		$acl = new Phalcon\Acl\Adapter\Memory();
		$acl->addRole($role);
		$acl->allow('Role', 'Resource', 'Access');
	}

	/**
	 * Allow or deny parameter
	*/
	public function testMemoryAllowOrDenyAccessNotExists()
	{
		$this->setExpectedException('Phalcon\Acl\Exception');
		
		$role = new Phalcon\Acl\Role('Role');
		$resource = new Phalcon\Acl\Resource('Resource');
		$acl = new Phalcon\Acl\Adapter\Memory();
		$acl->addRole($role);
		$acl->addResource($resource);
		$acl->allow('Role', 'Resource', 'Access');
	}

	/**
	 * Allow or deny parameter
	*/
	public function testMemoryAllowOrDenyAccessNotExistsArray()
	{
		$this->setExpectedException('Phalcon\Acl\Exception');
		
		$role = new Phalcon\Acl\Role('Role');
		$resource = new Phalcon\Acl\Resource('Resource');
		$acl = new Phalcon\Acl\Adapter\Memory();
		$acl->addRole($role);
		$acl->addResource($resource);
		$acl->deny('Role', 'Resource', array('Access'));
	}
}