<?php
/**
* Query
*
* @author Andres Gutierrez <andres@phalconphp.com>
* @author Eduar Carvajal <eduar@phalconphp.com>
* @author Wenzel Pünter <wenzel@phelix.me>
* @version 1.2.6
* @package Phalcon
*/
namespace Phalcon\Mvc\Model;

use \Phalcon\DiInterface;
use \Phalcon\DI\InjectionAwareInterface;
use \Phalcon\Mvc\Model\QueryInterface;
use \Phalcon\Mvc\Model\Exception;
use \Phalcon\Mvc\Model\ManagerInterface;
use \Phalcon\Mvc\Model\RelationInterface;
use \Phalcon\Mvc\Model\Row;
use \Phalcon\Mvc\Model\Resultset\Simple;
use \Phalcon\Mvc\Model\Query\Lang;
use \Phalcon\Mvc\Model\Query\Status;
use \Phalcon\Mvc\Model;
use \Phalcon\Db\RawValue;

/**
 * Phalcon\Mvc\Model\Query
 *
 * This class takes a PHQL intermediate representation and executes it.
 *
 *<code>
 *
 * $phql = "SELECT c.price*0.16 AS taxes, c.* FROM Cars AS c JOIN Brands AS b
 *          WHERE b.name = :name: ORDER BY c.name";
 *
 * $result = $manager->executeQuery($phql, array(
 *   'name' => 'Lamborghini'
 * ));
 *
 * foreach ($result as $row) {
 *   echo "Name: ", $row->cars->name, "\n";
 *   echo "Price: ", $row->cars->price, "\n";
 *   echo "Taxes: ", $row->taxes, "\n";
 * }
 *
 *</code>
 *
 * @see https://github.com/phalcon/cphalcon/blob/1.2.6/ext/mvc/model/query.c
 */
class Query implements QueryInterface, InjectionAwareInterface
{
    /**
     * Type: Select
     *
     * @var int
    */
    const TYPE_SELECT = 309;

    /**
     * Type: Insert
     *
     * @var int
    */
    const TYPE_INSERT = 306;

    /**
     * Type: Update
     *
     * @var int
    */
    const TYPE_UPDATE = 300;

    /**
     * Type: Delete
     *
     * @var int
    */
    const TYPE_DELETE = 303;

    /**
     * Dependency Injector
     *
     * @var null|\Phalcon\DiInterface
     * @access protected
    */
    protected $_dependencyInjector;

    /**
     * Manager
     *
     * @var null|object
     * @access protected
    */
    protected $_manager;

    /**
     * Metadata
     *
     * @var null|object
     * @access protected
    */
    protected $_metaData;

    /**
     * Type
     *
     * @var null|int
     * @access protected
    */
    protected $_type;

    /**
     * PHQL
     *
     * @var null|string
     * @access protected
    */
    protected $_phql;

    /**
     * AST
     *
     * @var null|array
     * @access protected
    */
    protected $_ast;

    /**
     * Intermediate
     *
     * @var null|array
     * @access protected
    */
    protected $_intermediate;

    /**
     * Models
     *
     * @var null|array
     * @access protected
    */
    protected $_models;

    /**
     * SQL Aliases
     *
     * @var null|array
     * @access protected
    */
    protected $_sqlAliases;

    /**
     * SQL Aliases Models
     *
     * @var null|array
     * @access protected
    */
    protected $_sqlAliasesModels;

    /**
     * SQL Models Aliases
     *
     * @var null|array
     * @access protected
    */
    protected $_sqlModelsAliases;

    /**
     * SQL Aliases Models Instances
     *
     * @var null|array
     * @access protected
    */
    protected $_sqlAliasesModelsInstances;

    /**
     * SQL Column Aliases
     *
     * @var null|array
     * @access protected
    */
    protected $_sqlColumnAliases;

    /**
     * Model Instances
     *
     * @var null|array
     * @access protected
    */
    protected $_modelsInstances;

    /**
     * Cache
     *
     * @var null|\Phalcon\Cache\BackendInterface
     * @access protected
    */
    protected $_cache;

    /**
     * Cache Options
     *
     * @var null|array
     * @access protected
    */
    protected $_cacheOptions;

    /**
     * Unique Row
     *
     * @var null|boolean
     * @access protected
    */
    protected $_uniqueRow;

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
     * IR PHQL Cache
     *
     * @var null|array
     * @access protected
    */
    protected static $_irPhqlCache;

    /**
     * \Phalcon\Mvc\Model\Query constructor
     *
     * @param string|null $phql
     * @param \Phalcon\DiInterface|null $dependencyInjector
     */
    public function __construct($phql = null, $dependencyInjector = null)
    {
        if (is_string($phql) === true) {
            $this->_phql = $phql;
        }

        if (is_object($dependencyInjector) === true) {
            $this->setDi($dependencyInjector);
        }
    }

    /**
     * Sets the dependency injection container
     *
     * @param \Phalcon\DiInterface $dependencyInjector
     * @throws Exception
     */
    public function setDI($dependencyInjector)
    {
        if (is_object($dependencyInjector) === false ||
            $dependencyInjector instanceof DiInterface === false) {
            throw new Exception('A dependency injector container is required to obtain the ORM services');
        }

        $manager = $dependencyInjector->getShared('modelsManager');
        if (is_object($manager) === false) {
            //@note no interface validation
            throw new Exception("Injected service 'modelsManager' is invalid");
        }

        $metaData = $dependencyInjector->getShared('modelsMetadata');
        if (is_object($metaData) === false) {
            //@note no interface validation
            throw new Exception("Injected service 'modelsMetadata' is invalid");
        }

        $this->_manager = $manager;
        $this->_metaData = $metaData;
        $this->_dependencyInjector = $dependencyInjector;
    }

    /**
     * Returns the dependency injection container
     *
     * @return \Phalcon\DiInterface|null
     */
    public function getDI()
    {
        return $this->_dependencyInjector;
    }

    /**
     * Tells to the query if only the first row in the resultset must be returned
     *
     * @param boolean $uniqueRow
     * @return \Phalcon\Mvc\Model\Query
     * @throws Exception
     */
    public function setUniqueRow($uniqueRow)
    {
        if (is_bool($uniqueRow) === false) {
            throw new Exception('Invalid parameter type.');
        }

        $this->_uniqueRow = $uniqueRow;

        return $this;
    }

    /**
     * Check if the query is programmed to get only the first row in the resultset
     *
     * @return boolean|null
     */
    public function getUniqueRow()
    {
        return $this->_uniqueRow;
    }

    /**
     * Replaces the model's name to its source name in a qualifed-name expression
     *
     * @param array $expr
     * @return string
     * @throws Exception
     * @todo optimize variable usage
     */
    protected function _getQualified(array $expr)
    {
        $columnName = $expr['name'];
        $sqlColumnAliases = $expr['_sqlColumnAliases'];

        //Check if the qualified name is a column alias
        if (isset($sqlColumnAliases[$columnName]) === true) {
            return array('type' => 'qualified', 'name' => $columnName);
        }

        $metaData = $this->_metaData;

        //Check if the qualified name has a domain
        if (isset($expr['domain']) === true) {
            $columnDomain = $expr['domain'];
            $sqlAliases = $expr['_sqlAliases'];

            //The column has a domain, we need to check if it's an alias
            if (isset($sqlAliases[$columnDomain]) === false) {
                throw new Exception("Unknown model or alias '".$columnDomain."' (1), when preparing: ".$this->_phql);
            }

            $source = $sqlAliases[$columnDomain];

            //Change the selected column by its real name on its mapped table
            if (isset($GLOBALS['_PHALCON_ORM_COLUMN_RENAMING']) === true &&
                $GLOBALS['_PHALCON_ORM_COLUMN_RENAMING'] === true) {
                //Retrieve the corresponding model by its alias
                $sqlAliasesModelsInstances = $this->_sqlAliasesModelsInstances;

                //We need the model instances to retrieve the reversed column map
                if (isset($sqlAliasesModelsInstances[$columnDomain]) === false) {
                    throw new Exception("There is no model related to model or alias '".$columnDomain."', when executing: ".$this->_phql);
                }

                $model = $sqlAliasesModelsInstances[$columnDomain];
                $columnMap = $metaData->getReverseColumnMap($model);
            } else {
                $columnMap = null;
            }

            if (is_array($columnMap) === true) {
                if (isset($columnMap[$columnName]) === true) {
                    $realColumnName = $columnMap[$columnName];
                } else {
                    throw new Exception("Column '".$columnName."' doesn't belong to the model or alias '".$columnDomain."', when executing: ".$this->_phql);
                }
            } else {
                $realColumnName = $columnName;
            }
        } else {
            $number = 0;
            $hasModel = false;

            $modelsInstances = $this->_modelsInstances;
            foreach ($modelsInstances as $model) {
                //Check if the attribute belongs to the current model
                if ($metaData->hasAttribute($model, $columnName) === true) {
                    $number++;
                    if ($number > 1) {
                        throw new Exception("The column '".$columnName."' is ambiguous, when preparing: ".$this->_phql);
                    }

                    $hasModel = $model;
                }
            }

            //After check in every model, the column does not belong to any of the selected models
            if ($hasModel === false) {
                throw new Exception("Column '".$columnName."' doesn't belong to any of the selected models (1), when preparing: ".$this->_phql);
            }

            //Check if the _models property is correctly prepared
            if (is_array($this->_models) === false) {
                throw new Exception('The models list was not loaded correctly');
            }

            //Obtain the model's source from the _models lsit
            $className = get_class($hasModel);
            if (isset($this->_models[$className]) === true) {
                $source = $this->_models[$className];
            } else {
                throw new Exception("Column '".$columnName."' doesn't belong to any of the selected models (2) when preparing: ".$this->_phql);
            }

            //Rename the column
            if (isset($GLOBALS['_PHALCON_ORM_COLUMN_RENAMING']) === true &&
                $GLOBALS['_PHALCON_ORM_COLUMN_RENAMING'] === true) {
                $columnMap = $metaData->getReverseColumnMap($hasModel);
            } else {
                $columnMap = null;
            }

            if (is_array($columnMap) === true) {
                //The real column name is in the column map
                if (isset($columnMap[$columnName]) === true) {
                    $realColumnName = $columnMap[$columnName];
                } else {
                    throw new Exception("Column '".$columnName."' doesn't belong to any of the selected models (3), when preparing: ".$this->_phql);
                }
            } else {
                $realColumnName = $columnName;
            }
        }

        //Create an array with the qualified info
        return array('type' => 'qualified', 'domain' => $source, 'name' => $realColumnName, 'balias' => $columnName);
    }

