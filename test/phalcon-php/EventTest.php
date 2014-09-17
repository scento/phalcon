<?php
/**
 * Events Testsuite
 *
 * @author Wenzel Pünter <wenzel@phelix.me>
*/
class EventTest extends BaseTest
{
	public function testEvent()
	{
		$obj = (object)'scalardata';
		$event = new \Phalcon\Events\Event('type', $obj, array('data'), false);

		$this->assertEquals($event->getType(), 'type');
		$this->assertEquals($event->getSource(),  $obj);
		$this->assertEquals($event->getData(), array('data'));
		$this->assertFalse($event->getCancelable());
		$this->assertFalse($event->isStopped());
	}

	public function testEventTypeType()
	{
		$this->setExpectedException('\Phalcon\Events\Exception');

		$e = new \Phalcon\Events\Event(1, (object)'scalardata', array('data'));
	}

	public function testEventSourceType()
	{
		$this->setExpectedException('\Phalcon\Events\Exception');

		$e = new \Phalcon\Events\Event('type', 1, array('data'));
	}

	public function testEventCancelableType()
	{
		$this->setExpectedException('\Phalcon\Events\Exception');

		$e = new \Phalcon\Events\Event('type', (object)'scalardata', array('data'), 3);
	}

	public function testEventCancelableException()
	{
		$this->setExpectedException('\Phalcon\Events\Exception');

		$e = new \Phalcon\Events\Event('type', (object)'scalardata', array('data'), false);

		$this->assertFalse($e->getCancelable());

		$e->stop();
	}

	public function testEventStop()
	{
		$e = new \Phalcon\Events\Event('type', (object)'scalardata', array('data'), true);
		$this->assertFalse($e->isStopped());
		$e->stop();
		$this->assertTrue($e->isStopped());
	}

	public function testSetter()
	{
		$e = new \Phalcon\Events\Event('wrong', (object)'scalardata', array('of', 'params'));

		$this->assertEquals($e->getType(), 'wrong');
		$e->setType('type');
		$this->assertEquals($e->getType(), 'type');

		$this->assertEquals($e->getData(), array('of', 'params'));
		$e->setData('data');
		$this->assertEquals($e->getData(), 'data');

		$this->assertFalse($e->getCancelable());
		$e->setCancelable(true);
		$this->assertTrue($e->getCancelable());
	}

	public function testTypeSetterException()
	{
		$this->setExpectedException('\Phalcon\Events\Exception');
		$e = new \Phalcon\Events\Event('type', (object)'scalardata', 1);
		$e->setType(false);
	}

	public function testCancelableSetterException()
	{
		$this->setExpectedException('\Phalcon\Events\Exception');
		$e = new \Phalcon\Events\Event('type', (object)'scalardata', 'payload');
		$e->setCancelable('fals');
	}
}