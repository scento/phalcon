<?php
/**
 * Request
 *
 * @author Andres Gutierrez <andres@phalconphp.com>
 * @author Eduar Carvajal <eduar@phalconphp.com>
 * @author Wenzel Pünter <wenzel@phelix.me>
 * @version 1.2.6
 * @package Phalcon
*/
namespace Phalcon\Http;

use \Phalcon\Http\RequestInterface,
	\Phalcon\http\Request\Exception,
	\Phalcon\Http\Request\File,
	\Phalcon\DI\InjectionAwareInterface,
	\Phalcon\DiInterface,
	\Phalcon\Text;

/**
 * Phalcon\Http\Request
 *
 * <p>Encapsulates request information for easy and secure access from application controllers.</p>
 *
 * <p>The request object is a simple value object that is passed between the dispatcher and controller classes.
 * It packages the HTTP request environment.</p>
 *
 *<code>
 *	$request = new Phalcon\Http\Request();
 *	if ($request->isPost() == true) {
 *		if ($request->isAjax() == true) {
 *			echo 'Request was made using POST and AJAX';
 *		}
 *	}
 *</code>
 *
 * @see https://github.com/phalcon/cphalcon/blob/1.2.6/ext/http/request.c
 */
class Request implements RequestInterface, InjectionAwareInterface
{
	/**
	 * Deoendency Injector
	 * 
	 * @var null|\Phaclon\DiInterface
	 * @access protected
	*/
	protected $_dependencyInjector;

	/**
	 * Filter
	 * 
	 * @var null
	 * @access protected
	*/
	protected $_filter;

	/**
	 * Raw Body
	 * 
	 * @var null
	 * @access protected
	*/
	protected $_rawBody;

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
	 * Gets a variable from the $_REQUEST superglobal applying filters if needed.
	 * If no parameters are given the $_REQUEST superglobal is returned
	 *
	 *<code>
	 *	//Returns value from $_REQUEST["user_email"] without sanitizing
	 *	$userEmail = $request->get("user_email");
	 *
	 *	//Returns value from $_REQUEST["user_email"] with sanitizing
	 *	$userEmail = $request->get("user_email", "email");
	 *</code>
	 *
	 * @param string|null $name
	 * @param string|array|null $filters
	 * @param mixed $defaultValue
	 * @return mixed
	 * @throws Exception
	 */
	public function get($name = null, $filters = null, $defaultValue = null)
	{
		/* Validate input */
		if(is_string($name) === false && is_null($name) === false) {
			throw new Exception('Invalid parameter type.');
		}

		if(is_string($filters) === false && is_array($filter) === false &&
			is_null($filters) === false) {
			throw new Exception('Invalid parameter type.');
		}

		/* Get data */
		if(is_null($name) === false) {
			if(isset($_REQUEST[$name]) === true) {
				$value = $_REQUEST[$name];

				//Apply filters is required
				if(is_null($filters) === false) {
					//Get filter service
					if(is_object($this->_filter) === false) {
						$dependencyInjector = $this->_dependencyInjector;
						if(is_object($this->_dependencyInjector) === false) {
							throw new Exception("A dependency injection object is required to access the 'filter' service");
						}

						$this->_filter = $dependencyInjector->getShared('filter');
					}

					return $this->_filter->sanitize($value, $filters);
				} else {
					return $value;
				}
			}
			
			return $defaultValue;
		}

		return $request;
	}

	/**
	 * Gets a variable from the $_POST superglobal applying filters if needed
	 * If no parameters are given the $_POST superglobal is returned
	 *
	 *<code>
	 *	//Returns value from $_POST["user_email"] without sanitizing
	 *	$userEmail = $request->getPost("user_email");
	 *
	 *	//Returns value from $_POST["user_email"] with sanitizing
	 *	$userEmail = $request->getPost("user_email", "email");
	 *</code>
	 *
	 * @param string|null $name
	 * @param string|array|null $filters
	 * @param mixed $defaultValue
	 * @return mixed
	 * @throws Exception
	 */
	public function getPost($name = null, $filters = null, $defaultValue = null)
	{
		if(is_string($name) === false && is_null($name) === false) {
			throw new Exception('Invalid parmeter type.');
		}

		if(is_string($filters) === false && is_array($filters) === false &&
			is_null($filters) === false) {
			throw new Exception('Invalid parameter type.');
		}

		if(is_null($name) === false) {
			if(isset($_POST[$name]) === true) {
				$value = $_POST[$name];

				if(is_null($filters) === false) {
					if(is_object($this->_filter) === false) {
						$dependencyInjector = $this->_dependencyInjector;
						if(is_object($dependencyInjector) === false) {
							throw new Exception("A dependency injection object is required to access the 'filter' service");
						}

						$this->_filter = $dependencyInjector->getShared('filter');
					}

					return $this->_filter->sanitize($value, $filters);
				}
				
				return $value;
			}

			return $defaultValue;
		}

		return $_POST;
	}

