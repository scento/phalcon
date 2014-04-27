<?php
/**
 * Select
 *
 * @author Andres Gutierrez <andres@phalconphp.com>
 * @author Eduar Carvajal <eduar@phalconphp.com>
 * @author Wenzel Pünter <wenzel@phelix.me>
 * @version 1.2.6
 * @package Phalcon
*/
namespace Phalcon\Tag;

use \Phalcon\Tag,
	\Phalcon\Tag\Exception,
	\Phalcon\Mvc\ModelInterface,
	\Closure;

/**
 * Phalcon\Tag\Select
 *
 * Generates a SELECT html tag using a static array of values or a Phalcon\Mvc\Model 
 * resultset
 * 
 * @see https://github.com/phalcon/cphalcon/blob/1.2.6/ext/tag/select.c
 */
abstract class Select
{
	/**
	 * Generates a SELECT tag
	 *
	 * @param mixed $parameters
	 * @param array|null $data
	 * @throws Exception
	 */
	public static function selectField($parameters, $data = null)
	{
		/* Type check */
		if(is_null($data) === false && is_array($data) === false) {
			throw new Exception('Invalid parameter type.');
		}
		if(is_array($parameters) === false) {
			$parameters = array($parameters, $data);
		}

		/* Get data */
		
		/* ID */
		//@note added check for $params[id]
		if(isset($parameters[0]) === false && isset($parameters['id']) === true) {
			$parameters[0] = $parameters['id'];
		}
		$id = $parameters[0];

		/* Name */
		if(isset($parameters['name']) === false) {
			$parameters['name'] = $id;
		}
		$name = $parameters['name'];

		//Automatically assign the id if the name is not an array
		if($id[0] !== '[' && isset($params['id']) === false) {
			$params['id'] = $id;
		}

		/* Value */
		if(isset($params['value']) === false) {
			$value = Tag::getValue($id, $parameters);
		} else {
			$value = $parameters['value'];
			unset($parameters['value']);
		}

		/* Empty */
		$use_empty = false;
		if(isset($parameters['useEmpty']) === true) {
			/* Empty Value */
			if(isset($parameters['emptyValue']) === false) {
				$empty_value = '';
			} else {
				$empty_value = $parameters['emptyValue'];
				unset($parameters['emptyValue']);
			}

			/* Empty Text */
			if(isset($parameters['emptyText']) === false) {
				$empty_text = 'Choose...';
			} else {
				$empty_text = $parameters['emptyText'];
				unset($parameters['emptyText']);
			}

			$use_empty = $parameters['useEmpty'];
			unset($parameters['useEmpty']);
		}


		/* Generate Code */
		$code = '<select';
		if(is_array($parameters) === true) {
			foreach($parameters as $key => $avalue) {
				if(is_int($key) === false) {
					if(is_array($avalue) === false) {
						$code .= ' '.$key.' = "'.htmlspecialchars($avalue).'"';
					}
				}
			}
		}

		$code .= '>'.\PHP_EOL;

		if($use_empty === true) {
			//Create an empty value
			$code .= '\t<option value="'.$empty_value.'">'.$empty_text.'</option>'.\PHP_EOL;
		}

		if(isset($parameters[1]) === true) {
			$options = $params[1];
		} else {
			$options = $data;
		}

		if(is_object($options) === true) {
			//The options is a resultset
			if(isset($parameters['using']) === false) {
				throw new Exception("The 'using' parameter is required");
			} else {
				$using = $parameters['using'];
				if(is_array($using) === false && is_object($using) === false) {
					throw new Exception("The 'using' parameter should be an Array");
				}
			}

			//Create the SELECT's option from a resultset
			$code .= $this->_optionsFromResultset($options, 
				$using, $value, '</option>'.\PHP_EOL);
		} else {
			if(is_array($options) === true) {
				//Create the SELECT's option from an array
				$code .= $this->_optionsFromArray($options, $value, '</option>'.\PHP_EOL);
			} else {
				throw new Exception('Invalid data provided to SELECT helper');
			}
		}

		$code .= '</select>';

		return $code;
	}

