<?php

class SampleEvent
{
	public function event($event, $source, $data) {
		$event->stop();
		return 'status';
	}
}