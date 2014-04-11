<?php
/**
 * Assets Collection
 *
 * @author Andres Gutierrez <andres@phalconphp.com>
 * @author Eduar Carvajal <eduar@phalconphp.com>
 * @author Wenzel Pünter <wenzel@phelix.me>
 * @version 1.2.6
 * @package Phalcon
*/
namespace Phalcon\Assets;

use \Countable,
	\Iterator,
	\Traversable,
	\Phalcon\Assets\Exception,
	\Phalcon\Assets\Resource,
	\Phalcon\Assets\Resource\Css,
	\Phalcon\Assets\Resource\Js,
	\Phalcon\Assets\FilterInterface;

/**
 * Phalcon\Assets\Collection
 *
 * Represents a collection of resources
 * 
 * @see https://github.com/phalcon/cphalcon/blob/1.2.6/ext/assets/collection.c
 */
class Collection implements Countable, Iterator, Traversable
{
	/**
	 * Prefix
	 * 
	 * @var null|string
	 * @access protected
	*/
	protected $_prefix = null;

	/**
	 * Local
	 * 
	 * @var boolean
	 * @access protected
	*/
	protected $_local = true;

	/**
	 * Resources
	 * 
	 * @var null|array
	 * @access protected
	*/
	protected $_resources = null;

	/**
	 * Position
	 * 
	 * @var null|int
	 * @access protected
	*/
	protected $_position = null;

	/**
	 * Filters
	 * 
	 * @var null|array
	 * @access protected
	*/
	protected $_filters = null;

	/**
	 * Attributes
	 * 
	 * @var null|array
	 * @access protected
	*/
	protected $_attributes = null;

	/**
	 * Join?
	 * 
	 * @var boolean
	 * @access protected
	*/
	protected $_join = true;

	/**
	 * Target URI
	 * 
	 * @var null|string
	 * @access protected
	*/
	protected $_targetUri = null;

	/**
	 * Target Path
	 * 
	 * @var null|string
	 * @access protected
	*/
	protected $_targetPath = null;

	/**
	 * Source Path
	 * 
	 * @var null|string
	 * @access protected
	*/
	protected $_sourcePath = null;

	/**
	 * Adds a resource to the collection
	 *
	 * @param \Phalcon\Assets\Resource $resource
	 * @return \Phalcon\Assets\Collection
	 * @throws Exception
	 */
	public function add($resource)
	{
		if(is_object($resource) === false ||
			$resource implements Resource === false) {
			throw new Exception('Resource must be an object');
		}

		$this->_resources[] = $resource;

		return $this;
	}

	/**
	 * Adds a CSS resource to the collection
	 *
	 * @param string $path
	 * @param boolean|null $local
	 * @param boolean|null $filter
	 * @param array|null $attributes
	 * @return \Phalcon\Assets\Collection
	 * @throws Exception
	 */
	public function addCss($path, $local = null, $filter = null, $attributes = null)
	{
		/* Type check */
		if(is_string($path) === false) {
			throw new Exception('Invalid parameter type.');
		}

		if(is_null($filter) === true) {
			$filter = true;
		} elseif(is_bool($filter) === false) {
			throw new Exception('Invalid parameter type.');
		}
		/* Create Object */
		if(is_array($this->_resources) === false) {
			$this->_resources = array();
		}
		
		$this->_resources[] = new Css($path, 
			(is_bool($local) === true ? $local : $this->_local), $filter, 
			(is_array($attributes) === true ?  $attributes : $this->_attributes));

		return $this;
	}

