<?php
/**
 * Adapter
 *
 * @author Andres Gutierrez <andres@phalconphp.com>
 * @author Eduar Carvajal <eduar@phalconphp.com>
 * @author Wenzel Pünter <wenzel@phelix.me>
 * @version 1.2.6
 * @package Phalcon
*/
namespace Phalcon\Db;

use \Phalcon\Db\Exception,
	\Phalcon\Db\DialectInterface,
	\Phalcon\Db\Index,
	\Phalcon\Db\Reference,
	\Phalcon\Db\RawValue,
	\Phalcon\Events\EventsAwareInterface,
	\Phalcon\Events\ManagerInterface;

/**
 * Phalcon\Db\Adapter
 *
 * Base class for Phalcon\Db adapters
 * 
 * @see https://github.com/phalcon/cphalcon/blob/1.2.6/ext/db/adapter.c
 */
abstract class Adapter implements EventsAwareInterface
{
	/**
	 * Events Manager
	 * 
	 * @var null|\Phalcon\Events\ManagerInterface
	 * @access protected
	*/
	protected $_eventsManager;

	/**
	 * Descriptor
	 * 
	 * @var null|array
	 * @access protected
	*/
	protected $_descriptor;

	/**
	 * Dialect Type
	 * 
	 * @var null|string
	 * @access protected
	*/
	protected $_dialectType;

	/**
	 * Type
	 * 
	 * @var null
	 * @access protected
	*/
	protected $_type;

	/**
	 * Dialect
	 * 
	 * @var null|object
	 * @access protected
	*/
	protected $_dialect;

	/**
	 * Connection ID
	 * 
	 * @var null|int
	 * @access protected
	*/
	protected $_connectionId;

	/**
	 * SQL Statement
	 * 
	 * @var null|string
	 * @access protected
	*/
	protected $_sqlStatement;

	/**
	 * SQL Variables
	 * 
	 * @var null|array
	 * @access protected
	*/
	protected $_sqlVariables;

	/**
	 * SQL Bind Types
	 * 
	 * @var null|array
	 * @access protected
	*/
	protected $_sqlBindTypes;

	/**
	 * Transaction Level
	 * 
	 * @var int
	 * @access protected
	*/
	protected $_transactionLevel = 0;

	/**
	 * Transactions With Savepoints
	 * 
	 * @var boolean
	 * @access protected
	 * @note updated data type
	*/
	protected $_transactionsWithSavepoints = false;

	/**
	 * Connection Consecutive
	 * 
	 * @var int
	 * @access protected
	*/
	protected static $_connectionConsecutive = 0;

	/**
	 * \Phalcon\Db\Adapter constructor
	 *
	 * @param array $descriptor
	 * @throws Exception
	 */
	protected function __construct($descriptor)
	{
		if(is_array($descriptor) === false) {
			throw new Exception('Invalid parameter type.');
		}

		//Every new connection created obtain a consecutive number from the static
		//property self::$_connectionConsecutive
		$this->_connectionId = self::$_connectionConsecutive;
		self::$_connectionConsecutive = $this->_connectionId + 1;

		//Dialect class can override the default dialect
		//@note no interface validation
		if(isset($descriptor['dialectClass']) === false) {
			$dialectClass = 'Phalcon\\Db\\Dialect\\'.$this->_dialectType;
		} else {
			$dialectClass = $descriptor['dialectClass'];
		}

		//Create the instance only if the dialect is a string
		if(is_string($dialectClass) === true) {
			$dialectObject = new $dialectClass();
			$this->_dialect = $dialectObject;
		}

		//@note what happens when $descriptor does not contain a dialect object/name
		//and $this->_dialectType is not set?!

		$this->_descriptor = $descriptor;
	}

	/**
	 * Sets the event manager
	 *
	 * @param \Phalcon\Events\ManagerInterface $eventsManager
	 * @throws Exception
	 */
	public function setEventsManager($eventsManager)
	{
		if(is_object($eventsManager) === false ||
			$eventsManager instanceof ManagerInterface === false) {
			throw new Exception('Invalid parameter type.');
		}

		$this->_eventsManager = $eventsManager;
	}

