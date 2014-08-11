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
		$acl->addResource($resource_pub);
		$acl->addResource($resource_pri, array('create', 'update', 'delete'));
		$this->assertEquals($acl->getResources(), array(
			$resource_pub,
			$resource_pri
		));

		$acl->allow('*', 'Public', '*');
		$this->assertEquals(
			$acl->isAllowed('Administrators', 'Public', 'show'),
			Phalcon\Acl::ALLOW
		);
		$this->assertEquals(
			$acl->isAllowed('Guests', 'Public', 'edit'),
			Phalcon\Acl::ALLOW
		);

		$acl->allow('Administrators', '*', '*');
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
}