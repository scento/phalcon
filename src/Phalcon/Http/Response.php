<?php
/**
 * Response
 *
 * @author Andres Gutierrez <andres@phalconphp.com>
 * @author Eduar Carvajal <eduar@phalconphp.com>
 * @author Wenzel Pünter <wenzel@phelix.me>
 * @version 1.2.6
 * @package Phalcon
*/
namespace Phalcon\Http;

use \Phalcon\Http\ResponseInterface,
	\Phalcon\Http\Response\Exception,
	\Phalcon\Http\Response\HeadersInterface,
	\Phalcon\Http\Response\Headers,
	\Phalcon\Http\Response\CookiesInterface,
	\Phalcon\Mvc\UrlInterface,
	\Phalcon\DI\InjectionAwareInterface,
	\Phalcon\DiInterface,
	\Phalcon\DI,
	\DateTime,
	\DateTimeZone;

/**
 * Phalcon\Http\Response
 *
 * Part of the HTTP cycle is return responses to the clients.
 * Phalcon\HTTP\Response is the Phalcon component responsible to achieve this task.
 * HTTP responses are usually composed by headers and body.
 *
 *<code>
 *	$response = new Phalcon\Http\Response();
 *	$response->setStatusCode(200, "OK");
 *	$response->setContent("<html><body>Hello</body></html>");
 *	$response->send();
 *</code>
 * 
 * @see https://github.com/phalcon/cphalcon/blob/1.2.6/ext/http/response.c
 */
class Response implements ResponseInterface, InjectionAwareInterface
{
	/**
	 * Sent
	 * 
	 * @var boolean
	 * @access protected
	*/
	protected $_sent = false;

	/**
	 * Content
	 * 
	 * @var null|string
	 * @access protected
	*/
	protected $_content;

	/**
	 * Headers
	 * 
	 * @var null|\Phalcon\Http\Response\HeadersInterface
	 * @access protected
	*/
	protected $_headers;

	/**
	 * Cookies
	 * 
	 * @var null|\Phalcon\Ḩttp\Response\CookiesInterface
	 * @access protected
	*/
	protected $_cookies;

	/**
	 * File
	 * 
	 * @var null|string
	 * @access protected
	*/
	protected $_file;

	/**
	 * Dependency Injector
	 * 
	 * @var null|\Phalcon\DiInterface
	 * @access protected
	*/
	protected $_dependencyInjector;

	/**
	 * \Phalcon\Http\Response constructor
	 *
	 * @param string|null $content
	 * @param int|null $code
	 * @param string|null $status
	 * @throws Exception
	 */
	public function __construct($content = null, $code = null, $status = null)
	{
		if(is_string($content) === true) {
			$this->_content = $content;
		} elseif(is_null($content) === false) {
			throw new Exception('Invalid parameter type.');
		}

		if(is_int($code) === true && is_string($status) === true) {
			$this->setStatusCode($code, $status);
		} elseif(is_null($code) === false || is_null($status) === false) {
			throw new Exception('Invalid parameter type.');
		}
	}

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
	 * @return \Phalcon\DiInterface
	 */
	public function getDI()
	{
		if(is_object($this->_dependencyInjector) === false) {
			$dependencyInjector = DI::getDefault();
			if(is_object($dependencyInjector) === false) {
				//@note potentially misleading exception
				throw new Exception("A dependency injection object is required to access the 'url' service");
			}

			$this->_dependencyInjector = $dependencyInjector;
		}

		return $this->_dependencyInjector;
	}

	/**
	 * Sets the HTTP response code
	 *
	 *<code>
	 *	$response->setStatusCode(404, "Not Found");
	 *</code>
	 *
	 * @param int $code
	 * @param string $message
	 * @return \Phalcon\Http\ResponseInterface
	 * @throws Exception
	 */
	public function setStatusCode($code, $message)
	{
		if(is_int($code) === false ||
			is_string($message) === false) {
			throw new Exception('Invalid parameter type.');
		}

		$headers = $this->getHeaders();

		//We use HTTP/1.1 instead of HTTP/1.0
		$headers->setRaw('HTTP/1.1 '.(string)$code.' '.$message);

		//We also define a 'Status' header with the HTTP status
		$headers->set('Status', (string)$code.' '.$message);

		$this->_headers = $headers;

		return $this;
	}

