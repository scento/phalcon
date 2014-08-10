<?php
/**
 * Tag
 *
 * @author Andres Gutierrez <andres@phalconphp.com>
 * @author Eduar Carvajal <eduar@phalconphp.com>
 * @author Wenzel Pünter <wenzel@phelix.me>
 * @version 1.2.6
 * @package Phalcon
*/
namespace Phalcon;

use \Phalcon\Tag\Exception as TagException,
	\Phalcon\DiInterface,
	\Phalcon\DI,
	\Phalcon\EscaperInterface,
	\Phalcon\Mvc\UrlInterface,
	\Phalcon\Tag\Select;

/**
 * Phalcon\Tag
 *
 * Phalcon\Tag is designed to simplify building of HTML tags.
 * It provides a set of helpers to generate HTML in a dynamic way.
 * This component is an abstract class that you can extend to add more helpers.
 * 
 * @see https://github.com/phalcon/cphalcon/blob/1.2.6/ext/tag.c
 */
class Tag
{
	/**
	 * HTML 32
	 * 
	 * @var int
	*/
	const HTML32 = 1;

	/**
	 * HTML 401 Strict
	 * 
	 * @var int
	*/
	const HTML401_STRICT = 2;

	/**
	 * HTML 401 Transitional
	 * 
	 * @var int
	*/
	const HTML401_TRANSITIONAL = 3;

	/**
	 * HTML 401 Frameset
	 * 
	 * @var int
	*/
	const HTML401_FRAMESET = 4;

	/**
	 * HTML 5
	 * 
	 * @var int
	*/
	const HTML5 = 5;

	/**
	 * XHTML 10 Strict
	 * 
	 * @var int
	*/
	const XHTML10_STRICT = 6;

	/**
	 * XHTML 10 Transitional
	 * 
	 * @var int
	*/
	const XHTML10_TRANSITIONAL = 7;

	/**
	 * XHTML 10 Frameset
	 * 
	 * @var int
	*/
	const XHTML10_FRAMESET = 8;

	/**
	 * XHTML 11
	 * 
	 * 	@var int
	*/
	const XHTML11 = 9;

	/**
	 * XHTML 20
	 * 
	 * @var int
	*/
	const XHTML20 = 10;

	/**
	 * XHTML 5
	 * 
	 * @var int
	*/
	const XHTML5 = 11;

	/**
	 * Display Values
	 * 
	 * @var null|array
	 * @access protected
	*/
	protected static $_displayValues = null;

	/**
	 * Document Title
	 * 
	 * @var null
	 * @access protected
	*/
	protected static $_documentTitle = null;

	/**
	 * Document Type
	 * 
	 * @var int
	 * @access protected
	*/
	protected static $_documentType = 11;

	/**
	 * Dependency Injector
	 * 
	 * @var null|\Phalcon\DiInterface
	 * @access protected
	*/
	protected static $_dependencyInjector = null;

	/**
	 * URL Service
	 * 
	 * @var null|\Phalcon\Mvc\UrlInterface
	 * @access protected
	*/
	protected static $_urlService = null;

	/**
	 * Dispatcher Service
	 * 
	 * @var null
	 * @access protected
	*/
	protected static $_dispatcherService = null;

	/**
	 * Escaper Service
	 * 
	 * @var null|\Phalcon\EscaperInterface
	 * @access protected
	*/
	protected static $_escaperService = null;

	/**
	 * Auto-Escape
	 * 
	 * @var boolean
	 * @access protected
	*/
	protected static $_autoEscape = true;

	/**
	 * Sets the dependency injector container.
	 *
	 * @param \Phalcon\DiInterface $dependencyInjector
	 * @throws TagException
	 */
	public static function setDI($dependencyInjector)
	{
		//@note there is originally no instanceof check
		if(is_object($dependencyInjector) === false ||
			$dependencyInjector instanceof DiInterface === false) {
			throw new TagException('Parameter dependencyInjector must be an Object');
		}

		self::$_dependencyInjector = $dependencyInjector;
	}

	/**
	 * Internally gets the dependency injector
	 *
	 * @return \Phalcon\DiInterface|null
	 */
	public static function getDI()
	{
		return self::$_dependencyInjector;
	}

	/**
	 * Return a URL service from the default DI
	 *
	 * @return \Phalcon\Mvc\UrlInterface
	 * @throws TagException
	 */
	public static function getUrlService()
	{
		if(is_object(self::$_urlService) === false) {
			if(is_object(self::$_dependencyInjector) === false) {
				$dependency_injector = DI::getDefault();
			} else {
				$dependency_injector = self::$_dependencyInjector;
			}

			if(is_object($dependency_injector) === false) {
				throw new TagException('A dependency injector container is required to obtain the "url" service');
			}

			$url = $dependency_injector->getShared('url');

			//@note added type check
			if(is_object($url) === false || $url instanceof UrlInterface === false) {
				throw new TagException('Invalid url service.');
			}

			self::$_urlService = $url;
		} else {
			$url = self::$_urlService;
		}

		return $url;
	}