	/**
	 * Returns the internal event manager
	 *
	 * @return \Phalcon\Events\ManagerInterface|null
	 */
	public function getEventsManager()
	{
		return $this->_eventsManager;
	}

	/**
	 * Sets the dialect used to produce the SQL
	 *
	 * @param \Phalcon\Db\DialectInterface $dialect
	 */
	public function setDialect($dialect)
	{
		if(is_object($dialect) === false ||
			$dialect instanceof DialectInterface === false) {
			throw new Exception('Invalid parameter type.');
		}

		$this->_dialect = $dialect;
	}

	/**
	 * Returns internal dialect instance
	 *
	 * @return \Phalcon\Db\DialectInterface|null
	 */
	public function getDialect()
	{
		return $this->_dialect;
	}

	/**
	 * Returns the first row in a SQL query result
	 *
	 *<code>
	 *	//Getting first robot
	 *	$robot = $connection->fetchOne("SELECT * FROM robots");
	 *	print_r($robot);
	 *
	 *	//Getting first robot with associative indexes only
	 *	$robot = $connection->fetchOne("SELECT * FROM robots", \Phalcon\Db::FETCH_ASSOC);
	 *	print_r($robot);
	 *</code>
	 *
	 * @param string $sqlQuery
	 * @param int|null $fetchMode
	 * @param array|null $bindParams
	 * @param array|null $bindTypes
	 * @return array
	 * @throws Exception
	 */
	public function fetchOne($sqlQuery, $fetchMode = null, $bindParams = null, $bindTypes = null)
	{
		if(is_string($sqlQuery) === false ||
			(is_int($fetchMode) === false &&
				is_null($fetchMode) === false) ||
			(is_array($bindParams) === false &&
				is_null($bindParams) === false) ||
			(is_array($bindTypes) === false &&
				is_null($bindTypes) === false)) {
			throw new Exception('Invalid parameter type.');
		}

		$result = $this->query($sqlQuery, $bindParams, $bindTypes);
		if(is_object($result) === true) {
			if(is_null($fetchMode) === false) {
				$result->setFetchMode($fetchMode);
			}

			return $result->fetch();
		}

		return array();
	}

	/**
	 * Dumps the complete result of a query into an array
	 *
	 *<code>
	 *	//Getting all robots with associative indexes only
	 *	$robots = $connection->fetchAll("SELECT * FROM robots", \Phalcon\Db::FETCH_ASSOC);
	 *	foreach ($robots as $robot) {
	 *		print_r($robot);
	 *	}
	 *
	 *  //Getting all robots that contains word "robot" withing the name
	 *  $robots = $connection->fetchAll("SELECT * FROM robots WHERE name LIKE :name",
	 *		Phalcon\Db::FETCH_ASSOC,
	 *		array('name' => '%robot%')
	 *  );
	 *	foreach($robots as $robot){
	 *		print_r($robot);
	 *	}
	 *</code>
	 *
	 * @param string $sqlQuery
	 * @param int|null $fetchMode
	 * @param array|null $bindParams
	 * @param array|null $bindTypes
	 * @return array
	 * @throws Exception
	 */
	public function fetchAll($sqlQuery, $fetchMode = null, $bindParams = null, $bindTypes = null)
	{
		if(is_string($sqlQuery) === false ||
			(is_int($fetchMode) === false &&
				is_null($fetchMode) === false) ||
			(is_array($bindParams) === false &&
				is_null($bindParams) === false) ||
			(is_array($bindTypes) === false &&
				is_null($bindTypes) === false)) {
			throw new Exception('Invalid parameter type.');
		}

		$results = array();
		$result = $this->query($sqlQuery, $bindParams, $bindTypes);
		if(is_object($result) === true) {
			if(is_null($fetchMode) === false) {
				$result->setFetchMode($fetchMode);
			}

			$row = $result->fetch();
			while(isset($row)) {
				$results[] = $row;
				$row = $result->fetch();
			}
		}

		return $results;
	}