	/**
	 * Gets variable from $_GET superglobal applying filters if needed
	 * If no parameters are given the $_GET superglobal is returned
	 *
	 *<code>
	 *	//Returns value from $_GET["id"] without sanitizing
	 *	$id = $request->getQuery("id");
	 *
	 *	//Returns value from $_GET["id"] with sanitizing
	 *	$id = $request->getQuery("id", "int");
	 *
	 *	//Returns value from $_GET["id"] with a default value
	 *	$id = $request->getQuery("id", null, 150);
	 *</code>
	 *
	 * @param string|null $name
	 * @param string|array|null $filters
	 * @param mixed $defaultValue
	 * @return mixed
	 * @throws Exception
	 */
	public function getQuery($name = null, $filters = null, $defaultValue = null)
	{
		if(is_string($name) === false && is_null($name) === false) {
			throw new Exception('Invalid parameter type.');
		}

		if(is_null($filters) === false && is_string($filters) === false &&
			is_array($filters) === false) {
			throw new Exception('Invalid parameter type.');
		}

		if(is_null($name) === false) {
				if(isset($_GET[$name]) === true) {
					$value = $_GET[$name];

					if(is_null($filters) === false) {
						if(is_object($this->_filter) === false) {
							$dependencyInjector = $this->_dependencyInjector;
							if(is_object($dependencyInjector) === false) {
								throw new Exception("A dependency injection object is required to access the 'filter' service");
							}

							$this->_filter = $dependencyInjector->getShared('filter');
						}

						return $this->_filter->sanitize($value, $filters);
					}

					return $value;
				}

				return $defaultValue;
			}

		return $_GET;
	}

	/**
	 * Gets variable from $_SERVER superglobal
	 *
	 * @param string $name
	 * @return mixed
	 * @throws Exception
	 */
	public function getServer($name)
	{
		if(is_string($name) === false) {
			throw new Exception('Invalid parameter type.');
		}

		if(isset($_SERVER[$name]) === true) {
			return $_SERVER[$name];
		}

		return null;
	}

	/**
	 * Checks whether $_REQUEST superglobal has certain index
	 *
	 * @param string $name
	 * @return boolean
	 * @throws Exception
	 */
	public function has($name)
	{
		if(is_string($name) === false) {
			throw new Exception('Invalid parameter type.');
		}

		return isset($_REQUEST[$name]);
	}

	/**
	 * Checks whether $_POST superglobal has certain index
	 *
	 * @param string $name
	 * @return boolean
	 * @throws Exception
	 */
	public function hasPost($name)
	{
		if(is_string($name) === false) {
			throw new Exception('Invalid parameter type.');
		}

		return isset($_POST[$name]);
	}

	/**
	 * Checks whether $_GET superglobal has certain index
	 *
	 * @param string $name
	 * @return boolean
	 * @throws Exception
	 */
	public function hasQuery($name)
	{
		if(is_string($name) === false) {
			throw new Exception('Invalid parameter type.');
		}

		return isset($_GET[$name]);
	}

	/**
	 * Checks whether $_SERVER superglobal has certain index
	 *
	 * @param string $name
	 * @return mixed
	 * @throws Exception
	 */
	public function hasServer($name)
	{
		if(is_string($name) === false) {
			throw new Exception('Invalid parameter type.');
		}

		return isset($_SERVER[$name]);
	}

	/**
	 * Gets HTTP header from request data
	 *
	 * @param string $header
	 * @return string
	 * @throws Exception
	 */
	public function getHeader($header)
	{
		if(is_string($header) === false) {
			throw new Exception('Invalid parameter type.');
		}

		if(isset($_SERVER[$header]) === true) {
			return $_SERVER[$header];
		} else {
			if(isset($_SERVER['HTTP_'.$header]) === true) {
				return $_SERVER['HTTP_'.$header];
			}
		}

		return '';
	}

	/**
	 * Gets HTTP schema (http/https)
	 *
	 * @return string
	 */
	public function getScheme()
	{
		$https = $this->getServer('HTTPS');
		if(empty($https) === false) {
			if($https === 'off') {
				$scheme = 'http';
			} else {
				$scheme = 'https';
			}
		} else {
			$scheme = 'http';
		}

		return $scheme;
	}

