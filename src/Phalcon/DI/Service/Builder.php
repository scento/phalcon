<?php
/**
 * Service Builder
 *
 * @author Andres Gutierrez <andres@phalconphp.com>
 * @author Eduar Carvajal <eduar@phalconphp.com>
 * @author Wenzel Pünter <wenzel@phelix.me>
 * @version 1.2.6
 * @package Phalcon
*/
namespace Phalcon\DI\Service;

use \Phalcon\DI\Exception,
	\Phalcon\DiInterface,
	\ReflectionClass,
	\ReflectionProperty;

/**
 * Phalcon\DI\Service\Builder
 *
 * This class builds instances based on complex definitions
 * 
 * @see https://github.com/phalcon/cphalcon/blob/1.2.6/ext/di/service/builder.c
 */
class Builder
{
	/**
	 * Resolves a constructor/call parameter
	 *
	 * @param \Phalcon\DiInterface $dependencyInjector
	 * @param int $position
	 * @param array $argument
	 * @return mixed
	 * @throws Exception
	 */
	protected function _buildParameter($dependencyInjector, $position, $argument)
	{
		if(is_int($position) === false) {
			throw new Exception('Invalid parameter type.');
		}

		if(is_array($argument) === false) {
			throw new Exception('Argument at position '.$position.' must be an array');
		}

		if(isset($argument['type']) === false) {
			throw new Exception('Argument at position '.$position.' must have a type');
		}

		//If the argument type is 'service', we obtain the service from the DI
		if($argument['type'] === 'service') {
			if(isset($argument['name']) === false) {
				throw new Exception("Service 'name' is required in parameter on position ".$position);
			}

			if(is_object($dependencyInjector) === false ||
				$dependencyInjector instanceof DiInterface === false) {
				throw new Exception('The dependency injector container is not valid');
			}

			return $dependencyInjector->get($argument['name']);
		}

		//If the argument type is 'parameter' we assign the value as it is
		if($argument['type'] === 'parameter') {
			if(isset($argument['value']) === false) {
				throw new Exception("Service 'value' is required in parameter on position".$position);
			}

			return $argument['value'];
		}

		//If the argument type is 'instance', we assign the value as it is
		if($argument['type'] === 'instance') {
			if(isset($argument['className']) === false) {
				throw new Exception("Service 'className' is required in parameter on position".$position);
			}

			if(is_object($dependencyInjector) === false ||
				$dependencyInjector instanceof DiInterface === false) {
				throw new Exception('The dependency injector container is not valid');
			}

			if(isset($argument['arguments']) === false) {
				//The instance parameter does not have arguments for its constructor
				return $dependencyInjector->get($argument['className']);
			} else {
				return $dependencyInjector->get($argument['className'], $argument['arguments']);
			}
		}


		//Unknown parameter type
		throw new Exception('Unknown service type in parameter on position'.$position);
	}

	/**
	 * Resolves an array of parameters
	 *
	 * @param \Phalcon\DiInterface $dependencyInjector
	 * @param array $arguments
	 * @return array
	 * @throws Exception
	 */
	protected function _buildParameters($dependencyInjector, $arguments)
	{
		if(is_object($dependencyInjector) === false ||
			$dependencyInjector instanceof DiInterface === false) {
			throw new Exception('Invalid parameter type.');
		}

		if(is_array($arguments) === false) {
			throw new Exception('Definition arguemnts must be an array');
		}

		$buildArguments = array();

		foreach($arguments as $position => $argument) {
			$buildArguments[] = $this->_buildParameter($dependencyInjector, $position, $argument);
		}

		return $buildArguments;
	}