    /**
     * Resolves a expression in a single call argument
     *
     * @param array $argument
     * @return string
     */
    protected function _getCallArgument(array $argument)
    {
        if ($this->_type === 352) {
            return array('type' => 'all');
        }

        return $this->_getExpression();
    }

    /**
     * Resolves a expression in a single call argument
     *
     * @param array $expr
     * @return string
     */
    protected function _getFunctionCall(array $expr)
    {
        if (isset($expr['arguments']) === true) {
            $arguments = $expr['arguments'];
            if (isset($arguments[0]) === true) {
                //There are more than one argument
                $functionArgs = array();
                foreach ($arguments as $argument) {
                    $functionArgs[] = $this->_getCallArgument($argument);
                }
            } else {
                //There is only one argument
                $functionArgs[] = $this->_getCallArgument[$arguments];
            }

            return array('type' => 'functionCall', 'name' => $expr['name'], 'arguments' => $functionArgs);
        } else {
            return array('type' => 'functionCall', 'name' => $expr['name']);
        }
    }

    /**
     * Resolves an expression from its intermediate code into a string
     *
     * @param array $expr
     * @param boolean|null $quoting
     * @return string
     * @throws Exception
     */
    protected function _getExpression(array $expr, $quoting = null)
    {
        if (is_null($quoting) === true) {
            $quoting = true;
        } elseif (is_bool($quoting) === false) {
            throw new Exception('Invalid parameter type.');
        }

        if (isset($expr['type']) === true) {
            $tempNoQuoting = true;

            //Resolving left part of the expression if any
            if (isset($expr['left']) === true) {
                $left = $this->_getExpression($expr['left'], $tempNoQuoting);
            }

            //Resolving right part of the expression if any
            if (isset($expr['right']) === true) {
                $right = $this->_getExpression($expr['right'], $tempNoQuoting);
            }

            //Every node in the AST has a unique integer type
            switch((int)$expr['type']) {
                case 60:
                    return array('type' => 'binary-op', 'op' => '<', 'left' => $left, 'right' => $right);
                    break;
                case 61:
                    return array('type' => 'binary-op', 'op' => '=', 'left' => $left, 'right' => $right);
                    break;
                case 62:
                    return array('type' => 'binary-op', 'op' => '>', 'left' => $left, 'right' => $right);
                    break;
                case 270:
                    return array('type' => 'binary-op', 'op' => '<>', 'left' => $left, 'right' => $right);
                    break;
                case 271:
                    return array('type' => 'binary-op', 'op' => '<=', 'left' => $left, 'right' => $right);
                    break;
                case 272:
                    return array('type' => 'binary-op', 'op' => '>=', 'left' => $left, 'right' => $right);
                    break;
                case 266:
                    return array('type' => 'binary-op', 'op' => 'AND', 'left' => $left, 'right' => $right);
                    break;
                case 267:
                    return array('type' => 'binary-op', 'op' => 'OR', 'left' => $left, 'right' => $right);
                    break;
                case 355:
                    return $this->_getQualified($expr);
                    break;
                case 359:
                    return $this->_getAliased($expr); //@todo?
                    break;
                case 43:
                    return array('type' => 'binary-op', 'op' => '+', 'left' => $left, 'right' => $right);
                    break;
                case 35:
                    return array('type' => 'binary-op', 'op' => '-', 'left' => $left, 'right' => $right);
                    break;
                case 42:
                    return array('type' => 'binary-op', 'op' => '*', 'left' => $left, 'right' => $right);
                    break;
                case 47:
                    return array('type' => 'binary-op', 'op' => '/', 'left' => $left, 'right' => $right);
                    break;
                case 37:
                    return array('type' => 'binary-op', 'op' => '%', 'left' => $left, 'right' => $right);
                    break;
                case 38:
                    return array('type' => 'binary-op', 'op' => '&', 'left' => $left, 'right' => $right);
                    break;
                case 124:
                    return array('type' => 'binary-op', 'op' => '|', 'left' => $left, 'right' => $right);
                    break;
                case 356:
                    return array('type' => 'parentheses', 'left' => $left);
                    break;
                case 367:
                    return array('type' => 'unary-op', 'op' => '-', 'right' => $right);
                    break;
                case 258:
                case 259:
                    return array('type' => 'literal', 'value' => $expr['value']);
                    break;
                case 333:
                    return array('type' => 'literal', 'value' => 'TRUE');
                    break;
                case 334:
                    return array('type' => 'literal', 'value' => 'FALSE');
                    break;
                case 260:
                    $value = $expr['value'];
                    if ($quoting === true) {
                        //CHeck if static literals have single quotes and escape them
                        if (strpos($value, "'") !== false) {
                            $value = self::singleQuotes($value);
                        }

                        $value = "'".$value."'";
                    }

                    return array('type' => 'literal', 'value' => $value);
                    break;
                case 273:
                    return array('type' => 'placeholder', 'value' => str_replace('?', ':', $expr['value']));
                    break;
                case 274:
                    return array('type' => 'placeholder', 'value' => ':'.$expr['value']);
                    break;
                case 322:
                    return array('type' => 'literal', 'value' => 'NULL');
                    break;
                case 268:
                    return array('type' => 'binary-op', 'op' => 'LIKE', 'left' => $left, 'right' => $right);
                    break;
                case 351:
                    return array('type' => 'binary-op', 'op' => 'NOT LIKE', 'left' => $left, 'right' => $right);
                    break;
                case 275:
                    return array('type' => 'binary-op', 'op' => 'ILIKE', 'left' => $left, 'right' => $right);
                    break;
                case 357:
                    return array('type' => 'binary-op', 'op' => 'NOT ILIKE', 'left' => $left, 'right' => $right);
                    break;
                case 33:
                    return array('type' => 'unary-op', 'op' => 'NOT ', 'right' => $right);
                    break;
                case 365:
                    return array('type' => 'unary-op', 'op' => ' IS NULL', 'left' => $left);
                    break;
                case 366:
                    return array('type' => 'unary-op', 'op' => ' IS NOT NULL', 'left' => $left);
                    break;
                case 315:
                    return array('type' => 'binary-op', 'op' => 'IN', 'left' => $left, 'right' =>  $right);
                    break;
                case 323:
                    return array('type' => 'binary-op', 'op' => 'NOT IN', 'left' => $left, 'right' => $right);
                    break;
                case 330:
                    return array('type' => 'unary-op', 'op' => 'DISTINCT ', 'right' => $right);
                    break;
                case 331:
                    return array('type' => 'binary-op', 'op' => 'BETWEEN', 'left' => $left, 'right' => $right);
                    break;
                case 276:
                    return array('type' => 'binary-op', 'op' => 'AGAINST', 'left' => $left, 'right' => $right);
                    break;
                case 332:
                    return array('type' => 'cast', 'left' => $left, 'right' => $right);
                    break;
                case 335:
                    return array('type' => 'convert', 'left' => $left, 'right' => $right);
                    break;
                case 358:
                    return array('type' => 'literal', 'value' => $expr['name']);
                    break;
                case 350:
                    return $this->_getFunctionCall($expr);
                    break;
                default:
                    throw new Exception('Unknown expression type '.$expr['type']);
            }
        }

        //Is a qualified column?
        if (isset($expr['domain']) === true) {
            return $this->_getQualified($expr);
        }

        //If the expression doesn't have a type it's a list of nodes
        if (isset($expr[0]) === true) {
            $listItems = array();
            foreach ($expr as $exprListItem) {
                $listItems[] = $this->_getExpression($exprListItem);
            }

            return array('type' => 'list', $listItems);
        }

        throw new Exception('Unknown expression');
    }

    /**
     * Escapes single quotes into database single quotes
     *
     * @param string $value
     * @return string
    */
    private static function singleQuotes($value)
    {
        if (is_string($value) === false) {
            return '';
        }
        $esc = '';

        $l = strlen($value);
        $n = chr(0);
        for ($i = 0; $i < $l; ++$i) {
            if ($value[$i] === $n) {
                break;
            }

            if ($value[$i] === '\'') {
                if ($i > 0) {
                    if ($value[$i-1] != '\\') {
                        $esc .= '\'';
                    }
                } else {
                    $esc .= '\'';
                }
            }

            $esc .= $value[$i];
        }

        return $esc;
    }