	/**
	 * Returns an Escaper service from the default DI
	 *
	 * @return \Phalcon\EscaperInterface
	 * @throws TagException
	 */
	public static function getEscaperService()
	{
		$escaper = self::$_escaperService;
		if(is_object($escaper) === false) {
			$dependency_injector = self::$_dependencyInjector;
			if(is_object($dependency_injector) === false) {
				$dependency_injector = DI::getDefault();
			}

			if(is_object($dependency_injector) === false) {
				throw new TagException('A dependency injector container is required to obtain the "escaper" service');
			}

			$escaper = $dependency_injector->getShared('escaper');

			//@note added type check
			if(is_object($escaper) === false || 
				$escaper instanceof EscaperInterface === false) {
				throw new TagException('Invalid escaper service.');
			}

			self::$_escaperService = $escaper;
		}

		return $escaper;
	}

	/**
	 * Get current autoescape mode
	 *
	 * @return bool
	 */
	public static function getAutoescape()
	{
		return self::$_autoEscape;
	}

	/**
	 * Set autoescape mode in generated html
	 *
	 * @param boolean $autoescape
	 * @throws TagException
	 */
	public static function setAutoescape($autoescape)
	{
		//@note added type check
		if(is_bool($autoescape) === false) {
			throw new TagException('Invalid parameter type.');
		}

		self::$_autoEscape = $autoescape;
	}

	/**
	 * Assigns default values to generated tags by helpers
	 *
	 * <code>
	 * //Assigning "peter" to "name" component
	 * \Phalcon\Tag::setDefault("name", "peter");
	 *
	 * //Later in the view
	 * echo \Phalcon\Tag::textField("name"); //Will have the value "peter" by default
	 * </code>
	 *
	 * @param string $id
	 * @param scalar $value
	 * @throws TagException
	 */
	public static function setDefault($id, $value)
	{
		//@note added typecheck for $id
		if(is_string($id) === false) {
			throw new TagException('Invalid parameter type.');
		}

		//@note used instead of is_array and is_object is_scalar
		if(is_null($value) === false && is_scalar($value) === false) {
			throw new TagException('only scalar values can be assigned to UI components');
		}

		if(is_array(self::$_displayValues) === false) {
			self::$_displayValues = array();
		}

		self::$_displayValues[$id] = $value;
	}

	/**
	 * Assigns default values to generated tags by helpers
	 *
	 * <code>
	 * //Assigning "peter" to "name" component
	 * \Phalcon\Tag::setDefaults(array("name" => "peter"));
	 *
	 * //Later in the view
	 * echo \Phalcon\Tag::textField("name"); //Will have the value "peter" by default
	 * </code>
	 *
	 * @param array $values
	 * @throws TagException
	 */
	public static function setDefaults($values)
	{
		if(is_array($values) === false) {
			throw new TagException('An array is required as default values');
		}

		self::$_displayValues = $values;
	}


	/**
	 * Alias of \Phalcon\Tag::setDefault
	 *
	 * @param string $id
	 * @param string $value
	 */
	public static function displayTo($id, $value)
	{
		self::setDefault($id, $value);
	}

	/**
	 * Check if a helper has a default value set using \Phalcon\Tag::setDefault or value from $_POST
	 *
	 * @param string $name
	 * @return boolean
	 */
	public static function hasValue($name)
	{
		if(isset(self::$_displayValues) === true) {
			return true;
		} else {
			return isset($_POST[$name]);
		}
	}

	/**
	 * Every helper calls this function to check whether a component has a predefined
	 * value using \Phalcon\Tag::setDefault or value from $_POST
	 *
	 * @param string $name
	 * @param array|null $params
	 * @return mixed
	 * @throws TagException
	 */
	public static function getValue($name, $params = null)
	{
		//@note added type check
		if(is_string($name) === false) {
			throw new TagException('Invalid parameter type.');
		}

		if(is_null($params) === true) {
			$params = array();
		} elseif(is_array($params) === false) {
			//@note added type check
			throw new TagException('Invalid parameter type.');
		}

		if(isset($params['value']) === true) {
			$value = $params['value'];
		} else {
			//Check if there is a predefined value for it
			if(isset(self::$_displayValues[$name]) === true) {
				$value = self::$_displayValues[$name];
			} else {
				//Check if there is a post value for the item
				if(isset($_POST[$name]) === true) {
					//@warning This value is not escaped or validated!
					$value = $_POST[$name];
				} else {
					return null;
				}
			}
		}

		return $value;
	}