	/**
	 * Sets a headers bag for the response externally
	 *
	 * @param \Phalcon\Http\Response\HeadersInterface $headers
	 * @return \Phalcon\Http\ResponseInterface
	 * @throws Exception
	 */
	public function setHeaders($headers)
	{
		if(is_object($headers) === false ||
			$headers instanceof HeadersInterface === false) {
			throw new Exception('Invalid parameter type.');
		}

		$this->_headers = $headers;

		return $this;
	}

	/**
	 * Returns headers set by the user
	 *
	 * @return \Phalcon\Http\Response\HeadersInterface
	 */
	public function getHeaders()
	{
		if(is_null($this->_headers) === true) {
			/*
			 * A Phalcon\Http\Response\Headers bag is temporary used to manage the headers
			 * before sent them to the client
			*/
			$headers = new Headers();
			$this->_headers = $headers;
		}

		return $this->_headers;
	}

	/**
	 * Sets a cookies bag for the response externally
	 *
	 * @param \Phalcon\Http\Response\CookiesInterface $cookies
	 * @return \Phalcon\Http\ResponseInterface
	 * @throws Exception
	 */
	public function setCookies($cookies)
	{
		if(is_object($cookies) === false ||
			$cookies instanceof CookiesInterface === false) {
			throw new Exception('The cookies bag is not valid');
		}

		$this->_cookies = $cookies;

		return $this;
	}

	/**
	 * Returns coookies set by the user
	 *
	 * @return \Phalcon\Http\Response\CookiesInterface|null
	 */
	public function getCookies()
	{
		return $this->_cookies;
	}

	/**
	 * Overwrites a header in the response
	 *
	 *<code>
	 *	$response->setHeader("Content-Type", "text/plain");
	 *</code>
	 *
	 * @param string $name
	 * @param string $value
	 * @return \Phalcon\Http\ResponseInterface
	 * @throws Exception
	 */
	public function setHeader($name, $value)
	{
		if(is_string($name) === false ||
			is_string($value) === false) {
			throw new Exception('Invalid parameter type.');
		}

		$this->getHeaders()->set($name, $value);

		return $this;
	}

	/**
	 * Send a raw header to the response
	 *
	 *<code>
	 *	$response->setRawHeader("HTTP/1.1 404 Not Found");
	 *</code>
	 *
	 * @param string $header
	 * @return \Phalcon\Http\ResponseInterface
	 * @throws Exception
	 */
	public function setRawHeader($header)
	{
		if(is_string($header) === false) {
			throw new Exception('Invalid parameter type.');
		}

		$this->getHeaders()->setRaw($header);

		return $this;
	}

	/**
	 * Resets all the stablished headers
	 *
	 * @return \Phalcon\Http\ResponseInterface
	 */
	public function resetHeaders()
	{
		$this_>getHeaders()->reset();

		return $this;
	}

	/**
	 * Sets a Expires header to use HTTP cache
	 *
	 *<code>
	 *	$this->response->setExpires(new DateTime());
	 *</code>
	 *
	 * @param DateTime $datetime
	 * @return \Phalcon\Http\ResponseInterface
	 * @throws Exception
	 */
	public function setExpires($datetime)
	{
		if(is_object($datetime) === false ||
			$datetime instanceof DateTime === false) {
			throw new Exception('datetime parameter must be an instance of DateTime');
		}

		$headers = $this->getHeaders();
		try {
			$date = clone $datetime;
		} catch(\Exception $e) {
			return;
		}

		//All the expiration times are sent in UTC
		$timezone = new DateTimeZone('UTC');

		//Change the timezone to UTC
		$date->setTimezone($timezone);
		$utc_date = $date->format('D, d M Y H:i:s').' GMT';

		//The 'Expires' header set this info
		$this->setHeader('Expires', $utc_date);

		return $this;
	}

	/**
	 * Sends a Not-Modified response
	 *
	 * @return \Phalcon\Http\ResponseInterface
	 */
	public function setNotModified()
	{
		$this->setStatusCode(304, 'Not modified');

		return $this;
	}

