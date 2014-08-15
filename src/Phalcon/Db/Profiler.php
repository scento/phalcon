<?php
/**
 * Profiler
 *
 * @author Andres Gutierrez <andres@phalconphp.com>
 * @author Eduar Carvajal <eduar@phalconphp.com>
 * @author Wenzel Pünter <wenzel@phelix.me>
 * @version 1.2.6
 * @package Phalcon
*/
namespace Phalcon\Db;

use \Phalcon\Db\Profiler\Item;

/**
 * Phalcon\Db\Profiler
 *
 * Instances of Phalcon\Db can generate execution profiles
 * on SQL statements sent to the relational database. Profiled
 * information includes execution time in miliseconds.
 * This helps you to identify bottlenecks in your applications.
 *
 *<code>
 *
 *	$profiler = new Phalcon\Db\Profiler();
 *
 *	//Set the connection profiler
 *	$connection->setProfiler($profiler);
 *
 *	$sql = "SELECT buyer_name, quantity, product_name
 *	FROM buyers LEFT JOIN products ON
 *	buyers.pid=products.id";
 *
 *	//Execute a SQL statement
 *	$connection->query($sql);
 *
 *	//Get the last profile in the profiler
 *	$profile = $profiler->getLastProfile();
 *
 *	echo "SQL Statement: ", $profile->getSQLStatement(), "\n";
 *	echo "Start Time: ", $profile->getInitialTime(), "\n";
 *	echo "Final Time: ", $profile->getFinalTime(), "\n";
 *	echo "Total Elapsed Time: ", $profile->getTotalElapsedSeconds(), "\n";
 *
 *</code>
 *
 * @see https://github.com/phalcon/cphalcon/blob/1.2.6/ext/db/profiler.c
 */
class Profiler
{
	/**
	 * All Profiles
	 * 
	 * @var null|array
	 * @access protected
	*/
	protected $_allProfiles;

	/**
	 * Active Profile
	 * 
	 * @var null|\Phalcon\Db\Profiler\Item
	 * @access protected
	*/
	protected $_activeProfile;

	/**
	 * Total Seconds
	 * 
	 * @var int
	 * @access protected
	*/
	protected $_totalSeconds = 0;

	/**
	 * Starts the profile of a SQL sentence
	 *
	 * @param string $sqlStatement
	 * @return \Phalcon\Db\Profiler
	 */
	public function startProfile($sqlStatement)
	{
		$activeProfile = new Item();
		$activeProfile->setSqlStatement($sqlStatement);
		$activeProfile->setInitialTime(microtime(true));
		if(method_exists($this, 'beforeStartProfile') === true) {
			$this->beforeStartProfile($activeProfile);
		}

		$this->_activeProfile = $activeProfile;

		return $this;
	}

	/**
	 * Stops the active profile
	 *
	 * @return \Phalcon\Db\Profiler
	 */
	public function stopProfile()
	{
		$finalTime = microtime(true);

		$activeProfile = $this->_activeProfile;
		$this->_activeProfile->setFinalTime($finalTime);
		$difference = $finalTime - $activeProfile->getInitialTime();
		$this->_totalSeconds = $this->_totalSeconds + $difference;

		if(is_array($this->_allProfiles) === false) {
			$this->_allProfiles = array();
		}
		$this->_allProfiles[] = $activeProfile;

		if(method_exists($this, 'afterEndProfile') === true) {
			$this->afterEndProfile($activeProfile);
		}

		return $this;
	}

	/**
	 * Returns the total number of SQL statements processed
	 *
	 * @return integer
	 */
	public function getNumberTotalStatements()
	{
		if(is_array($this->_allProfiles) === true) {
			return count($this->_allProfiles);
		} else {
			return 0;
		}
	}

	/**
	 * Returns the total time in seconds spent by the profiles
	 *
	 * @return int
	 */
	public function getTotalElapsedSeconds()
	{
		return $this->_totalSeconds;
	}

	/**
	 * Returns all the processed profiles
	 *
	 * @return \Phalcon\Db\Profiler\Item[]|null
	 */
	public function getProfiles()
	{
		return $this->_allProfiles;
	}

	/**
	 * Resets the profiler, cleaning up all the profiles
	 *
	 * @return \Phalcon\Db\Profiler
	 */
	public function reset()
	{
		$this->_allProfiles = array();

		return $this;
	}

	/**
	 * Returns the last profile executed in the profiler
	 *
	 * @return \Phalcon\Db\Profiler\Item
	 */
	public function getLastProfile()
	{
		return $this->_activeProfile;
	}
}