	/**
	 * Generate the OPTION tags based on a resulset
	 *
	 * @param \Phalcon\Mvc\ModelInterface $resultset
	 * @param array|object $using
	 * @param mixed value
	 * @param string $closeOption
	 * @throws Exception
	 */
	protected static function _optionsFromResultset($resultset, $using, $value, $closeOption)
	{
		/* Type check */
		if(is_object($resultset) === false ||
			$resultset instanceof ModelInterface === false) {
			throw new Exception('Invalid parameter type.');
		}

		if(is_array($using) === false && is_object($using) === false) {
			throw new Exception('Invalid parameter type.');
		}

		if(is_string($closeOption) === false) {
			throw new Exception('Invalid parameter type.');
		}

		$code = '';

		/* Loop through resultset */
		$resultset->rewind();
		while($resultset->isValid() === true) {
			$option = $resultset->current();

			if(is_array($using) === true) {
				if(is_object($option) === true) {
					/* Object */
					if(method_exists($option, 'readAttribute') === true) {
						//Read the value attribute from the model
						$option_value = $option->readAttribute(0);

						//Read the text attribute from the model
						$option_text = $option->readAttribute(1);
					} else {
						//Read the value directly from the model/object
						$option_value = $option[0];

						//Read the text directly from the model/object
						$option_text = $option[1];
					}
				} elseif(is_array($option) === true) {
					/* Array */

					//Read the value directly from the array
					$option_value = $option[0];

					//Read the text directly from the array
					$option_text = $option[1];
				} else {
					throw new Exception('Resultset returned an invalid value');
				}

				//If the value is equal to the option's value we mark it as selected
				$option_value = htmlspecialchars($option_value);
				if(is_array($value) === true) {
					if(in_array($option_value, $value) === true) {
						$code .= '\t<option selected="selected" value="'.$option_value.'">'.$option_text.$closeOption;
					} else {
						$code .= '\t<option value="'.$option_value.'">'.$option_text.$closeOption;
					}
				} else {
					if($option_value === $value) {
						$code .= '\t<option selected="selected" value="'.$option_value.'">'.$option_text.$closeOption;
					} else {
						$code .= '\t<option value="'.$option_value.'">'.$option_text.$closeOption;
					}
				}
			} elseif(is_object($using) === true &&
				$using instanceof Closure === true) {
				//Check if using a closure
				$params = array($option);
				$code .= call_user_func_array($using, $params);
			}

			$resultset->next();
		}

		return $code;
	}

	/**
	 * Generate the OPTION tags based on an array
	 *
	 * @param array $resultset
	 * @param mixed value
	 * @param string $closeOption
	 * @throws Exception
	 */
	protected static function _optionsFromArray($resultset, $value, $closeOption)
	{
		/* Type check */
		if(is_array($resultset) === false) {
			throw new Exception('Invalid parameter type.');
		}

		if(is_string($closeOption) === false) {
			throw new Exception('Invalid parameter type.');
		}

		/* Loop through resultset */
		$code = '';

		foreach ($resultset as $option_value => $option_text) {
			$option_value = htmlspecialchars($option_value);

			if(is_array($value) === true) {
				if(in_array($option_value, $value) === true) {
					$code .= '\t<option selected="selected" value="'.$option_value.'">'.$option_text.$closeOption;
				} else {
					$code .= '\t<option value="'.$option_value.'">'.$option_text.$closeOption;
				}
			} else {
				if($option_value === $value) {
					$code .= '\t<option selected="selected" value="'.$option_value.'">'.$option_text.$closeOption;
				} else {
					$code .= '\t<option value="'.$option_value.'">'.$option_text.$closeOption;
				}
			}
		}

		return $code;
	}
}