	/**
	 * Inserts data into a table using custom RBDM SQL syntax
	 *
	 * <code>
	 * //Inserting a new robot
	 * $success = $connection->insert(
	 *     "robots",
	 *     array("Astro Boy", 1952),
	 *     array("name", "year")
	 * );
	 *
	 * //Next SQL sentence is sent to the database system
	 * INSERT INTO `robots` (`name`, `year`) VALUES ("Astro boy", 1952);
	 * </code>
	 *
	 * @param string $table
	 * @param array $values
	 * @param array|null $fields
	 * @param array|null $dataTypes
	 * @return boolean
	 * @throws Exception
	 */
	public function insert($table, $values, $fields = null, $dataTypes = null)
	{
		if(is_string($table) === false ||
			(is_array($fields) === false &&
				is_null($fields) === false) ||
			(is_array($dataTypes) === false &&
				is_null($dataTypes) === false)) {
			throw new Exception('Invalid parameter type.');
		}

		if(is_array($values) === false) {
			throw new Exception("The second parameter for insert isn't an Array");
		}

		//A valid array with elements is required
		if(empty($values) === false) {
			throw new Exception('Unable to insert into '.$table.' without data');
		}

		$placeholders = array();
		$insertValues = array();

		if(is_array($dataTypes) === true) { //@note this seems to be wrong considering the usage
			$bindDataTypes = array();
		} else {
			$bindDataTypes = $dataTypes;
		}

		//Objects are casted using __toString, null values are converted to string 'null',
		//everything else is passed as '?'
		foreach($values as $position => $value) {
			if(is_object($value) === true) {
				$placeholders[] = strval($value);
			} elseif(is_null($value) === true) {
				$placeholders[] = 'null';
			} else {
				$placeholders[] = '?';
				$insertValues[] = $value;
				if(is_array($dataTypes) === true) {
					if(isset($dataTypes[$position]) === false) {
						throw new Exception('Incomplete number of bind types');
					}

					$bindDataTypes[] = $dataTypes[$position];
				}
			}
		}

		if(isset($GLOBALS['_PHALCON_DB_ESCAPE_IDENTIFIERS']) === true &&
			$GLOBALS['_PHALCON_DB_ESCAPE_IDENTIFIERS'] === true) {
			$escapedTable = $this->escapeIdentifier($table);
		} else {
			$escapedTable = $table;
		}

		//Build the final SQL INSERT statement
		$joinedValues = implode(', ', $placeholders);

		if(is_array($fields) === true) {
			if(isset($GLOBALS['_PHALCON_DB_ESCAPE_IDENTIFIERS']) === true &&
				$GLOBALS['_PHALCON_DB_ESCAPE_IDENTIFIERS'] === true) {
				$escapedFields = array();
				foreach($fields as $field) {
					$escapedFields[] = $this->escapeIdentifier($field);
				}
			} else {
				$escapedFields = $fields;
			}

			$joinedFields = implode(', ', $escapedFields);
			$insertSql = 'INSERT INTO '.$escapedTable.' ('.$joinedFields.') VALUES ('.$joinedValues.')';
		} else {
			$insertSql = 'INSERT INTO '.$escapedTable.' VALUES ('.$joinedValues.')';
		}

		//Perform the execution via PDO::execute
		return $this->execute($insertSql, $insertValues, $bindDataTypes);
	}

