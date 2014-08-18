<?php
/**
 * PDO
 *
 * @author Andres Gutierrez <andres@phalconphp.com>
 * @author Eduar Carvajal <eduar@phalconphp.com>
 * @author Wenzel Pünter <wenzel@phelix.me>
 * @version 1.2.6
 * @package Phalcon
*/
namespace Phalcon\Db\Adapter;

use \Phalcon\Db\Adapter,
	\Phalcon\Db\Exception,
	\Phalcon\Db\Result\Pdo as PdoResult,
	\Phalcon\Events\EventsAwareInterface,
	\PDO as Service,
	\PDOStatement;

/**
 * Phalcon\Db\Adapter\Pdo
 *
 * Phalcon\Db\Adapter\Pdo is the Phalcon\Db that internally uses PDO to connect to a database
 *
 *<code>
 *	$connection = new Phalcon\Db\Adapter\Pdo\Mysql(array(
 *		'host' => '192.168.0.11',
 *		'username' => 'sigma',
 *		'password' => 'secret',
 *		'dbname' => 'blog',
 *		'port' => '3306'
 *	));
 *</code>
 * 
 * @see https://github.com/phalcon/cphalcon/blob/1.2.6/ext/db/adapter/pdo.c
 */
abstract class Pdo extends Adapter implements EventsAwareInterface
{
	/**
	 * PDO
	 * 
	 * @var null|\PDO
	 * @access protected
	*/
	protected $_pdo;

	/**
	 * Affected Rows
	 * 
	 * @var null|int
	 * @access protected
	*/
	protected $_affectedRows;

	/**
	 * Transaction Level
	 * 
	 * @var int
	 * @access protected
	*/
	protected $_transactionLevel = 0;

	/**
	 * Constructor for \Phalcon\Db\Adapter\Pdo
	 *
	 * @param array $descriptor
	 * @throws Exception
	 */
	public function __construct($descriptor)
	{
		if(is_array($descriptor) === false) {
			throw new Exception('The descriptor must be an array');
		}

		$this->connect($descriptor);
		parent::__construct($descriptor);
	}

	/**
	 * This method is automatically called in \Phalcon\Db\Adapter\Pdo constructor.
	 * Call it when you need to restore a database connection
	 *
	 *<code>
	 * //Make a connection
	 * $connection = new \Phalcon\Db\Adapter\Pdo\Mysql(array(
	 *  'host' => '192.168.0.11',
	 *  'username' => 'sigma',
	 *  'password' => 'secret',
	 *  'dbname' => 'blog',
	 * ));
	 *
	 * //Reconnect
	 * $connection->connect();
	 * </code>
	 *
	 * @param array|null $descriptor
	 * @return 	boolean
	 * @throws Exception
	 */
	public function connect($descriptor = null)
	{
		if(is_null($descriptor) === true) {
			$descriptor = $this->_descriptor;
		} elseif(is_array($descriptor) === false) {
			throw new Exception('Invalid parameter type.');
		}

		//Check for a unsername or use null as default
		if(isset($descriptor['username']) === true) {
			$username = $descriptor['username'];
			unset($descriptor['username']);
		} else {
			$username = null;
		}

		//Check for a password or use null as default
		if(isset($descriptor['password']) === true) {
			$password = $descriptor['password'];
			unset($descriptor['password']);
		} else {
			$password = null;
		}

		//Check if the developer has defined custom options or create one from scratch
		if(isset($descriptor['options']) === true) {
			$options = $descriptor['options'];
			unset($descriptor['options']);
		} else {
			$options = array();
		}

		//Check if the user has defined a custom dsn
		if(isset($descriptor['dns']) === false) {
			$dns_parts = array();

			foreach($descriptor as $key => $value) {
				$dns_parts[] = $key.'='.$value;
			}

			$dns_attributes = implode(';', $dns_parts);
		} else {
			$dns_attributes = $descriptor['dns'];
		}

		$dns = $this->_type.':'.$dns_attributes;

		//Default options
		$options[\PDO::ATTR_ERRMODE] = \PDO::ERRMODE_EXCEPTION;

		//Check if the connection must be persistent
		if(isset($descriptor['persistent']) === true &&
			$descriptor['persistent'] === true) {
			$options[\PDO::ATTR_PERSISTENT] = true;
		}

		//Create the connection using PDO
		$this->_pdo = new Service($dns, $username, $password, $options);
	}