    /**
     * Resolves a column from its intermediate representation into an array used to determine
     * if the resulset produced is simple or complex
     *
     * @param array $column
     * @return array
     * @throws Exception
     */
    protected function _getSelectColumn(array $column)
    {
        if (isset($column['type']) === false) {
            throw new Exception('Corrupted SELECT AST');
        }

        $sqlColumns = array();

        //Check for SELECT * (all)
        $columnType = $column['type'];
        if ($columnType === 352) {
            foreach ($this->_models as $modelName => $source) {
                $sqlColumns[] = array('type' => 'object', 'model' => $modelName, 'column' => $source);
            }

            return $sqlColumns;
        }

        if (isset($column['column']) === false) {
            throw new Exception('Corrupted SELECT AST');
        }

        //Check if selected column is qualified.
        if ($columnType === 353) {
            $sqlAliases = $this->_sqlAliases;

            //We only allow the alias.
            $columnDomain = $column['column'];

            if (isset($sqlAliases[$columnDomain]) === false) {
                throw new Exception("Unknown model or alias '".$columnDomain."' (2), when preparing: ".$this->_phql);
            }

            //Get the SQL alias if any
            $sqlColumnAlias = $sqlAliases[$columnDomain];

            //Get the real source name
            $modelName = $this->_sqlAliasesModels[$columnDomain];

            //Get the best alias for the column
            $bestAlias = $this->_sqlModelsAliases[$modelName];

            //If the best alias is the model name we lowercase the first letter
            $alias = ($bestAlias === $modelName ? lcfirst($modelName) : $bestAlias);

            //The sql column is a complex type returning a complete object
            return array('type' => 'object', 'model' => $modelName, 'column' => $sqlColumnAlias, 'balias' => $alias);
        }

        //Check for columns qualified and not qualified
        if ($columnType === 354) {
            //The sql_column is a scalar type returning a simple string
            $sqlColumn = array('type' => 'scalar');
            $sqlExprColumn = $this->_getExpression($column['column']);

            //Create balias and sqlAlias
            if (isset($sqlExprColumn['balias']) === true) {
                $balias = $sqlExprColumn['balias'];
                $sqlColumn['balias'] = $balias;
                $sqlColumn['sqlAlias'] = $balias;
            }

            $sqlColumn['column'] = $sqlExprColumn;
            $sqlColumns[] = $sqlColumn;

            return $sqlColumns;
        }

        throw new Exception("Unknown type of column ".$columnType);
    }

    /**
     * Resolves a table in a SELECT statement checking if the model exists
     *
     * @param \Phalcon\Mvc\Model\ManagerInterface $manager
     * @param array $qualifiedName
     * @return string
     * @throws Exception
     */
    protected function _getTable(ManagerInterface $manager, array $qualifiedName)
    {
        if (isset($qualifiedName['name']) === true) {
            $Model = $manager->load($qualifiedName['name']);

            $schema = $model->getSchema();
            if ($schema == true) {
                return array($schema, $model->getSource());
            }
            return $model->getSource();
        }

        throw new Exception('Corrupted SELECT AST');
    }

    /**
     * Resolves a JOIN clause checking if the associated models exist
     *
     * @param \Phalcon\Mvc\Model\ManagerInterface $manager
     * @param array $join
     * @return array
     * @throws Exception
     */
    protected function _getJoin(ManagerInterface $manager, array $join)
    {
        if (isset($join['qualified']) === true && $join['qualified']['type'] === 355) {
            return array(
                $model->getSchema(),
                $model->getSource(),
                $join['qualified']['name'],
                $manager->load($join['qualified']['name'])
            );
        }

        throw new Exception('Corrupted SELECT AST');
    }

    /**
     * Resolves a JOIN type
     *
     * @param array $join
     * @return string
     * @throws Exception
     */
    protected function _getJoinType(array $join)
    {
        if (isset($join['type']) === false) {
            throw new Exception('Corrupted SELECT AST');
        }

        switch((int)$join['type']) {
            case 360:
                return 'INNER';
                break;
            case 361:
                return 'LEFT';
                break;
            case 362:
                return 'RIGHT';
                break;
            case 363:
                return 'CROSS';
                break;
            case 364:
                return 'FULL OUTER';
                break;
            default:
                throw new Exception("Unknown join type ".$join['type'].', when preparing: '.$this->_phql);
                break;
        }
    }

    /**
     * Resolves joins involving has-one/belongs-to/has-many relations
     *
     * @param string $joinType
     * @param string $joinSource
     * @param string $modelAlias
     * @param string $joinAlias
     * @param \Phalcon\Mvc\Model\RelationInterface $relation
     * @return array
     * @throws Exception
     */
    protected function _getSingleJoin($joinType, $joinSource, $modelAlias, $joinAlias, RelationInterface $relation)
    {
        if (is_string($joinType) === false ||
            is_string($joinSource) === false ||
            is_string($modelAlias) === false ||
            is_string($joinAlias) === false) {
            throw new Exception('Invalid parameter type.');
        }

        //Local fields in the 'from' relation
        $fields = $relation->getFields();

        //Referenced fields in the joined relation
        $referencedFields = $relation->getReferencedFields();

        if (is_array($fields) === false) {
            //Create the left part of the expression
            $leftExpr = $this->_getQualified(array('type' => 355, 'domain' => $modelAlias, 'name' => $fields));

            //Create the right part of the expression
            $rightExpr = $this->_getQualified(array('type' => 'qualified', 'domain' => $joinAlias, 'name' => $referencedFields));

            //Create a binary operation for the join conditions
            $sqlJoinConditions = array(array('type' => 'binary-op', 'op' => '=', 'left' => $leftExpr, 'right' => $rightExpr));
        } else {
            //Resolve the compound operation
            $sqlJoinPartialConditions = array();
            foreach ($fields as $position => $field) {
                if (isset($referencedFields[$position]) === false) {
                    throw new Exception('The number of fields must be equal to the number of referenced fields in join '.$modelAlias.'-'.$joinAlias.', when preparing: '.$this->_phql);
                }

                //Get the referenced field in the same position
                $referencedField = $referencedFields[$position];

                //Create the left part of the expression
                $leftExpr = $this->_getQualified(array('type' => 355, 'domain' => $modelAlias, 'name' => $field));

                //Create the right part of the expression
                $rightExpr = $this->_getQualified(array('type' => 'qualified', 'domain' => $joinAlias, 'name' => $referencedFields));

                //Create a binary operation for the join conditions
                $sqlJoinPartialConditions[] = array('type' => 'binary-op', 'op' => '=', 'left' => $leftExpr, 'right' => $rightExpr);
            }
        }

        //A single join
        return array('type' => $joinType, 'source' => $joinSource, 'conditions' => $sqlJoinConditions); //@note sql_join_conditions is not set when $fields is an array
    }

    /**
     * Resolves joins involving many-to-many relations
     *
     * @param string $joinType
     * @param string $joinSource
     * @param string $modelAlias
     * @param string $joinAlias
     * @param \Phalcon\Mvc\Model\RelationInterface $relation
     * @return array
     * @throws Exception
     */
    protected function _getMultiJoin($joinType, $joinSource, $modelAlias, $joinAlias, RelationInterface $relation)
    {
        if (is_string($joinType) === false ||
            is_string($joinSource) === false ||
            is_string($modelAlias) === false ||
            is_string($joinAlias) === false) {
            throw new Exception('Invalid parameter type.');
        }

        $sqlJoins = array();

        //Local fields in the 'from' relation
        $fields = $relation->getFields();

        //Referenced fields in the joined relation
        $referencedFields = $relation->getReferencedFields();

        //Intermediate model
        $intermediateModelName = $relation->getIntermediateModel();

        //Get the intermediate model instance
        $intermediateModel = $this->_manager->load($intermediateModelName);

        //Source of the related model
        $intermediateSource = $intermediateModel->getSource();

        //$intermediateFullSource = array($intermediateModel->getSchema(),
        //  $intermediateSource); @note this variable is not used

        //Update the internal sqlAlias to set up the intermediate model
        $this->_sqlAliases[$intermediateModelName] = $intermediateSource;

        //Update the internal _sqlAliasesModelsInstances to rename columns if necessary
        $this->_sqlAliasesModelsInstances[$intermediateModelName] = $intermediateModel;

        //Fields that join the 'from' model with the 'intermediate' model
        $intermediateFields = $relation->getIntermediateFields();

        //Fields that join the 'intermediate' model with the model
        $intermediateReferencedFields = $relation->getIntermediateReferencedFields();

        //Intermediate model
        $referencedModelName = $relation->getReferencedModel();
        if (is_array($fields) === true) {
            //@note when $fields is an array this function returns an empty array and the following code is unnecessary
            foreach ($fields as $position => $field) {
                if (isset($referencedFields[$position]) === false) {
                    throw new Exception('The number of fields must be equal to the number of referenced fields in join '.$modelAlias.'-'.$joinAlias.', when preparing: '.$this->_phql);
                }

                //Get the referenced field in the same position @note this variable is not used
                //$intermediateField = $intermediateFields[$position];

                //Create the left part of the expression
                $leftExpr = $this->_getQualified(array('type' => 355, 'domain' => $modelAlias, 'name' => $field));
                $rightExpr = $this->_getQualified(array('type' => 'qualified', 'domain' => $joinAlias, 'name' => $referencedFields));
                $sqlEqualsJoinCondition = array('type' => 'binary-op', 'op' => '=', 'left' => $leftExpr, 'right' => $rightExpr);
            }
        } else {
            /* First Condition */
            $leftExpr = $this->_getQualified(array('type' => 355, 'domain' => $modelAlias, 'name' => $fields));
            $rightExpr = $this->_getQualified(array('type' => 'qualified', 'domain' => $intermediateModelName, 'name' => $intermediateFields));
            $sqlJoinConditionsFirst = array(array('type' => 'binary-op', 'op' => '=', 'left' => $leftExpr, 'right' => $rightExpr));
            $sqlJoinFirst = array('type' => $joinType, 'source' => $intermediateSource, 'conditions' => $sqlJoinConditionsFirst);

            /* Second Condition */
            $leftExpr = $this->_getQualified(array('type' => 355, 'domain' => $intermediateModelName, 'name' => $intermediateReferencedFields));
            $rightExpr = $this->_getQualified(array('type' => 'qualified', 'domain' => $referencedModelName, 'name' => $referencedFields));
            $sqlJoinConditionsSecond = array(array('type' => 'binary-op', 'op' => '=', 'left' => $leftExpr, 'right' => $rightExpr));
            $sqlJoinSecond = array('type' => $joinType, 'source' => $joinSource, 'conditions' => $sqlJoinConditionsSecond);

            /* Store in result array */
            $sqlJoins[0] = $sqlJoinFirst;
            $sqlJoins[1] = $sqlJoinSecond;
        }

        return $sqlJoins;
    }

