<?php
/**
 * Base Testcase
 *
 * @author Wenzel Pünter <wenzel@phelix.me>
*/
class BaseTest extends PHPUnit_Framework_TestCase
{
	/**
	 * Helper function for Exception testing
	 * 
	 * @param callable $trigger
	 * @param array $params
	 * @param string $exception
	*/
	protected function assertException($trigger, $params, $exception)
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
}