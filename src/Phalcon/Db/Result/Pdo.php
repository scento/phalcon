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
namespace Phalcon\Db\Result;

use \Phalcon\Db\Exception;
use \Phalcon\Db\AdapterInterface;
use \PDOStatement;

/**
 * Phalcon\Db\Result\Pdo
 *
 * Encapsulates the resultset internals
 *
 * <code>
 *  $result = $connection->query("SELECT * FROM robots ORDER BY name");
 *  $result->setFetchMode(Phalcon\Db::FETCH_NUM);
 *  while ($robot = $result->fetchArray()) {
 *      print_r($robot);
 *  }
 * </code>
 *
 * @see https://github.com/phalcon/cphalcon/blob/1.2.6/ext/db/result/pdo.c
 */
class Pdo
{
    /**
     * Connection
     *
     * @var null|\Phalcon\Db\AdapterInterface
     * @access protected
    */
    protected $_connection;

    /**
     * Result
     *
     * @var null
     * @access protected
    */
    protected $_result;

    /**
     * Fetch Model
     *
     * @var int
     * @access protected
    */
    protected $_fetchMode = 4;

    /**
     * PDO Statement
     *
     * @var null|\PDOStatement
     * @access protected
    */
    protected $_pdoStatement;

    /**
     * SQL Statement
     *
     * @var null|string
     * @access protected
    */
    protected $_sqlStatement;

    /**
     * Bind Params
     *
     * @var null|array
     * @access protected
    */
    protected $_bindParams;

    /**
     * Bind Types
     *
     * @var null|array
     * @access protected
    */
    protected $_bindTypes;

    /**
     * Row Count
     *
     * @var boolean|int
     * @access protected
    */
    protected $_rowCount = false;

    /**
     * Row Offset
     *
     * @var null|int
     * @access private
    */
    private $_rowOffset;

    /**
     * \Phalcon\Db\Result\Pdo constructor
     *
     * @param \Phalcon\Db\AdapterInterface $connection
     * @param \PDOStatement $result
     * @param string|null $sqlStatement
     * @param array|null $bindParams
     * @param array|null $bindTypes
     */
    public function __construct($connection, $result, $sqlStatement = null, $bindParams = null, $bindTypes = null)
    {
        if (is_object($connection) === false ||
            $connection instanceof AdapterInterface === false) {
            throw new Exception('Invalid parameter type.');
        }

        if (is_object($result) === false ||
            $result instanceof PDOStatement === false) {
            throw new Exception('Invalid PDOStatement supplied to Phalcon\\Db\\Result\\Pdo');
        }

        $this->_connection = $connection;
        $this->_pdoStatement = $result;

        if (is_string($sqlStatement) === true) {
            $this->_sqlStatement = $sqlStatement;
        } elseif (is_null($sqlStatement) === false) {
            throw new Exception('Invalid parameter type.');
        }

        if (is_array($bindParams) === true) {
            $this->_bindParams = $bindParams;
        } elseif (is_null($bindParams) === false) {
            throw new Exception('Invalid parameter type.');
        }

        if (is_array($bindTypes) === true) {
            $this->_bindTypes = $bindTypes;
        } elseif (is_null($bindTypes) === false) {
            throw new Exception('Invalid parameter type.');
        }
    }

    /**
     * Allows to executes the statement again. Some database systems don't support scrollable cursors,
     * So, as cursors are forward only, we need to execute the cursor again to fetch rows from the begining
     *
     * @return boolean
     */
    public function execute()
    {
        return $this->_pdoStatement->execute();
    }

    /**
     * Fetches an array/object of strings that corresponds to the fetched row, or FALSE if there are no more rows.
     * This method is affected by the active fetch flag set using \Phalcon\Db\Result\Pdo::setFetchMode
     *
     *<code>
     *  $result = $connection->query("SELECT * FROM robots ORDER BY name");
     *  $result->setFetchMode(Phalcon\Db::FETCH_OBJ);
     *  while ($robot = $result->fetch()) {
     *      echo $robot->name;
     *  }
     *</code>
     *
     * @return mixed
     */
    public function fetch()
    {
        if (is_null($this->_rowOffset) === true) {
            return $this->_pdoStatement->fetch();
        } else {
            return $this->_pdoStatement->fetch(
                $this->_fetchMode,
                \PDO::FETCH_ORI_ABS,
                $this->_rowOffset
            );
        }
    }

