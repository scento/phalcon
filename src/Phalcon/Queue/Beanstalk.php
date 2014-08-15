<?php
/**
 * Beanstalk Queue
 *
 * @author Andres Gutierrez <andres@phalconphp.com>
 * @author Eduar Carvajal <eduar@phalconphp.com>
 * @author Wenzel Pünter <wenzel@phelix.me>
 * @version 1.2.6
 * @package Phalcon
*/
namespace Phalcon\Queue;

use \Phalcon\Exception,
	\Phalcon\Queue\Beanstalk\Job;

/**
 * Phalcon\Queue\Beanstalk
 *
 * Class to access the beanstalk queue service.
 * Partially implements the protocol version 1.2
 *
 * @see http://www.igvita.com/2010/05/20/scalable-work-queues-with-beanstalk/
 * @see https://github.com/phalcon/cphalcon/blob/1.2.6/ext/queue/beanstalk.c
 */
class Beanstalk
{
	/**
	 * Connection
	 * 
	 * @var null|resource
	 * @access protected
	*/
	protected $_connection;

	/**
	 * Parameters
	 * 
	 * @var null|array
	 * @access protected
	*/
	protected $_parameters;

	/**
	 * \Phalcon\Queue\Beanstalk
	 *
	 * @param array|null $options
	 */
	public function __construct($options = null)
	{
		if(is_array($options) === false) {
			$options = array();
		}

		if(isset($options['host']) === false) {
			$options['host'] = '127.0.0.1';
		}

		if(isset($options['port']) === false) {
			$options['port'] = 11300;
		}

		$this->_parameters = $options;
	}

	/**
	 * Connect
	 * 
	 * @throws Exception
	 * @return resource
	*/
	public function connect()
	{
		if(is_resource($this->_connection) === true) {
			$this->disconnect();
		}

		//@note no $errno, $errstr handeling
		$connection = fsockopen($this->_parameters['host'], $this->_parameters['port']);

		if(is_resource($connection) === false) {
			throw new Exception("Can't connect to Beanstalk server");
		}

		//@note no exception handeling
		stream_set_timeout($connection, -1);

		$this->_connection = $connection;

		return $connection;
	}

	/**
	 * Inserts jobs into the queue
	 *
	 * @param string $data
	 * @param array|null $options
	 * @return string|boolean
	 * @throws Exception
	 */
	public function put($data, $options = null)
	{
		/* Type check */
		if(is_string($data) === false) {
			throw new Exception('Invalid parameter type.');
		}

		if(is_array($options) === false) {
			$options = array();
		}

		/* Set default values if required */
		if(isset($options['priority']) === false) {
			$options['priority'] = '100';
		}

		if(isset($options['delay']) === false) {
			$options['delay'] = '0';
		}

		if(isset($options['ttr']) === false) {
			$options['ttr'] = '86400';
		}

		/* Data is automatically serialized before be sent to the server */
		$serialized = serialize($data);
		$serializedLength = strlen($serialized);

		/* Create the command */
		$this->write('put '.$options['priority'].' '.$options['delay'].' '.
			$options['ttr'].' '.$serializedLength);
		$this->write($serialized);

		/* Response */
		$response = $this->readStatus();
		if($response[0] === 'INSERTED' || $response[0] === 'BURIED') {
			return $response[1];
		}

		return false;
	}

	/**
	 * Reserves a job in the queue
	 *
	 * @param null|boolean $timeout
	 * @return boolean|Phalcon\Queue\Beanstalk\Job
	 */
	public function reserve($timeout = null)
	{
		/* Type check */
		if(is_null($timeout) === true) {
			$timeout = false;
		} elseif(is_bool($timeout) === false) {
			throw new Exception('Invalid parameter type.');
		}

		/* Build command */
		if($timeout === true) {
			$command = 'reserve-with-timeout '.$timeout;
		} else {
			$command = 'reserve';
		}

		$this->write($command);

		$response = $this->readStatus();
		if($response[0] === 'RESERVED') {
			//@note there is no further verification

			//The job is in the first position
			//Next is the job length
			//The body is serialized
			$serializedBody = $this->read($response[2]);
			$body = unserialize($serializedBody);

			//Create a beanstalk job abstraction
			return new Job($this, $response[1], $body);
		}

		return false;
	}

	/**
	 * Change the active tube. By default the tube is 'default'
	 *
	 * @param string $tube
	 * @return string|boolean
	 * @throws Exception
	 */
	public function choose($tube)
	{
		if(is_string($tube) === false) {
			throw new Exception('Invalid parameter type.');
		}

		$this->write('use '.$tube);
		$response = $this->readStatus();

		if($response[0] === 'USING') {
			return $response[1];
		}

		return false;
	}

	/**
	 * Change the active tube. By default the tube is 'default'
	 *
	 * @param string $tube
	 * @return string|boolean
	 * @throws Exception
	 */
	public function watch($tube)
	{
		if(is_string($tube) === false) {
			throw new Exception('Invalid parameter type.');
		}

		$this->write('watch '.$tube);
		$response = $this->readStatus();

		if($response[0] === 'WATCHING') {
			return $response[1];
		}

		return false;
	}

	/**
	 * Inspect the next ready job.
	 *
	 * @return boolean|Phalcon\Queue\Beanstalk\Job
	 */
	public function peekReady()
	{
		$this->write('peek-ready');

		$response = $this->readStatus();

		if($response[0] === 'FOUND') {
			return new Job($this, $response[1], unserialize($this->read($response[2])));
		}

		return false;
	}

	/**
	 * Reads the latest status from the Beanstalkd server
	 *
	 * @return array|boolean
	 */
	protected function readStatus()
	{
		//@note explode() can return false!
		return explode(' ', $this->read());
	}

	/**
	 * Reads a packet from the socket. Prior to reading from the socket will
	 * check for availability of the connection.
	 *
	 * @param int|null $length Number of bytes to read.
	 * @return string|boolean Data or `false` on error.
	 */
	public function read($length = null)
	{
		/* Type check */
		if(is_null($length) === false && is_int($length) === false) {
			throw new Exception('Invalid parameter type.');
		}

		/* Connect if required */
		if(is_resource($this->_connection) === false) {
			$this->connnect();
			if(is_resource($this->_connection) === false) {
				return false;
			}
		}

		if(is_null($length) === false) {
			if(feof($this->_connection) === true) {
				return false;
			}

			$data = fread($this->_connection, $length + 2);
			$meta = stream_get_meta_data($this->_connection);
			if($meta['timed_out'] === true) {
				throw new Exception('Connection timed out');
			}

			$packet = rtrim($data, "\r\n");
		} else {
			$packet = fgets($this->_connection, 16384);
		}

		return $packet;
	}

	/**
	 * Writes data to the socket. Performs a connection if none is available
	 *
	 * @param string $data
	 * @return integer|boolean
	 * @throws Exception
	 */
	protected function write($data)
	{
		if(is_string($data) === false) {
			throw new Exception('Invalid parameter type.');
		}

		if(is_resource($this->_connection) === false) {
			$this->connect();
			if(is_resource($this->_connection) === false) {
				return false;
			}
		}

		$packet = $data."\r\n";
		return fwrite($this->_connection, $packet, strlen($packet));
	}

	/**
	 * Closes the connection to the beanstalk server.
	 *
	 * @return boolean
	 */
	public function disconnect()
	{
		if(is_resource($this->_connection) === false) {
			return false;
		}

		fclose($this->_connection);

		return true;
	}
}