<?php
/**
 * Config Testsuite
 *
 * @author Wenzel Pünter <wenzel@phelix.me>
*/
class ConfigTest extends BaseTest
{
	public function testArraySimple()
	{
		$config = new \Phalcon\Config(array(
			'database' => array(
				'adapter' => 'Mysql',
				'host' => 'localhost',
				'username' => 'scott',
				'password' => 'cheetah',
				'dbname' => 'test_db',
			),
     		'app' => array(
        		'controllersDir' => '../app/controllers/',
        		'modelsDir' => '../app/models/',
        		'viewsDir' => '../app/views/',
    		),
    		'mysetting' => 'the-value'
    	));

		$this->assertEquals(isset($config->app->controllersDir), true);
		$this->assertEquals(isset($config->notExistingProperty), false);
		$this->assertEquals(isset($config['app']['controllersDir']), true);
		$this->assertEquals(isset($config['mysetting']), true);
		$this->assertEquals($config->offsetExists('mysetting'), true);

    	$this->assertEquals($config->app->controllersDir, '../app/controllers/');
    	$this->assertEquals($config['app']['controllersDir'], '../app/controllers/');
    	$this->assertEquals($config['notExistingProperty'], null);

    	$this->assertEquals($config->mysetting, $config->get('mysetting'));
    	$this->assertEquals($config['mysetting'], $config->mysetting);
	}

	public function testArrayExtended()
	{
		$config = new \Phalcon\Config(array());

		$config['boolTrue'] = true;
		$this->assertEquals($config->offsetExists('boolTrue'), true);
		$this->assertEquals(isset($config['boolTrue']), true);
		$this->assertEquals(isset($config->boolTrue), true);
		$this->assertEquals($config['boolTrue'], true);
		$this->assertEquals($config->boolTrue, true);
		$this->assertEquals($config->get('boolTrue'), true);
		$this->assertEquals($config->offsetGet('boolTrue'), true);

		$config->offsetUnset('boolTrue');
		$this->assertEquals(isset($config['boolTrue']), false);
		$this->assertEquals($config['boolTrue'], null);

		$this->assertEquals($config->toArray(), array());
		$this->assertEquals($config->count, 0);
	}
}