<?php

class InvalidEventType extends \Phalcon\Events\Event
{
	public function getType()
	{
		return false;
	}
}