	/**
	 * Updates data on a table using custom RBDM SQL syntax
	 *
	 * <code>
	 * //Updating existing robot
	 * $success = $connection->update(
	 *     "robots",
	 *     array("name"),
	 *     array("New Astro Boy"),
	 *     "id = 101"
	 * );
	 *
	 * //Next SQL sentence is sent to the database system
	 * UPDATE `robots` SET `name` = "Astro boy" WHERE id = 101
	 * </code>
	 *
	 * @param string $table
	 * @param array $fields
	 * @param array $values
	 * @param string|null $whereCondition
	 * @param array|null $dataTypes
	 * @return boolean
	 * @throws Exception
	 */
	public function update($table, $fields, $values, $whereCondition = null, $dataTypes = null)
	{
		if(is_string($table) === false ||
			is_array($fields) === false ||
			is_array($values) === false ||
			(is_string($whereCondition) === false &&
				is_null($whereCondition) === false) ||
			(is_array($dataTypes) === false &&
				is_null($dataTypes) === false)) {
			throw new Exception('Invalid parameter type.');
		}

		$placeholders = array();
		$updateValues = array();

		if(is_array($dataTypes) === true) { //@note this seems to be wrong considering the usage
			$bindDataTypes = array();
		} else {
			$bindDataTypes = $dataTypes;
		}

		//Objects are casted using __toString, null values are converted to string 'null',
		//everything else is passed as '?'
		foreach($values as $position => $value) {
			if(isset($fields[$position]) === false) {
				throw new Exception('The number of values in the update is not the same as fields');
			}

			$field = $fields[$position];

			if(isset($GLOBALS['_PHALCON_DB_ESCAPE_IDENTIFIERS']) === true &&
				$GLOBALS['_PHALCON_DB_ESCAPE_IDENTIFIERS'] === true) {
				$field = $this->escapeIdentifier($field);
			}

			if(is_object($value) === true) {
				$placeholders[] = $field.' = '.$value;
			} elseif(is_null($value) === true) {
				$placeholders[] = $field.' = null';
			} else {
				$updateValues[] = $value;
				$placeholders[] = $field.' = ?';
				if(is_array($dataTypes) === true) {
					if(isset($dataTypes[$position]) === false) {
						throw new Exception('Incomplete number of bind types');
					}

					$bindDataTypes[] = $dataTypes[$position];
				}
			}
		}

		if(isset($GLOBALS['_PHALCON_DB_ESCAPE_IDENTIFIERS']) === true &&
			$GLOBALS['_PHALCON_DB_ESCAPE_IDENTIFIERS'] === true) {
			$table = $this->escapeIdentifier($table);
		}

		$setClause = implode(', ', $placeholders);
		if(is_null($whereCondition) === false) {
			$updateSql = 'UPDATE '.$table.' SET '.$setClause.' WHERE ';

			//String conditions are simply appended to the SQL
			if(is_string($whereCondition) === true) {
				$updateSql .= $whereCondition;
			} else {
				//Array conditions may have bound params and bound types
				if(is_array($whereCondition) === false) {
					throw new Exception('Invalid WHERE clause conditions');
				}

				//If an index 'conditions' is present it contains string where conditions that are
				//appended to the UPDATE sql
				if(isset($whereCondition['conditions']) === true) {
					$updateSql .= $whereCondition['conditions'];
				}

				//Bound parameters are arbitrary values that are passed by separate
				if(isset($whereCondition['bind']) === true) {
					$updateValues = array_merge($updateValues, $whereCondition['bind']);
				}

				//Bind types is how the bound parameters must be casted before be sent to the
				//database system
				if(isset($whereCondition['bindTypes']) === true) {
					$bindDataTypes = array_merge($bindDataTypes, $whereCondition['bindTypes']);
				}
			}
		} else {
			$updateSql = 'UPDATE '.$table.' SET '.$setClause;
		}

		//Perform the update via PDO::execute
		return $this->execute($updateSql, $updateValues, $bindDataTypes);
	}

	/**
	 * Deletes data from a table using custom RBDM SQL syntax
	 *
	 * <code>
	 * //Deleting existing robot
	 * $success = $connection->delete(
	 *     "robots",
	 *     "id = 101"
	 * );
	 *
	 * //Next SQL sentence is generated
	 * DELETE FROM `robots` WHERE `id` = 101
	 * </code>
	 *
	 * @param string $table
	 * @param string|null $whereCondition
	 * @param array|null $placeholders
	 * @param array|null $dataTypes
	 * @return boolean
	 * @throws Exception
	 */
	public function delete($table, $whereCondition = null, $placeholders = null, $dataTypes = null)
	{
		if(is_string($table) === false ||
			(is_string($whereCondition) === false &&
				is_null($whereCondition) === false) ||
			(is_array($placeholders) === false &&
				is_null($placeholders) === false) ||
			(is_array($dataTypes) === false &&
				is_null($dataTypes) === false)) {
			throw new Exception('Invalid parameter type.');
		}

		if(isset($GLOBALS['_PHALCON_DB_ESCAPE_IDENTIFIERS']) === true &&
			$GLOBALS['_PHALCON_DB_ESCAPE_IDENTIFIERS'] === true) {
			$table = $this->escapeIdentifier($table);
		}

		//Perform the update via PDO::execute
		return $this->execute(
			'DELETE FROM '.$table.(empty($whereCondition) === false ? ' WHERE '.$whereCondition : ''),
			$placeholders, $dataTypes);
	}

