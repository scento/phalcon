<?php
/**
 * Session Adapter
 *
 * @author Andres Gutierrez <andres@phalconphp.com>
 * @author Eduar Carvajal <eduar@phalconphp.com>
 * @author Wenzel Pünter <wenzel@phelix.me>
 * @version 1.2.6
 * @package Phalcon
*/
namespace Phalcon\Session;

use \Phalcon\Session\Exception;

/**
 * Phalcon\Session\Adapter
 *
 * Base class for Phalcon\Session adapters
 *
 * @see https://github.com/phalcon/cphalcon/blob/1.2.6/ext/session/adapter.c
 */
abstract class Adapter
{
    /**
     * Unique ID
     *
     * @var null|string
     * @access protected
    */
    protected $_uniqueId;

    /**
     * Started
     *
     * @var boolean
     * @access protected
    */
    protected $_started = false;

    /**
     * Options
     *
     * @var null|array
     * @access protected
    */
    protected $_options;

    /**
     * \Phalcon\Session\Adapter constructor
     *
     * @param array|null $options
     */
    public function __construct($options = null)
    {
        if (is_array($options) === true) {
            $this->setOptions($options);
        }
    }

    /**
     * Destructor
    */
    public function __destruct()
    {
        if ($this->_started === true) {
            session_write_close();
            $this->_started = false;
        }
    }

    /**
     * Starts the session (if headers are already sent the session will not be started)
     *
     * @return boolean
     */
    public function start()
    {
        if (headers_sent() === false) {
            //@note no result check for session_start()
            session_start();
            $this->_started = true;

            return true;
        }

        return false;
    }

    /**
     * Sets session's options
     *
     *<code>
     *  $session->setOptions(array(
     *      'uniqueId' => 'my-private-app'
     *  ));
     *</code>
     *
     * @param array $options
     * @throws Exception
     */
    public function setOptions($options)
    {
        if (is_array($options) === false) {
            throw new Exception('Options must be an Array');
        }

        if (isset($options['uniqueId']) === true) {
            //@note no type check
            $this->_uniqueId = $options['uniqueId'];
        }

        $this->_options = $options;
    }

    /**
     * Get internal options
     *
     * @return array|null
     */
    public function getOptions()
    {
        return $this->_options;
    }

    /**
     * Gets a session variable from an application context
     *
     * @param string $index
     * @param mixed $defaultValue
     * @return mixed
     * @throws Exception
     */
    public function get($index, $defaultValue = null)
    {
        if (is_string($index) === false) {
            throw new Exception('Invalid parameter type.');
        }

        $key = $this->_uniqueId.$index;

        if (isset($_SESSION[$key]) === true) {
            $value = $_SESSION[$key];
            if (empty($value) === false) {
                return $value;
            }
        }

        return $defaultValue;
    }

    /**
     * Sets a session variable in an application context
     *
     *<code>
     *  $session->set('auth', 'yes');
     *</code>
     *
     * @param string $index
     * @param mixed $value
     * @throws Exception
     */
    public function set($index, $value)
    {
        if (is_string($index) === false) {
            throw new Exception('Invalid parameter type.');
        }

        $_SESSION[$this->_uniqueId.$index] = $value;
    }

    /**
     * Check whether a session variable is set in an application context
     *
     *<code>
     *  var_dump($session->has('auth'));
     *</code>
     *
     * @param string $index
     * @return boolean
     * @throws Exception
     */
    public function has($index)
    {
        if (is_string($index) === false) {
            throw new Exception('Invalid parameter type.');
        }

        return isset($_SESSION[$this->_uniqueId.$index]);
    }

    /**
     * Removes a session variable from an application context
     *
     *<code>
     *  $session->remove('auth');
     *</code>
     *
     * @param string $index
     * @throws Exception
     */
    public function remove($index)
    {
        if (is_string($index) === false) {
            throw new Exception('Invalid parameter type.');
        }

        unset($_SESSION[$this->_uniqueId.$index]);
    }

    /**
     * Returns active session id
     *
     *<code>
     *  echo $session->getId();
     *</code>
     *
     * @return string
     */
    public function getId()
    {
        return session_id();
    }

    /**
     * Check whether the session has been started
     *
     *<code>
     *  var_dump($session->isStarted());
     *</code>
     *
     * @return boolean|null
     */
    public function isStarted()
    {
        return $this->_started;
    }

    /**
     * Destroys the active session
     *
     *<code>
     *  var_dump($session->destroy());
     *</code>
     *
     * @return boolean
     */
    public function destroy()
    {
        $this->_started = false;
        //@note no return value check
        session_destroy();
        return true;
    }
}