	/**
	 * Resets the request and internal values to avoid those fields will have any default value
	 */
	public static function resetInput()
	{
		self::$_displayValues = array();

		//@warning $_POST is not the only array containing POST variables...
		$_POST = array();
	}

	/**
	 * Bind Escaper
	 * 
	 * @param array|null $params
	 * @return \Phalcon\EscaperInterface|null
	*/
	private static function getEscaper(&$params = null) {
		$escaper = null;

		if(is_array($params) === true && isset($params['escape']) === true) {
			$autoescape = $params['escape'];
			unset($params['escape']);
		} else {
			$autoescape = self::$_autoEscape;
		}

		try {
			if($autoescape === true) {
				$escaper = self::getEscaperService();
			}
		} catch(\Exception $e) {
			$escaper = null;
		}

		return $escaper;
	}

	/**
	 * Builds a attribute string
	 * 
	 * @param array $params
	 * @return string
	*/
	private static function writeAttributes($params) {

		$escaper = self::getEscaper($params);
		$code = '';

		if(is_null($escaper) === false) {
			//Escaping...
			foreach($params as $key => $value) {
				if(is_string($key) === true) {
					//@note no type check for $value
					$code .= ' '.$key.'="'.$escaper->escapeHtmlAttr($value).'"';
				}
			}
		} else {
			//Without escaping
			foreach($params as $key => $value) {
				if(is_string($key) === true) {
					//@note no type check for $value
					$code .= ' '.$key.'="'.$value.'"';
				}
			}
		}

		return $code;
	}

	/**
	 * Builds a HTML A tag using framework conventions
	 *
	 *<code>
	 *	echo \Phalcon\Tag::linkTo('signup/register', 'Register Here!');
	 *	echo \Phalcon\Tag::linkTo(array('signup/register', 'Register Here!'));
	 *	echo \Phalcon\Tag::linkTo(array('signup/register', 'Register Here!', 'class' => 'btn-primary'));
	 *</code>
	 *
	 * @param array|string $parameters
	 * @param string|null $text
	 * @return string
	 */
	public static function linkTo($parameters, $text = null)
	{
		if(is_null($text) === true) {
			$text = '';
		}

		if(is_array($parameters) === false) {
			$params = array($parameters, $text);
		} else {
			$params = $parameters;
		}

		//action
		if(isset($params[0]) === true) {
			$action = $params[0];
		} elseif(isset($params['action']) === true) {
			$action = $params['action'];
		} else {
			$action = '';
		}

		//link text
		if(isset($params[1]) === true) {
			$link_text = $params[1];
		} elseif(isset($params['text']) === true) {
			$link_text = $params['text'];
			unset($params['text']);
		} else {
			$link_text = '';
		}

		$params['href'] = $this->getUrlService()->get($action);

		return '<a'.self::writeAttributes($params).'>'.$link_text.'</a>';
	}

	/**
	 * Builds generic INPUT tags
	 *
	 * @param string $type
	 * @param array $parameters
	 * @param boolean|null $asValue
	 * @return string
	 * @throws TagException
	 */
	protected static function _inputField($type, $parameters, $as_value = null)
	{
		if(is_string($type) === false) {
			//@note added type check
			throw new TagException('Invalid parameter type.');
		}

		if(is_null($as_value) === true) {
			$as_value = false;
		} elseif(is_bool($as_value) === false) {
			//@note added exception
			throw new TagException('Invalid parameter type.');
		}


		if(is_array($parameters) === false) {
			$params = array($parameters);
		} else {
			$params = array($parameters);
		}

		if($as_value === false) {
			if(isset($params[0]) === false) {
				//@note no isset check
				$id = $params['id'];
				$params[0] = $id;
			}

			if(isset($params['name']) === false) {
				$params['name'] = $params[0];
			} else {
				if($params['name'] != true) {
					$params['name'] = $params[0];
				}
			}

			//Automatically assign the id if the name is not an array
			if(strpos($params[0], '[') != true) {
				if(isset($params['id']) === false) {
					$params['id'] = $params[0];
				}
			}

			$params['value'] = $this->getValue($params[0], $params);
		} else {
			//Use the 'id' as value if the user hadn't set it
			if(isset($params['value']) === false) {
				if(isset($params[0]) === true) {
					$params['value'] = $params[0];
				}
			}
		}

		//@note without any reason, the original sources get an escaper instance...
		//$escaper = self::getEscaper($params);

		$params['type'] = $type;
		$code = '<input'.self::writeAttributes($params);

		if(self::$_documentType > 5) {
			return $code.' />';
		} else {
			return $code.'>';
		}
	}

