<?php
/**
 * None Cache Frontend
 *
 * @author Andres Gutierrez <andres@phalconphp.com>
 * @author Eduar Carvajal <eduar@phalconphp.com>
 * @author Wenzel Pünter <wenzel@phelix.me>
 * @version 1.2.6
 * @package Phalcon
*/
namespace Phalcon\Cache\Frontend;

use \Phalcon\Cache\FrontendInterface;

/**
 * Phalcon\Cache\Frontend\None
 *
 * Discards any kind of frontend data input. This frontend does not have expiration time or any other options
 *
 *<code>
 *
 *  //Create a None Cache
 *  $frontCache = new Phalcon\Cache\Frontend\None();
 *
 *  // Create the component that will cache "Data" to a "Memcached" backend
 *  // Memcached connection settings
 *  $cache = new Phalcon\Cache\Backend\Memcache($frontCache, array(
 *      "host" => "localhost",
 *      "port" => "11211"
 *  ));
 *
 *  // This Frontend always return the data as it's returned by the backend
 *  $cacheKey = 'robots_order_id.cache';
 *  $robots    = $cache->get($cacheKey);
 *  if ($robots === null) {
 *
 *      // This cache doesn't perform any expiration checking, so the data is always expired
 *      // Make the database call and populate the variable
 *      $robots = Robots::find(array("order" => "id"));
 *
 *      $cache->save($cacheKey, $robots);
 *  }
 *
 *  // Use $robots :)
 *  foreach ($robots as $robot) {
 *      echo $robot->name, "\n";
 *  }
 *</code>
 *
 * @see https://github.com/phalcon/cphalcon/blob/1.2.6/ext/cache/frontend/none.c
 */
class None implements FrontendInterface
{
    /**
     * Returns cache lifetime, always one second expiring content
     *
     * @return int
     */
    public function getLifetime()
    {
        return 1;
    }

    /**
     * Check whether if frontend is buffering output, always false
     *
     * @return boolean
     */
    public function isBuffering()
    {
        return false;
    }

    /**
     * Starts output frontend
     */
    public function start()
    {
    }

    /**
     * Returns output cached content
     *
     * @return string|null
     */
    public function getContent()
    {
    }

    /**
     * Stops output frontend
     */
    public function stop()
    {
    }

    /**
     * Prepare data to be stored
     *
     * @param mixed $data
     */
    public function beforeStore($data)
    {
        return $data;
    }

    /**
     * Prepares data to be retrieved to user
     *
     * @param mixed $data
     */
    public function afterRetrieve($data)
    {
        return $data;
    }
}