    /**
     * Processes the JOINs in the query returning an internal representation for the database dialect
     *
     * @param array $select
     * @return array
     * @throws Exception
     */
    protected function _getJoins(array $select)
    {
        $models = $this->_models;
        $sqlAliases = $this->_sqlAliases;
        $sqlAliasesModels = $this->_sqlAliasesModels;
        $sqlModelsAliases = $this->_sqlModelsAliases;
        $sqlAliasesModelsInstances = $this->_sqlAliasesModelsInstances;
        $modelsInstances = $this->_modelsInstances;
        $fromModels = $modelsInstances;
        $manager = $this->_manager;

        $sqlJoins = array();
        $joinModels = array();
        $joinSources = array();
        $joinTypes = array();
        $joinPreConditions = array();
        $joinPrepared = array();

        if (isset($select['joins'][0]) === false) {
            $selectJoins = array($select['joins']);
        } else {
            $selectJoins = $select['joins'];
        }

        foreach ($selectJoins as $joinItem) {
            //Check join alias
            $joinData = $this->_getJoin($manager, $joinItem);
            $source = $joinData['source'];
            $schema = $joinData['schema'];
            $model = $joinData['model'];
            $modelName = $joinData['modelName'];
            $completeSource = array($source, $schema);

            //Check join alias
            $joinType = $this->_getJoinType($joinItem);

            //Process join alias
            if (isset($joinItem['alias']) === true) {
                $alias = $joinItem['alias']['name'];

                //Check if alias is unique
                if (isset($joinModels[$alias]) === true) {
                    throw new Exception("Cannot use '".$alias."' as join alias because it was already used, when preparing: ".$this->_phql);
                }

                //Add the alias to the source
                $completeSource[] = $alias;

                //Set the join type
                $joinTypes[$alias] = $joinType;

                //Update alias => $alias
                $sqlAliases[$alias] = $alias;

                //Update model => alias
                $joinModels[$alias] = $modelName;
                $sqlModelsAliases[$modelName] = $alais;
                $sqlAliasesModels[$alias] = $modelName;
                $sqlAliasesModelsInstances[$alias] = $model;

                //Update model => alias
                $models[$modelName] = $alias;

                //Complete source related to a model
                $joinSources[$alias] = $completeSource;
                $joinPrepared[$alias] = $joinItem;
            } else {
                //Check if alias is unique
                if (isset($joinModels[$modelName]) === true) {
                    throw new Exception("Cannot use '".$modelName."' as join because it was already used, when preparing: ".$this->_phql);
                }

                //Set the join type
                $joinTypes[$modelName] = $joinType;

                //Update model => source
                $sqlAliases[$modelName] = $source;
                $joinModels[$modelName] = $source;

                //Update model => model
                $sqlModelsAliases[$modelName] = $modelName;
                $sqlAliasesModels[$modelName] = $modelName;

                //Update model => model instances
                $sqlAliasesModelsInstances[$modelName] = $model;

                //Update model => source
                $models[$modelName] = $source;

                //Complete source related to a model
                $joinSources[$modelName] = $completeSource;
                $joinPrepared[$modelName] = $joinItem;
            }

            $modelsInstances[$modelName] = $model;
        }

        //Update temporary properties
        $this->_models = $models;
        $this->_sqlAliases = $sqlAliases;
        $this->_sqlAliasesModels = $sqlAliasesModels;
        $this->_sqlModelsAliases = $sqlModelsAliases;
        $this->_sqlAliasesModelsInstances = $sqlAliasesModelsInstances;
        $this->_modelsInstances = $modelsInstances;

        foreach ($joinPrepared as $joinAliasName => $joinItem) {
            //Check for predefined conditions
            if (isset($joinItem['conditions']) === true) {
                $joinPreConditions[$joinAliasName] = $this->_getExpression($joinItem['conditions']);
            }
        }

        //Create join relationships dynamically
        foreach ($fromModels as $fromModelName => $source) {
            foreach ($joinModels as $joinAlias => $joinModel) {
                //Real source name for joined model
                $joinSource = $joinSources[$joinAlias];

                //Join type is: LEFT, RIGHT, INNER, etc.
                $joinType = $joinTypes[$joinAlias];

                //Check if the model already has pre-defined conditions
                if (isset($joinPreConditions[$joinAlias]) === false) {
                    //Get the model name from its source
                    $modelNameAlias = $sqlAliasesModels[$joinAlias];

                    //Check if the joined model is an alias
                    $relation = $manager->getRelationByAlias($fromModelName, $modelNameAlias);
                    if ($relation === false) {
                        $relations = $manager->getRelationsBetween($fromModelName, $modelNameAlias);

                        if (is_array($relations) === true) {
                            //More than one relation must throw an exception
                            $numberRelations = count($relations);
                            if ($numberRelations !== 1) {
                                throw new Exception("There is more than one relation between models '".$modelName."' and '".$joinModel.'", the join must be done using an alias, when preparing: '.$this->_phql);
                            }

                            //Get the first relationship
                            $relation = $relations[0];
                        }
                    }

                    //Valid relations are objects
                    if (is_object($relation) === true) {
                        //Get the related model alias of the left part
                        $modelAlias = $sqlModelsAliases[$fromModelName];

                        //Generate the conditions based on the type of join
                        if ($relation->isThrough() === false) {
                            $sqlJoin = $this->_getSingleJoin($joinType, $joinSource, $modelAlias, $joinAlias, $relation); //no Many-To-Many
                        } else {
                            $sqlJoin = $this->_getMultiJoin($joinType, $joinSource, $modelAlias, $joinAlias, $relation);
                        }

                        //Append or merge joins
                        if (isset($sqlJoins[0]) === true) {
                            $sqlJoins = array_merge($sqlJoins, $sqlJoin);
                        } else {
                            $sqlJoins[] = $sqlJoin;
                        }
                    } else {
                        $sqlJoinConditions = array();

                        //Join without conditions because no relation has been found between the models
                        $sqlJoin = array('type' => $joinType, 'source' => $joinSource, 'conditions' => $sqlJoinConditions);
                    }
                } else {
                    //Get the conditions established by the developer
                    $sqlJoinConditions = array(array($joinPreConditions, $joinAlias));

                    //Join with conditions established  by the devleoper
                    $sqlJoins[] = array('type' => $joinType, 'source' => $joinSource, 'conditions' => $sqlJoinConditions);
                }
            }
        }

        return $sqlJoins;
    }

    /**
     * Returns a processed order clause for a SELECT statement
     *
     * @param array $order
     * @return string
     */
    protected function _getOrderClause(array $order)
    {
        if (isset($order[0]) === false) {
            $orderColumns = array($order);
        } else {
            $orderColumns = $order;
        }

        $orderParts = array();

        foreach ($orderColumns as $orderItem) {
            $orderPartExpr = $this->_getExpression($orderItem['column']);

            //Check if the order has a predefined ordering mode
            if (isset($orderItem['sort']) === true) {
                if ($orderItem['sort'] === 327) {
                    $orderPartSort = array($orderPartExpr, 'ASC');
                } else {
                    $orderPartSort = array($orderPartExpr, 'DESC');
                }
            } else {
                $orderPartSort = array($orderPartExpr);
            }

            $orderParts[] = $orderPartSort;
        }

        return $orderParts;
    }

    /**
     * Returns a processed group clause for a SELECT statement
     *
     * @param array $group
     * @return string
     */
    protected function _getGroupClause(array $group)
    {
        if (isset($group[0]) === true) {
            //The SELECT is grouped by several columns
            $groupParts = array();
            foreach ($group as $groupItem) {
                $groupParts[] = $this->_getExpression($groupItem);
            }
        } else {
            $groupParts = array($this->_getExpression($group));
        }

        return $groupParts;
    }

