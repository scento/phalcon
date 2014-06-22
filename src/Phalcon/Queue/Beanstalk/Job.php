<?php
/**
 * Beanstalk Job
 *
 * @author Andres Gutierrez <andres@phalconphp.com>
 * @author Eduar Carvajal <eduar@phalconphp.com>
 * @author Wenzel Pünter <wenzel@phelix.me>
 * @version 1.2.6
 * @package Phalcon
*/
namespace Phalcon\Queue\Beanstalk;

use \Phalcon\Queue\Beanstalk,
	\Phalcon\Exception;

/**
 * Phalcon\Queue\Beanstalk\Job
 *
 * Represents a job in a beanstalk queue
 * 
 * @see https://github.com/phalcon/cphalcon/blob/master/ext/queue/beanstalk/job.c
 */
class Job
{
	/**
	 * Queue
	 * 
	 * @var null|\Phalcon\Queue\Beanstalk
	 * @access protected
	*/
	protected $_queue;

	/**
	 * Job ID
	 * 
	 * @var null|string
	 * @access protected
	*/
	protected $_id;

	/**
	 * Body
	 * 
	 * @var null|string
	 * @access protected
	*/
	protected $_body;

	/**
	 * \Phalcon\Queue\Beanstalk\Job
	 *
	 * @param \Phalcon\Queue\Beanstalk $queue
	 * @param string $id
	 * @param string $body
	 */
	public function __construct($queue, $id, $body)
	{
		if(is_object($queue) === false ||
			$queue instanceof Beanstalk === false) {
			throw new Exception('Invalid parameter type.');
		}

		$this->_queue = $queue;
		$this->_id = $id;
		$this->_body = $body;
	}

	/**
	 * Returns the job id
	 *
	 * @return string
	 */
	public function getId()
	{
		return $this->_id;
	}

	/**
	 * Returns the job body
	 *
	 * @return string
	 */
	public function getBody()
	{
		return $this->_body;
	}

	/**
	 * Removes a job from the server entirely
	 *
	 * @return boolean
	 */
	public function delete()
	{
		$this->_queue->write('delete '.$this->_id);

		$response = $this->_queue->readStatus();

		if($response[0] === 'DELETED') {
			return true;
		}

		return false;
	}
}