	/**
	 * Builds INPUT tags that implements the checked attribute
	 *
	 * @param string $type
	 * @param array|mixed $parameters
	 * @return string
	 */
	protected static function _inputFieldChecked($type, $parameters)
	{
		if(is_array($parameters) === false) {
			$params = array($parameters);
		} else {
			$params = $parameters;
		}

		if(isset($params[0]) === false) {
			$params[0] = $params['id'];
		}

		if(isset($params['name']) === false) {
			$params['name'] = $params[0];
		} else {
			if($params['name'] != true) {
				$params['name'] = $params[0];
			}
		}

		//Automatically assign the id if the name is not an array
		if(strpos($params[0], '[') != true) {
			if(isset($params['id']) === false) {
				$params['id'] = $params[0];
			}
		}

		//Automatically check inputs
		if(isset($params['value']) === true) {
			$current_value = $params['value'];
			unset($params['value']);
			$value = $this->getValue($params[0], $params);
			if($value != null && $current_value === $value) {
				$params['checked'] = 'checked';
			}

			$params['value'] = $current_value;
		} else {
			$value = $this->getValue($params[0], $params);

			//Evaluate the value in POST
			if($value == true) {
				$params['checked'] = 'checked';
			}

			$params['value'] = $value;
		}

		$params['type'] = $type;

		$code = '<input'.self::writeAttributes($params);

		if(self::$_documentType > 5) {
			return $code.' />';
		} else {
			return $code.'>';
		}
	}

	/**
	 * Builds a HTML input[type="text"] tag
	 *
	 * <code>
	 *	echo \Phalcon\Tag::textField(array("name", "size" => 30));
	 * </code>
	 *
	 * @param array $parameters
	 * @return string
	 */
	public static function textField($parameters)
	{
		return $this->_inputField('text', $parameters);
	}

	/**
	 * Builds a HTML input[type="number"] tag
	 *
	 * <code>
	 *	echo \Phalcon\Tag::numericField(array("price", "min" => "1", "max" => "5"));
	 * </code>
	 *
	 * @param array $parameters
	 * @return string
	 */
	public static function numericField($parameters)
	{
		return $this->_inputField('number', $parameters);
	}

	/**
	 * Builds a HTML input[type="email"] tag
	 *
	 * <code>
	 *	echo \Phalcon\Tag::emailField("email");
	 * </code>
	 *
	 * @param array $parameters
	 * @return string
	 */
	public static function emailField($parameters)
	{
		return $this->_inputField('email', $parameters);
	}

	/**
	 * Builds a HTML input[type="date"] tag
	 *
	 * <code>
	 *	echo \Phalcon\Tag::dateField(array("born", "value" => "14-12-1980"))
	 * </code>
	 *
	 * @param array $parameters
	 * @return string
	 */
	public static function dateField($parameters)
	{
		return $this->_inputField('date', $parameters);
	}

	/**
	 * Builds a HTML input[type="password"] tag
	 *
	 *<code>
	 * echo \Phalcon\Tag::passwordField(array("name", "size" => 30));
	 *</code>
	 *
	 * @param array $parameters
	 * @return string
	 */
	public static function passwordField($parameters)
	{
		return $this->_inputField('password', $parameters);
	}

	/**
	 * Builds a HTML input[type="hidden"] tag
	 *
	 *<code>
	 * echo \Phalcon\Tag::hiddenField(array("name", "value" => "mike"));
	 *</code>
	 *
	 * @param array $parameters
	 * @return string
	 */
	public static function hiddenField($parameters)
	{
		return $this->_inputField('hidden', $parameters);
	}

	/**
	 * Builds a HTML input[type="file"] tag
	 *
	 *<code>
	 * echo \Phalcon\Tag::fileField("file");
	 *</code>
	 *
	 * @param array $parameters
	 * @return string
	 */
	public static function fileField($parameters)
	{
		return $this->_inputField('file', $parameters);
	}

	/**
	 * Builds a HTML input[type="check"] tag
	 *
	 *<code>
	 * echo \Phalcon\Tag::checkField(array("terms", "value" => "Y"));
	 *</code>
	 *
	 * @param array $parameters
	 * @return string
	 */
	public static function checkField($parameters)
	{
		return $this->_inputFieldChecked('checkbox', $parameters);
	}