    /**
     * Analyzes a SELECT intermediate code and produces an array to be executed later
     *
     * @return array
     * @throws Exception
     */
    protected function _prepareSelect()
    {
        $ast = $this->_ast;
        if (isset($ast['select']['tables']) === false ||
            isset($ast['select']['columns']) === false) {
            throw new Exception('Corrupted SELECT AST');
        }

        $sqlModels = array();
        $sqlTables = array();
        $sqlAliases = array();
        $sqlColumns = array();
        $sqlAliasesModels = array();
        $sqlModelsAliases = array();
        $sqlAliasesModelsInstances = array();
        $models = array();
        $modelsInstances = array();

        if (isset($ast['select']['tables'][0]) === false) {
            $selectedModels = array($ast['select']['tables']);
        } else {
            $selectedModels = $ast['select']['tables'];
        }

        //@note we don't need the metadata object

        //Processing selected columns
        foreach ($selectedModels as $selectedModel) {
            $qualifiedName = $selectedModel['qualifiedName'];
            $modelName = $qualifiedName['name'];

            //Load a model instance from the models manager
            $model = $this->_manager->load((isset($qualifiedName['ns-alias']) === true ? $this->_manager->getNamespaceAlias($qualifiedName['ns-alias']).'\\'.$modelName : $modelName));

            //Define a complete schema/source
            $schema = $model->getSchema();
            $source = $model->getSource();

            //Obtain the real source including the schema
            if ($schema == true) {
                $completeSource = array($source, $schema);
            } else {
                $completeSource = $source;
            }

            //If an alias is defined for a model the model cannot be referenced in the column list
            if (isset($selectedModel['alias']) === true) {
                $alias = $selectedModel['alias'];

                //Check that the alias hasn't been used before
                if (isset($sqlAliases[$alias]) === true) {
                    throw new Exception('Alias "'.$alias.' is already used, when preparing: '.$this->_phql); //@note missing quote
                }

                $sqlAliases[$alias] = $alias;
                $sqlAliasesModels[$alias] = $modelName;
                $sqlModelsAliases[$modelName] = $alias;
                $sqlAliasesModelsInstances[$alias] = $model;

                //Append or convert complete source to an array
                if (is_array($completeSource) === true) {
                    $completeSource[] = $alias;
                } else {
                    $completeSource = array($source, null, $alias);
                }

                $models[$modelName] = $alias;
            } else {
                $sqlAliases[$modelName] = $alias;
                $sqlAliasesModels[$modelName] = $modelName;
                $sqlModelsAliases[$modelName] = $modelName;
                $sqlAliasesModelsInstances[$modelName] = $model;
                $models[$modelName] = $source;
            }

            $sqlModels[] = $modelName;
            $sqlTables[] = $completeSource;
            $modelsInstances[$modelName] = $model;
        }

        //Assign models/tables information
        $this->_models = $models;
        $this->_modelsInstances = $modelsInstances;
        $this->_sqlAliases = $sqlAliases;
        $this->_sqlAliasesModels = $sqlAliasesModels;
        $this->_sqlModelsAliases = $sqlModelsAliases;
        $this->_sqlAliasesModelsInstances = $sqlAliasesModelsInstances;
        $this->_modelsInstances = $modelsInstances;

        //Processing joins
        if (isset($ast['select']['joins']) === true) {
            $sqlJoins = (count($ast['select']['joins']) > 0 ? $this->_getJoins($ast['select']) : array());
        } else {
            $sqlJoins = array();
        }

        //Processing selected columns
        if (isset($ast['select']['columns'][0]) === false) {
            $selectColumns = array($ast['select']['columns']);
        } else {
            $selectColumns = $ast['select']['columns'];
        }

        //Resolve selected columns
        $position = 0;
        $sqlColumnAliases = array();

        foreach ($selectColumns as $column) {
            $sqlColumnGroup = $this->_getSelectColumn($column);
            foreach ($sqlColumnGroup as $sqlColumn) {
                //If 'alias' is set, the user had defined an alias for the column
                if (isset($column['alias']) === true) {
                    $alias = $column['alias'];

                    //The best alias if the one provided by the user
                    $sqlColumn['balias'] = $alias;
                    $sqlColumn['sqlAlias'] = $alias;
                    $sqlColumns[$alias] = $sqlColumn;
                    $this->_sqlColumnAliases[$alias] = true;
                } else {
                    //'balias' is the best alias selected for the column
                    if (isset($sqlColumn['balias']) === true) {
                        $alias = $sqlColumn['balias'];
                        $sqlColumn[$alias] = $sqlColumn;
                    } else {
                        if ($sqlColumn['type'] === 'scalar') {
                            $sqlColumns['_'.$position] = $sqlColumn;
                        } else {
                            $sqlColumns[] = $sqlColumn;
                        }
                    }
                }

                $position++;
            }
        }

        //sql_select is the final prepared SELECt
        $sqlSelect = array('models' => $sqlModels, 'tables' => $sqlTables, 'columns' => $sqlColumns);
        if (count($sqlJoins) > 0) {
            $sqlSelect['joins'] = $sqlJoins;
        }

        //Process WHERE clauses if any
        if (isset($ast['where']) === true) {
            $sqlSelect['where'] = $this->_getExpression($ast['where']);
        }

        //Process GROUP BY clauses if any
        if (isset($ast['groupBy']) === true) {
            $sqlSelect['group'] = $this->_getGroupClause($ast['groupBy']);
        }

        //Process HAVING clauses if any
        if (isset($ast['having']) === true) {
            $sqlSelect['having'] = $this->_getExpression($ast['having']);
        }

        //Process ORDER BY clauses if any
        if (isset($ast['orderBy']) === true) {
            $sqlSelect['order'] = $this->_getOrderClause($ast['orderBy']);
        }

        //Process LIMIT clauses if any
        if (isset($ast['limit']) === true) {
            $sqlSelect['limit'] = $ast['limit'];
        }

        return $sqlSelect;
    }

    /**
     * Analyzes an INSERT intermediate code and produces an array to be executed later
     *
     * @return array
     * @throws Exception
     */
    protected function _prepareInsert()
    {
        $ast = $this->_ast;
        if (isset($ast['qualifiedName']) === false ||
            isset($ast['values']) === false ||
            isset($ast['qualifiedName']['name']) === false) {
            throw new Exception('Corrupted INSERT AST');
        }

        $modelName = $ast['qualifiedName']['name'];
        $model = $this->_manager->load($modelName);
        $source = $model->getSource();
        $schema = $model->getSchema();

        if ($schema == true) {
            $source = array($schema, $source);
        }

        //@note sql_aliases is not used
        $exprValues = array();

        foreach ($ast['values'] as $exprValue) {
            //Resolve every expression in the 'values' clause
            $exprValues[] = array(
                'type' => $exprValue['type'],
                'value' => $this->_getExpression($exprValue, false)
            );
        }

        $sqlInsert = array('model' => $modelName, 'table' => $source);

        if (isset($ast['fields']) === true) {
            $sqlFields = array();
            foreach ($ast['fields'] as $field) {
                //Check that inserted fields are part of the model
                if ($this->_metaData->hasAttribute($model, $field['name']) === false) {
                    throw new Exception("The model '".$modelName."' doesn't have the attribute '".$field['name']."', when preparing: ".$this->_phql);
                }

                //Add the file to the insert list
                $sqlFields[] = $field['name'];
            }

            $sqlInsert['fields'] = $sqlFields;
        }
        $sqlInsert['values'] = $exprValues;

        return $sqlInsert;
    }

    /**
     * Analyzes an UPDATE intermediate code and produces an array to be executed later
     *
     * @return array
     * @throws Exception
     */
    protected function _prepareUpdate()
    {
        $ast = $this->_ast;
        if (isset($ast['update']) === false ||
            isset($ast['update']['tables']) === false ||
            isset($ast['update']['values']) === false) {
            throw new Exception('Corrupted UPDATE AST');
        }

        $update = $ast['update'];

        //We use these arrays to store info related to models, alias and its sources. With
        //them we can rename columns later
        $models = array();
        $modelsInstances = array();
        $sqlTables = array();
        $sqlModels = array();
        $sqlAliases = array();
        $sqlAliasesModelsInstances = array();

        if (isset($update['tables'][0]) === false) {
            $updateTables = array($update['tables']);
        } else {
            $updateTables = $update['tables'];
        }

        foreach ($updateTables as $table) {
            $modelName = $table['qualifiedName']['name'];

            //Check if the table has a namespace alias
            if (isset($table['qualifiedName']['ns-alias']) === true) {
                $realModelName = $this->_manager->getNamespaceAlias($table['qualifiedName']['ns-alias']).'\\'.$modelName;
            } else {
                $realModelName = $modelName;
            }

            //Load a model instance from the models manager
            $model = $this->_manager->load($realModelName);
            $source = $model->getSource();
            $schema = $model->getSchema();

            //Create a full source representation including schema
            if ($schema == true) {
                $completeSource = array($source, $schema);
            } else {
                $completeSource = array($source, null);
            }

            //Check if table is aliased
            if (isset($table['alias']) === true) {
                $alias = $table['alias'];
                $sqlAliases[$alias] = $alias;
                $completeSource[] = $alias;
                $sqlTables[] = $completeSource;
                $sqlAliasesModelsInstances[$alias] = $model;
                $models[$alias] = $modelName;
            } else {
                $sqlAliases[$modelName] = $source;
                $sqlAliasesModelsInstances[$modelName] = $model;
                $sqlTables[] = $source;
                $models[$modelName] = $source;
            }

            $sqlModels[] = $modelName;
            $modelsInstances[$modelName] = $model;
        }

        //Update the models/aliases/sources in the object
        $this->_models = $models;
        $this->_modelsInstances = $modelsInstances;
        $this->_sqlAliases = $sqlAliases;
        $this->_sqlAliasesModelsInstances = $sqlAliasesModelsInstances;

        $sqlFields = array();
        $sqlValues = array();
        $updateValues = (isset($update['values'][0]) === false ? array($update['values']) : $update['values']);

        foreach ($updateValues as $updateValue) {
            $sqlFields[]  = $this->_getExpression($updateValue['column'], false);
            $value = array('type' => $updateValue['type'], 'value' => $this->_getExpression($updateValue['expr'], false));
        }

        $sqlUpdate = array('tables' => $sqlTables, 'models' => $sqlModels, 'fields' => $sqlFields, 'values' => $sqlValues);

        if (isset($ast['where']) === true) {
            $sqlUpdate['where'] = $this->_getExpression($ast['where'], false);
        }

        if (isset($ast['limit']) === true) {
            $sqlUpdate['limit'] = $ast['limit'];
        }

        return $sqlUpdate;
    }

