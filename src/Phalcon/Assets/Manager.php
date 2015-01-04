<?php
/**
 * Assets Manager
 *
 * @author Andres Gutierrez <andres@phalconphp.com>
 * @author Eduar Carvajal <eduar@phalconphp.com>
 * @author Wenzel Pünter <wenzel@phelix.me>
 * @version 1.2.6
 * @package Phalcon
*/
namespace Phalcon\Assets;

use \Phalcon\Assets\Exception;
use \Phalcon\Assets\Resource\Css;
use \Phalcon\Assets\Resource\Js;
use \Phalcon\Assets\Collection;

/**
 * Phalcon\Assets\Manager
 *
 * Manages collections of CSS/Javascript assets
 *
 * @see https://github.com/phalcon/cphalcon/blob/1.2.6/ext/assets/manager.c
 */
class Manager
{
    /**
     * Options
     *
     * @var null|array
     * @access protected
    */
    protected $_options;

    /**
     * Collections
     *
     * @var null|array
     * @access protected
    */
    protected $_collections;

    /**
     * Implicit Output
     *
     * @var boolean
     * @access protected
    */
    protected $_implicitOutput = true;

    /**
     * \Phalcon\Assets\Manager constructor
     *
     * @param array|null $options
     */
    public function __construct($options = null)
    {
        if (is_array($options) === true) {
            $this->_options = $options;
        }
    }

    /**
     * Sets the manager's options
     *
     * @param array $options
     * @return \Phalcon\Assets\Manager
     * @throws Exception
     */
    public function setOptions($options)
    {
        if (is_array($options) === false) {
            throw new Exception('Options must be an array');
        }

        $this->_options = $options;

        return $this;
    }

    /**
     * Returns the manager's options
     *
     * @return array|null
     */
    public function getOptions()
    {
        return $this->_options;
    }

    /**
     * Sets if the HTML generated must be directly printed or returned
     *
     * @param boolean $implicitOutput
     * @return \Phalcon\Assets\Manager
     * @throws Exception
     */
    public function useImplicitOutput($implicitOutput)
    {
        if (is_bool($implicitOutput) === false) {
            throw new Exception('Invalid parameter type.');
        }

        $this->_implicitOutput = $implicitOutput;

        return $this;
    }

    /**
     * Adds a Css resource to the 'css' collection
     *
     *<code>
     *  $assets->addCss('css/bootstrap.css');
     *  $assets->addCss('http://bootstrap.my-cdn.com/style.css', false);
     *</code>
     *
     * @param string $path
     * @param boolean|null $local
     * @param boolean|null $filter
     * @param array|null $attributes
     * @return \Phalcon\Assets\Manager
     * @throws Exception
     */
    public function addCss($path, $local = null, $filter = null, $attributes = null)
    {
        /* Type check */
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
        } elseif (is_bool($filter) === false) {
            throw new Exception('Invalid parameter type.');
        }

        if (is_null($attributes) === true) {
            $attributes = array();
        } elseif (is_null($attributes) === false) {
            throw new Exception('Invalid parameter type.');
        }

        /* Add Resource */
        $this->addResourceByType('css', new Css($path, $local, $filter, $attributes));