	/**
	 * Builds a HTML input[type="radio"] tag
	 *
	 *<code>
	 * echo \Phalcon\Tag::radioField(array("wheather", "value" => "hot"))
	 *</code>
	 *
	 * Volt syntax:
	 *<code>
	 * {{ radio_field('Save') }}
	 *</code>
	 *
	 * @param array $parameters
	 * @return string
	 */
	public static function radioField($parameters)
	{
		return $this->_inputFieldChecked('radio', $parameters);
	}

	/**
	 * Builds a HTML input[type="image"] tag
	 *
	 *<code>
	 * echo \Phalcon\Tag::imageInput(array("src" => "/img/button.png"));
	 *</code>
	 *
	 * Volt syntax:
	 *<code>
	 * {{ image_input('src': '/img/button.png') }}
	 *</code>
	 *
	 * @param array $parameters
	 * @return string
	 */
	public static function imageInput($parameters)
	{
		return $this->_inputField('image', $parameters, true);
	}

	/**
	 * Builds a HTML input[type="submit"] tag
	 *
	 *<code>
	 * echo \Phalcon\Tag::submitButton("Save")
	 *</code>
	 *
	 * Volt syntax:
	 *<code>
	 * {{ submit_button('Save') }}
	 *</code>
	 *
	 * @param array $parameters
	 * @return string
	 */
	public static function submitButton($parameters)
	{
		return $this->_inputField('submit', $parameters, true);
	}

	/**
	 * Builds a HTML SELECT tag using a PHP array for options
	 *
	 *<code>
	 *	echo \Phalcon\Tag::selectStatic("status", array("A" => "Active", "I" => "Inactive"))
	 *</code>
	 *
	 * @param array $parameters
	 * @param array|null $data
	 * @return string
	 * @throws TagException
	 */
	public static function selectStatic($parameters, $data = null)
	{
		if(is_null($data) === true) {
			$data = array();
		} elseif(is_array($data) === false) {
			//@note added exception
			throw new TagException('Invalid parameter type.');
		}

		return Select::selectField($parameters, $data);
	}

	/**
	 * Builds a HTML SELECT tag using a \Phalcon\Mvc\Model resultset as options
	 *
	 *<code>
	 *	echo \Phalcon\Tag::select(array(
	 *		"robotId",
	 *		Robots::find("type = 'mechanical'"),
	 *		"using" => array("id", "name")
	 * 	));
	 *</code>
	 *
	 * Volt syntax:
	 *<code>
	 * {{ select("robotId", robots, "using": ["id", "name"]) }}
	 *</code>
	 *
	 * @param array $parameters
	 * @param null|array $data
	 * @return string
	 * @throws TagException
	 */
	public static function select($parameters, $data = null)
	{
		if(is_null($data) === true) {
			$data = array();
		} elseif(is_array($data) === false) {
			//@note added type-check
			throw new TagException('Invalid parameter type.');
		}

		return Select::selectField($parameters, $data);
	}

	/**
	 * Builds a HTML TEXTAREA tag
	 *
	 *<code>
	 * echo \Phalcon\Tag::textArea(array("comments", "cols" => 10, "rows" => 4))
	 *</code>
	 *
	 * Volt syntax:
	 *<code>
	 * {{ text_area("comments", "cols": 10, "rows": 4) }}
	 *</code>
	 *
	 * @param array $parameters
	 * @return string
	 */
	public static function textArea($parameters)
	{
		if(is_array($parameters) === false) {
			$params = array($parameters);
		} else {
			$params =  $parameters;
		}

		if(isset($params[0]) === false) {
			if(isset($params['id']) === true) {
				$params[0] = $params['id'];
			}
		}

		if(isset($params['name']) === false) {
			$params['name'] = $params[0];
		} else {
			if($params['name'] != true) {
				$params['name'] = $params[0];
			}
		}

		if(isset($params['id']) === false) {
			$params['id'] = $params[0];
		}

		try {
			$escaper = self::getEscaper($params);
		} catch(\Exception $e) {
			return null;
		}

		if(is_null($escaper) === false) {
			$escaped = $escaper->escapeHtmlAttr($this->getValue($params[0], $params));
		} else {
			$escaped = $this->getValue($params[0], $params);
		}

		return '<textarea'.self::writeAttributes($params).'>'.$escaped.'</textarea>';
	}