    /**
     * Analyzes a DELETE intermediate code and produces an array to be executed later
     *
     * @return array
     * @throws Exception
     */
    protected function _prepareDelete()
    {
        $ast = $this->_ast;

        if (isset($ast['delete']) === false ||
            isset($ast['delete']['tables']) === false) {
            throw new Exception('Corrupted DELETE AST');
        }

        $delete = $ast['delete'];

        //We use these arrays to store info related to models, alias and its sources. With
        //them we can rename columns later
        $models = array();
        $modelsInstances = array();
        $sqlTables = array();
        $sqlModels = array();
        $sqlAliases = array();
        $sqlAliasesModelsInstances = array();

        if (isset($delete['tables'][0]) === false) {
            $deleteTables = array($delete['tables']);
        } else {
            $deleteTables = $delete['tables'];
        }

        foreach ($deleteTables as $table) {
            $modelName = $table['qualifiedName']['name'];

            //Check if the table has a namespace alias
            if (isset($table['qualifiedName']['ns-alias']) === true) {
                $realModelName = $this->_manager->getNamespaceAlias($table['qualifiedName']['ns-alias']).'\\'.$modelName;
            } else {
                $realModelName = $modelName;
            }

            //Load a model instance from the models manager
            $model = $this->_manager->load($realModelName);
            $source = $model->getSoruce();
            $schema = $model->getSchema();

            if ($schema == true) {
                $completeSource = array($source, $schema);
            } else {
                $completeSource = array($source, null);
            }

            if (isset($table['alias']) === true) {
                $alias = $table['alias'];
                $sqlAliases[$alias] = $alias;
                $completeSource[] = $alias;
                $sqlTables[] = $completeSource;
                $sqlAliasesModelsInstances[$alias] = $model;
                $models[$alias] = $modelName;
            } else {
                $sqlAliases[$modelName] = $source;
                $sqlAliasesModelsInstances[$modelName] = $model;
                $sqlTables[] = $source;
                $models[$modelName] = $source;
            }

            $sqlModels[] = $modelName;
            $modelsInstances[$modelName] = $model;
        }

        //Update the models/aliases/sources in the object
        $this->_models = $models;
        $this->_modelsInstances = $modelsInstances;
        $this->_sqlAliases = $sqlAliases;
        $this->_sqlAliasesModelsInstances = $sqlAliasesModelsInstances;

        $sqlDelete = array('tables' => $sqlTables, 'models' => $sqlModels);

        if (isset($ast['where']) === true) {
            $sqlDelete['where'] = $this->_getExpression($ast['where'], true); //@note here: "not_quoting" = true
        }

        if (isset($ast['limit']) === true) {
            $sqlDelete['limit'] = $ast['limit'];
        }

        return $sqlDelete;
    }

    /**
     * Parses the intermediate code produced by \Phalcon\Mvc\Model\Query\Lang generating another
     * intermediate representation that could be executed by \Phalcon\Mvc\Model\Query
     *
     * @return array
     * @throws Exception
     */
    public function parse()
    {
        if (is_array($this->_intermediate) === true) {
            return $this->_intermediate;
        }

        //This function parses the PHQL statement
        $ast = Lang::parsePHQL($this->_phql);

        $irPhql = null;
        $irPhqlCache = null;
        $uniqueId = null;

        if (is_array($ast) === true) {
            //Check if the prepared PHQL is already cached
            if (isset($ast['id']) === true) {
                //Parsed ASTs have a unique id
                $uniqueId = $ast['id'];
                $irPhqlCache = self::$_irPhqlCache;
                if (isset($irPhqlCache[$uniqueId]) === true) {
                    $irPhql = $irPhqlCache[$uniqueId];
                    if (is_array($irPhql) === true) {
                        //Assign the type to the query
                        $this->_type = $ast['type'];
                        return $irPhql;
                    }
                }
            }

            //A valid AST must have a type
            if (isset($ast['type']) === true) {
                $this->_ast = $ast;

                //Produce an independent database system representation
                $this->_type = $ast['type'];

                switch((int)$this->_type) {
                    case 309:
                        $irPhql = $this->_prepareSelect();
                        break;
                    case 306:
                        $irPhql = $this->_prepareInsert();
                        break;
                    case 300:
                        $irPhql = $this->_prepareUpdate();
                        break;
                    case 303:
                        $irPhql = $this->_prepareDelete();
                        break;
                    default:
                        throw new Exception('Unknown statement '.$this->_type.', when preparing: '.$this->_phql);
                }
            }
        }

        if (is_array($irPhql) === false) {
            throw new Exception('Corrupted AST');
        }

        //Store the prepared AST in the cache
        if (is_int($uniqueId) === true) {
            if (is_array($irPhqlCache) === false) {
                $irPhqlCache = array();
            }

            $irPhqlCache[$uniqueId] = $irPhql;
            self::$_irPhqlCache = $irPhqlCache;
        }

        $this->_intermediate = $irPhql;
        return $irPhql;
    }

    /**
     * Sets the cache parameters of the query
     *
     * @param array $cacheOptions
     * @return \Phalcon\Mvc\Model\Query
     * @throws Exception
     */
    public function cache($cacheOptions)
    {
        if (is_array($cacheOptions) === false) {
            throw new Exception('Invalid parameter type.');
        }

        $this->_cacheOptions = $cacheOptions;
        return $this;
    }

    /**
     * Returns the current cache options
     *
     * @param array|null
     */
    public function getCacheOptions()
    {
        return $this->_cacheOptions;
    }

    /**
     * Returns the current cache backend instance
     *
     * @return \Phalcon\Cache\BackendInterface|null
     */
    public function getCache()
    {
        return $this->_cache;
    }

    /**
     * Executes the SELECT intermediate representation producing a \Phalcon\Mvc\Model\Resultset
     *
     * @param array $intermediate
     * @param array $bindParams
     * @param array $bindTypes
     * @return \Phalcon\Mvc\Model\ResultsetInterface
     * @throws Exception
     */
    protected function _executeSelect(array $intermediate, array $bindParams, array $bindTypes)
    {
        $manager = $this->_manager;

        //Models instances is an array if the SELECT was prepared with parse
        $modelsInstances = $this->_modelsInstances;
        if (is_array($modelsInstances) === false) {
            $modelsInstances = array();
        }

        $models = $intermediate['models'];

        $numberModels = count($models);
        if ($numberModels === 1) {
            //Load first model if it is not loaded
            $modelName = $models[0];
            if (isset($modelsInstances[$modelName]) === false) {
                $model = $manager->load($modelName);
                $modelsInstances[$modelName] = $model;
            } else {
                $model = $modelsInstances[$modelName];
            }

            //The 'selectConnection' method could be implemented
            if (method_exists($model, 'selectReadConnection') === true) {
                $connection = $model->selectReadConnection($intermediate, $bindParams, $bindTypes);
                if (is_object($connection) === false) {
                    throw new Exception("'selectReadConnection' didn't returned a valid connection");
                }
            } else {
                //Get the current connection
                $connection = $model->getReadConnection();
            }
        } else {
            //Check if all the models belong to the same connection
            $connections = array();
            foreach ($models as $modelName) {
                if (isset($modelsInstances[$modelName]) === false) {
                    $model = $manager->load($modelName);
                    $modelsInstances[$modelName] = $model;
                } else {
                    $model = $modelsInstances[$modelName];
                }

                //Get the models connection
                $connection = $model->getReadConnection();

                //Get the type of connection the model is using (mysql, postgresql, etc)
                $type = $connection->getType();

                //Mark the type of connection in the connection flags
                $connections[$type] = true;
                $connectionTypes = count($connections);

                //More than one type of connection is not allowed
                if ($connectionTypes === 2) {
                    throw new Exception('Cannot use models of different database systems in the same query');
                }
            }
        }

        $columns = $intermediate['columns'];
        $haveObjects = false;
        $haveScalars = false;
        $isComplex = false;

        //Check if the resultset has objects and how many of them
        $numberObjects = 0;
        foreach ($columns as $column) {
            $columnType = $column['type'];
            if ($columnType === 'scalar') {
                if (isset($column['balias']) === false) {
                    $isComplex = true;
                }

                $haveScalars = true;
            } else {
                $haveObjects = true;
                $numberObjects++;
            }
        }

        //Check if the resultset to return is complex or simple
        if ($isComplex === false) {
            if ($haveObjects === true) {
                if ($haveScalars === true) {
                    $isComplex = true;
                } else {
                    if ($numberObjects === 1) {
                        $isSimpleStd = false;
                    } else {
                        $isComplex = true;
                    }
                }
            } else {
                $isSimpleStd = true;
            }
        }

        //Processing selected columns
        $selectedColumns = array();
        $simpleColumnMap = array();
        $metaData = $this->_metaData;

        foreach ($columns as $aliasCopy => $column) {
            $type = $column['type'];
            $sqlColumn = $column['column'];

            //Complex objects are treated in a different way
            if ($type === 'object') {
                $modelName = $column['model'];

                //Base instance
                if (isset($modelsInstances[$modelName]) === true) {
                    $instance = $modelsInstances[$modelName];
                } else {
                    $instance = $manager->load($modelName);
                    $modelsInstances[$modelName] = $instance;
                }

                $attributes = $metaData->getAttributes($instance);
                if ($isComplex === true) {
                    //If the resultset is complex we open every model into their columns
                    if (isset($GLOBALS['_PHALCON_ORM_COLUMN_RENAMING']) === true &&
                        $GLOBALS['_PHALCON_ORM_COLUMN_RENAMING'] === true) {
                        $columnMap = $metaData->getColumnMap($instance);
                    } else {
                        $columnMap = null;
                    }

                    //Add every attribute in the model to the gernerated select
                    foreach ($attributes as $attribute) {
                        $hiddenAlias = '_'.$sqlColumn.'_'.$attribute;
                        $selectColumns[] = array($attribute, $sqlColumn, $hiddenAlias);
                    }

                    //We cache required meta-data to make its future access faster
                    $columns[$aliasCopy]['instance'] = $instnace;
                    $columns[$aliasCopy]['attributes'] = $attributes;
                    $columns[$aliasCopy]['columnMap'] = $columnMap;

                    //Check if the model keeps snapshots
                    $isKeepingSnapshots = $manager->isKeepingSnapshots($instance);
                    if ($isKeepingSnapshots === true) {
                        $columns[$aliasCopy]['keepSnapshopts'] = $isKeepingSnapshots;
                    }
                } else {
                    //Query only the columns that are registered as attributes in the metaData
                    foreach ($attributes as $attribute) {
                        $selectColumns[] = array($attribute, $sqlColumn);
                    }
                }
            } else {
                //Create an alias if the column doesn't have one
                if (is_int($aliasCopy) === true) {
                    $columnAlias = array($sqlColumn, null);
                } else {
                    $columnAlias = array($sqlColumn, null, $aliasCopy);
                }

                $selectColumns[] = $columnAlias;
            }

            //Simulate a column map
            if ($isComplex === false) {
                if ($isSimpleStd === true) {
                    if (isset($column['sqlAlias']) === true) {
                        $simpleColumnMap[$column['sqlAlias']] = $aliasCopy;
                    } else {
                        $simpleColumnMap[$aliasCopy] = $aliasCopy;
                    }
                }
            }
        }

        unset($columns);
        $intermediate['columns'] = $selectColumns;

        //The corresponding SQL dialect generates the SQL statement based on
        //the database system
        $dialect = $connection->getDialect();
        $sqlSelect = $dialect->select($intermediate);

        //Replace the placeholders
        if (is_array($bindParams) === true) {
            $processed = array();
            foreach ($bindParams as $wildcard => $value) {
                if (is_int($wildcard) === true) {
                    $processed[':'.$wildcard] = $value;
                } else {
                    $processed[$wildcard] = $value;
                }
            }
        } else {
            $processed = $bindParams;
        }

        //Replace the bind types
        if (is_array($bindTypes) === true) {
            $processedTypes = array();
            foreach ($bindTypes as $typeWildcard => $value) {
                if (is_int($typeWildcard) === true) {
                    $processedTypes[':'.$typeWildcard] = $value;
                } else {
                    $processedTypes[$typeWildcard] = $value;
                }
            }
        } else {
            $processedTypes = $bindTypes;
        }

        //Execute the query
        $result = $connection->query($sqlSelect, $processed, $processedTypes);

        //Choose a resultset type
        if ($isComplex === false) {
            //Select the base object
            if ($isSimpleStd === true) {
                //If the resultset is a simple standard object, use a Phalcon\Mvc\Model\Row as base
                $resultObject = new Row();

                //Standard objects can't keep snapshots
                $isKeepingSnapshots = false;
            } else {
                $resultObject = $model;

                //Get the column map
                $simpleColumnMap = $metaData->getColumnMap($model);

                //Check if the model keeps snapshots
                $isKeepingSnapshots = $manger->isKeepingSnapshots($model);
            }
        }

        //Simple resultsets contain only complete objects
        return new Simple(
            $simpleColumnMap,
            $resultObject,
            ($result->numRows($result) > 0 ? $result : false),
            $this->_cache,
            $isKeepingSnapshots
        );
    }

