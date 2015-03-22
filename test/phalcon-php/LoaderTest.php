<?php

namespace Scento\Tests;

/**
 * Loader Testsuite
 *
 * @author Wenzel Pünter <wenzel@phelix.me>
*/
class LoaderTest extends \BaseTest
{
	/**
	 * Test extensions
	*/
	public function testExtensions()
	{
		$loader = new \Phalcon\Loader();

		$this->assertException(
			array($loader, 'setExtensions'),
			array('php'),
			'Phalcon\Loader\Exception'
		);

		$this->assertEquals($loader->getExtensions(), array('php'));
		$loader->setExtensions(array('php', 'inc'));
		$this->assertEquals($loader->getExtensions(), array('php', 'inc'));
	}

	/**
	 * Test Namespaces
	*/
	public function testNamespaces()
	{
		$loader = new \Phalcon\Loader();

		$this->assertException(
			array($loader, 'registerNamespaces'), 
			array(array('Phalcon\Acl'), 'random'), 
			'Phalcon\Loader\Exception');

		$this->assertException(
			array($loader, 'registerNamespaces'), 
			array('Phalcon\Acl'), 
			'Phalcon\Loader\Exception');

		$loader->registerNamespaces(array('Phalcon\Acl' => 'lib/Phalcon/Acl/'));
		$this->assertEquals(
			$loader->getNamespaces(), array(
				'Phalcon\Acl' => 'lib/Phalcon/Acl/'
		));

		$loader->registerNamespaces(array('Phalcon\Assets' => 'lib/Phalcon/Assets/'), true);
		$this->assertEquals(
			$loader->getNamespaces(), array(
				'Phalcon\Acl' => 'lib/Phalcon/Acl/',
				'Phalcon\Assets' => 'lib/Phalcon/Assets/'
		));

		$loader->registerNamespaces(array('Phalcon' => 'lib/Phalcon/'));
		$this->assertEquals(
			$loader->getNamespaces(), array(
				'Phalcon' => 'lib/Phalcon/'
		));

		$loader->registerNamespaces(array('Phalcon' => 'src/Phalcon/'), false);
		$this->assertEquals(
			$loader->getNamespaces(), array(
				'Phalcon' => 'src/Phalcon/'
		));
	}

	/**
	 * Test Bind Events Manager
	*/
	public function testEventsManagerBind()
	{
		$loader = new \Phalcon\Loader();

		$this->assertException(
			array($loader, 'setEventsManager'),
			array('random string'),
			'Phalcon\Loader\Exception');

		$this->assertException(
			array($loader, 'setEventsManager'),
			array(new \stdClass()),
			'Phalcon\Loader\Exception');

		$manager = new \Phalcon\Events\Manager();
		$loader->setEventsManager($manager);
		$this->assertEquals($loader->getEventsManager(), $manager);
	}

	/**
	 * Test Prefixes
	*/
	public function testPrefixes()
	{
		$loader = new \Phalcon\Loader();

		$this->assertException(
			array($loader, 'registerPrefixes'),
			array('not even a prefix'),
			'Phalcon\Loader\Exception'
		);

		$this->assertException(
			array($loader, 'registerPrefixes'),
			array(array('prefix'), 'not a boolean'),
			'Phalcon\Loader\Exception'
		);

		$this->assertEquals($loader->getPrefixes(), null);

		$this->assertEquals(
			$loader->registerPrefixes(array('ThisIsAPrefix' => 'path/')),
			$loader
		);

		$this->assertEquals(
			$loader->getPrefixes(), 
			array('ThisIsAPrefix' => 'path/')
		);

		$loader->registerPrefixes(array('AnotherPrefix' => 'other/path/'), true);
		$this->assertEquals(
			$loader->getPrefixes(),
			array('ThisIsAPrefix' => 'path/', 'AnotherPrefix' => 'other/path/')
		);

		$loader->registerPrefixes(array('MasterPrefix' => 'prefix/'));
		$this->assertEquals(
			$loader->getPrefixes(),
			array('MasterPrefix' => 'prefix/')
		);

		$loader->registerPrefixes(array('AnotherMaster' => 'master/prefix/'), false);
		$this->assertEquals(
			$loader->getPrefixes(),
			array('AnotherMaster' => 'master/prefix/')
		);
	}

