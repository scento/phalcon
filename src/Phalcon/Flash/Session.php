<?php 
/**
 * Session Flash Messages
 *
 * @author Andres Gutierrez <andres@phalconphp.com>
 * @author Eduar Carvajal <eduar@phalconphp.com>
 * @author Wenzel Pünter <wenzel@phelix.me>
 * @version 1.2.6
 * @package Phalcon
*/
namespace Phalcon\Flash;

use \Phalcon\Flash,
	\Phalcon\FlashInterface,
	\Phalcon\DI\InjectionAwareInterface,
	\Phalcon\Flash\Exception,
	\Phalcon\DiInterface,
	\Phalcon\Session\AdapterInterface as SessionAdapterInterface;

/**
 * Phalcon\Flash\Session
 *
 * Temporarily stores the messages in session, then messages can be printed in the next request
 * 
 * @see https://github.com/phalcon/cphalcon/blob/1.2.6/ext/flash/session.c
 */
class Session extends Flash implements FlashInterface, InjectionAwareInterface
{
	/**
	 * Denpendency Injector
	 * 
	 * @var null|\Phalcon\DiInterface
	 * @access protected
	*/
	protected $_dependencyInjector;

	/**
	 * Sets the dependency injector
	 *
	 * @param \Phalcon\DiInterface $dependencyInjector
	 * @throws Exception
	 */
	public function setDI($dependencyInjector)
	{
		if(is_object($dependencyInjector) === false ||
			$dependencyInjector instanceof DiInterface === false) {
			throw new Exception('Invalid parameter type.');
		}

		$this->_dependencyInjector = $dependencyInjector;
	}

	/**
	 * Returns the internal dependency injector
	 *
	 * @return \Phalcon\DiInterface|null
	 */
	public function getDI()
	{
		return $this->_dependencyInjector;
	}

	/**
	 * Returns the messages stored in session
	 *
	 * @param boolean $remove
	 * @return mixed
	 * @throws Exception
	 */
	protected function _getSessionMessages($remove)
	{
		if(is_bool($remove) === false) {
			throw new Exception('Invalid parameter type.');
		}

		if(is_object($this->_dependencyInjector) === false) {
			throw new Exception('A dependency injection container is required to access the \'session\' service');
		}

		$session = $this->_dependencyInjector->getShared('session');
		if(is_object($session) === false ||
			$session instanceof SessionAdapterInterface === false) {
			throw new Exception('Session service is unavailable.');
		}

		$messages = $session->get('_flashMessages');

		if($remove === true) {
			$session->remove('_flashMessages');
		}

		return $messages;
	}

	/**
	 * Stores the messages in session
	 *
	 * @param array $messages
	 * @throws Exception
	 * @return array
	 */
	protected function _setSessionMessages($messages)
	{
		if(is_array($messages) === false) {
			throw new Exception('Invalid parameter type.');
		}

		if(is_object($this->_dependencyInjector) === false) {
			throw new Exception('A dependency injection container is required to access the \'session\' service');
		}

		$session = $this->_dependencyInjector->getShared('session');
		if(is_object($session) === false ||
			$session instanceof SessionAdapterInterface === false) {
			throw new Exception('Session service is unavailable.');
		}

		$session->set('_flashMessages', $messages);

		return $messages;
	}

	/**
	 * Adds a message to the session flasher
	 *
	 * @param string $type
	 * @param string $message
	 * @throws Exception
	 */
	public function message($type, $message)
	{
		if(is_string($type) === false ||
			is_string($message) === false) {
			throw new Exception('Invalid parameter type.');
		}

		$messages = $this->_getSessionMessages(false);

		if(is_array($messages) === false) {
			$messages = array();
		}

		if(isset($messages[$type]) === false) {
			$messages[$type] = array();
		}

		$messages[$type][] = $message;
		$this->_setSessionMessages($messages);
	}

	/**
	 * Returns the messages in the session flasher
	 *
	 * @param string|null $type
	 * @param boolean|null $remove
	 * @return array
	 * @throws Exception
	 */
	public function getMessages($type = null, $remove = null)
	{
		if(is_null($remove) === true) {
			$remove = true;
		}

		$do_remove = $remove;
		if(is_string($type) === true) {
			$do_remove = false;
		} elseif(is_null($type) === false) {
			throw new Exception('Invalid parameter type.');
		}

		$messages = $this->_getSessionMessages($do_remove);

		if(is_array($messages) === true) {
			if(is_null($type) === false) {
				if(isset($messages[$type]) === true) {
					$return_messages = $messages[$type];

					if($remove === true) {
						unset($messages[$type]);
						$this->_setSessionMessages($messages);
					}

					return $return_messages;
				}

				return array();
			}

			return $messages;
		}

		return array();
	}

	/**
	 * Prints the messages in the session flasher
	 *
	 * @param boolean $remove
	 * @throws Exception
	 */
	public function output($remove = null)
	{
		if(is_null($remove) === true) {
			$remove = true;
		} elseif(is_bool($remove) === false) {
			throw new Exception('Invalid parameter type.');
		}

		$messages = $this->_getSessionMessages($remove);
		if(is_array($messages) === true) {
			foreach($messages as $type => $message) {
				$this->outputMessage($type, $message);
			}
		}
	}
}