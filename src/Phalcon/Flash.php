<?php
/**
 * Flash
 *
 * @author Andres Gutierrez <andres@phalconphp.com>
 * @author Eduar Carvajal <eduar@phalconphp.com>
 * @author Wenzel Pünter <wenzel@phelix.me>
 * @version 1.2.6
 * @package Phalcon
*/
namespace Phalcon;

use \Phalcon\Flash\Exception as FlashException,
	\Phalcon\FlashInterface;

/**
 * Phalcon\Flash
 *
 * Shows HTML notifications related to different circumstances. Classes can be stylized using CSS
 *
 *<code>
 * $flash->success("The record was successfully deleted");
 * $flash->error("Cannot open the file");
 *</code>
 * 
 * @see https://github.com/phalcon/cphalcon/blob/1.2.6/ext/flash.c
 */
abstract class Flash implements FlashInterface
{
	/**
	 * CSS Classes
	 * 
	 * @var null|array
	 * @access protected
	*/
	protected $_cssClasses = null;

	/**
	 * Implicit Flush
	 * 
	 * @var boolean
	 * @access protected
	*/
	protected $_implicitFlush = true;

	/**
	 * Automatic HTML
	 * 
	 * @var boolean
	 * @access protected
	*/
	protected $_automaticHtml = true;

	/**
	 * \Phalcon\Flash constructor
	 *
	 * @param array|null $cssClasses
	 */
	public function __construct($cssClasses = null)
	{
		if(is_array($cssClasses) === false) {
			$this->_cssClasses = array(
				'error' => 'errorMessage',
				'notice' => 'noticeMessage',
				'success' => 'successMessage',
				'warning' => 'warningMessage'
				);
		} else {
			$this->_cssClasses = $cssClasses;
		}
	}

	/**
	 * Set whether the output must be implictly flushed to the output or returned as string
	 *
	 * @param boolean $implicitFlush
	 * @return \Phalcon\FlashInterface
	 * @throws FlashException
	 */
	public function setImplicitFlush($implicitFlush)
	{
		if(is_bool($implicitFlush) === false) {
			throw new FlashException('Invalid parameter type.');
		}

		$this->_implicitFlush = $implicitFlush;
	}

	/**
	 * Set if the output must be implictily formatted with HTML
	 *
	 * @param boolean $automaticHtml
	 * @return \Phalcon\FlashInterface
	 * @throws FlashException
	 */
	public function setAutomaticHtml($automaticHtml)
	{
		if(is_bool($automaticHtml) === false) {
			throw new FlashException('Invalid parameter type.');
		}

		$this->_automaticHtml = $automaticHtml;
	}

	/**
	 * Set an array with CSS classes to format the messages
	 *
	 * @param array $cssClasses
	 * @return \Phalcon\FlashInterface
	 * @throws FlashException
	 */
	public function setCssClasses($cssClasses)
	{
		if(is_array($cssClasses) === true) {
			$this->_cssClasses = $cssClasses;
		}

		throw new FlashException('CSS classes must be an Array');
	}

	/**
	 * Shows a HTML error message
	 *
	 *<code>
	 * $flash->error('This is an error');
	 *</code>
	 *
	 * @param string $message
	 * @return string
	 */
	public function error($message)
	{
		return $this->message('error', $message);
	}

	/**
	 * Shows a HTML notice/information message
	 *
	 *<code>
	 * $flash->notice('This is an information');
	 *</code>
	 *
	 * @param string $message
	 * @return string
	 */
	public function notice($message)
	{
		return $this->message('notice', $message);
	}

	/**
	 * Shows a HTML success message
	 *
	 *<code>
	 * $flash->success('The process was finished successfully');
	 *</code>
	 *
	 * @param string $message
	 * @return string
	 */
	public function success($message)
	{
		return $this->message('success', $message);
	}

	/**
	 * Shows a HTML warning message
	 *
	 *<code>
	 * $flash->warning('Hey, this is important');
	 *</code>
	 *
	 * @param string $message
	 * @return string
	 */
	public function warning($message)
	{
		return $this->message('warning', $message);
	}

	/**
	 * Outputs a message formatting it with HTML
	 *
	 *<code>
	 * $flash->outputMessage('error', $message);
	 *</code>
	 *
	 * @param string $type
	 * @param string|array $message
	 * @return string|null
	 * @throws FlashException
	 */
	public function outputMessage($type, $message)
	{
		if(is_string($type) === false) {
			throw new FlashException('Invalid parameter type.');
		}

		if(is_string($message) === false && is_array($message) === false) {
			throw new FlashException('Invalid parameter type.');
		}

		//Generate class tag
		if($this->_automaticHtml === true) {
			if(isset($this->_cssClasses[$type]) === true) {
				if(is_array($this->_cssClasses[$type]) === true) {
					$cssClasses = ' class="'.implode(' ', $this->_cssClasses[$type]).'"';
				} else {
					$cssClasses = ' class="'.$this->_cssClasses[$type].'"';
				}
			} else {
				$cssClasses = '';
			}
		}

		//Handle message(s)
		if(is_array($message) === true) {
			if($this->_implicitFlush === false) {
				$content = '';
			}

			foreach($message as $msg) {
				if($this->_automaticHtml === true) {
					$htmlMessage = '<div'.$cssClasses.'>'.$msg.'</div>';
				} else {
					$htmlMessage = $msg;
				}

				if($this->_implicitFlush === true) {
					echo $htmlMessage;
				} else {
					$content .= $htmlMessage;
				}
			}

			if($this->_implicitFlush === false) {
				return $content;
			}
		} else {
			if($this->_automaticHtml === true) {
				$htmlMessage = '<div'.$cssClasses.'>'.$message.'</div>';
			} else {
				$htmlMessage = $message;
			}

			if($this->_implicitFlush === true) {
				echo $htmlMessage;
			} else {
				return $htmlMessage;
			}
		}

	}
}