    /**
     * Executes the INSERT intermediate representation producing a \Phalcon\Mvc\Model\Query\Status
     *
     * @param array $intermediate
     * @param array $bindParams
     * @param array $bindTypes
     * @return \Phalcon\Mvc\Model\Query\StatusInterface
     * @throws Exception
     */
    protected function _executeInsert(array $intermediate, array $bindParams, array $bindTypes)
    {
        $manager = $this->_manager;

        $modelName = $intermediate['model'];
        $modelsInstances = $this->_modelsInstances;

        if (isset($modelsInstances[$modelName]) === true) {
            $model = $modelsInstances[$modelName];
        } else {
            $model = $manager->load($modelName);
        }

        //Get the model connection
        $connection = $model->getWriteConnection();
        $automaticFields = false;

        //The 'fields' index may already have the fields to be used in the query
        if (isset($intermediate['fields']) === tue) {
            $fields = $intermediate['fields'];
        } else {
            $automaticFields = true;
            $fields = $this->_metaData->getAttributes($model);
            if (isset($GLOBALS['_PHALCON_ORM_COLUMN_RENAMING']) === true &&
                $GLOBALS['_PHALCON_ORM_COLUMN_RENAMING'] === true) {
                $columnMap = $this->_metaData->getColumnMap($model);
            } else {
                $columnMap = null;
            }
        }

        //The number of calculated values must be equal to the number of fields in the
        //model
        if (count($fields) !== count($intermediate['values'])) {
            throw new Exception('The column count does not match the values count');
        }

        //Get the dialect to resolve the SQL expressions
        $dialect = $connection->getDialect();
        $notExists = false;
        $insertValues = array();

        foreach ($intermediate['values'] as $number => $value) {
            switch((int)$value['type']) {
                case 260:
                case 258:
                case 259:
                    $insertValue = $dialect->getSqlExpression($value['value']);
                    break;
                case 332:
                    $insertValue = null;
                    break;
                case 273:
                case 274:
                    if (is_array($bindParams) === false) {
                        throw new Exception('Bound parameter cannot be replaced because placeholders is not an array');
                    }

                    $wildcard = str_replace(':', '', $dialect->getSqlExpression($value['value']));
                    if (isset($bindParams[$wildcard]) === false) {
                        throw new Exception("Bound parameter '".$wildcard."' cannot be replaced because it isn't in the placeholder list");
                    }

                    $insertValue = $bindParams[$wildcard];
                    break;
                default:
                    $insertValue = new RawValue($dialect->getSqlExpression($value['value']));
                    break;
            }

            $fieldName = $fields[$number];

            //If the user didn't defined a column list we assume all the model's attributes are columns
            if ($automaticFields === true) {
                if (is_array($columnMap) === true) {
                    if (isset($columnMap[$fieldName]) === true) {
                        $attributeName = $columnMap[$fieldName];
                    } else {
                        throw new Exception("Column '".$fieldName."\" isn't part of the column map");
                    }
                } else {
                    $attributeName = $fieldName;
                }
            } else {
                $attributeName = $fieldName;
            }

            $insertValues[$attributeName] = $insertValue;
        }

        //Get a base model from the models manager
        $baseModel = $manager->load($modelName);

        //Clone the base model
        $insertModel = clone $baseModel;

        //Call 'create' to ensure that an insert is performed
        //Return the insertation status
        return new Status($insertModel->create($insertValues), $insertModel);
    }

    /**
     * Query the records on which the UPDATE/DELETE operation well be done
     *
     * @param \Phalcon\Mvc\Model $model
     * @param array $intermediate
     * @param array $bindParams
     * @param array $bindTypes
     * @return \Phalcon\Mvc\Model\ResultsetInterface
     */
    protected function _getRelatedRecords(Model $model, array $intermediate, array $bindParams, array $bindTypes)
    {
        $selectColumns = array(array(array('type' => 'object', 'model' => get_class($model), 'column' => $model->getSource())));

        //Instead of creating a PHQL string statement, we manually create the IR representation
        $selectIr = array('columns' => $selectColumns, 'models' => $intermediate['models'], 'tables' => $intermediate['tables']);

        //Check if a WHERE clause was especified
        if (isset($intermediate['where']) === true) {
            $selectIr['where'] = $intermediate['where'];
        }

        if (isset($intermediate['limit']) === true) {
            $selectIr['limit'] = $intermediate['limit'];
        }

        //We create another Phalcon\Mvc\Model\Query to get the related records
        $query = new Query();
        $query->setDi($this->_dependencyInjector);
        $query->setType(309);
        $query->setIntermediate($selectIr);
        return $query->execute($bindParams, $bindTypes);
    }

