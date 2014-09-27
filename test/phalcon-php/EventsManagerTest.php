<?php
/**
 * Events Manager Testsuite
 *
 * @author Wenzel Pünter <wenzel@phelix.me>
*/
class EventsManagerTest extends BaseTest
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

	public function testDetachAllException()
	{
		$this->setExpectedException('\Phalcon\Events\Exception');
		$e = new \Phalcon\Events\Manager();
		$e->detachAll((object)'invalid');
	}

	public function testDetachAllAlias()
	{
		$e = new \Phalcon\Events\Manager();
		$e->attach('event:event', function($event, $data){});
		$this->assertTrue($e->hasListeners('event:event'));
		$e->dettachAll();
		$this->assertFalse($e->hasListeners('event:event'));

		$e->attach('event2:event', function($event, $data){});
		$e->attach('event:event', function($event, $data){});
		$this->assertTrue($e->hasListeners('event2:event'));
		$this->assertEquals(count($e->getListeners('event:event')), 1);
		$e->dettachAll('event:event');
		$this->assertTrue($e->hasListeners('event2:event'));
		$this->assertEquals(count($e->getListeners('event:event')), 0);
		$this->assertEquals(count($e->getListeners('event2:event')), 1);
	}

	public function testFireQueueArray()
	{
		$e = new \Phalcon\Events\Manager();
		$e->collectResponses(true);
		$e->attach('event:event', function($event, $data){return 'payload';});
		$e->attach('event:event', function($event, $data){$event->stop();});
		$e->attach('event:event', function($event, $data){});
		$e->fire('event:event', (object)'source');
		$this->assertEquals($e->getResponses(), array('payload', null));
	}

	public function testFireQueuePriority()
	{
		$e = new \Phalcon\Events\Manager();
		$e->collectResponses(true);
		$e->enablePriorities(true);
		$e->attach('event:event', function($event, $data){return 'payload';}, 300);
		$e->attach('event:event', function($event, $data){$event->stop();}, 200);
		$e->attach('event:event', function($event, $data){}, 100);
		$e->fire('event:event', (object)'source');
		$this->assertEquals($e->getResponses(), array('payload', null));
	}

	public function testFireQueueExceptionQueue()
	{
		$this->setExpectedException('\Phalcon\Events\Exception');
		$e = new \Phalcon\Events\Manager();
		$e->fireQueue('invalid', (object)'event');
	}

	public function testFireQueueExceptionEvent()
	{
		$this->setExpectedException('\Phalcon\Events\Exception');
		$e = new \Phalcon\Events\Manager();
		$e->fireQueue(array('event'), 'invalid object');
	}

	public function testFireQueueInvalidObject()
	{
		$this->setExpectedException('\Phalcon\Events\Exception');
		$e = new \Phalcon\Events\Manager();
		$e->fireQueue(array('queue'), (object)'event');
	}

	public function testFireQueueInvalidEventNameType()
	{
		$e = new \Phalcon\Events\Manager();
		$this->setExpectedException('\Phalcon\Events\Exception');
		include_once(__DIR__.'/Events/InvalidEventType.php');
		$event = new InvalidEventType('type', (object)'random', array('data'), false);
		$e->fireQueue(array('queue'), $event);
	}

	public function testFireCalcelableException()
	{
		$this->setExpectedException('\Phalcon\Events\Exception');
		$e = new \Phalcon\Events\Manager();
		$e->fire('eventType', (object)'source', 'data', 'nobool');
	}

	public function testFireEventTypeException()
	{
		$this->setExpectedException('\Phalcon\Events\Exception');
		$e = new \Phalcon\Events\Manager();
		$e->fire(true, (object)'notanobject', 'data');
	}

	public function testFireNoEvents()
	{
		$e = new \Phalcon\Events\Manager();
		$this->assertTrue(is_null($e->fire('eventType', (object)'source', 'data')));
	}

	public function testFireInvalidEventType()
	{
		$this->setExpectedException('\Phalcon\Events\Exception');
		$e = new \Phalcon\Events\Manager();
		$e->attach('event', function($event, $data){return 'payload';});
		$e->fire('event', (object)'source');
	}

	public function testHasListenersType()
	{
		$this->setExpectedException('\Phalcon\Events\Exception');
		$e = new \Phalcon\Events\Manager();
		$e->hasListeners(false);
	}

	public function testFireInvalidSourceType()
	{
		$this->setExpectedException('\Phalcon\Events\Exception');
		$e = new \Phalcon\Events\Manager();
		$e->fire('type', 'source');
	}

	public function testGetListenersInvalidType()
	{
		$this->setExpectedException('\Phalcon\Events\Exception');
		$e = new \Phalcon\Events\Manager();
		$e->getListeners(false);
	}

	public function testFireQueueArrayObject()
	{
		include_once(__DIR__.'/Events/SampleEvent.php');
		$sample = new SampleEvent();
		$e = new \Phalcon\Events\Manager();
		$e->collectResponses(true);
		$e->attach('event:event', $sample);
		$e->fire('event:event', (object)'source');
		$this->assertEquals($e->getResponses(), array('status'));
	}

	public function testFireQueuePriorityObject()
	{
		include_once(__DIR__.'/Events/SampleEvent.php');
		$sample = new SampleEvent();
		$e = new \Phalcon\Events\Manager();
		$e->collectResponses(true);
		$e->enablePriorities(true);
		$e->attach('event:event', $sample, 300);
		$e->fire('event:event', (object)'source');
		$this->assertEquals($e->getResponses(), array('status'));
	}
}