	/**
	 * Sets the response content-type mime, optionally the charset
	 *
	 *<code>
	 *	$response->setContentType('application/pdf');
	 *	$response->setContentType('text/plain', 'UTF-8');
	 *</code>
	 *
	 * @param string $contentType
	 * @param string|null $charset
	 * @return \Phalcon\Http\ResponseInterface
	 * @throws Exception
	 */
	public function setContentType($contentType, $charset = null)
	{
		if(is_string($contentType) === false) {
			throw new Exception('Invalid parameter type.');
		}

		$headers = $this->getHeaders();

		if(is_null($charset) === true) {
			$headers->set('Content-Type', $contentType);
		} elseif(is_string($charset) === true) {
			$headers->set('Content-Type', $contentType.'; charset='.$charset);
		} else {
			throw new Exception('Invalid parameter type.');
		}

		return $this;
	}

	/**
	 * Set a custom ETag
	 *
	 *<code>
	 *	$response->setEtag(md5(time()));
	 *</code>
	 *
	 * @param string $etag
	 * @throws Exception
	 */
	public function setEtag($etag)
	{
		if(is_string($etag) === false) {
			throw new Exception('Invalid parameter type.');
		}

		$this->getHeaders()->set('Etag', $etag);

		return $this;
	}

	/**
	 * Redirect by HTTP to another action or URL
	 *
	 *<code>
	 *  //Using a string redirect (internal/external)
	 *	$response->redirect("posts/index");
	 *	$response->redirect("http://en.wikipedia.org", true);
	 *	$response->redirect("http://www.example.com/new-location", true, 301);
	 *
	 *	//Making a redirection based on a named route
	 *	$response->redirect(array(
	 *		"for" => "index-lang",
	 *		"lang" => "jp",
	 *		"controller" => "index"
	 *	));
	 *</code>
	 *
	 * @param string|null $location
	 * @param boolean|null $externalRedirect
	 * @param int|null $statusCode
	 * @return \Phalcon\Http\ResponseInterface
	 * @throws Exception
	 */
	public function redirect($location = null, $externalRedirect = null, 
		$statusCode = null)
	{
		$redirect_phrases = array(
			/* 300 */ 'Multiple Choices',
			/* 301 */ 'Moved Permanently',
			/* 302 */ 'Found',
			/* 303 */ 'See Other',
			/* 304 */ 'Not Modified',
			/* 305 */ 'Use Proxy',
			/* 306 */ 'Switch Proxy',
			/* 307 */ 'Temporary Redirect',
			/* 308 */ 'Permanent Redirect'
			);

		/* Type check */
		if(is_string($location) === false &&
			is_null($location) === false) {
			throw new Exception('Invalid parameter type.');
		}

		if(is_null($externalRedirect) === true) {
			$externalRedirect = false;
		} elseif(is_bool($externalRedirect) === false) {
			throw new Exception('Invalid parameter type.');
		}

		if(is_null($statusCode) === true) {
			$statusCode = 302;
		} elseif(is_int($statusCode) === false) {
			$statusCode = (int)$statusCode;
		}

		/* Preprocessing */
		if($externalRedirect === true) {
			$header = $location;
		} else {
			$dependency_injector = $this->getDi();
			$url = $dependency_injector->getShared('url');
			if(is_object($url) === false ||
				$url instanceof UrlInterface === false) {
				throw new Exception('Wrong url service.');
			}

			$header = $url->get($location);
		}

		/* Execution */
		//The HTTP status is 302 by default, a temporary redirection
		if($statusCode < 300 || $statusCode > 308) {
			$status_text = 'Redirect';
		} else {
			$status_text = $redirect_phrases[(int)$statusCode - 300];
		}

		$this->setStatusCode($statusCode, $status_text);

		//Change the current location using 'Location'
		$this->setHeader('Location', $header);

		return $this;
	}

