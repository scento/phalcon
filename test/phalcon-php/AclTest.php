<?php
/**
 * ACL Testsuite
 *
 * @author Wenzel Pünter <wenzel@phelix.me>
*/
class AclTest extends PHPUnit_Framework_TestCase
{
	/**
	 * Helper function for exception testing
	 * 
	 * @param callable $trigger
	 * @param array $params
	 * @param string $exception
	*/
	private function assertException($trigger, $params, $exception)
	{
		$thrown = false;
		try {
			call_user_func_array($trigger, $params);
		} catch(\Exception $e) {
			$thrown = true;
		}

		$this->assertEquals($thrown, true);

		return $thrown;
	}

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
	 * Is Role
	*/
	public function testIsRole()
	{
		$acl = new Phalcon\Acl\Adapter\Memory();
		$acl->addRole('Role');

		$this->assertEquals(
			$acl->isRole('Role'),
			true
		);
		$this->assertEquals(
			$acl->isRole('Another'),
			false
		);
	}

	/**
	 * Is Resource
	*/
	public function testIsResource()
	{
		$acl = new Phalcon\Acl\Adapter\Memory();
		$acl->addResource('Resource');

		$this->assertEquals(
			$acl->isResource('Resource'),
			true
		);
		$this->assertEquals(
			$acl->isResource('Another'),
			false
		);
	}

	/**
	 * Allow or Deny
	*/
	public function testAllowOrDeny()
	{
		$acl = new Phalcon\Acl\Adapter\Memory();

		//Invalid first param
		$this->assertException(array($acl, 'allow'), array(123, 'resource', 'access'),
			'Phalcon\Acl\Exception');

		//Invalid second param
		$this->assertException(array($acl, 'allow'), array('role', 123, 'access'),
			'Phalcon\Acl\Exception');

		//Invalid role
		$this->assertException(array($acl, 'allow'), array('role', 'resource', 'access'),
			'Phalcon\Acl\Exception');
		$acl->addRole('role');

		//Invalid resource
		$this->assertException(array($acl, 'allow'), array('role', 'resource', 'access'),
			'Phalcon\Acl\Exception');
		$acl->addResource('resource', array('index', 'show'));

		//Invalid access (string)
		$this->assertException(array($acl, 'allow'), array('role', 'resource', 'coffee'),
			'Phalcon\Acl\Exception');

		//Invalid third param
		$this->assertException(array($acl, 'allow'), array('role', 'resource', array('index', 'coffee')),
			'Phalcon\Acl\Exception');

		//Invalid access (int)
		$this->assertException(array($acl, 'allow'), array('role', 'resource', 1234),
			'Phalcon\Acl\Exception');

		//Valid access (array)
		$acl->allow('role', 'resource', array('index'));
		$this->assertEquals($acl->isAllowed('role', 'resource', 'index'), true);

		//valid access (string)
		$acl->allow('role', 'resource', 'show');
		$this->assertEquals($acl->isAllowed('role', 'resource', 'show'), true);
	}

	/**
	 * Memory::isAllowed
	*/
	public function testIsAllowed()
	{
		$acl = new Phalcon\Acl\Adapter\Memory();

		$acl->setDefaultAction(Phalcon\Acl::ALLOW);

		$this->assertEquals(
			$acl->isAllowed('role', 'resource', 'access'),
			Phalcon\Acl::ALLOW
		);

		$acl->setDefaultAction(Phalcon\Acl::DENY);

		$this->assertEquals(
			$acl->isAllowed('role', 'resource', 'access'),
			Phalcon\Acl::DENY
		);

		$this->assertException(array($acl, 'isAllowed'), array(123, 'resource', 'access'), 
			'Phalcon\Acl\Exception');
		$this->assertException(array($acl, 'isAllowed'), array('role', 123, 'access'), 
			'Phalcon\Acl\Exception');
		$this->assertException(array($acl, 'isAllowed'), array('role', 'resource', 123), 
			'Phalcon\Acl\Exception');

		$acl->addRole('Role');
		$acl->addRole('Role 2');
		$acl->addInherit('Role 2', 'Role');
		$acl->addResource('Index', array('test'));
		$acl->allow('Role', '*', '*');

		$this->assertEquals(
			$acl->isAllowed('Role', 'None', 'Index'),
			false
		);
	}
}