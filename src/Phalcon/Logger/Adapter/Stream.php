<?php
/**
 * Stream Adapter
 *
 * @author Andres Gutierrez <andres@phalconphp.com>
 * @author Eduar Carvajal <eduar@phalconphp.com>
 * @author Wenzel Pünter <wenzel@phelix.me>
 * @version 1.2.6
 * @package Phalcon
*/
namespace Phalcon\Logger\Adapter;

use \Phalcon\Logger\Adapter;
use \Phalcon\Logger\AdapterInterface;
use \Phalcon\Logger\Exception;
use \Phalcon\Logger\Formatter\Line;

/**
 * Phalcon\Logger\Adapter\Stream
 *
 * Sends logs to a valid PHP stream
 *
 *<code>
 *  $logger = new \Phalcon\Logger\Adapter\Stream("php://stderr");
 *  $logger->log("This is a message");
 *  $logger->log("This is an error", \Phalcon\Logger::ERROR);
 *  $logger->error("This is another error");
 *</code>
 *
 * @see https://github.com/phalcon/cphalcon/blob/1.2.6/ext/logger/adapter/stream.c
 */
class Stream extends Adapter implements AdapterInterface
{
    /**
     * Stream
     *
     * @var null|resource
     * @access protected
    */
    protected $_stream;

    /**
     * \Phalcon\Logger\Adapter\Stream constructor
     *
     * @param string $name
     * @param array|null $options
     * @throws Exception
     */
    public function __construct($name, $options = null)
    {
        if (is_string($name) === false) {
            throw new Exception('Invalid parameter type.');
        }

        if (is_null($options) === true) {
            $mode = 'ab';
        } elseif (is_array($options) === true) {
            $mode = $options['mode'];
            if (strpos($mode, 'r') === true) {
                throw new Exception('Stream must be opened in append or write mode');
            }
        } else {
            throw new Exception('Invalid parameter type.');
        }

        //We use 'fopen' to respect the open-basedir directive
        $stream = fopen($name, $mode);
        if (is_resource($stream) === false) {
            throw new Exception("Can't open stream '".$name."'");
        }

        $this->_stream = $stream;
    }

    /**
     * Returns the internal formatter
     *
     * @return \Phalcon\Logger\Formatter\Line
     */
    public function getFormatter()
    {
        if (is_object($this->_formatter) === false) {
            $this->_formatter = new Line();

        }

        return $this->_formatter;
    }

    /**
     * Writes the log to the stream itself
     *
     * @param string $message
     * @param int $type
     * @param int $time
     * @throws Exception
     */
    public function logInternal($message, $type, $time)
    {
        if (is_string($message) === false ||
            is_int($type) === false ||
            is_int($time) === false) {
            throw new Exception('Invalid parameter type.');
        }

        if (is_resource($this->_stream) === false) {
            throw new Exception('Cannot send message to the log because it is invalid');
        }

        //@note no return value handeling
        fwrite($this->_stream, $this->getFormatter()->format($message, $type, $time));
    }

    /**
     * Closes the logger
     *
     * @return boolean
     */
    public function close()
    {
        return fclose($this->_stream);
    }
}