	/**
	 * Gets a list of columns
	 *
	 * @param array $columnList
	 * @return string
	 */
	public function getColumnList($columnList)
	{
		return $this->_dialect->getColumnList($columnList);
	}

	/**
	 * Appends a LIMIT clause to $sqlQuery argument
	 *
	 * <code>
	 * 	echo $connection->limit("SELECT * FROM robots", 5);
	 * </code>
	 *
	 * @param string $sqlQuery
	 * @param int $number
	 * @return string
	 */
	public function limit($sqlQuery, $number)
	{
		return $this->_dialect->limit($sqlQuery, $number);
	}

	/**
	 * Generates SQL checking for the existence of a schema.table
	 *
	 * <code>
	 * 	var_dump($connection->tableExists("blog", "posts"));
	 * </code>
	 *
	 * @param string $tableName
	 * @param string|null $schemaName
	 * @return string
	 */
	public function tableExists($tableName, $schemaName = null)
	{
		$sql = $this->_dialect->tableExists($tableName, $schemaName);
		$fetchOne = $this->fetchOne($sql, 3);
		return $fetchOne[0];
	}

	/**
	 * Generates SQL checking for the existence of a schema.view
	 *
	 *<code>
	 * var_dump($connection->viewExists("active_users", "posts"));
	 *</code>
	 *
	 * @param string $viewName
	 * @param string|null $schemaName
	 * @return string
	 */
	public function viewExists($viewName, $schemaName = null)
	{
		$sql = $this->_dialect->viewExists($viewName, $schemaName);
		$fetchOne = $this->fetchOne($sql, 3);
		return $fetchOne[0];
	}

	/**
	 * Returns a SQL modified with a FOR UPDATE clause
	 *
	 * @param string $sqlQuery
	 * @return string
	 */
	public function forUpdate($sqlQuery)
	{
		return $this->_dialect->forUpdate($sqlQuery);
	}

	/**
	 * Returns a SQL modified with a LOCK IN SHARE MODE clause
	 *
	 * @param string $sqlQuery
	 * @return string
	 */
	public function sharedLock($sqlQuery)
	{
		return $this->_dialect->sharedLock($sqlQuery);
	}

	/**
	 * Creates a table
	 *
	 * @param string $tableName
	 * @param string $schemaName
	 * @param array $definition
	 * @return boolean
	 * @throws Exception
	 */
	public function createTable($tableName, $schemaName, $definition)
	{
		if(is_array($definition) === false) {
			throw new Exception("Invalid definition to create the table '".$tableName."'");
		}

		if(isset($definition['columns']) === false ||
			empty($definition['columns']) === true) {
			throw new Exception('The table must contain at least one column');
		}

		return $this->execute($this->_dialect->createTable($tableName, $schemaName, $definition));
	}

	/**
	 * Drops a table from a schema/database
	 *
	 * @param string $tableName
	 * @param string|null $schemaName
	 * @param boolean|null $ifExists
	 * @return boolean
	 * @throws Exception
	 */
	public function dropTable($tableName, $schemaName = null, $ifExists = null)
	{
		if(is_null($ifExists) === true) {
			$ifExists = true;
		} elseif(is_bool($ifExists) === false) {
			throw new Exception('Invalid parameter type.');
		}

		return $this->execute($this->_dialect->dropTable($tableName, $schemaName, $ifExists));
	}

	/**
	 * Creates a view
	 *
	 * @param string $tableName
	 * @param array $definition
	 * @param string|null $schemaName
	 * @return boolean
	 * @throws Exception
	 */
	public function createView($viewName, $definition, $schemaName = null)
	{
		if(is_array($definition) === false) {
			throw new Exception("Invalid definition to create the view '".$viewName."'");
		}

		if(isset($definition['sql']) === false) {
			throw new Exception('The table must contain at least one column');
		}

		return $this->execute($this->_dialect->createView($viewName, $definition, $schemaName));
	}