	/**
	 * Builds a HTML FORM tag
	 *
	 * <code>
	 * echo \Phalcon\Tag::form("posts/save");
	 * echo \Phalcon\Tag::form(array("posts/save", "method" => "post"));
	 * </code>
	 *
	 * Volt syntax:
	 * <code>
	 * {{ form("posts/save") }}
	 * {{ form("posts/save", "method": "post") }}
	 * </code>
	 *
	 * @param array|null $parameters
	 * @return string
	 */
	public static function form($parameters = null)
	{
		if(is_null($parameters) === true) {
			$parameters = array();
		}

		if(is_array($parameters) === false) {
			$params = array($parameters);
		} else {
			$params = $parameters;
		}

		if(isset($params[0]) === true) {
			$params_action = $params[0];
		} else {
			if(isset($params['action']) === true) {
				$params_action = (string)$params['action'];
			} else {
				$params_action = null;
			}
		}

		//By default the method is POST
		if(isset($params['method']) === false) {
			$params['method'] = 'post';
		}

		$action = '';

		if(is_null($params_action) === false) {
			$url = self::getUrlService();
			$action = $url->get($params_action);
		}

		//Check for extra parameters
		if(isset($params['parameters']) === true) {
			$parameters = $params['parameters'];
			$action .= '?'.$parameters;
		}

		if(is_null($action) === false) {
			$params['action'] = $action;
		}

		return '<form'.self::writeAttributes($params).'>';
	}

	/**
	 * Builds a HTML close FORM tag
	 *
	 * @return string
	 */
	public static function endForm()
	{
		return '</form>';
	}

	/**
	 * Set the title of view content
	 *
	 *<code>
	 * \Phalcon\Tag::setTitle('Welcome to my Page');
	 *</code>
	 *
	 * @param string $title
	 * @throws TagException
	 */
	public static function setTitle($title)
	{
		//@note added type-check
		if(is_string($title) === false) {
			throw new TagException('Invalid parameter type.');
		}

		self::$_documentTitle = $title;
	}

	/**
	 * Appends a text to current document title
	 *
	 * @param string $title
	 * @throws TagException
	 */
	public static function appendTitle($title)
	{
		//@note added type-check
		if(is_string($title) === false) {
			throw new TagException('Invalid parameter type.');
		}

		self::$_documentTitle = self::$_documentTitle.$title;
	}

	/**
	 * Prepends a text to current document title
	 *
	 * @param string $title
	 * @throws TagException
	 */
	public static function prependTitle($title)
	{
		//@note added type-check
		if(is_string($title) === false) {
			throw new TagException('Invalid parameter type.');
		}

		self::$_documentTitle = $title.self::$_documentTitle;
	}

	/**
	 * Gets the current document title
	 *
	 * <code>
	 * 	echo \Phalcon\Tag::getTitle();
	 * </code>
	 *
	 * <code>
	 * 	{{ get_title() }}
	 * </code>
	 *
	 * @param boolean|null $tags
	 * @return string
	 */
	public static function getTitle($tags = null)
	{
		if(is_null($tags) === true) {
			$tags = true;
		}

		if($tags === true) {
			return '<title>'.self::$_documentTitle.'</title>';
		} else {
			return self::$_documentTitle;
		}
	}

	/**
	 * Builds a LINK[rel="stylesheet"] tag
	 *
	 * <code>
	 * 	echo \Phalcon\Tag::stylesheetLink("http://fonts.googleapis.com/css?family=Rosario", false);
	 * 	echo \Phalcon\Tag::stylesheetLink("css/style.css");
	 * </code>
	 *
	 * Volt Syntax:
	 *<code>
	 * 	{{ stylesheet_link("http://fonts.googleapis.com/css?family=Rosario", false) }}
	 * 	{{ stylesheet_link("css/style.css") }}
	 *</code>
	 *
	 * @param array|null $parameters
	 * @param boolean|null $local
	 * @return string
	 * @throws TagException
	 */
	public static function stylesheetLink($parameters = null, $local = null)
	{
		/* Type check */
		if(is_null($local) === true) {
			$local = false;
		} elseif(is_bool($local) === false) {
			throw new TagException('Invalid parameter type.');
		}

		/* Set default values */
		$params = array('rel' => 'stylesheet', 'href' => '', 'type' => 'text/css');

		/* Parse parameters */
		if(is_array($parameters) === false) {
			$params['href'] = (string)$parameters;
		} else {
			if(isset($parameters['href']) === false) {
				$params['href'] = $parameters[0];
				unset($parameters[0]);
			}

			if(isset($parameters['local']) === true) {
				$local = (bool)$parameters['local'];
				unset($parameters['local']);
			}

			$params = array_merge($parameters, $params);
		}

		/* Manipulate values */
		if($local === true) {
			//URLs are generated through the 'url' service
			$url = self::getUrlService();
			$params['href'] = $url->getStatic($params['href']);
		}

		/* Return string */
		return '<link rel="stylesheet"'.self::writeAttributes($params).
			(self::$_documentType > 5 ? ' />' : '>\n');
	}