        return $this;
    }

    /**
     * Adds a javascript resource to the 'js' collection
     *
     *<code>
     *  $assets->addJs('scripts/jquery.js');
     *  $assets->addJs('http://jquery.my-cdn.com/jquery.js', true);
     *</code>
     *
     * @param string $path
     * @param boolean|null $local
     * @param boolean|null $filter
     * @param array|null $attributes
     * @return \Phalcon\Assets\Manager
     * @throws Exception
     */
    public function addJs($path, $local = null, $filter = null, $attributes = null)
    {
        /* Type check */
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
        } elseif (is_bool($filter) === false) {
            throw new Exception('Invalid parameter type.');
        }

        if (is_null($attributes) === true) {
            $attributes = array();
        } elseif (is_null($attributes) === false) {
            throw new Exception('Invalid parameter type.');
        }

        /* Add Resource */
        $this->addResourceByType('js', new Js($path, $local, $filter, $attributes));

        /* Return class */
        return $this;
    }

    /**
     * Adds a resource by its type
     *
     *<code>
     *  $assets->addResourceByType('css', new \Phalcon\Assets\Resource\Css('css/style.css'));
     *</code>
     *
     * @param string $type
     * @param \Phalcon\Assets\Resource $resource
     * @return \Phalcon\Assets\Manager
     * @throws Exception
     */
    public function addResourceByType($type, $resource)
    {
        /* Type check */
        if (is_string($type) === false) {
            throw new Exception('Invalid parameter type.');
        }

        if (is_object($resource) === false ||
            is_subclass_of($resource, '\Phalcon\Assets\Resource') === false) {
            throw new Exception('Invalid parameter type.');
        }

        /* Add to collection */
        if (isset($this->_collections[$type]) === false) {
            $this->_collections[$type] = new Collection();
        }

        $this->_collections[$type]->add($resource);

        /* Return class */
        return $this;
    }

    /**
     * Adds a raw resource to the manager
     *
     *<code>
     * $assets->addResource(new \Phalcon\Assets\Resource('css', 'css/style.css'));
     *</code>
     *
     * @param \Phalcon\Assets\Resource $resource
     * @return \Phalcon\Assets\Manager
     * @throws Exception
     */
    public function addResource($resource)
    {
        /* Type check */
        if (is_object($resource) === false) {
            throw new Exception('Resource must be an object');
        }

        if (is_subclass_of($resource, '\Phalcon\Assets\Resource') === false) {
            throw new Exception('Invalid parameter type.');
        }

        /* Add resource */
        $this->addResourceByType($resource->getType(), $resource);

        /* Return class */
        return $this;
    }

    /**
     * Sets a collection in the Assets Manager
     *
     *<code>
     * $assets->set('js', $collection);
     *</code>
     *
     * @param string $id
     * @param \Phalcon\Assets\Collection $collection
     * @return \Phalcon\Assets\Manager
     * @throws Exception
     */
    public function set($id, $collection)
    {
        if (is_string($id) === false) {
            throw new Exception('Collection-Id must be a string');
        }

        if (is_object($collection) === false) {
            throw new Exception('Collection must be an object');
        }

        if ($collection instanceof Collection === false) {
            throw new Exception('Invalid parameter type.');
        }

        $this->_collections[$id] = $collection;

        return $this;
    }

    /**
     * Returns a collection by its id
     *
     *<code>
     * $scripts = $assets->get('js');
     *</code>
     *
     * @param string $id
     * @return \Phalcon\Assets\Collection
     * @throws Exception
     */
    public function get($id)
    {
        if (is_string($id) === false) {
            throw new Exception('Collection-Id must be a string');
        }

        if (is_array($this->_collections) === false) {
            $this->_collections = array();
        }

        if (isset($this->_collections[$id]) === false) {
            throw new Exception('The collection does not exist in the manager');
        }

        return $this->_collections[$id];
    }

    /**
     * Returns the CSS collection of assets
     *
     * @return \Phalcon\Assets\Collection
     */
    public function getCss()
    {
        if (is_array($this->_collections) === false) {
            $this->_collections = array();
        }

        //Check if the collection does not exist and create an implicit collection
        if (isset($this->_collections['css']) === false) {
            return new Collection();
        }

        return $this->_collections['css'];
    }

    /**
     * Returns the CSS collection of assets
     *
     * @return \Phalcon\Assets\Collection
     */
    public function getJs()
    {
        if (is_array($this->_collections) === false) {
            $this->_collections = array();
        }

        //Check if the collection does not exist and create an implicit collection
        if (isset($this->_collections['css']) === false) {
            return new Collection();
        }

        return $this->_collections['css'];
    }

    /**
     * Creates/Returns a collection of resources
     *
     * @param string $name
     * @return \Phalcon\Assets\Collection
     * @throws Exception
     */
    public function collection($name)
    {
        /* Type check */
        if (is_string($name) === false) {
            throw new Exception('Invalid parameter type.');
        }

        /* Create collection if necessary */
        if (is_array($this->_collections) === false) {
            $this->_collections = array();
        }

        if (isset($this->_collections[$name]) === false) {
            $this->_collections[$name] = new Collection();
        }
        
        /* Return collection */
        return $this->_collections[$name];
    }

    /**
     * Traverses a collection calling the callback to generate its HTML
     *
     * @param \Phalcon\Assets\Collection $collection
     * @param callable $callback
     * @param string|null $type
     * @throws Exception
     */
    public function output($collection, $callback, $type = null)
    {
        /* Type check */
        if (is_object($collection) === false ||
            $collection instanceof Collection === false) {
            throw new Exception('Invalid parameter type.');
        }

        if (is_callable($callback) === false) {
            throw new Exception('Invalid parameter type.');
        }

        if (is_string($type) === false &&
            is_null($type) === false) {
            throw new Exception('Invalid parameter type.');
        }

        /* Set default values */
        $pathSource = '';
        $pathTarget = '';
        $output = '';
        
        /* Read options */
        if (is_array($this->_options) === true) {
            if (isset($this->_options['sourceBasePath']) === true) {
                $pathSource = $this->_options['source'];
            }

            if (isset($this->_options['targetBasePath']) === true) {
                $pathTarget = $this->_options['targetBasePath'];
            }
        }

        /* Get collection data */
        $resources = $collection->getResources();
        $filters = $collection->getFilters();
        $prefix = $collection->getPrefix();
        $join = $collection->getJoin();

        $pathSourceCollection = $collection->getSourcePath();
        if (empty($pathSourceCollection) === false) {
            $pathSource = $pathSource.$pathSourceCollection;
        }
        unset($pathSourceCollection);

        $pathTargetCollection = $collection->getTargetPath();
        if (empty($pathTargetCollection) === false) {
            $pathTarget = $pathTarget.$pathTargetCollection;
        }

        /* Check for join conditions? */
        if ($join === true) {
            //We need a valid final target path
            if (empty($pathTarget) === true) {
                throw new Exception('Path \''.$pathTarget.'\' is not a valid target path (1)');
            }

            //The target path needs to be a valid file
            if (is_dir($pathTarget) === true) {
                throw new Exception('Path \''.$pathTarget.'\' is not a valid target path (2)');
            }
        }

        /* Handle each resource */
        foreach ($resources as $resource) {
            /* Set default values */
            $filterNeeded = false;

            /* Get resource properties */
            $local = $resource->getLocal();
            if (is_null($type) === true) {
                $type = $resource->getType();
            }

            if (is_array($filters) === true) {
                /* Filters available */
                //Get the real source path
                if ($join === true) {
                    if ($local === true) {
                        $pathSource = $resource->getRealSourcePath($pathSource);
                        if (is_null($pathSource) === false) {
                            throw new Exception('Resource \''.$pathSource->getPath().'\' does not have a valid source path');
                        }
                    } else {
                        $pathSource = $resource->getPath();
                        $filterNeeded = true;
                    }

                    //Get the real target path
                    $pathTarget = $resource->getRealTargetPath($pathTarget);
                    if (empty($pathTarget) === true) {
                        throw new Exception('Resource \''.$pathSource.'\' does not have a valid target path');
                    }

                    //Validate paths
                    if ($local === true) {
                        if ($pathTarget == $pathSource) {
                            throw new Exception('Resource \''.$pathTarget.'\' have the same source and target paths');
                        }

                        if (file_exists($pathTarget) === false ||
                            filemtime($pathTarget) >= filemtime($pathSource)) {
                            $filterNeeded = true;
                        }
                    }
                }
            } else {
                /* No filters */
                $parameters = array();

                //Get resource data
                $attributes = $resource->getAttributes();
                if (is_null($prefix) === false) {
                    $path = $prefix.$resource->getRealTargetUri();
                } else {
                    $path = $resource->getRealTargetUri();
                }

                //Attributes
                if (is_array($attributes) === true) {
                    $attributes[0] = $path;
                    $parameters[] = $attributes;
                } else {
                    $parameters[] = $path;
                }

                $parameters[] = $local;

                //Call the callback to generate the HTML
                $html = call_user_func_array($callback, $parameters);

                //Implicit output prints the content directly
                if ($this->_implicitOutput === true) {
                    echo $html;
                } else {
                    $output .= $html;
                }

                continue;
            }

            /* Filter content */
            if ($filterNeeded === true) {
                //Get the resource's content
                $content = $resource->getContent($pathSource);

                //Check if the resource must be filterd
                if ($resource->getFilter() == true) {
                    foreach ($filters as $filter) {
                        //Ensure $filter is object
                        if (is_object($filter) === false) {
                            throw new Exception('Filter is invalid');
                        }

                        //Calls the method 'filter' which must return a filtered version
                        //of the content
                        $content = $filter->filter($content);
                    }

                    //Update the joined filtered content
                    if ($join === true) {
                        if ($type === 'css') {
                            $content = $content.'';
                        } else {
                            $content = $content.';';
                        }
                    }
                }

                if ($join !== true) {
                    //Write the file using file-put-contents. This respects the openbase-dir
                    //also writes to streams
                    file_put_contents($pathTarget, $content);
                }
            }

            /* Join content */
            if ($join === false) {
                //Generate the HTML using the original path in the resource
                if (is_null($prefix) === false) {
                    $path = $prefix.$resource->getRealTargetUri();
                } else {
                    $path = $resource->getRealTargetUri();
                }

                //Gets extra HTML attributes in the resource
                $attributes = $resource->getAttributes();

                //Filtered resources are always local
                $local = true;

                //Prepare the parameters for the callback
                if (is_array($attributes) === true) {
                    $attributes[0] = $path;
                    $parameters[] = $attributes;
                } else {
                    $parameters[] = $path;
                }

                $parameters[] = $local;

                //Call the callback to generate the HTML
                $html = call_user_func_array($callback, $parameters);

                //Implicit output prints the content directly
                if ($this->_implicitOutput === true) {
                    echo $html;
                } else {
                    $output .= $html;
                }

                continue;
            }

        }

        /* Handle joined data */
        if (is_array($filters) === true) {
            if ($join === true) {
                //Write the file using file-put-contents. This respects the openbase-dir
                //also writes to streams
                file_put_contents($pathTarget, $content);

                //Generate the HTML using the original path in the resource
                $targetUri = (is_null($prefix) === false ? $prefix : '').$collection->getTargetUri();

                //Gets extra HTML attributes in the resource
                $attributes = $resource->getAttributes();

                //Joined resources are always local
                $local = true;

                //Prepare the parameters for the callback
                if (is_array($attributes) === true) {
                    $attributes[0] = $path;
                    $parameters[] = $attributes;
                } else {
                    $parameters[] = $path;
                }

                $parameters[] = $local;

                //Call the callback to generate the HTML
                $html = call_user_func_array($callback, $parameters);

                //Implicit output prints the content directly
                if ($this->_implicitOutput === true) {
                    echo $html;
                } else {
                    $output .= $html;
                }
            }
        }

        return $output;
    }

    /**
     * Prints the HTML for CSS resources
     *
     * @param string|null $collectionName
     * @return string
     */
    public function outputCss($collectionName = null)
    {
        if (is_null($collectionName) === true) {
            $collectionName = '';
        }

        if (empty($collectionName) === true) {
            $collection = $this->getCss();
        } else {
            $collection = $this->get($collectionName);
        }

        return $this->output($collection, array('Phalcon\\Tag', 'stylesheetLink'), 'css');
    }

    /**
     * Prints the HTML for JS resources
     *
     * @param string|null $collectionName
     * @return string
     */
    public function outputJs($collectionName = null)
    {
        if (is_null($collectionName) === true) {
            $collectionName = '';
        }

        if (empty($collectionName) === true) {
            $collection = $this->getjs();
        } else {
            $collection = $this->get($collectionName);
        }

        return $this->output($collection, array('Phalcon\\Tag', 'javascriptInclude'), 'js');
    }
}