	/**
	 * Adds a javascript resource to the collection
	 *
	 * @param string $path
	 * @param boolean|null $local
	 * @param boolean|null $filter
	 * @param array|null $attributes
	 * @return \Phalcon\Assets\Collection
	 * @throws Exception
	 */
	public function addJs($path, $local = null, $filter = null, $attributes = null)
	{
		/* Type check */
		if(is_string($path) === false) {
			throw new Exception('Invalid parameter type.');
		}

		if(is_null($filter) === true) {
			$filter = true;
		} elseif(is_bool($filter) === false) {
			throw new Exception('Invalid parameter type.');
		}

		/* Create object */
		if(is_array($this->_resources) === false) {
			$this->_resources = array();
		}

		$this->_resources[] = new Js($path, 
			(is_bool($local) === true ? $local : $this->_local), $filter,
			(is_array($attributes) === true ? $attributes : $this->_attributes));

		return $this;
	}

	/**
	 * Returns the resources as an array
	 *
	 * @return \Phalcon\Assets\Resource[]
	 */
	public function getResources()
	{
		return (is_array($this->_resources) === true ? $this->_resources : array());
	}

	/**
	 * Returns the number of elements in the form
	 *
	 * @return int
	 */
	public function count()
	{
		if(is_array($this->_resources) === false) {
			$this->_resources = array();
		}

		return count($this->_resources);
	}

	/**
	 * Rewinds the internal iterator
	 */
	public function rewind()
	{
		$this->_position = 0;
	}

	/**
	 * Returns the current resource in the iterator
	 *
	 * @return \Phalcon\Assets\Resource|null
	 */
	public function current()
	{
		if(is_array($this->_resources) === false) {
			$this->_resources = array();
		}

		if(is_int($this->_position) === false) {
			$this->_position = 0;
		}

		if(isset($this->_resources[$this->_position]) ? 
			$this->_resources[$this->_position] : null);
	}

	/**
	 * Returns the current position/key in the iterator
	 *
	 * @return int
	 */
	public function key()
	{
		if(is_int($this->_position) === false) {
			$this->_position = 0;
		}

		return $this->_position;
	}

	/**
	 * Moves the internal iteration pointer to the next position
	 *
	 */
	public function next()
	{
		if(is_int($this->_position) === false) {
			$this->_position = 0;
		}

		$this->_position++;
	}

	/**
	 * Check if the current element in the iterator is valid
	 *
	 * @return boolean
	 */
	public function valid()
	{
		if(is_int($this->_position) === false) {
			$this->_position = 0;
		}

		if(is_array($this->_resources) === false) {
			$this->_resources = array();
		}

		return isset($this->_resources[$this->_position]);
	}

	/**
	 * Sets the target path of the file for the filtered/join output
	 *
	 * @param string $targetPath
	 * @return \Phalcon\Assets\Collection
	 * @throws Exception
	 */
	public function setTargetPath($targetPath)
	{
		if(is_string($targetPath) === false) {
			throw new Exception('Invalid parameter type.');
		}

		$this->_targetPath = $targetPath;

		return $this;
	}

	/**
	 * Returns the target path of the file for the filtered/join output
	 *
	 * @return string|null
	 */
	public function getTargetPath()
	{
		return $this->_targetPath;
	}

	/**
	 * Sets a base source path for all the resources in this collection
	 *
	 * @param string $sourcePath
	 * @return \Phalcon\Assets\Resource
	 * @throws Exception
	 */
	public function setSourcePath($sourcePath)
	{
		if(is_string($sourcePath) === false) {
			throw new Exception('Invalid parameter type.');
		}

		$this->_sourcePath = $sourcePath;

		return $this;
	}

	/**
	 * Returns the base source path for all the resources in this collection
	 *
	 * @return string|null
	 */
	public function getSourcePath()
	{
		return $this->_sourcePath;
	}

	/**
	 * Sets a target uri for the generated HTML
	 *
	 * @param string $targetUri
	 * @return \Phalcon\Assets\Resource
	 * @throws Exception
	 */
	public function setTargetUri($targetUri)
	{
		if(is_string($targetUri) === false) {
			throw new Exception('Invalid parameter type.');
		}

		$this->_targetUri = $targetUri;

		return $this;
	}

	/**
	 * Returns the target uri for the generated HTML
	 *
	 * @return string|null
	 */
	public function getTargetUri()
	{
		return $this->_targetUri;
	}