	/**
	 * Builds a SCRIPT[type="javascript"] tag
	 *
	 * <code>
	 * 	echo \Phalcon\Tag::javascriptInclude("http://ajax.googleapis.com/ajax/libs/jquery/1.7.1/jquery.min.js", false);
	 * 	echo \Phalcon\Tag::javascriptInclude("javascript/jquery.js");
	 * </code>
	 *
	 * Volt syntax:
	 * <code>
	 * {{ javascript_include("http://ajax.googleapis.com/ajax/libs/jquery/1.7.1/jquery.min.js", false) }}
	 * {{ javascript_include("javascript/jquery.js") }}
	 * </code>
	 *
	 * @param array|null $parameters
	 * @param boolean|null $local
	 * @return string
	 * @throws TagException
	 */
	public static function javascriptInclude($parameters = null, $local = null)
	{
		/* Type check */
		if(is_null($local) === true) {
			$local = false;
		} elseif(is_bool($local) === false) {
			throw new TagException('Invalid parameter type.');
		}

		/* Set default values */
		$params = array('src' => '', 'type' => 'text/javascript');

		/* Parse parameters */
		if(is_array($parameters) === false) {
			$params['src'] = (string)$parameters;
		} else {
			if(isset($parameters['src']) === false) {
				$params['src'] = $parameters[0];
				unset($parameters[0]);
			}

			if(isset($parameters['local']) === true) {
				$local = (bool)$parameters['local'];
				unset($parameters['local']);
			}

			$params = array_merge($parameters, $params);
		}

		/* Manipulate values */
		if($local === true) {
			//URLs are generated through the 'url' service
			$url = self::getUrlService();
			$params['src'] = $url->getStatic($params['src']);
		}

		/* Return string */
		return '<script'.self::writeAttributes($params)."></script>\n";
	}

	/**
	 * Builds HTML IMG tags
	 *
	 * <code>
	 * 	echo \Phalcon\Tag::image("img/bg.png");
	 * 	echo \Phalcon\Tag::image(array("img/photo.jpg", "alt" => "Some Photo"));
	 * </code>
	 *
	 * Volt Syntax:
	 * <code>
	 * 	{{ image("img/bg.png") }}
	 * 	{{ image("img/photo.jpg", "alt": "Some Photo") }}
	 * 	{{ image("http://static.mywebsite.com/img/bg.png", false) }}
	 * </code>
	 *
	 * @param array|null $parameters
	 * @param boolean|null $local
	 * @return string
	 * @throws TagException
	 */
	public static function image($parameters = null, $local = null)
	{
		/* Type check */
		if(is_null($local) === true) {
			$local = false;
		} elseif(is_bool($local) === false) {
			throw new TagException('Invalid parameter type.');
		}

		/* Set default values */
		$params = array('src' => '');

		/* Parse parameters */
		if(is_array($parameters) === false) {
			$params['src'] = (string)$parameters;
		} else {
			if(isset($parameters['src']) === false) {
				$params['src'] = $parameters[0];
				unset($parameters[0]);
			}

			if(isset($parameters['local']) === true) {
				$local = (bool)$parameters['local'];
				unset($parameters['local']);
			}

			$params = array_merge($parameters, $params);
		}

		/* Manipulate values */
		if($local === false) {
			//URLs are generated through the 'url' service
			$url = self::getUrlService();
			$params['src'] = $url->getStatic($params['src']);
		}

		/* Return string */
		return '<img'.self::writeAttributes($params).
			(self::$_documentType > 5 ? ' />' : '>');
	}

	/**
	 * Converts texts into URL-friendly titles
	 *
	 *<code>
	 * echo \Phalcon\Tag::friendlyTitle('These are big important news', '-')
	 *</code>
	 *
	 * @param string $text
	 * @param string|null $separator
	 * @param boolean|null $lowercase
	 * @return string
	 * @throws TagException
	 */
	public static function friendlyTitle($text, $separator = null, $lowercase = null)
	{
		/* Type check */
		if(is_string($text) === false) {
			throw new TagException('Invalid parameter type.');
		}

		if(is_null($separator) === true) {
			$separator = '-';
		} elseif(is_string($separator) === false) {
			throw new TagException('Invalid parameter type.');
		}

		if(is_null($lowercase) === true) {
			$lowercase = true;
		} elseif(is_bool($lowercase) === false) {
			throw new TagException('Invalid parameter type.');
		}

		/* Manipulate text */
		$friendly = preg_replace('~[^a-z0-9A-Z]+~', $separator, $text);
		if($lowercase === true) {
			$friendly = strtolower($friendly);
		}

		/* Return string */
		return $friendly;
	}

