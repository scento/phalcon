<?php
/**
 * Files Adapter
 *
 * @author Andres Gutierrez <andres@phalconphp.com>
 * @author Eduar Carvajal <eduar@phalconphp.com>
 * @author Wenzel Pünter <wenzel@phelix.me>
 * @version 1.2.6
 * @package Phalcon
*/
namespace Phalcon\Session\Adapter;

use \Phalcon\Session\Adapter,
	\Phalcon\Session\AdapterInterface;

/**
 * Phalcon\Session\Adapter\Files
 *
 * This adapter store sessions in plain files
 *
 *<code>
 * $session = new Phalcon\Session\Adapter\Files(array(
 *    'uniqueId' => 'my-private-app'
 * ));
 *
 * $session->start();
 *
 * $session->set('var', 'some-value');
 *
 * echo $session->get('var');
 *</code>
 * 
 * @see https://github.com/phalcon/cphalcon/blob/1.2.6/ext/session/adapter/files.c
 */
class Files extends Adapter implements AdapterInterface
{

}