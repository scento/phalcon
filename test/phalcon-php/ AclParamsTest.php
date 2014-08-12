<?php
/**
 * ACL Parameter ValidationTestsuite
 *
 * @author Wenzel Pünter <wenzel@phelix.me>
*/
class AclParamsTest extends PHPUnit_Framework_TestCase
{
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