<?php
/**
 * Resource
 *
 * @author Andres Gutierrez <andres@phalconphp.com>
 * @author Eduar Carvajal <eduar@phalconphp.com>
 * @author Wenzel Pünter <wenzel@phelix.me>
 * @version 1.2.6
 * @package Phalcon
*/
namespace Phalcon\Assets;

use \Phalcon\Assets\Exception;

/**
 * Phalcon\Assets\Resource
 *
 * Represents an asset resource
 *
 *<code>
 * $resource = new Phalcon\Assets\Resource('js', 'javascripts/jquery.js');
 *</code>
 *
 * @see https://github.com/phalcon/cphalcon/blob/1.2.6/ext/assets/manager.c
 */
class Resource
{
    /**
     * Type
     *
     * @var null|string
     * @access protected
    */
    protected $_type;

    /**
     * Path
     *
     * @var null|string
     * @access protected
    */
    protected $_path;

    /**
     * Local
     *
     * @var null|boolean
     * @access protected
    */
    protected $_local;

    /**
     * Filter
     *
     * @var null|boolean
     * @access protected
    */
    protected $_filter;

    /**
     * Attributes
     *
     * @var null|array
     * @access protected
    */
    protected $_attributes;

    /**
     * Source Path
     *
     * @var null|string
     * @access protected
    */
    protected $_sourcePath;

    /**
     * Target Path
     *
     * @var null|string
     * @access protected
    */
    protected $_targetPath;

    /**
     * Target URI
     *
     * @var null|string
     * @access protected
    */
    protected $_targetUri;

    /**
     * \Phalcon\Assets\Resource constructor
     *
     * @param string $type
     * @param string $path
     * @param boolean|null $local
     * @param boolean|null $filter
     * @param array|null $attributes
     * @throws Exception
     */
    public function __construct($type, $path, $local = null, $filter = null, $attributes = null)
    {
        /* Type check */
        if (is_string($type) === false) {
            throw new Exception('Invalid parameter type.');
        }

        if (is_string($path) === false) {
            throw new Exception('Invalid parameter type.');
        }

        if (is_null($local) === true) {
            $local = true;
        } elseif (is_bool($local) === false) {
            throw new Exception('Invalid parameter type.');
        }

        if (is_null($filter) === true) {
            $filter = true;
        } elseif (is_bool($local) === false) {
            throw new Exception('Invalid parameter type.');
        }

        /* Copy data */
        $this->_type = $type;
        $this->_path = $path;
        $this->_local = $local;
        $this->_filter = $filter;
        if (is_array($attributes) === true) {
            $this->_attributes = $attributes;
        }
    }

    /**
     * Sets the resource's type
     *
     * @param string $type
     * @return \Phalcon\Assets\Resource
     * @throws Exception
     */
    public function setType($type)
    {
        if (is_string($type) === false) {
            throw new Exception('Invalid parameter type.');
        }
        $this->_type = $type;

        return $this;
    }

    /**
     * Returns the type of resource
     *
     * @return string
     */
    public function getType()
    {
        return $this->_type;
    }

    /**
     * Sets the resource's path
     *
     * @param string $path
     * @return \Phalcon\Assets\Resource
     * @throws Exception
     */
    public function setPath($path)
    {
        if (is_string($path) === false) {
            throw new Exception('Invalid parameter type.');
        }

        $this->_path = $path;

        return $this;
    }

    /**
     * Returns the URI/URL path to the resource
     *
     * @return string
     */
    public function getPath()
    {
        return $this->_path;
    }

    /**
     * Sets if the resource is local or external
     *
     * @param boolean $local
     * @return \Phalcon\Assets\Resource
     * @throws Exception
     */
    public function setLocal($local)
    {
        if (is_bool($local) === false) {
            throw new Exception('Invalid parameter type.');
        }

        $this->_local = $local;

        return $this;
    }

    /**
     * Returns whether the resource is local or external
     *
     * @return boolean
     */
    public function getLocal()
    {
        return $this->_local;
    }

    /**
     * Sets if the resource must be filtered or not
     *
     * @param boolean $filter
     * @return \Phalcon\Assets\Resource
     * @throws Exception
     */
    public function setFilter($filter)
    {
        if (is_bool($filter) === false) {
            throw new Exception('Invalid parameter type.');
        }

        $this->_filter = $filter;

        return $this;
    }

    /**
     * Returns whether the resource must be filtered or not
     *
     * @return boolean
     */
    public function getFilter()
    {
        return $this->_filter;
    }