	/**
	 * Returns a PDO prepared statement to be executed with 'executePrepared'
	 *
	 *<code>
	 * $statement = $db->prepare('SELECT * FROM robots WHERE name = :name');
	 * $result = $connection->executePrepared($statement, array('name' => 'Voltron'));
	 *</code>
	 *
	 * @param string $sqlStatement
	 * @return \PDOStatement
	 */
	public function prepare($sqlStatement)
	{
		//@note pdo can be null
		return $this->_pdo->prepare($sqlStatement);
	}

	/**
	 * Executes a prepared statement binding. This function uses integer indexes starting from zero
	 *
	 *<code>
	 * $statement = $db->prepare('SELECT * FROM robots WHERE name = :name');
	 * $result = $connection->executePrepared($statement, array('name' => 'Voltron'));
	 *</code>
	 *
	 * @param \PDOStatement $statement
	 * @param array $placeholders
	 * @param array|null $dataTypes
	 * @return \PDOStatement
	 * @throws Exception
	 */
	public function executePrepared($statement, $placeholders, $dataTypes)
	{
		if(is_object($statement) === false ||
			$statement instanceof PDOStatement === false ||
			is_array($placeholders) === false) {
			throw new Exception('Invalid parameter type.');
		}

		foreach ($placeholders as $wildcard => $value) {
			if(is_int($wildcard) === true) {
				$parameter = $wildcard+1;
			} elseif(is_string($wildcard) === true) {
				$parameter = $wildcard;
			} else {
				throw new Exception('Invalid bind parameter');
			}

			if(is_array($dataTypes) === true) {
				if(isset($dataTypes[$wildcard]) === true) {
					//The bind type is double so we try to get the double value
					$type = $dataTypes[$wildcard];
					if($type === 32) {
						$castValue = (int)$value;
						$type = 1024;
					} else {
						$castValue = $value;
					}

					//1024 is ignore the bind type
					if($type === 1024) {
						$statement->bindParam($parameter, $castValue);
					} else {
						$statement->bindParam($parameter, $castValue, $type);
					}
				} else {
					throw new Exception('Invalid bind type parameter');
				}
			} else {
				$statement->bindParam($parameter, $value);
			}
		}

		$statement->execute();
		return $statement;
	}

	/**
	 * Sends SQL statements to the database server returning the success state.
	 * Use this method only when the SQL statement sent to the server is returning rows
	 *
	 *<code>
	 *	//Querying data
	 *	$resultset = $connection->query("SELECT * FROM robots WHERE type='mechanical'");
	 *	$resultset = $connection->query("SELECT * FROM robots WHERE type=?", array("mechanical"));
	 *</code>
	 *
	 * @param string $sqlStatement
	 * @param array|null $bindParams
	 * @param array|null $bindTypes
	 * @return \Phalcon\Db\ResultInterface|boolean
	 * @throws Exception
	 */
	public function query($sqlStatement, $bindParams = null, $bindTypes = null)
	{
		if(is_string($sqlStatement) === false ||
			(is_array($bindParams) === false &&
				is_null($bindParams) === false) ||
			(is_array($bindTypes) === false &&
				is_null($bindTypes) === false)) {
			throw new Exception('Invalid parameter type.');
		}

		$eventsManager = $this->_eventsManager;

		//Execute the beforeQuery event if an EventsManager is available
		if(is_object($eventsManager) === true) {
			$this->_sqlStatement = $sqlStatement;
			$this->_sqlVariables = $bindParams;
			$this->_sqlBindTypes = $bindTypes;

			if($eventsManager->fire('db:beforeQuery', $this, $bindParams) === false) {
				return false;
			}
		}

		$pdo = $this->_pdo; //@note pdo can be null

		if(is_array($bindParams) === true) {
			$statement = $pdo->prepare($sqlStatement);
			if(is_object($statement) === true) {
				$statement = $this->executePrepared($statement, $bindTypes);
			}
		} else {
			$statement = $pdo->query($sqlStatement);
		}

		//Execute the afterQuery event if an EventsManager is available
		if(is_object($statement) === true) {
			if(is_object($eventsManager) === true) {
				$eventsManager->fire('db:afterQuery', $this, $bindParams);
			}

			return new PdoResult($this, $statement, $sqlStatement, $bindParams, $bindTypes);
		}

		return $statement;
	}

