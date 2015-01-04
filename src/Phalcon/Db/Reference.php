<?php
/**
 * Reference
 *
 * @author Andres Gutierrez <andres@phalconphp.com>
 * @author Eduar Carvajal <eduar@phalconphp.com>
 * @author Wenzel Pünter <wenzel@phelix.me>
 * @version 1.2.6
 * @package Phalcon
*/
namespace Phalcon\Db;

use \Phalcon\Db\ReferenceInterface;
use \Phalcon\Db\Exception;

/**
 * Phalcon\Db\Reference
 *
 * Allows to define reference constraints on tables
 *
 *<code>
 *  $reference = new Phalcon\Db\Reference("field_fk", array(
 *      'referencedSchema' => "invoicing",
 *      'referencedTable' => "products",
 *      'columns' => array("product_type", "product_code"),
 *      'referencedColumns' => array("type", "code")
 *  ));
 *</code>
 *
 * @see https://github.com/phalcon/cphalcon/blob/1.2.6/ext/db/reference.c
 */
class Reference implements ReferenceInterface
{
    /**
     * Schema Name
     *
     * @var null|string
     * @access protected
    */
    protected $_schemaName;

    /**
     * Referenced Schema
     *
     * @var null|string
     * @access protected
    */
    protected $_referencedSchema;

    /**
     * Reference Name
     *
     * @var null|string
     * @access protected
    */
    protected $_referenceName;

    /**
     * Referenced Table
     *
     * @var null|string
     * @access protected
    */
    protected $_referencedTable;

    /**
     * Columns
     *
     * @var null|array
     * @access protected
    */
    protected $_columns;

    /**
     * Referenced Columns
     *
     * @var null|array
     * @access protected
    */
    protected $_referencedColumns;

    /**
     * \Phalcon\Db\Reference constructor
     *
     * @param string $referenceName
     * @param array $definition
     * @throws Exception
     */
    public function __construct($referenceName, $definition)
    {
        if (is_string($referenceName) === false ||
            is_array($definition) === false) {
            throw new Exception('Invalid parameter type.');
        }

        $this->_referenceName = $referenceName;

        if (isset($definition['referencedTable']) === true) {
            $this->_referencedTable = $definition['referencedTable'];
        } else {
            throw new Exception('Referenced table is required');
        }

        if (isset($definition['columns']) === true) {
            $this->_columns = $definition['columns'];
        } else {
            throw new Exception('Foreign key columns are required');
        }

        if (isset($definition['referencedColumns']) === true) {
            $this->_referencedColumns = $definition['referencedColumns'];
        } else {
            throw new Exception('Referenced columns of the foreign key are required');
        }

        if (isset($definition['schema']) === true) {
            $this->_schemaName = $definition['schema'];
        }

        if (isset($definition['referencedSchema']) === true) {
            $this->_referencedSchema = $definition['referencedSchema'];
        }

        if (count($definition['columns']) !== count($definition['referencedColumns'])) {
            throw new Exception('Number of columns is not equals than the number of columns referenced');
        }
    }

    /**
     * Gets the index name
     *
     * @return string
     */
    public function getName()
    {
        return $this->_referenceName;
    }

    /**
     * Gets the schema where referenced table is
     *
     * @return string|null
     */
    public function getSchemaName()
    {
        return $this->_schemaName;
    }

    /**
     * Gets the schema where referenced table is
     *
     * @return string|null
     */
    public function getReferencedSchema()
    {
        return $this->_referencedSchema;
    }

    /**
     * Gets local columns which reference is based
     *
     * @return array
     */
    public function getColumns()
    {
        return $this->_columns;
    }

    /**
     * Gets the referenced table
     *
     * @return string
     */
    public function getReferencedTable()
    {
        return $this->_referencedTable;
    }

    /**
     * Gets referenced columns
     *
     * @return array
     */
    public function getReferencedColumns()
    {
        return $this->_referencedColumns;
    }

    /**
     * Restore a \Phalcon\Db\Reference object from export
     *
     * @param array $data
     * @return \Phalcon\Db\Reference
     * @throws Exception
     */
    public static function __set_state($data)
    {
        if (is_array($data) === false) {
            throw new Exception('Invalid parameter type.');
        }

        if (isset($data['_referenceName']) === false) {
            throw new Exception('_referenceName parameter is required');
        } else {
            $constraintName = $data['_referenceName'];
        }

        $definition = array();
        $definition['_referencedSchema'] = (isset($data['_referencedSchema']) === true ? $data['_referencedSchema'] : null);
        $definition['_referencedTable'] = (isset($data['_referencedTable']) === true ? $data['_referencedTable'] : null);
        $definition['_columns'] = (isset($data['_columns']) === true ? $data['_columns'] : null);
        $definition['_referencedColumns'] = (isset($data['_referencedColumns']) === true ? $data['_referencedColumns'] : null);

        return new Reference($constraintName, $definition);
    }
}