	/**
	 * Set the document type of content
	 *
	 * @param int $doctype
	 * @throws TagException
	 */
	public static function setDocType($doctype)
	{
		//@note replaced string with integer!
		if(is_int($doctype) === false) {
			throw new TagException('Invalid parameter type.');
		}

		self::$_documentType = $doctype;
	}

	/**
	 * Get the document type declaration of content
	 *
	 * @return string|null
	 */
	public static function getDocType()
	{
		switch(self::$_documentType) {
			case self::HTML32:
				return "<!DOCTYPE html PUBLIC \"-//W3C//DTD HTML 3.2 Final//EN\">\n";
				break;
			case self::HTML401_STRICT:
				return "<!DOCTYPE html PUBLIC \"-//W3C//DTD HTML 4.01//EN\"\n\t\"http://www.w3.org/TR/html4/strict.dtd\">\n";
				break;
			case self::HTML401_TRANSITIONAL:
				return "<!DOCTYPE html PUBLIC \"-//W3C//DTD HTML 4.01 Transitional//EN\"\n\t\"http://www.w3.org/TR/html4/loose.dtd\">\n";
				break;
			case self::HTML401_FRAMESET:
				return "<!DOCTYPE html PUBLIC \"-//W3C//DTD HTML 4.01 Frameset//EN\"\n\t\"http://www.w3.org/TR/html4/frameset.dtd\">\n";
				break;
			case self::HTML5:
				return "<!DOCTYPE html>\n";
				break;
			case self::XHTML10_STRICT:
				return "<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Strict//EN\"\n\t\"http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd\">\n";
				break;
			case self::XHTML10_TRANSITIONAL:
				return "<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Transitional//EN\"\n\t\"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd\">\n";
				break;
			case self::XHTML10_FRAMESET:
				return "<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Frameset//EN\"\n\t\"http://www.w3.org/TR/xhtml1/DTD/xhtml1-frameset.dtd\">\n";
				break;
			case self::XHTML11:
				return "<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.1//EN\"\n\t\"http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd\">\n";
				break;
			case self::XHTML20:
				return "<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 2.0//EN\"\n\t\"http://www.w3.org/MarkUp/DTD/xhtml2.dtd\">\n";
		}
	}

	/**
	 * Builds a HTML tag
	 *
	 *<code>
	 *	echo \Phalcon\Tag::tagHtml($name, $parameters, $selfClose, $onlyStart, $eol);
	 *</code>
	 *
	 * @param string $tagName
	 * @param mixed $parameters
	 * @param boolean|null $selfClose
	 * @param boolean|null $onlyStart
	 * @param boolean|null $useEol
	 * @return string
	 * @throws TagException
	 */
	public static function tagHtml($tagName, $parameters = null, $selfClose = null, $onlyStart = null, $useEol = null)
	{
		/* Type check */
		if(is_string($tagName) === false) {
			throw new TagException('Invalid parameter type.');
		}

		if(is_null($selfClose) === true) {
			$selfClose = false;
		} elseif(is_bool($selfClose) === false) {
			throw new TagException('Invalid parameter type.');
		}

		if(is_null($onlyStart) === true) {
			$onlyStart = false;
		} elseif(is_bool($onlyStart) === false) {
			throw new TagException('Invalid parameter type.');
		}

		if(is_null($useEol) === true) {
			$useEol = false;
		} elseif(is_bool($useEol) === false) {
			throw new TagException('Invalid parameter type.');
		}

		if(is_array($parameters) === false) {
			$params = array($parameters);
		} else {
			$params = $parameters;
		}

		/* Process data */
		$local_code = '<'.$tagName.self::writeAttributes($params);

		if(self::$_documentType > 5) {
			if($selfClose === true) {
				$local_code .= ' />';
			} else {
				$local_code .= '>';
			}
		} else {
			if($onlyStart === true) {
				$local_code .= '>';
			} else {
				$local_code .= '></'.$tagName.'>';
			}
		}

		return ($useEol === true ? $local_code."\n" : $local_code);
	}

	/**
	 * Builds a HTML tag closing tag
	 *
	 *<code>
	 *	echo \Phalcon\Tag::tagHtmlClose('script', true)
	 *</code>
	 *
	 * @param string $tagName
	 * @param boolean $useEol
	 * @return string
	 * @throws TagException
	 */
	public static function tagHtmlClose($tagName, $useEol = null)
	{
		/* Type check */
		if(is_string($tagName) === false) {
			throw new TagException('Invalid parameter type.');
		}

		if(is_null($useEol) === true) {
			$useEol = false;
		} elseif(is_bool($useEol) === false) {
			throw new TagException('Invalid parameter type.');
		}

		/* Return string */
		return '</'.$tagName.($useEol === true ? ">\n" : '>');
	}
}