	/**
	 * Sends SQL statements to the database server returning the success state.
	 * Use this method only when the SQL statement sent to the server doesn't return any row
	 *
	 *<code>
	 *	//Inserting data
	 *	$success = $connection->execute("INSERT INTO robots VALUES (1, 'Astro Boy')");
	 *	$success = $connection->execute("INSERT INTO robots VALUES (?, ?)", array(1, 'Astro Boy'));
	 *</code>
	 *
	 * @param string $sqlStatement
	 * @param array|null $bindParams
	 * @param array|null $bindTypes
	 * @return boolean
	 * @throws Exception
	 */
	public function execute($sqlStatement, $bindParams = null, $bindTypes = null)
	{
		if(is_string($sqlStatement) === false ||
		(is_null($bindParams) === false &&
			is_array($bindParams) === false) ||
		(is_array($bindTypes) === false &&
			is_null($bindTypes) === false)) {
			throw new Exception('Invalid parameter type.');
		}

		//Execute the beforeQuery event if an EventsManager is available
		$eventsManager = $this->_eventsManager;
		if(is_object($eventsManager) === true) {
			$this->_sqlStatement = $sqlStatement;
			$this->_sqlVariables = $bindParams;
			$this->_sqlBindTypes = $bindTypes;

			if($eventsManager->fire('db:beforeQuery', $this, $bindParams) === false) {
				return false;
			}
		}

		//Initialize affected_rows to 0
		$affectedRows = 0;
		$pdo = $this->_pdo; //@note pdo can be null
		if(is_array($bindParams) === true) {
			$statement = $pdo->prepare();
			if(is_object($statement) === true) {
				$newStatement = $this->executePrepared($statement, $bindParams, $bindTypes);
				$affectedRows = $newStatement->rowCount();
			}
		} else {
			$affectedRows = $pdo->exec($sqlStatement);
		}

		//Execute the afterQuery event if an EventsManager is available
		if(is_int($affectedRows) === true) {
			$this->_affectedRows = $affectedRows;
			if(is_object($eventsManager) === true) {
				$eventsManager->fire('db:afterQuery', $this, $bindParams);
			}
		}

		return true;
	}

	/**
	 * Returns the number of affected rows by the lastest INSERT/UPDATE/DELETE executed in the database system
	 *
	 *<code>
	 *	$connection->execute("DELETE FROM robots");
	 *	echo $connection->affectedRows(), ' were deleted';
	 *</code>
	 *
	 * @return int
	 */
	public function affectedRows()
	{
		return $this->_affectedRows;
	}

	/**
	 * Closes the active connection returning success. \Phalcon automatically closes and destroys
	 * active connections when the request ends
	 *
	 * @return boolean
	 */
	public function close()
	{
		if(is_object($this->_pdo) === true) {
			$this->_pdo = null;
			return true;
		}

		return true;
	}

	/**
	 * Escapes a column/table/schema name
	 *
	 *<code>
	 *	$escapedTable = $connection->escapeIdentifier('robots');
	 *	$escapedTable = $connection->escapeIdentifier(array('store', 'robots'));
	 *</code>
	 *
	 * @param string|array $identifier
	 * @return string
	 * @throws Exception
	 */
	public function escapeIdentifier($identifier)
	{
		if(is_array($identifier) === true) {
			return '"'.$identifier[0].'"."'.$identifier[1].'"';
		} elseif(is_string($identifier) === true) {
			return '"'.$identifier.'"';
		} else {
			throw new Exception('Invalid parameter type.');
		}
	}