    /**
     * Sets extra HTML attributes
     *
     * @param array $attributes
     * @return \Phalcon\Assets\Resource
     * @throws Exception
     */
    public function setAttributes($attributes)
    {
        if (is_array($attributes) === false) {
            throw new Exception('Invalid parameter type.');
        }

        $this->_attributes = $attributes;

        return $this;
    }

    /**
     * Returns extra HTML attributes set in the resource
     *
     * @return array|null
     */
    public function getAttributes()
    {
        return $this->_attributes;
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
        if (is_string($targetUri) === false) {
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
     * Sets the resource's source path
     *
     * @param string $sourcePath
     * @return \Phalcon\Assets\Resource
     * @throws Exception
     */
    public function setSourcePath($sourcePath)
    {
        if (is_string($sourcePath) === false) {
            throw new Exception('Invalid parameter type.');
        }

        $this->_sourcePath = $sourcePath;

        return $this;
    }

    /**
     * Returns the resource's target path
     *
     * @return string|null
     */
    public function getSourcePath()
    {
        return $this->_sourcePath;
    }

    /**
     * Sets the resource's target path
     *
     * @param string $targetPath
     * @return \Phalcon\Assets\Resource
     * @throws Exception
     */
    public function setTargetPath($targetPath)
    {
        if (is_string($targetPath) === false) {
            throw new Exception('Invalid parameter type.');
        }

        $this->_targetPath = $targetPath;

        return $this;
    }

    /**
     * Returns the resource's target path
     *
     * @return string|null
     */
    public function getTargetPath()
    {
        return $this->_targetPath;
    }

    /**
     * Returns the content of the resource as an string
     * Optionally a base path where the resource is located can be set
     *
     * @param string|null $basePath
     * @return string
     * @throws Exception
     */
    public function getContent($basePath = null)
    {
        /* Type check */
        if (is_string($basePath) === false &&
            is_null($basePath) === false) {
            throw new Exception('Invalid parameter type.');
        }

        /* Get source path */
        if (empty($this->_sourcePath) === true) {
            $sourcePath = $this->_path;
        } else {
            $sourcePath = $this->_sourcePath;
        }

        //A base path for resources can be set in the assets manager
        $completePath = $basePath.$sourcePath;

        //Local resources are loaded from the local disk
        if ($local === true) {
            if (file_exists($completePath) === false) {
                throw new Exception('Resource\'s content for "'.$completePath.'" cannot be loaded');
            }
        }

        //Use file_get_contents to respect the openbase_dir
        //Access urls must be enabled
        $content = file_get_contents($completePath);

        if ($content === false) {
            throw new Exception('Resource\'s content for "'.$completePath.'" cannot be read');
        }

        return $content;
    }

    /**
     * Returns the real target uri for the generated HTML
     *
     * @return string
     */
    public function getRealTargetUri()
    {
        if (empty($this->_targetUri) === true) {
            return (string)$this->_path;
        } else {
            return (string)$this->_targetUri;
        }
    }

    /**
     * Returns the complete location where the resource is located
     *
     * @param string|null $basePath
     * @return string
     * @throws Exception
     */
    public function getRealSourcePath($basePath = null)
    {
        /* Type check */
        if (is_string($basePath) === false &&
            is_null($basePath) === false) {
            throw new Exception('Invalid parameter type.');
        }

        if (empty($this->_sourcePath) === true) {
            $sourcePath = $this->_path;
        } else {
            $sourcePath = $this->_sourcePath;
        }

        if ($this->_local === true) {
            //A base path for resources can be set in the assets manager
            $completePath = $basePath.$sourcePath;

            //Get the real template path
            return (string)realpath($completePath);
        }

        return (string)$sourcePath;
    }

    /**
     * Returns the complete location where the resource must be written
     *
     * @param string $basePath
     * @return string
     * @throws Exception
     */
    public function getRealTargetPath($basePath = null)
    {
        /* Type check */
        if (is_string($basePath) === false &&
            is_null($basePath) === false) {
            throw new Exception('Invalid parameter type.');
        }

        $targetPath = (empty($this->_targetPath) === false ? $this->_targetPath : $this->_path);

        if ($this->_local === true) {
            //A base path for resource can be set in the assets manager
            $completePath = $basePath.$targetPath;

            //Get the real template path, the target path can optionally don't exist
            if (file_exists($completePath) === true) {
                return realpath($completePath);
            }

            return $completePath;
        }

        return $targetPath;
    }
}