    /**
     * Executes the UPDATE intermediate representation producing a \Phalcon\Mvc\Model\Query\Status
     *
     * @param array $intermediate
     * @param array $bindParams
     * @param array $bindTypes
     * @return \Phalcon\Mvc\Model\Query\StatusInterface
     * @throws Exception
     */
    protected function _executeUpdate(array $itermediate, array $bindParams, array $bindTypes)
    {
        //Get the model_name
        $models = $itermediate['models'];
        if (isset($models[1]) === true) {
            throw new Exception('Updating several models at the same time is still not supported');
        }
        $modelName = $models[0];

        //Load the model from the modelsManager or from the _modelsInstances property
        if (isset($this->_modelsInstances[$modelName]) === true) {
            $model = $this->_modelsInstances[$modelName];
        } else {
            $model = $this->_manager->load($modelName);
        }

        //Get the connection
        $connection = $model->getWriteConnection();
        $dialect = $connection->getDialect();

        //update_values is applied to every record
        $fields = $intermediate['fields'];
        $values = $intermediate['values'];
        $updateValues = array();

        //If a placeholder is unused in the update values, we assume that it's used in the
        //SELECT
        $selectBindParams = $bindParams;
        $selectBindTypes = $bindTypes;

        //Loop through fields
        foreach ($fields as $number => $field) {
            $fieldName = $field['name'];
            $value = $values[$number];

            switch((int)$value['type']) {
                case 260:
                case 258:
                case 259:
                    $updateValue = $dialect->getSqlExpression($value['value']);
                    break;
                case 322:
                    $updateValue = null;
                    break;
                case 273:
                case 274:
                    if (is_array($bindParams) === false) {
                        throw new Exception('Bound parameter cannot be replaced because placeholders is not an array');
                    }

                    $wildcard = str_replace(':', '', $dialect->getSqlExpression($value['value']));
                    if (isset($bindParams[$wildcard]) === true) {
                        $updateValue = $bindParams[$wildcard];
                        unset($selectBindParams[$wildcard]);
                        unset($selectBindTypes[$wildcard]);
                    } else {
                        throw new Exception("Bound parameter '".$wildcard."' cannnot be replaced because it's not in the placeholders list");
                    }
                    break;
                default:
                    $updateValue = new RawValue($dialect->getSqlExpression($value['value']));
                    break;
            }
        }

        //We need to query the records related to the update
        $records = $this->_getRelatedRecords($model, $intermediate, $selectBindParams, $selectBindTypes);

        //If there are no records to apple the update, we return success
        if (count($records) == 0) {
            return new Status(true, null);
        }

        //@note we don't need to get the write connection here again

        //Create a transaction in the write connection
        $connection->begin();
        $records->rewind();

        while ($records->valid() !== false) {
            //Get the current record in the iterator
            $record = $records->current();

            //We apply the executed values to every record found
            if ($record->update($updateValues) !== true) {
                //Rollback the transaction on failure
                $connection->rollback();
                return new Status(false, $record);
            }

            //Move the cursor to the next record
            $records->next();
        }

        //Commit transaction on success
        $connection->commit();
        return new Status(true, null);
    }

    /**
     * Executes the DELETE intermediate representation producing a \Phalcon\Mvc\Model\Query\Status
     *
     * @param array $intermediate
     * @param array $bindParams
     * @param array $bindTypes
     * @return \Phalcon\Mvc\Model\Query\StatusInterface
     * @throws Exception
     */
    protected function _executeDelete(array $itermediate, array $bindParams, array $bindTypes)
    {
        $models = $intermediate['models'];
        if (isset($models[1]) === true) {
            throw new Exception('Delete from several models at the same time is still not supported');
        }

        $modelName = $models[0];

        //Load the model from the modelsManager or from the _modelsInstances property
        if (isset($this->_modelsInstances[$modelName]) === true) {
            $model = $modelsInstances[$modelName];
        } else {
            $model = $this->_manager->load($modelName);
        }

        //Get the records to be deleted
        $records = $this->_getRelatedRecords($model, $intermediate, $bindParams, $bindTypes);

        //If there are no records to delete we return success
        if (count($records) == 0) {
            return new Status(true, null);
        }

        //Create a transaction in the write connection
        $connection = $model->getWriteConnection();
        $connection->begin();
        $records->rewind();

        while ($records->valid() !== false) {
            $record = $records->current();

            //We delete every record found
            if ($record->delete() !== true) {
                //Rollback the transaction
                $connection->rollback();
                return new Status(false, $record);
            }

            //Move the cursor to the next record
            $records->next();
        }

        //Commit the transaction
        $connection->commit();

        //Create a status to report the deletion status
        return new Status(true, null);
    }

    /**
     * Executes a parsed PHQL statement
     *
     * @param array|null $bindParams
     * @param array|null $bindTypes
     * @return mixed
     * @throws Exception
     */
    public function execute($bindParams = null, $bindTypes = null)
    {
        if ((is_array($bindParams) === false &&
            is_null($bindParams) === false) ||
            (is_array($bindTypes) === false &&
                is_null($bindTypes) === false)) {
            throw new Exception('Invalid parameter type.');
        }

        /* GET THE CACHE */
        $cacheOptions = $this->_cacheOptions;
        if (is_null($cacheOptions) === false) {
            if (is_array($cacheOptions) === false) {
                throw new Exception('Invalid caching options');
            }

            //The user must set a cache key
            if (isset($cacheOptions['key']) === true) {
                $key = $cacheOptions['key'];
            } else {
                throw new Exception('A cache key must be provided to identify the cached resultset in the cache backend');
            }

            //By default use 3600 seconds (1 hour) as cache lifetime
            if (isset($cacheOptions['lifetime']) === true) {
                $lifetime = $cacheOptions['lifetime'];
            } else {
                $lifetime = 3600;
            }

            //'modelsCache' is the default name for the models cache service
            if (isset($cacheOptions['service']) === true) {
                $cacheService = $cacheOptions['service'];
            } else {
                $cacheService = 'modelsCache';
            }

            $cache = $this->_dependencyInjector->getShared($cacheService);
            if (is_object($cache) === false) {
            //@note no interface validation
                throw new Exception('The cache service must be an object');
            }

            $result = $cache->get($key, $lifetime);
            if (is_null($result) === false) {
                if (is_object($result) === false) {
                    throw new Exception("The cache didn't return a valid resultset"); //@note (sic!)
                }

                $result->setIsFresh(false);

                //Check if only the first two rows must be returned
                if ($this->_uniqueRow == true) {
                    $preparedResult = $result->getFirst();
                } else {
                    $preparedResult = $result;
                }

                return $preparedResult;
            }

            $this->_cache = $cache;
        }

        //The statement is parsed from its PHQL string or a previously processed IR
        $intermediate = $this->parse();

        //Check for default bind parameters and merge them with the passed ones
        $defaultBindParams = $this->_bindParams;
        if (is_array($defaultBindParams) === true) {
            if (is_array($bindParams) === true) {
                $mergedParams = array_merge($defaultBindParams, $bindParams);
            } else {
                $mergedParams = $defaultBindParams;
            }
        } else {
            $mergedParams = $bindParams;
        }

        //Check for default bind types and merge them with the passed onees
        $defaultBindTypes = $this->_bindTypes;
        if (is_array($defaultBindTypes) === true) {
            if (is_array($bindTypes) === true) {
                $mergedTypes = array_merge($defaultBindTypes, $bindTypes);
            } else {
                $mergedTypes = $defaultBindTypes;
            }
        } else {
            $mergedTypes = $bindTypes;
        }

        switch((int)$this->_type) {
            case 309:
                $result = $this->_executeSelect($intermediate, $mergedParams, $mergedTypes);
                break;
            case 306:
                $result = $this->_executeInsert($intermediate, $mergedParams, $mergedTypes);
                break;
            case 300:
                $result = $this->_executeUpdate($intermediate, $mergedParams, $mergedTypes);
                break;
            case 303:
                $result = $this->_executeDelete($intermediate, $mergedParams, $mergedTypes);
                break;
            default:
                throw new Exception('Unknown statement '.$this->_type);
                break;
        }

        //We store the resultset in the cache if any
        if (is_null($cacheOptions) === false) {
            //Only PHQL SELECTs can be cached
            if ($type !== 309) {
                throw new Exception('Only PHQL statements return resultsets can be cached');
            }

            $cache->save($key, $result, $lifetime);
        }

        //Check if only the first row must be returned
        if ($this->_uniqueRow == true) {
            return $result->getFirst();
        } else {
            return $result;
        }
    }

    /**
     * Executes the query returning the first result
     *
     * @param array|null $bindParams
     * @param array|null $bindTypes
     * @return \Phalcon\Mvc\ModelInterface
     */
    public function getSingleResult($bindParams = null, $bindTypes = null)
    {
        //The query is already programmed to return just one row
        if ($this->_uniqueRow == true) {
            return $this->execute($bindParams, $bindTypes);
        }

        //return $this->execute($bindParams, $bindTypes)->getFirst();
        return $this->execute($bindParams, $bindTypes); //@note this is wrong, "first_result" should be returned instead
    }

    /**
     * Sets the type of PHQL statement to be executed
     *
     * @param int $type
     * @return \Phalcon\Mvc\Model\Query
     * @throws Exception
     */
    public function setType($type)
    {
        if (is_int($type) === false) {
            throw new Exception('Invalid parameter type.');
        }

        $this->_type = $type;
        return $this;
    }

    /**
     * Gets the type of PHQL statement executed
     *
     * @return int|null
     */
    public function getType()
    {
        return $this->_type;
    }

    /**
     * Set default bind parameters
     *
     * @param array $bindParams
     * @return \Phalcon\Mvc\Model\Query
     * @throws Exception
     */
    public function setBindParams($bindParams)
    {
        if (is_array($bindParams) === false) {
            throw new Exception('Bind parameters must be an array');
        }

        $this->_bindParams = $bindParams;

        return $this;
    }

    /**
     * Returns default bind params
     *
     * @return array|null
     */
    public function getBindParams()
    {
        return $this->bindParams;
    }

    /**
     * Set default bind parameters
     *
     * @param array $bindTypes
     * @return \Phalcon\Mvc\Model\Query
     * @throws Exception
     */
    public function setBindTypes($bindTypes)
    {
        if (is_array($bindTypes) === false) {
            throw new Exception('Bind types must be an array');
        }

        $this->_bindTypes = $bindTypes;

        return $this;
    }

    /**
     * Returns default bind types
     *
     * @return array|null
     */
    public function getBindTypes()
    {
        return $this->_bindTypes;
    }

    /**
     * Allows to set the IR to be executed
     *
     * @param array $intermediate
     * @return \Phalcon\Mvc\Model\Query
     * @throws Exception
     */
    public function setIntermediate($intermediate)
    {
        if (is_array($intermediate) === false) {
            throw new Exception('Invalid parameter type.');
        }

        $this->_intermediate = $intermediate;

        return $this;
    }

    /**
     * Returns the intermediate representation of the PHQL statement
     *
     * @return array|null
     */
    public function getIntermediate()
    {
        return $this->_intermediate;
    }
}