	/**
	 * Drops a view
	 *
	 * @param string $viewName
	 * @param string|null $schemaName
	 * @param boolean|null $ifExists
	 * @return boolean
	 * @throws Exception
	 */
	public function dropView($viewName, $schemaName = null, $ifExists = null)
	{
		if(is_null($ifExists) === true) {
			$ifExists = true;
		} elseif(is_bool($ifExists) === false) {
			throw new Exception('Invalid parameter type.');
		}

		return $this->execute($this->_dialect->dropView($viewName, $schemaName, $ifExists));
	}

	/**
	 * Adds a column to a table
	 *
	 * @param string $tableName
	 * @param string $schemaName
	 * @param \Phalcon\Db\ColumnInterface $column
	 * @return boolean
	 */
	public function addColumn($tableName, $schemaName, $column)
	{
		return $this->execute($this->_dialect->addColumn($tableName, $schemaName, $column));
	}

	/**
	 * Modifies a table column based on a definition
	 *
	 * @param string $tableName
	 * @param string $schemaName
	 * @param \Phalcon\Db\ColumnInterface $column
	 * @return 	boolean
	 */
	public function modifyColumn($tableName, $schemaName, $column)
	{
		return $this->execute($this->_dialect->modifyColumn($tableName, $schemaName, $column));
	}

	/**
	 * Drops a column from a table
	 *
	 * @param string $tableName
	 * @param string $schemaName
	 * @param string $columnName
	 * @return 	boolean
	 */
	public function dropColumn($tableName, $schemaName, $columnName)
	{
		return $this->excute($this->_dialect->dropColumn($tableName, $schemaName, $columnName));
	}

	/**
	 * Adds an index to a table
	 *
	 * @param string $tableName
	 * @param string $schemaName
	 * @param \Phalcon\Db\IndexInterface $index
	 * @return 	boolean
	 */
	public function addIndex($tableName, $schemaName, $index)
	{
		return $this->exceute($this->_dialect->addIndex($tableName, $schemaName, $index));
	}

	/**
	 * Drop an index from a table
	 *
	 * @param string $tableName
	 * @param string $schemaName
	 * @param string $indexName
	 * @return boolean
	 */
	public function dropIndex($tableName, $schemaName, $indexName)
	{
		return $this->execute($this->_dialect->dropIndex($tableName, $schemaName, $indexName));
	}

	/**
	 * Adds a primary key to a table
	 *
	 * @param string $tableName
	 * @param string $schemaName
	 * @param \Phalcon\Db\IndexInterface $index
	 * @return boolean
	 */
	public function addPrimaryKey($tableName, $schemaName, $index)
	{
		return $this->execute($this->_dialect->addPrimaryKey($tableName, $schemaName, $index));
	}

	/**
	 * Drops a table's primary key
	 *
	 * @param string $tableName
	 * @param string $schemaName
	 * @return boolean
	 */
	public function dropPrimaryKey($tableName, $schemaName)
	{
		return $this->execute($this->_dialect->dropPrimaryKey($tableName, $schemaName));
	}

	/**
	 * Adds a foreign key to a table
	 *
	 * @param string $tableName
	 * @param string $schemaName
	 * @param \Phalcon\Db\ReferenceInterface $reference
	 * @return boolean true
	 */
	public function addForeignKey($tableName, $schemaName, $reference)
	{
		return $this->execute($this->_dialect->addForeignKey($tableName, $schemaName, $reference));
	}

	/**
	 * Drops a foreign key from a table
	 *
	 * @param string $tableName
	 * @param string $schemaName
	 * @param string $referenceName
	 * @return boolean true
	 */
	public function dropForeignKey($tableName, $schemaName, $referenceName)
	{
		return $this->execute($this->_dialect->dropForeignKey($tableName, $schemaName, $referenceName));
	}

	/**
	 * Returns the SQL column definition from a column
	 *
	 * @param \Phalcon\Db\ColumnInterface $column
	 * @return string
	 */
	public function getColumnDefinition($column)
	{
		return $this->_dialect->getColumnDefinition($column);
	}