	/**
	 * Sets HTTP response body
	 *
	 *<code>
	 *	$response->setContent("<h1>Hello!</h1>");
	 *</code>
	 *
	 * @param string $content
	 * @return \Phalcon\Http\ResponseInterface
	 * @throws Exception
	 */
	public function setContent($content)
	{
		if(is_string($content) === false) {
			throw new Exception('Invalid parameter type.');
		}

		$this->_content = $content;

		return $this;
	}

	/**
	 * Sets HTTP response body. The parameter is automatically converted to JSON
	 *
	 *<code>
	 *	$response->setJsonContent(array("status" => "OK"));
	 *</code>
	 *
	 * @param mixed $content
	 * @param int|null $jsonOptions
	 * @return \Phalcon\Http\ResponseInterface
	 */
	public function setJsonContent($content, $jsonOptions = null)
	{
		if(is_null($jsonOptions) === false) {
			$options = (int)$jsonOptions;
		} else {
			$options = 0;
		}

		//@note no return value check 
		$this->_content = json_encode($content, $options);

		return $this;
	}

	/**
	 * Appends a string to the HTTP response body
	 *
	 * @param string $content
	 * @return \Phalcon\Http\ResponseInterface
	 * @throws Exception
	 */
	public function appendContent($content)
	{
		if(is_string($content) === false) {
			throw new Exception('Invalid parameter type.');
		}

		if(is_null($this->_content) === false) {
			$this->_content .= $content;
		} else {
			$this->_content = $content;
		}

		return $this;
	}

	/**
	 * Gets the HTTP response body
	 *
	 * @return string|null
	 */
	public function getContent()
	{
		return $this->_content;
	}

	/**
	 * Check if the response is already sent
	 *
	 * @return boolean
	 */
	public function isSent()
	{
		return $this->_sent;
	}

	/**
	 * Sends headers to the client
	 *
	 * @return \Phalcon\Http\ResponseInterface
	 */
	public function sendHeaders()
	{
		if(is_object($this->_headers) === true) {
			$this->_headers->send();
		}

		return $this;
	}

	/**
	 * Sends cookies to the client
	 *
	 * @return \Phalcon\Http\ResponseInterface
	 */
	public function sendCookies()
	{
		if(is_object($this->_cookies) === true) {
			$this->_cookies->send();
		}

		return $this;
	}

	/**
	 * Prints out HTTP response to the client
	 *
	 * @return \Phalcon\Http\ResponseInterface
	 * @throws Exception
	 */
	public function send()
	{
		if($this->_sent === false) {
			//Send headers
			$this->sendHeaders();
			$this->sendCookies();

			//Output the response body
			$content = $this->_content;
			if(is_string($content) === true &&
				isset($content[0]) === true) {
				echo $content;
			} else {
				if(empty($this->_file) === false) {
					$stream = fopen($this->_file, 'rb');
					if($stream === false) {
						throw new Exception('Error while opening stream.');
					}

					if(fpassthru($stream) === false) {
						throw new Exception('Error while passing stream.');
					}

					if(fclose($stream) === false) {
						throw new Exception('Error while closing stream.');
					}
				}
			}

			$this->_sent = true;
			return $this;
		}

		throw new Exception('Response was already sent');
	}

	/**
	 * Sets an attached file to be sent at the end of the request
	 *
	 * @param string $filePath
	 * @param string|null $attachmentName
	 * @param boolean|null $attachment
	 * @throws Excepiton
	 */
	public function setFileToSend($filePath, $attachmentName = null, $attachment = null)
	{
		/* Type check */
		if(is_string($filePath) === false) {
			throw new Exception('Invalid parameter type.');
		}

		if(is_null($attachment) === true) {
			$attachment = true;
		} elseif(is_bool($attachment) === false) {
			throw new Exception('Invalid parameter type.');
		}

		if(is_string($attachmentName) === false) {
			$attachmentName = basename($filePath);
		}

		/* Execute */
		if($attachment === true) {
			$headers = $this->getHeaders();
			$headers->setRaw('Content-Description: File Transfer');
			$headers->setRaw('Content-Disposition: attachment; filename='.$attachmentName);
			$headers->setRaw('Content-Transfer-Encoding: binary');
		}

		//@note no check if path is valid
		$this->_file = $filePath;

		return $this;
	}
}