	/**
	 * Sets a common prefix for all the resources
	 *
	 * @param string $prefix
	 * @return \Phalcon\Assets\Collection
	 * @throws Exception
	 */
	public function setPrefix($prefix)
	{
		if(is_string($prefix) === false) {
			throw new Exception('Invalid parameter type.');
		}

		$this->_prefix = $prefix;

		return $this;
	}

	/**
	 * Returns the prefix
	 *
	 * @return string|null
	 */
	public function getPrefix()
	{
		return $this->_prefix;
	}

	/**
	 * Sets if the collection uses local resources by default
	 *
	 * @param boolean $local
	 * @return \Phalcon\Assets\Collection
	 * @throws Exception
	 */
	public function setLocal($local)
	{
		if(is_bool($local) === false) {
			throw new Exception('Invalid parameter type.');
		}

		$this->_local = $local;
	}

	/**
	 * Returns if the collection uses local resources by default
	 *
	 * @return boolean
	 */
	public function getLocal()
	{
		return $this->_local;
	}

	/**
	 * Sets extra HTML attributes
	 *
	 * @param array $attributes
	 * @return $this
	 * @throws Exception
	 */
	public function setAttributes($attributes)
	{
		if(is_array($attributes) === false) {
			throw new Exception('Attributes must be an array');
		}

		if(is_array($this->_attributes) === false) {
			$this->_attributes = array();
		}

		$this->_attributes = $attributes;

		return $this;
	}

	/**
	 * Returns extra HTML attributes
	 *
	 * @return array|null
	 */
	public function getAttributes()
	{
		return $this->_attributes;
	}

	/**
	 * Adds a filter to the collection
	 *
	 * @param \Phalcon\Assets\FilterInterface $filter
	 * @return \Phalcon\Assets\Collection
	 * @throws Exception
	 */
	public function addFilter($filter)
	{
		if(is_object($filter) === false || 
			$filter instanceof FilterInterface === false) {
			throw new Exception('Invalid parameter type.');
		}

		if(is_array($this->_filters) === false) {
			$this->_filters = array();
		}

		$this->_filters = $filter;

		return $this;
	}

	/**
	 * Sets an array of filters in the collection
	 *
	 * @param array $filters
	 * @return \Phalcon\Assets\Collection
	 * @throws Exception
	 */
	public function setFilters($filters)
	{
		if(is_array($filters) === false) {
			throw new Exception('Filters must be an array of filters');
		}

		if(is_array($this->_filters) === false) {
			$this->_filters = array();
		}

		$this->_filters = $filters;

		return $this;
	}

	/**
	 * Returns the filters set in the collection
	 *
	 * @return array|null
	 */
	public function getFilters()
	{
		return $this->_filters;
	}

	/**
	 * Sets if all filtered resources in the collection must be joined in a single result file
	 *
	 * @param boolean $join
	 * @return \Phalcon\Assets\Collection
	 * @throws Exception
	 */
	public function join($join)
	{
		if(is_bool($join) === false) {
			throw new Exception('Invalid parameter type.');
		}

		$this->_join = $join;

		return $this;
	}

	/**
	 * Returns if all the filtered resources must be joined
	 *
	 * @return boolean
	 */
	public function getJoin()
	{
		return $this->_join;
	}

	/**
	 * Returns the complete location where the joined/filtered collection must be written
	 *
	 * @param string $basePath
	 * @return string
	 * @throws Exception
	 */
	public function getRealTargetPath($basePath = null)
	{
		if(is_null($basePath) === true) {
			$basePath = '';
		} elseif(is_string($basePath) === false) {
			throw new Exception('Invalid parameter type.');
		}

		//A base path for resources can be set in the assets manager
		$complete_path = $basePath.$this->_targetPath;

		//Get the real template path, the target path can optionally don't exist
		if(file_exists($complete_path) === true) {
			return realpath($complete_path);
		}

		return $complete_path;
	}
}