    /**
     * Returns an array of strings that corresponds to the fetched row, or FALSE if there are no more rows.
     * This method is affected by the active fetch flag set using \Phalcon\Db\Result\Pdo::setFetchMode
     *
     *<code>
     *  $result = $connection->query("SELECT * FROM robots ORDER BY name");
     *  $result->setFetchMode(Phalcon\Db::FETCH_NUM);
     *  while ($robot = $result->fetchArray()) {
     *      print_r($robot);
     *  }
     *</code>
     *
     * @return mixed
     */
    public function fetchArray()
    {
        return $this->_pdoStatement->fetch();
    }

    /**
     * Returns an array of arrays containing all the records in the result
     * This method is affected by the active fetch flag set using \Phalcon\Db\Result\Pdo::setFetchMode
     *
     *<code>
     *  $result = $connection->query("SELECT * FROM robots ORDER BY name");
     *  $robots = $result->fetchAll();
     *</code>
     *
     * @return array
     */
    public function fetchAll()
    {
        return $this->_pdoStatement->fetchAll();
    }

    /**
     * Gets number of rows returned by a resulset
     *
     *<code>
     *  $result = $connection->query("SELECT * FROM robots ORDER BY name");
     *  echo 'There are ', $result->numRows(), ' rows in the resulset';
     *</code>
     *
     * @return int
     */
    public function numRows()
    {
        $rowCount = $this->_rowCount;
        if ($rowCount === false) {
            switch($this->_connection->getType()) {
                case 'mysql':
                    $rowCount = $this->_pdoStatement->rowCount();
                    break;
                case 'pgsql':
                    $rowCount = $this->_pdoStatement->rowCount();
                    break;
            }

            if ($rowCount === false) {
                //SQLite/Oracle/SQLServer returns resultsets that to the client eyes (PDO) has an
                //arbitrary number of rows, so we need to perform an extra count to know that
                $sqlStatement = $this->_sqlStatement;

                //If the sql_statement starts with SELECT COUNT(*) we don't make the count
                if (strpos($sqlStatement, 'SELECT COUNT(*) ') !== 0) {
                    $bindParams = $this->_bindParams;
                    $bindTypes = $this->_bindTypes;
                    $matches = null;

                    if (preg_match("/^SELECT\\s+(.*)$/i", $sqlStatement, $matches) == true) {
                        $rowCount = $this->_connection->query(
                            "SELECT COUNT(*) \"numrows\" FROM (SELECT ".$matches[0].')',
                            $bindParams,
                            $bindTypes
                        )->fetch()->numRows();
                    }
                } else {
                    $rowCount = 1;
                }
            }

            //Update the value to avoid further calculations
            $this->_rowCount = $rowCount;
        }

        return $rowCount;
    }

    /**
     * Moves internal resulset cursor to another position letting us to fetch a certain row
     *
     *<code>
     *  $result = $connection->query("SELECT * FROM robots ORDER BY name");
     *  $result->dataSeek(2); // Move to third row on result
     *  $row = $result->fetch(); // Fetch third row
     *</code>
     *
     * @param int $number
     * @return null|false
     */
    public function dataSeek($number)
    {
        /* Validation */
        if (is_int($number) === false) {
            return;
        }

        $this->_rowOffset = $number;

        if ($this->_pdoStatement->setAttribute(\PDO::ATTR_CURSOR, \PDO::CURSOR_SCROLL) === false) {
            return false;
        }
    }

    /**
     * Changes the fetching mode affecting \Phalcon\Db\Result\Pdo::fetch()
     *
     *<code>
     *  //Return array with integer indexes
     *  $result->setFetchMode(Phalcon\Db::FETCH_NUM);
     *
     *  //Return associative array without integer indexes
     *  $result->setFetchMode(Phalcon\Db::FETCH_ASSOC);
     *
     *  //Return associative array together with integer indexes
     *  $result->setFetchMode(Phalcon\Db::FETCH_BOTH);
     *
     *  //Return an object
     *  $result->setFetchMode(Phalcon\Db::FETCH_OBJ);
     *</code>
     *
     * @param int $fetchMode
     * @throws Exception
     */
    public function setFetchMode($fetchMode)
    {
        if (is_int($fetchMode) === false) {
            throw new Exception('Invalid parameter type.');
        }

        switch($fetchMode) {
            case 1:
                $fetchType = 2;
                break;
            case 2:
                $fetchType = 4;
                break;
            case 3:
                $fetchType = 3;
                break;
            case 4:
                $fetchType = 5;
                break;
            default:
                $fetchType = 0;
                break;
        }

        if ($fetchType !== 0) {
            $this->_pdoStatement->setFetchMode($fetchType);
            $this->_fetchMode = $fetchType;
        }
    }

    /**
     * Gets the internal PDO result object
     *
     * @return \PDOStatement
     */
    public function getInternalResult()
    {
        return $this->_pdoStatement;
    }
}
