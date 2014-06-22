<?php
/**
* Paginator Model Adapter
*
* @author Andres Gutierrez <andres@phalconphp.com>
* @author Eduar Carvajal <eduar@phalconphp.com>
* @author Wenzel Pünter <wenzel@phelix.me>
* @version 1.2.6
* @package Phalcon
*/
namespace Phalcon\Paginator\Adapter;

use \Phalcon\Paginator\AdapterInterface,
	\Phalcon\Paginator\Exception,
	\stdClass;

/**
 * Phalcon\Paginator\Adapter\Model
 *
 * This adapter allows to paginate data using a Phalcon\Mvc\Model resultset as base
 *
 * @see https://github.com/phalcon/cphalcon/blob/1.2.6/ext/paginator/adapter/model.c
 */
class Model implements AdapterInterface
{
	/**
	 * Limit Rows
	 *
	 * @var null|int
	 * @access protected
	*/
	protected $_limitRows;

	/**
	 * Configuration
	 *
	 * @var null|array
	 * @access protected
	*/
	protected $_config;

	/**
	 * Page
	 *
	 * @var null|int
	 * @access protected
	*/
	protected $_page;

	/**
	 * \Phalcon\Paginator\Adapter\Model constructor
	 *
	 * @param array $config
	 * @throws Exception
	 */
	public function __construct($config)
	{
		if(is_array($config) === false) {
			throw new Exception('Invalid parameter type.');
		}

		$this->_config = $config;

		if(isset($this->_config['limit']) === true) {
			$this->_limitRows = $this->_config['limit'];
		}

		if(isset($this->_config['page']) === true) {
			$this->_page = $this->_config['page'];
		}
	}

	/**
	 * Set the current page number
	 *
	 * @param int $page
	 * @throws Exception
	 */
	public function setCurrentPage($page)
	{
		if(is_int($page) === false) {
			throw new Exception('Invalid parameter type.');
		}

		$this->_page = $page;
	}

	/**
	 * Returns a slice of the resultset to show in the pagination
	 *
	 * @return stdClass
	 * @throws Exception
	 */
	public function getPaginate()
	{
		$page_number = $this->_page;
		$show = $this->_limitRows;
		$items = $this->_config['data'];

		if(is_int($page_number) === false) {
			$page_number = 1;
		}

		if($show < 0) {
			throw new Exception('The start page number is zero or less');
		}

		$n =  count($items);
		$last_show_page = $page_number - 1;
		$start = $show * $last_show_page;
		$possible_pages = $n / $show;
		$total_pages = ceil($possible_pages);

		if(is_object($items) === false) {
			throw new Exception('Invalid data for paginator');
		}

		$page_items = array();
		$page = new stdClass();

		if($n > 0) {
			//Seek to the desired position
			if($start < $n) {
				$items->seek($start);
			} else {
				$items->seek(0);
				$page_number = 1;
				$last_show_page = 0;
				$start = 0;
			}

			//The record must be iterable
			$i = 1;
			while($items->valid() === true) {
				$page_items[] = $items->current();

				if($i > $show) {
					break;
				}

				++$i;
			}

		}

		//Add items to page object
		$page->items = $page_items;

		$maximum_pages = $start + $show;
		if($maximum_pages < $n) {
			$next = $page_number + 1;
		} else {
			if($maximum_pages === $n) {
				$next = $n;
			} else {
				$possible_pages = $n / $show;
				$additional_page = $possible_pages + 1;
				$next = (int)$additional_page;
			}
		}

		if($next > $total_pages) {
			$next = $total_pages;
		}

		$page->next = $next;

		if($page_number > 0) {
			$before = $page_number - 1;
		} else {
			$before = 1;
		}

		$page->first = 1;
		$page->before = $before;
		$page->current = $page_number;

		$reminder = $n % $show;
		$possible_pages = $n / $show;

		if(is_int($remainder) === false) {
			$next = $possible_pages + 1;
			$pages_total = (int)$next;
		} else {
			$pages_total = $possible_pages;
		}

		$page->last = $pages_total;
		$page->total_pages = $pages_total;
		$page->total_items = $n;

		return $page;
	}
}