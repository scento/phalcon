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

use \Phalcon\Tag;
use \Phalcon\Tag\Exception;
use \Phalcon\Mvc\ModelInterface;
use \Closure;

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
        if (is_null($data) === false && is_array($data) === false) {
            throw new Exception('Invalid parameter type.');
        }
        if (is_array($parameters) === false) {
            $parameters = array($parameters, $data);
        }

        /* Get data */
        
        /* ID */
        //@note added check for $params[id]
        if (isset($parameters[0]) === false && isset($parameters['id']) === true) {
            $parameters[0] = $parameters['id'];
        }
        $id = $parameters[0];

        /* Name */
        if (isset($parameters['name']) === false) {
            $parameters['name'] = $id;
        }
        $name = $parameters['name'];

        //Automatically assign the id if the name is not an array
        if ($id[0] !== '[' && isset($params['id']) === false) {
            $params['id'] = $id;
        }

        /* Value */
        if (isset($params['value']) === false) {
            $value = Tag::getValue($id, $parameters);
        } else {
            $value = $parameters['value'];
            unset($parameters['value']);
        }

        /* Empty */
        $useEmpty = false;
        if (isset($parameters['useEmpty']) === true) {
            /* Empty Value */
            if (isset($parameters['emptyValue']) === false) {
                $emptyValue = '';
            } else {
                $emptyValue = $parameters['emptyValue'];
                unset($parameters['emptyValue']);
            }

            /* Empty Text */
            if (isset($parameters['emptyText']) === false) {
                $emptyText = 'Choose...';
            } else {
                $emptyText = $parameters['emptyText'];
                unset($parameters['emptyText']);
            }

            $useEmpty = $parameters['useEmpty'];
            unset($parameters['useEmpty']);
        }


        /* Generate Code */
        $code = '<select';
        if (is_array($parameters) === true) {
            foreach ($parameters as $key => $avalue) {
                if (is_int($key) === false) {
                    if (is_array($avalue) === false) {
                        $code .= ' '.$key.' = "'.htmlspecialchars($avalue).'"';
                    }
                }
            }
        }

        $code .= '>'.\PHP_EOL;

        if ($useEmpty === true) {
            //Create an empty value
            $code .= '\t<option value="'.$emptyValue.'">'.$emptyText.'</option>'.\PHP_EOL;
        }

        if (isset($parameters[1]) === true) {
            $options = $params[1];
        } else {
            $options = $data;
        }

        if (is_object($options) === true) {
            //The options is a resultset
            if (isset($parameters['using']) === false) {
                throw new Exception("The 'using' parameter is required");
            } else {
                $using = $parameters['using'];
                if (is_array($using) === false && is_object($using) === false) {
                    throw new Exception("The 'using' parameter should be an Array");
                }
            }

            //Create the SELECT's option from a resultset
            $code .= self::_optionsFromResultset(
                $options,
                $using,
                $value,
                '</option>'.\PHP_EOL
            );
        } else {
            if (is_array($options) === true) {
                //Create the SELECT's option from an array
                $code .= self::_optionsFromArray($options, $value, '</option>'.\PHP_EOL);
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
        if (is_object($resultset) === false ||
            $resultset instanceof ModelInterface === false) {
            throw new Exception('Invalid parameter type.');
        }

        if (is_array($using) === false && is_object($using) === false) {
            throw new Exception('Invalid parameter type.');
        }

        if (is_string($closeOption) === false) {
            throw new Exception('Invalid parameter type.');
        }

        $code = '';

        /* Loop through resultset */
        $resultset->rewind();
        while ($resultset->isValid() === true) {
            $option = $resultset->current();

            if (is_array($using) === true) {
                if (is_object($option) === true) {
                    /* Object */
                    if (method_exists($option, 'readAttribute') === true) {
                        //Read the value attribute from the model
                        $optionValue = $option->readAttribute(0);

                        //Read the text attribute from the model
                        $optionText = $option->readAttribute(1);
                    } else {
                        //Read the value directly from the model/object
                        $optionValue = $option[0];

                        //Read the text directly from the model/object
                        $optionText = $option[1];
                    }
                } elseif (is_array($option) === true) {
                    /* Array */

                    //Read the value directly from the array
                    $optionValue = $option[0];

                    //Read the text directly from the array
                    $optionText = $option[1];
                } else {
                    throw new Exception('Resultset returned an invalid value');
                }

                //If the value is equal to the option's value we mark it as selected
                $optionValue = htmlspecialchars($optionValue);
                if (is_array($value) === true) {
                    if (in_array($optionValue, $value) === true) {
                        $code .= '\t<option selected="selected" value="'.$optionValue.'">'.$optionText.$closeOption;
                    } else {
                        $code .= '\t<option value="'.$optionValue.'">'.$optionText.$closeOption;
                    }
                } else {
                    if ($optionValue === $value) {
                        $code .= '\t<option selected="selected" value="'.$optionValue.'">'.$optionText.$closeOption;
                    } else {
                        $code .= '\t<option value="'.$optionValue.'">'.$optionText.$closeOption;
                    }
                }
            } elseif (is_object($using) === true &&
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
        if (is_array($resultset) === false) {
            throw new Exception('Invalid parameter type.');
        }

        if (is_string($closeOption) === false) {
            throw new Exception('Invalid parameter type.');
        }

        /* Loop through resultset */
        $code = '';

        foreach ($resultset as $optionValue => $optionText) {
            $optionValue = htmlspecialchars($optionValue);

            if (is_array($value) === true) {
                if (in_array($optionValue, $value) === true) {
                    $code .= '\t<option selected="selected" value="'.$optionValue.'">'.$optionText.$closeOption;
                } else {
                    $code .= '\t<option value="'.$optionValue.'">'.$optionText.$closeOption;
                }
            } else {
                if ($optionValue === $value) {
                    $code .= '\t<option selected="selected" value="'.$optionValue.'">'.$optionText.$closeOption;
                } else {
                    $code .= '\t<option value="'.$optionValue.'">'.$optionText.$closeOption;
                }
            }
        }

        return $code;
    }
}