	/**
	 * Builds a service using a complex service definition
	 *
	 * @param \Phalcon\DiInterface $dependencyInjector
	 * @param array $definition
	 * @param array|null $parameters
	 * @return mixed
	 * @throws Exception
	 */
	public function build($dependencyInjector, $definition, $parameters = null)
	{
		if(is_object($dependencyInjector) === false ||
			$dependencyInjector instanceof DiInterface === false) {
			throw new Exception('Invalid parameter type.');
		}

		if(is_array($definition) === false) {
			throw new Exception('The service definition must be an array');
		}

		//The class name is required
		if(isset($definition['className']) === false) {
			throw new Exception("Invalid service definition. Missing 'className' parameter");
		}

		/* Get instance */
		if(is_array($parameters) === true) {
			//Build the instance overriding the definition constructor parameters
			if(empty($parameters) === true) {
				try {
					$instance = new $definition['className'];
				} catch(\Exception $e) {
					return null;
				}
			} else {
				try {
					$mirror = new ReflectionClass($definition['className']);
					$instance = $mirror->newInstanceArgs($parameters);
				} catch(\Exception $e) {
					return null;
				}
			}
		} else {
			//Check if the argument has constructor arguments
			if(isset($definition['arguments']) === true) {
				//Resolve the constructor parameters
				$buildArguments = $this->_buildParameters($dependencyInjector, $definition['arguments']);

				//Create the instance based on the parameters
				try {
					$mirror = new ReflectionClass($definition['className']);
					$instance = $mirror->newInstanceArgs($buildArguments);
				} catch(\Exception $e) {
					return null;
				}
			} else {
				try {
					$instance = new $definition['className'];
				} catch(\Exception $e) {
					return null;
				}
			}
		}

		//The definition has calls?
		if(isset($definition['calls']) === true) {
			if(is_object($instance) === false) {
				throw new Exception('The definition has setter injection parameters but the constructor didn\'t return an instance');
			}

			if(is_array($definition['calls']) === false) {
				throw new Exception('Setter injection parameters must be an array');
			}

			//The method call has parameters
			foreach($definition['calls'] as $methodPosition => $method) {
				//The call parameter must be an array of array
				if(is_array($method) === false) {
					throw new Exception('Method call must be an array on position '.$methodPosition);
				}

				//A param 'method' is required
				if(isset($method['method']) === false) {
					throw new Exception('The method name is required on position '.$methodPosition);
				}

				//Create the method call
				if(isset($method['arguments']) === true) {
					if(is_array($method['arguments']) === false) {
						throw new Exception('Call arguments must be an array '.$methodPosition);
					}

					if(empty($arguments) === false) {
						//Call the method on the instance
						$status = call_user_func_array(array($instance, $method['method']), $this->_buildParameters($dependencyInjector, $arguments));

						continue;
					}
				}

				//Call the method on the instance without arguments
				$status = call_user_func(array($instance, $method['method']));
			}
		}

		//The definition has properties?
		if(isset($definition['properties']) === true) {
			if(is_object($instance) === false) {
				throw new Exception('The definition has properties injection parameters but the constructor didn\'t return an instance');
			}

			if(is_array($definition['properties']) === false) {
				throw new Exception('Setter injection parameters must be an array');
			}

			//The method call has parameters
			foreach($definition['properties'] as $propertyPosition => $property) {
				//The call parameter must be an array of arrays
				if(is_array($property) === false) {
					throw new Exception('Property must be an array on position '.$propertyPosition);
				}

				//A param 'name' is required
				if(isset($property['name']) === false) {
					throw new Exception('The property name is required on position '.$propertyPosition);
				}

				//A param 'value' is required
				if(isset($property['value']) === false) {
					throw new Exception('The property value is required on position '.$propertyPosition);
				}

				//Update the public property
				$reflection = new ReflectionProperty(get_class($instance), $property['name']);
				if($reflection->isPublic() === true) {
					$reflection->setAccessible(true);
					$reflection->setValue($instance, 
						$this->_buildParameter($dependencyInjector, $propertyPosition, 
						$property['value']));
					$reflection->setAccessible(false);
				} else {
					throw new Exception('Property must be public.');
				}
			}
		}

		return $instance;
	}
}