	/**
	 * Escapes a value to avoid SQL injections according to the active charset in the connection
	 *
	 *<code>
	 *	$escapedStr = $connection->escapeString('some dangerous value');
	 *</code>
	 *
	 * @param string $str
	 * @return string
	 */
	public function escapeString($str)
	{
		//@note pdo can be null
		return $this->_pdo->quote($str);
	}

	/**
	 * Converts bound parameters such as :name: or ?1 into PDO bind params ?
	 *
	 *<code>
	 * print_r($connection->convertBoundParams('SELECT * FROM robots WHERE name = :name:', array('Bender')));
	 *</code>
	 *
	 * @param string $sql
	 * @param array $params
	 * @return array
	 * @throws Exception
	 */
	public function convertBoundParams($sql, $params)
	{
		if(is_string($sql) === false ||
			is_array($params) === false) {
			throw new Exception('Invalid parameter type.');
		}

		$queryParams = array();
		$placeholders = array();
		$matches = null;

		if(preg_match_all("/\\?([0-9]+)|:([a-zA-Z0-9_]+):/", $sql, $matches, 2) === true) {
			foreach($matches as $placeMatch) {
				$numericPlace = $placMmatch[1];
				if(isset($params[$numericPlace]) === true) {
					$value = $params[$numericPlace];
				} else {
					if(isset($placeMatch[2]) === true) {
						$strPlace = $placeMatch[2];
						if(isset($params[$strPlace]) === true) {
							$value = $params[$strPlace];
						} else {
							throw new Exception("Matched parameter wasn't found in parameters list");
						}
					} else {
						throw new Exception("Matched parameter wasn't found in parameters list");
					}
				}

				$placeholders[] = $value;
			}

			$boundSql = preg_replace("/\\?([0-9]+)|:([a-zA-Z0-9_]+):/", '?', $sql);
		} else {
			$boundSql = $sql;
		}

		//Returns an array with the processed SQL and parameters
		return array('sql' => $boundSql, 'params' => $placeholders);
	}

	/**
	 * Returns the insert id for the auto_increment/serial column inserted in the lastest executed SQL statement
	 *
	 *<code>
	 * //Inserting a new robot
	 * $success = $connection->insert(
	 *     "robots",
	 *     array("Astro Boy", 1952),
	 *     array("name", "year")
	 * );
	 *
	 * //Getting the generated id
	 * $id = $connection->lastInsertId();
	 *</code>
	 *
	 * @param string|null $sequenceName
	 * @return int|boolean
	 */
	public function lastInsertId($sequenceName = null)
	{
		$pdo = $this->_pdo;
		if(is_object($pdo) === false) {
			return false;
		}

		return $pdo->lastInsertid($sequenceName);
	}

	/**
	 * Starts a transaction in the connection
	 *
	 * @param boolean|null $nesting
	 * @return boolean
	 * @throws Exception
	 */
	public function begin($nesting = null)
	{
		if(is_null($nesting) === true) {
			$nesting = true;
		} elseif(is_bool($nesting) === false) {
			throw new Exception('Invalid parameter type.');
		}

		$pdo = $this->_pdo;

		if(is_object($pdo) === false) {
			return false;
		}

		//Increase the transaction nesting level
		$this->_transactionLevel++;

		//Check the transaction nesting level
		$transactionLevel = $this->_transactionLevel;

		if($transactionLevel === 1) {
			$eventsManager = $this->_eventsManager;

			//Notify the events manager about the started transaction
			if(is_object($eventsManager) === true) {
				$eventsManager->fire('db:beginTransaction', $this);
			}

			return $pdo->beginTransaction();
		} else {
			if($transactionLevel == true &&
				$nesting === true) {
				$ntwSavepoint = $this->isNestedTransactionWithSavepoints();
				if($ntwSavepoint === true) {
					$eventsManager = $this->_eventsManager;
					$savepointName = $this->getNestedTransactionSavepointName();

					//Notify the eventsManager about the created savepoints
					if(is_object($eventsManager) === true) {
						$eventsManager->fire('db:createSavepoint', $this, $savepointName);
					}

					return $this->createSavepoint($savepointName);
				}
			}
		}

		return false;
	}

