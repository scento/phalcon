<?php
/**
 * Events Manager Testsuite
 *
 * @author Wenzel Pünter <wenzel@phelix.me>
*/
class EventsTest extends BaseTest
{
	public function testEnablePriorities()
	{
		$e = new \Phalcon\Events\Manager();
		$this->assertFalse($e->arePrioritiesEnabled());
		$e->enablePriorities(true);
		$this->assertTrue($e->arePrioritiesEnabled());
	}

	public function testEnablePrioritiesException()
	{
		$this->setExpectedException('\Phalcon\Events\Exception');
		$e = new \Phalcon\Events\Manager();
		$e->enablePriorities('invalid data');
	}

	public function testSimpleAttachArray()
	{
		$listener = function($event, $data){};

		$e = new \Phalcon\Events\Manager();
		$this->assertEquals($e->getListeners('event.type'), array());
		$e->attach('event.type', $listener);
		$this->assertEquals($e->getListeners('event.type'), array($listener));
	}

	public function testSimpleAttachSpl()
	{
		$listener = function($event, $data){};

		$e = new \Phalcon\Events\Manager();
		$this->assertEquals($e->getListeners('type'), array());
		$e->enablePriorities(true);
		$e->attach('type', $listener);
		$this->assertTrue($e->getListeners('type') instanceof \SplPriorityQueue);
	}

	public function testAttachExceptionsPriority()
	{
		$this->setExpectedException('\Phalcon\Events\Exception');
		$e = new \Phalcon\Events\Manager();
		$e->attach('event', function($event, $data){}, 'prio');
	}

	public function testAttachExceptionsType()
	{
		$this->setExpectedException('\Phalcon\Events\Exception');
		$e = new \Phalcon\Events\Manager();
		$e->attach(array('not a string'), function($event, $data){});
	}

	public function testAttachExceptionsHandler()
	{
		$this->setExpectedException('\Phalcon\Events\Exception');
		$e = new \Phalcon\Events\Manager();
		$e->attach('type', 'not an object');
	}

	public function testCollectResponses()
	{
		$e = new \Phalcon\Events\Manager();
		$this->assertFalse($e->isCollecting());
		$e->collectResponses(true);
		$this->assertTrue($e->isCollecting());
	}

	public function testCollectException()
	{
		$this->setExpectedException('\Phalcon\Events\Exception');
		$e = new \Phalcon\Events\Manager();
		$e->collectResponses((object)'invalid data');
	}

	public function testGetResponses()
	{
		$e = new \Phalcon\Events\Manager();
		$this->assertEquals($e->getResponses(), null);
		$this->assertFalse($e->hasListeners('event:expressions'));
		$this->assertFalse($e->isCollecting());
		$e->attach('event:expressions', function($event, $data){return 'data';});
		$this->assertTrue($e->hasListeners('event:expressions'));
		$e->collectResponses(true);
		$this->assertTrue($e->isCollecting());
		$e->fire('event:expressions', (object)'source', 'payload', true);
		$this->assertEquals($e->getResponses(), array('data'));
	}

	public function testDetachAll()
	{
		$e = new \Phalcon\Events\Manager();
		$e->attach('event:event', function($event, $data){});
		$this->assertTrue($e->hasListeners('event:event'));
		$e->detachAll();
		$this->assertFalse($e->hasListeners('event:event'));

		$e->attach('event2:event', function($event, $data){});
		$e->attach('event:event', function($event, $data){});
		$this->assertTrue($e->hasListeners('event2:event'));
		$this->assertEquals(count($e->getListeners('event:event')), 1);
		$e->detachAll('event:event');
		$this->assertTrue($e->hasListeners('event2:event'));
		$this->assertEquals(count($e->getListeners('event:event')), 0);
		$this->assertEquals(count($e->getListeners('event2:event')), 1);
	}
}