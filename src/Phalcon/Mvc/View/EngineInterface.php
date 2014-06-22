<?php
/**
 * View
 *
 * @author Andres Gutierrez <andres@phalconphp.com>
 * @author Eduar Carvajal <eduar@phalconphp.com>
 * @version 1.2.6
 * @package Phalcon
*/
namespace Phalcon\Mvc\View;

/**
 * Phalcon\Mvc\View\EngineInterface initializer
 * 
 * @see https://github.com/phalcon/cphalcon/blob/1.2.6/ext/mvc/view/engineinterface.c
 */
interface EngineInterface
{
	/**
	 * \Phalcon\Mvc\View\Engine constructor
	 *
	 * @param \Phalcon\Mvc\ViewInterface $view
	 * @param \Phalcon\DiInterface|null $dependencyInjector
	 */
	public function __construct($view, $dependencyInjector = null);

	/**
	 * Returns cached ouput on another view stage
	 *
	 * @return array
	 */
	public function getContent();

	/**
	 * Renders a partial inside another view
	 *
	 * @param string $partialPath
	 * @return string
	 */
	public function partial($partialPath);

	/**
	 * Renders a view using the template engine
	 *
	 * @param string $path
	 * @param array $params
	 * @param boolean|null $mustClean
	 */
	public function render($path, $params, $mustClean = null);
}