	/**
	 * Rollbacks the active transaction in the connection
	 *
	 * @param boolean|null $nesting
	 * @return boolean
	 * @throws Exception
	 */
	public function rollback($nesting = null)
	{
		if(is_null($nesting) === true) {
			$nesting = true;
		} elseif(is_bool($nesting) === false) {
			throw new Exception('Invalid parameter type.');
		}

		$pdo = $this->_pdo;
		if(is_object($pdo) === false) {
			return false;
		}

		//Check the transaction nesting level
		$transactionLevel = $this->_transactionLevel;
		if($transactionLevel === 0) {
			throw new Exception('There is no active transaction');
		}

		if($transactionLevel === 1) {
			$eventsManager = $this->_eventsManager;
			
			//Notify the eventsManager about the rollbacked transaction
			if(is_object($eventsManager) === true) {
				$eventsManager->fire('db:rollbackTransaction', $this);
			}

			//Reduce the transaction nesting level
			$this->_transactionLevel--;
			return $pdo->rollback();
		} else {
			if($transactionLevel == true &&
				$nesting === true) {
				if($this->isNestedTransactionWithSavepoints() === true) {
					$eventsManager = $this->_eventsManager;
					$savepointName = $this->getNestedTransactionSavepointName();

					//Notify the eventsManager about the rollbacked savepoint
					if(is_object($eventsManager) === true) {
						$eventsManager->fire('db:rollbackSavepoint', $this, $savepointName);
					}

					//Reduce the transaction nesting level
					$this->_transactionLevel--;
					return $this->rollbackSavepoint($savepointName);
				}
			}
		}

		//Reduce the transaction nesting level
		if($transactionLevel > 0) {
			$this->_transactionLevel--;
		}

		return false;
	}

	/**
	 * Commits the active transaction in the connection
	 *
	 * @param boolean|null $nesting
	 * @return boolean
	 * @throws Exception
	 */
	public function commit($nesting = null)
	{
		if(is_null($nesting) === true) {
			$nesting = true;
		} elseif(is_bool($nesting) === false) {
			throw new Exception('Invalid parameter type.');
		}

		$pdo = $this->_pdo;
		if(is_object($pdo) === false) {
			return false;
		}

		//Check the transaction nesting level
		$transactionLevel = $this->_transactionLevel;
		if($transactionLevel === 0) {
			throw new Exception('There is no active transaction');
		}

		if($transactionLevel === 1) {
			$eventsManager = $this->_eventsManager;

			//Notify the eventsManager about the commited transaction
			if(is_object($eventsManager) === true) {
				$eventsManager->fire('db:commitTransaction', $this);
			}

			//Reduce the transaction nesting level
			$this->_transactionLevel--;
			return $pdo->commit();
		} else {
			if($transactionLevel == true &&
				$nesting === true) {
				//Check if the current database system supports nesting transactions
				if($this->isNestedTransactionWithSavepoints() === true) {
					$eventsManager = $this->_eventsManager;
					$savepointName = $this->getNestedTransactionSavepointName();

					//Notify the eventsManager about the commited savepoint
					if(is_object($eventsManager) === true) {
						$eventsManager->fire('db:releaseSavepoint', $this, $savepointName);
					}

					//Reduce the transaction nesting level
					$this->_transactionLevel--;
					return $this->releaseSavepoint($savepointName);
				}
			}
		}

		if($transactionLevel > 0) {
			$this->_transactionLevel--;
		}

		return false;
	}

	/**
	 * Returns the current transaction nesting level
	 *
	 * @return int
	 */
	public function getTransactionLevel()
	{
		return $this->_transactionLevel;
	}

	/**
	 * Checks whether the connection is under a transaction
	 *
	 *<code>
	 *	$connection->begin();
	 *	var_dump($connection->isUnderTransaction()); //true
	 *</code>
	 *
	 * @return boolean
	 */
	public function isUnderTransaction()
	{
		$pdo = $this->_pdo;
		if(is_object($pdo) === true) {
			return $pdo->inTransaction();
		}

		return false;
	}

	/**
	 * Return internal PDO handler
	 *
	 * @return \PDO|null
	 */
	public function getInternalHandler()
	{
		return $this->_pdo;
	}
}