	/**
	 * Checks whether request has been made using ajax. Checks if $_SERVER['HTTP_X_REQUESTED_WITH']=='XMLHttpRequest'
	 *
	 * @return boolean
	 */
	public function isAjax()
	{
		return ($this->getHeader('HTTP_X_REQUESTED_WITH') == 'XMLHttpRequest' ? true : false);
	}

	/**
	 * Checks whether request has been made using SOAP
	 *
	 * @return boolean
	 */
	public function isSoapRequested()
	{
		if(isset($_SERVER['HTTP_SOAPACTION']) === true) {
			return true;
		} elseif(isset($_SERVER['CONTENT_TYPE']) === true) {
			if(strpos($_SERVER['CONTENT_TYPE'], 'application/soap+xml') !== false) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Checks whether request has been made using any secure layer
	 *
	 * @return boolean
	 */
	public function isSecureRequest()
	{
		return ($this->getScheme() === 'https' ? true : false);
	}

	/**
	 * Gets HTTP raw request body
	 *
	 * @return string
	 */
	public function getRawBody()
	{
		if(is_string($this->_rawBody)) {
			return $this->_rawBody;
		} elseif($_SERVER['REQUEST_METHOD'] === 'POST') {
			$raw = file_get_contents('php://input');
			if($raw === false) {
				$raw = '';
			}

			$this->_rawBody = $raw;
			return $raw;
		}

		return '';
	}

	/**
	 * Gets decoded JSON HTTP raw request body
	 *
	 * @return mixed
	 */
	public function getJsonRawBody()
	{
		$rawBody = $this->getRawBody();
		if(is_string($rawBody) === true) {
			return json_decode($rawBody, 0);
		}
	}

	/**
	 * Gets active server address IP
	 *
	 * @return string
	 */
	public function getServerAddress()
	{
		if(isset($_SERVER['SERVER_ADDR']) === true) {
			return $_SERVER['SERVER_ADDR'];
		}

		return gethostbyname('localhost');
	}

	/**
	 * Gets active server name
	 *
	 * @return string
	 */
	public function getServerName()
	{
		if(isset($_SERVER['SERVER_NAME']) === true) {
			return $_SERVER['SERVER_NAME'];
		}

		return 'localhost';
	}

	/**
	 * Gets information about schema, host and port used by the request
	 *
	 * @return string
	 */
	public function getHttpHost()
	{
		//Get the server name from _SERVER['HTTP_HOST']
		$httpHost = $this->getServer('HTTP_HOST');
		if(isset($httpHost) === true) {
			return $httpHost;
		}

		//Get current scheme
		$scheme = $this->getScheme();

		//Get the server name from _SERVER['SERVER_NAME']
		$serverName = $this->getServer['SERVER_NAME'];

		//Get the server port from _SERVER['SERVER_PORT']
		$serverPort = $this->getServer['SERVER_PORT'];

		//Check if the request is a standard http
		$isStdName = ($scheme === 'http' ? true : false);
		$isStdPort = ($port === 80 ? true : false);
		$isStdHttp = ($isStdName && $isStdPort ? true : false);

		//Check if the request is a secure http request
		$isSecureScheme = ($scheme === 'https' ? true : false);
		$isSecurePort = ($port === 443 ? true : false);
		$isSecureHttp = ($isSecureScheme && $isSecurePort ? true : false);

		//If is is a standard http we return the server name only
		if($isStdHttp === true ||
			$isSecureHttp === true) {
			return $name;
		}

		return $name.':'.$port;
	}

	/**
	 * Gets most possible client IPv4 Address. This method search in $_SERVER['REMOTE_ADDR'] and optionally in $_SERVER['HTTP_X_FORWARDED_FOR']
	 *
	 * @param boolean|null $trustForwardedHeader
	 * @return string
	 * @throws Exception
	 */
	public function getClientAddress($trustForwardedHeader = null)
	{
		if(is_null($trustForwardedHeader) === true) {
			$trustForwardedHeader = false;
		} elseif(is_bool($trustForwardedHeader) === false) {
			throw new Exception('Invalid parameter type.');
		}

		//Proxies use this IP
		if($trustForwardedHeader === true &&
			isset($_SERVER['HTTP_X_FORWARDED_FOR']) === true) {
			$address = $_SERVER['HTTP_X_FORWARDED_FOR'];
		}

		if(isset($address) === false) {
			if(isset($_SERVER['REMOTE_ADDR']) === true) {
				$address = $_SERVER['REMOTE_ADDR'];
			}
		}

		if(isset($address) === true) {
			if(strpos($address, ',') !== false) {
				//The client address has multiple parts, only return the first part
				$addresses = explode(',', $address);
				return $addresses[0];
			}

			return $address;
		}

		return false;
	}

	/**
	 * Gets HTTP method which request has been made
	 *
	 * @return string
	 */
	public function getMethod()
	{
		if(isset($_SERVER['REQUEST_METHOD']) === true) {
			return $_SERVER['REQUEST_METHOD'];
		}

		return '';
	}

	/**
	 * Gets HTTP user agent used to made the request
	 *
	 * @return string
	 */
	public function getUserAgent()
	{
		if(isset($_SERVER['HTTP_USER_AGENT']) === true) {
			return $_SERVER['HTTP_USER_AGENT'];
		} else {
			return '';
		}
	}

	/**
	 * Check if HTTP method match any of the passed methods
	 *
	 * @param string|array $methods
	 * @return boolean
	 */
	public function isMethod($methods)
	{
		$methodHttp = $this->getMethod();

		if(is_string($methods) === true) {
			return ($methods == $methodHttp ? true : false);
		} else {
			foreach($methods as $method) {
				if($method === $methodHttp) {
					return true;
				}
			}
		}
		
		return false;
	}

	/**
	 * Checks whether HTTP method is POST. if $_SERVER['REQUEST_METHOD']=='POST'
	 *
	 * @return boolean
	 */
	public function isPost()
	{
		return ($this->getMethod() === 'POST' ? true : false);
	}

	/**
	 * Checks whether HTTP method is GET. if $_SERVER['REQUEST_METHOD']=='GET'
	 *
	 * @return boolean
	 */
	public function isGet()
	{
		return ($this->getMethod() === 'GET' ? true : false);
	}

	/**
	 * Checks whether HTTP method is PUT. if $_SERVER['REQUEST_METHOD']=='PUT'
	 *
	 * @return boolean
	 */
	public function isPut()
	{
		return ($this->getMethod() === 'PUT' ? true : false);
	}

	/**
	 * Checks whether HTTP method is PATCH. if $_SERVER['REQUEST_METHOD']=='PATCH'
	 *
	 * @return boolean
	 */
	public function isPatch()
	{
		return ($this->getMethod() === 'PATCH' ? true : false);
	}

	/**
	 * Checks whether HTTP method is HEAD. if $_SERVER['REQUEST_METHOD']=='HEAD'
	 *
	 * @return boolean
	 */
	public function isHead()
	{
		return ($this->getMethod() === 'HEAD' ? true : false);
	}

	/**
	 * Checks whether HTTP method is DELETE. if $_SERVER['REQUEST_METHOD']=='DELETE'
	 *
	 * @return boolean
	 */
	public function isDelete()
	{
		return ($this->getMethod() === 'DELETE' ? true : false);
	}

	/**
	 * Checks whether HTTP method is OPTIONS. if $_SERVER['REQUEST_METHOD']=='OPTIONS'
	 *
	 * @return boolean
	 */
	public function isOptions()
	{
		return ($this->getMethod() === 'OPTIONS' ? true : false);
	}

	/**
	 * Checks whether request includes attached files
	 *
	 * @param null|boolean $notErrored
	 * @return boolean
	 * @throws Exception
	 */
	public function hasFiles($notErrored = null)
	{
		if(is_null($notErrored) === true) {
			$notErrored = true;
		} elseif(is_bool($notErrored) === false) {
			throw new Exception('Invalid parameter type.');
		}

		if(is_array($_FILES) === false) {
			return 0;
		}

		$count = 0;
		foreach($_FILES as $file) {
			if($notErrored === false) {
				++$count;
			} else {
				if(isset($file['error']) === true) {
					foreach($file['error'] as $error) {
						if($error === \UPLOAD_ERR_OK) {
							++$count;
							break;
						}
					}
				}
			}
		}

		return $count;
	}

	/**
	 * Gets attached files as \Phalcon\Http\Request\File instances
	 *
	 * @param boolean|null $notErrored
	 * @return \Phalcon\Http\Request\File[]|null
	 * @throws Exception
	 */
	public function getUploadedFiles($notErrored = null)
	{
		if(is_null($notErrored) === true) {
			$notErrored = true;
		} elseif(is_bool($notErrored) === false) {
			throw new Exception('Invalid parameter type.');
		}

		if(is_array($_FILES) === false ||
			count($_FILES) === 0) {
			return;
		}

		$result = array();

		foreach($_FILES as $name => $file) {
			//Skip if upload failed
			if($notErrored === true) {
				if(isset($file['error']) === true) {
					foreach($file['error'] as $error) {
						if($error !== \UPLOAD_ERR_OK) {
							continue 2;
						}
					}
				}
			}

			//Create object
			$result[] = new File($file, $name);
		}

		return $result;
	}

	/**
	 * Returns the available headers in the request
	 *
	 * @return array
	 */
	public function getHeaders()
	{
		if(is_array($_SERVER) === false) {
			return;
		}

		$result = array();
		foreach($_SERVER as $key => $value) {
			if(Text::startsWith($key, 'HTTP_') === true) {
				$result[] = substr($key, 5);
			}
		}

		return $result;
	}

	/**
	 * Gets web page that refers active request. ie: http://www.google.com
	 *
	 * @return string
	 */
	public function getHTTPReferer()
	{
		if(isset($_SERVER['HTTP_REFERER']) === true) {
			return $_SERVER['HTTP_REFERER'];
		}

		return '';
	}

	/**
	 * Process a request header and return an array of values with their qualities
	 *
	 * @param string $serverIndex
	 * @param string $name
	 * @return array
	 * @throws Exception
	 */
	protected function _getQualityHeader($serverIndex, $name)
	{
		if(is_string($serverIndex) === false ||
			is_string($name) === false) {
			throw new Exception('Invalid parameter type.');
		}

		$return = array();

		$parts = preg_split('/,\\s*/', $this->getServer($serverIndex));

		foreach($parts as $part) {
			$headerParts = explode(';', $part);
			if(isset($headerParts[1]) === true) {
				$quality = substr($headerParts[1], 2);
			} else {
				$quality = 1;
			}

			$return[] = array($name => $headerParts[0], 'quality' => $quality);
		}

		return $return;
	}

	/**
	 * Process a request header and return the one with best quality
	 *
	 * @param array $qualityParts
	 * @param string $name
	 * @return string
	 * @throws Exception
	 */
	protected function _getBestQuality($qualityParts, $name)
	{
		if(is_array($qualityParts) === false ||
			is_string($name) === false) {
			throw new Exception('Invalid parameter type.');
		}

		$quality = 0;
		$i = 0;

		foreach($qualityParts as $accept) {
			if($i === 0) {
				$quality = $accept['quality'];
				$selectedName = $accept[$name];
			} else {
				if($quality < $accept['quality']) {
					$quality = $accept['quality'];
					$selectedName = $accept[$name];
				}
			}

			++$i;
		}

		return $selectedName;
	}

	/**
	 * Gets array with mime/types and their quality accepted by the browser/client from $_SERVER['HTTP_ACCEPT']
	 *
	 * @return array
	 */
	public function getAcceptableContent()
	{
		return $this->_getQualityHeader('HTTP_ACCEPT', 'accept');
	}

	/**
	 * Gets best mime/type accepted by the browser/client from $_SERVER['HTTP_ACCEPT']
	 *
	 * @return array
	 */
	public function getBestAccept()
	{
		return $this->_getBestQuality($this->getAcceptableContent(), 'accept');
	}

	/**
	 * Gets charsets array and their quality accepted by the browser/client from $_SERVER['HTTP_ACCEPT_CHARSET']
	 *
	 * @return array
	 */
	public function getClientCharsets()
	{
		return $this->_getQualityHeader('HTTP_ACCEPT_CHARSET', 'charset');
	}

	/**
	 * Gets best charset accepted by the browser/client from $_SERVER['HTTP_ACCEPT_CHARSET']
	 *
	 * @return string
	 */
	public function getBestCharset()
	{
		return $this->_getBestQuality($this->getClientCharsets(), 'charset');
	}

	/**
	 * Gets languages array and their quality accepted by the browser/client from $_SERVER['HTTP_ACCEPT_LANGUAGE']
	 *
	 * @return array
	 */
	public function getLanguages()
	{
		return $this->_getQualityHeader('HTTP_ACCEPT_LANGUAGE', 'language');
	}

	/**
	 * Gets best language accepted by the browser/client from $_SERVER['HTTP_ACCEPT_LANGUAGE']
	 *
	 * @return string
	 */
	public function getBestLanguage()
	{
		return $this->_getBestQuality($this->getLanguages(), 'language');
	}
}