	/**
	 * List all tables on a database
	 *
	 *<code>
	 * 	print_r($connection->listTables("blog"));
	 *</code>
	 *
	 * @param string $schemaName
	 * @return array
	 */
	public function listTables($schemaName = null)
	{
		//Get the SQL to list the tables
		$sql = $this->_dialect->listTables($schemaName);

		//Execute the SQL returning the tables
		$table = $this->fetchAll($sql, 3);

		$allTables = array();
		foreach($tables as $table) {
			$allTables[] = $table[0];
		}

		return $allTables;
	}

	/**
	 * List all views on a database
	 *
	 *<code>
	 *	print_r($connection->listViews("blog")); ?>
	 *</code>
	 *
	 * @param string|null $schemaName
	 * @return array
	 */
	public function listViews($schemaName = null)
	{
		//Get the SQL to list the views
		$sql = $dialect->listViews($schemaName);

		//Execute the SQL returning the views
		$views = $this->fetchAll($sql, 3);

		$allViews = array();
		foreach($views as $view) {
			$allViews[] = $view[0];
		}

		return $allViews;
	}

	/**
	 * Lists table indexes
	 *
	 *<code>
	 *	print_r($connection->describeIndexes('robots_parts'));
	 *</code>
	 *
	 * @param string $table
	 * @param string|null $schema
	 * @return \Phalcon\Db\Index[]
	 */
	public function describeIndexes($table, $schema = null)
	{
		//Get the SQL required to describe indexes from the dialect
		$sql = $this->_dialect->describeIndexes($table, $schema);

		//Cryptic Guide: 2: table, 3: from, 4: to
		$describe = $this->fetchAll($sql, 3);

		$indexes = array();
		foreach($describe as $index) {
			if(isset($indexes[$index[2]]) === false) {
				$indexes[$index[2]] = array();
			}

			$indexes[$index[2]][] = $index[4];
		}

		$indexObjects = array();
		foreach($indexes as $name => $indexColumns) {
			//Every index is abstracted using a Phalcon\Db\Index instance
			$indexObjects[$name] = new Index($name, $indexColumns);
		}

		return $indexObjects;
	}

	/**
	 * Lists table references
	 *
	 *<code>
	 * print_r($connection->describeReferences('robots_parts'));
	 *</code>
	 *
	 * @param string $table
	 * @param string|null $schema
	 * @return \Phalcon\Db\Reference[]
	 */
	public function describeReferences($table, $schema = null)
	{
		//Get the SQL required to describe the references from the dialect
		$sql = $this->_dialect->describeReferences($table, $schema);

		$references = array();

		//Execute the SQL
		$describe = $this->fetchAll($sql, 3);

		//Process references
		foreach($describe as $reference) {
			$constraintName = $reference[2];
			if(isset($references[$constraintName]) === false) {
				$references[$constraintName] = array('referencedSchema' => $reference[3], 
					'referencedTable' => $reference[4], 'columns' => array(), 'referencedColumns' => array());
			}

			$references[$constraintName]['columns'][] = $reference[1];
			$references[$constraintName]['referencedColumns'][] = $reference[5];
		}

		$referenceObjects = array();
		foreach($references as $name => $arrayReference) {
			$referenceObjects[$name] = new Reference($name, $arrayReference);
		}

		return $referenceObjects;
	}

	/**
	 * Gets creation options from a table
	 *
	 *<code>
	 * print_r($connection->tableOptions('robots'));
	 *</code>
	 *
	 * @param string $tableName
	 * @param string|null $schemaName
	 * @return array
	 */
	public function tableOptions($tableName, $schemaName = null)
	{
		$sql = $this->_dialect->tableOptions($tableName, $schemaName);
		if(isset($sql) === true) {
			$fetchAll = $this->fetchAll($sql, 1);
			return $fetchAll[0];
		}

		return array();
	}

	/**
	 * Creates a new savepoint
	 *
	 * @param string $name
	 * @return boolean
	 * @throws Exception
	 */
	public function createSavepoint($name)
	{
		if($this->_dialect->supportsSavepoints() != true) {
			throw new Exception('Savepoints are not supported by this database adapter.');
		}

		return $this->execute($this->_dialect->createSavepoint($name));
	}

