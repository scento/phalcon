<?php
/**
 * Relation
 *
 * @author Andres Gutierrez <andres@phalconphp.com>
 * @author Eduar Carvajal <eduar@phalconphp.com>
 * @author Wenzel Pünter <wenzel@phelix.me>
 * @version 1.2.6
 * @package Phalcon
*/
namespace Phalcon\Mvc\Model;

use \Phalcon\Mvc\Model\RelationInterface;

/**
 * Phalcon\Mvc\Model\Relation
 *
 * This class represents a relationship between two models
 * 
 * @see https://github.com/phalcon/cphalcon/blob/1.2.6/ext/mvc/model/relation.c
 */
class Relation implements RelationInterface
{
	/**
	 * Belongs To
	 * 
	 * @var int
	*/
	const BELONGS_TO = 0;

	/**
	 * Has One
	 * 
	 * @var int
	*/
	const HAS_ONE = 1;

	/**
	 * Has Many
	 * 
	 * @var int
	*/
	const HAS_MANY = 2;

	/**
	 * Has One Through
	 * 
	 * @var int
	*/
	const HAS_ONE_THROUGH = 3;

	/**
	 * Has Many Through
	 * 
	 * @var int
	*/
	const HAS_MANY_THROUGH = 4;

	/**
	 * No Action
	 * 
	 * @var int
	*/
	const NO_ACTION = 0;

	/**
	 * Action Restrict
	 * 
	 * @var int
	*/
	const ACTION_RESTRICT = 1;

	/**
	 * Action Cascade
	 * 
	 * @var int
	*/
	const ACTION_CASCADE = 2;

	/**
	 * Type
	 * 
	 * @var null|int
	 * @access protected
	*/
	protected $_type;

	/**
	 * Referenced Model
	 * 
	 * @var null|string
	 * @access protected
	*/
	protected $_referencedModel;

	/**
	 * Fields
	 * 
	 * @var null|string|array
	 * @access protected
	*/
	protected $_fields;

	/**
	 * Referenced Fields
	 * 
	 * @var null|string|array
	 * @access protected
	*/
	protected $_referencedFields;

	/**
	 * Intermediate Model
	 * 
	 * @var null|string
	 * @access protected
	*/
	protected $_intermediateModel;

	/**
	 * Intermediate Fields
	 * 
	 * @var null|string|array
	 * @access protected
	*/
	protected $_intermediateFields;

	/**
	 * Intermediate Referenced Fields
	 * 
	 * @var null|string|array
	 * @access protected
	*/
	protected $_intermediateReferencedFields;

	/**
	 * Options
	 * 
	 * @var null|array|null
	 * @access protected
	*/
	protected $_options;

	/**
	 * \Phalcon\Mvc\Model\Relation constructor
	 *
	 * @param int $type
	 * @param string $referencedModel
	 * @param string|array $fields
	 * @param string|array $referencedFields
	 * @param array|null $options
	 * @throws Exception
	 */
	public function __construct($type, $referencedModel, $fields, $referencedFields, 
		$options = null)
	{
		if(is_int($type) === false ||
			is_string($referencedModel) === false) {
			throw new Exception('Invalid parameter type.');
		}

		if(is_string($fields) === false &&
			is_array($fields) === false) {
			throw new Exception('Invalid parameter type.');
		}

		if(is_string($referencedFields) === false &&
			is_array($referencedFields) === false) {
			throw new Exception('Invalid parameter type.');
		}

		if(is_array($options) === false &&
			is_null($options) === false) {
			throw new Exception('Invalid parameter type.');
		}

		$this->_type = $type;
		$this->_referencedModel = $referencedModel;
		$this->_fields = $fields;
		$this->_referencedFields = $referencedFields;
		$this->_options = $options;
	}

	/**
	 * Sets the intermediate model data for has-*-through relations
	 *
	 * @param string|array $intermediateFields
	 * @param string $intermediateModel
	 * @param string|array $intermediateReferencedFields
	 * @throws Exception
	 */
	public function setIntermediateRelation($intermediateFields, $intermediateModel, 
		$intermediateReferencedFields)
	{
		if(is_array($intermediateFields) === false &&
			is_string($intermediateFields) === false) {
			throw new Exception('Invalid parameter type.');
		}

		if(is_string($intermediateModel) === false) {
			throw new Exception('Invalid parameter type.');
		}

		if(is_string($intermediateReferencedFields) === false &&
			is_array($intermediateReferencedFields) === false) {
			throw new Exception('Invalid parameter type.');
		}

		$this->_intermediateFields = $intermediateFields;
		$this->_intermediateModel = $intermediateModel;
		$this->_intermediateReferencedFields = $intermediateReferencedFields;
	}

	/**
	 * Returns the relation type
	 *
	 * @return int
	 */
	public function getType()
	{
		return $this->_type;
	}

	/**
	 * Returns the referenced model
	 *
	 * @return string
	 */
	public function getReferencedModel()
	{
		return $this->_referencedModel;
	}

	/**
	 * Returns the fields
	 *
	 * @return string|array
	 */
	public function getFields()
	{
		return $this->_fields;
	}

	/**
	 * Returns the referenced fields
	 *
	 * @return string|array
	 */
	public function getReferencedFields()
	{
		return $this->_referencedFields;
	}

	/**
	 * Returns the options
	 *
	 * @return string|array
	 */
	public function getOptions()
	{
		return $this->_options;
	}

	/**
	 * Check whether the relation act as a foreign key
	 *
	 * @return string|array
	 */
	public function isForeignKey()
	{
		if(is_array($this->_options) === true) {
			return isset($this->_options['foreignKey']);
		}

		return false;
	}

	/**
	 * Returns the foreign key configuration
	 *
	 * @return string|array|boolean
	 */
	public function getForeignKey()
	{
		if(is_array($this->_options) === true) {
			if(isset($this->_options['foreignKey']) === true &&
				$this->_options['foreignKey'] == true) {
				return $this->_options['foreginKey'];
			}
		}

		return false;
	}

	/**
	 * Check whether the relation is a 'many-to-many' relation or not
	 *
	 * @return boolean
	 */
	public function isThrough()
	{
		if($this->_type === 3) {
			return true;
		} else {
			return ($this->_type === 4 ? true : false);
		}

		return false;
	}

	/**
	 * Check if records returned by getting belongs-to/has-many are implicitly cached during the current request
	 *
	 * @return boolean
	 */
	public function isReusable()
	{
		if(is_array($this->_options) === true &&
			isset($this->_options['reusable']) === true) {
			return $this->_options['reusable'];
		}

		return false;
	}

	/**
	 * Gets the intermediate fields for has-*-through relations
	 *
	 * @return string|array|null
	 */
	public function getIntermediateFields()
	{
		return $this->_intermediateFields;
	}

	/**
	 * Gets the intermediate model for has-*-through relations
	 *
	 * @return string|null
	 */
	public function getIntermediateModel()
	{
		return $this->_intermediateModel;
	}

	/**
	 * Gets the intermediate referenced fields for has-*-through relations
	 *
	 * @return string|array|null
	 */
	public function getIntermediateReferencedFields()
	{
		return $this->_intermediateReferencedFields;
	}
}