	/**
	 * Test Directories
	*/
	public function testDirs()
	{
		$loader = new \Phalcon\Loader();

		$this->assertException(
			array($loader, 'registerDirs'),
			array(array('prefix' => 'dir/'), 123),
			'Phalcon\Loader\Exception'
		);

		$this->assertException(
			array($loader, 'registerDirs'),
			array('randomData'),
			'Phalcon\Loader\Exception'
		);

		$this->assertEquals(
			$loader->getDirs(),
			null
		);

		$this->assertEquals(
			$loader->registerDirs(array('prefix' => 'dir/')),
			$loader
		);
		$this->assertEquals(
			$loader->getDirs(),
			array('prefix' => 'dir/')
		);

		$loader->registerDirs(array('another' => 'another/dir/'), true);
		$this->assertEquals(
			$loader->getDirs(),
			array(
				'prefix' => 'dir/',
				'another' => 'another/dir/'
			)
		);

		$loader->registerDirs(array('overwrite' => 'overwrite/'));
		$this->assertEquals(
			$loader->getDirs(),
			array('overwrite' => 'overwrite/')
		);

		$loader->registerDirs(array('anotherOverwrite' => 'another/overwrite/'), false);
		$this->assertEquals(
			$loader->getDirs(),
			array('anotherOverwrite' => 'another/overwrite/')
		);
	}

	/**
	 * Test Classes
	*/
	public function testClasses()
	{
		$loader = new \Phalcon\Loader();

		$this->assertException(
			array($loader, 'registerClasses'),
			array('notAnArray'),
			'Phalcon\Loader\Exception'
		);

		$this->assertException(
			array($loader, 'registerClasses'),
			array(array('classes'), 666),
			'Phalcon\Loader\Exception'
		);

		$this->assertEquals($loader->getClasses(), null);

		$this->assertEquals(
			$loader->registerClasses(array('Test' => 'src/Test.php')),
			$loader
		);
		$this->assertEquals(
			$loader->getClasses(),
			array('Test' => 'src/Test.php')
		);

		$loader->registerClasses(array('Another' => 'src/Another.php'), true);
		$this->assertEquals(
			$loader->getClasses(),
			array(
				'Test' => 'src/Test.php',
				'Another' => 'src/Another.php'
			)
		);

		$loader->registerClasses(array('Overwrite' => 'src/OverWrite.inc'));
		$this->assertEquals(
			$loader->getClasses(),
			array('Overwrite' => 'src/OverWrite.inc')
		);

		$loader->registerClasses(array('Cleanup' => 'src/666.php'), false);
		$this->assertEquals(
			$loader->getClasses(),
			array('Cleanup' => 'src/666.php')
		);
	}

	/**
	 * Test register/unregister
	*/
	public function testRegisterUnregister()
	{
		$functions = spl_autoload_functions();

		$loader = new \Phalcon\Loader();
		$this->assertEquals($loader->register(), $loader);
		$this->assertTrue((spl_autoload_functions() !== $functions ? true : false));

		$this->assertEquals($loader->unregister(), $loader);
		$this->assertEquals(spl_autoload_functions(), $functions);

		$this->assertEquals($loader->register(), $loader);
		$this->assertTrue((spl_autoload_functions() !== $functions ? true : false));
	}

	/**
	 * Test paths
	*/
	public function testPaths()
	{
		$loader = new \Phalcon\Loader();

		$this->assertEquals($loader->getFoundPath(), null);
		$this->assertEquals($loader->getCheckedPath(), null);
	}
}