	/**
	 * Releases given savepoint
	 *
	 * @param string $name
	 * @return boolean
	 * @throws Exception
	 */
	public function releaseSavepoint($name)
	{
		if($this->_dialect->supportsSavepoints() != true) {
			throw new Exception('Savepoints are not supported by this database adapter.');
		}

		if($this->_dialect->supportsReleaseSavepoints() != true) {
			return false;
		}

		return $this->execute($this->_dialect->releaseSavepoint($name));
	}

	/**
	 * Rollbacks given savepoint
	 *
	 * @param string $name
	 * @return boolean
	 * @throws Exception
	 */
	public function rollbackSavepoint($name)
	{
		if($this->_dialect->supportsSavepoints() != true) {
			throw new Exception('Savepoints are not supported by this database adapter.');
		}

		return $this->execute($this->_dialect->rollbackSavepoint($name));
	}

	/**
	 * Set if nested transactions should use savepoints
	 *
	 * @param boolean $nestedTransactionsWithSavepoints
	 * @return \Phalcon\Db\AdapterInterface
	 * @throws Exception
	 */
	public function setNestedTransactionsWithSavepoints($nestedTransactionsWithSavepoints)
	{
		if(is_bool($nestedTransactionsWithSavepoints) === false) {
			throw new Exception('Invalid parameter type.');
		}

		if($this->_transactionLevel > 0) {
			throw new Exception('Nested transaction with savepoints behavior cannot be changed while a transaction is open');
		}

		if($this->_dialect->supportsSavepoints() != true) {
			throw new Exception('Savepoints are not supported by this database adapter.');
		}

		$this->_transactionsWithSavepoints = $nestedTransactionsWithSavepoints;

		return $this;
	}

	/**
	 * Returns if nested transactions should use savepoints
	 *
	 * @return boolean|null
	 */
	public function isNestedTransactionsWithSavepoints()
	{
		return $this->_transactionsWithSavepoints;
	}

	/**
	 * Returns the savepoint name to use for nested transactions
	 *
	 * @return string
	 */
	public function getNestedTransactionSavepointName()
	{
		return 'PHALCON_SAVEPOINT_'.$this->_transactionLevel;
	}

	/**
	 * Returns the default identity value to be inserted in an identity column
	 *
	 *<code>
	 * //Inserting a new robot with a valid default value for the column 'id'
	 * $success = $connection->insert(
	 *     "robots",
	 *     array($connection->getDefaultIdValue(), "Astro Boy", 1952),
	 *     array("id", "name", "year")
	 * );
	 *</code>
	 *
	 * @return \Phalcon\Db\RawValue
	 */
	public function getDefaultIdValue()
	{
		return new RawValue('null');
	}

	/**
	 * Check whether the database system requires a sequence to produce auto-numeric values
	 *
	 * @return boolean
	 */
	public function supportSequences()
	{
		return false;
	}

	/**
	 * Check whether the database system requires an explicit value for identity columns
	 *
	 * @return boolean
	 */
	public function useExplicitIdValue()
	{
		return false;
	}

	/**
	 * Return descriptor used to connect to the active database
	 *
	 * @return array|null
	 */
	public function getDescriptor()
	{
		return $this->_descriptor;
	}

	/**
	 * Gets the active connection unique identifier
	 *
	 * @return string|null
	 */
	public function getConnectionId()
	{
		return (string)$this->_connectionId;
	}

	/**
	 * Active SQL statement in the object
	 *
	 * @return string|null
	 */
	public function getSQLStatement()
	{
		return $this->_sqlStatement;
	}

	/**
	 * Active SQL statement in the object without replace bound paramters
	 *
	 * @return string|null
	 */
	public function getRealSQLStatement()
	{
		return $this->_sqlStatement;
	}

	/**
	 * Active SQL statement in the object
	 *
	 * @return array|null
	 */
	public function getSQLVariables()
	{
		return $this->_sqlVariables;
	}

	/**
	 * Active SQL statement in the object
	 *
	 * @return array|null
	 */
	public function getSQLBindTypes()
	{
		return $this->_sqlBindTypes;
	}

	/**
	 * Returns type of database system the adapter is used for
	 *
	 * @return string|null
	 */
	public function getType()
	{
		return $this->_type;
	}

	/**
	 * Returns the name of the dialect used
	 *
	 * @return string|null
	 */
	public function getDialectType()
	{
		return $this->_dialectType;
	}
}