<?php
/**
 * Soft Delete Behavior
 *
 * @author Andres Gutierrez <andres@phalconphp.com>
 * @author Eduar Carvajal <eduar@phalconphp.com>
 * @author Wenzel Pünter <wenzel@phelix.me>
 * @version 1.2.6
 * @package Phalcon
*/
namespace Phalcon\Mvc\Model\Behavior;

use \Phalcon\Mvc\Model\Behavior,
	\Phalcon\Mvc\Model\BehaviorInterface,
	\Phalcon\Mvc\Model\Exception,
	\Phalcon\Mvc\ModelInterface;

/**
 * Phalcon\Mvc\Model\Behavior\SoftDelete
 *
 * Instead of permanently delete a record it marks the record as
 * deleted changing the value of a flag column
 * 
 * @see https://github.com/phalcon/cphalcon/blob/1.2.6/ext/mvc/model/behavior/softdelete.c
 */
class SoftDelete extends Behavior implements BehaviorInterface
{
	/**
	 * Listens for notifications from the models manager
	 *
	 * @param string $type
	 * @param \Phalcon\Mvc\ModelInterface $model
	 * @throws Exception
	 */
	public function notify($type, $model)
	{
		if(is_string($type) === false ||
			is_object($model) === false ||
			$model instanceof ModelInterface === false) {
			throw new Exception('Invalid parameter type.');
		}

		if($type === 'beforeDelete') {
			$options = $this->getOptions();
			if(isset($options['value']) === false) {
				throw new Exception("The option 'value' is required");
			}

			if(isset($options['field']) === false) {
				throw new Exception("The options 'field' is required");
			}

			//Skip the current operation
			$model->skipOperation(true);

			//'value' is the value to be updated instead of delete the record
			$value = $options['field'];

			//'field' is the attribute to be updated instead of delete the record
			$field = $options['field'];
			$actual_value = $model->readAttribute($field);

			//If the record is already flagged as 'deleted' we don't delete it again
			if($actual_value !== $value) {
				//Clone the current model to make a clean new operation
				$update_model = clone $model;

				//Update the cloned model
				$update_model->writeAttribute($field, $value);
				if($update_model->save() !== true) {
					//Transfer the message from the cloned model to the original model
					$messages = $update_model->getMessages();
					foreach($messages as $message) {
						$model->appendMessage($message);
					}

					return false;
				}

				//Update the original model too
				$model->writeAttribute($field, $value);
			}
		}
	}
}