<?php
/**
* Paginator Query Builder Adapter
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
 * Phalcon\Paginator\Adapter\QueryBuilder
 *
 * Pagination using a PHQL query builder as source of data
 *
 *<code>
 *  $builder = $this->modelsManager->createBuilder()
 *                   ->columns('id, name')
 *                   ->from('Robots')
 *                   ->orderBy('name');
 *
 *  $paginator = new Phalcon\Paginator\Adapter\QueryBuilder(array(
 *      "builder" => $builder,
 *      "limit"=> 20,
 *      "page" => 1
 *  ));
 *</code>
 * 
 * @see https://github.com/phalcon/cphalcon/blob/1.2.6/ext/paginator/adapter/querybuilder.c
 */
class QueryBuilder implements AdapterInterface
{
	/**
	 * Configuration
	 * 
	 * @var null|array
	 * @access protected
	*/
	protected $_config;

	/**
	 * Builder
	 * 
	 * @var null|object
	 * @access protected
	*/
	protected $_builder;

	/**
	 * Limit Rows
	 * 
	 * @var null|int
	 * @access protected
	*/
	protected $_limitRows;

	/**
	 * Page
	 * 
	 * @var int
	 * @access protected
	*/
	protected $_page;

	/**
	 * \Phalcon\Paginator\Adapter\QueryBuilder
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
		if(isset($config['builder']) === false) {
			throw new Exception("Parameter 'builder' is required");
		} else {
			//@note no further builder validation
			$this->_builder = $config['builder'];
		}

		if(isset($config['limit']) === false) {
			throw new Exception("Parameter 'limit' is required");
		} else {
			$this->_limitRows = $config['limit'];
		}

		if(isset($config['page']) === true) {
			$this->_page = $config['page'];
		}
	}

	/**
	 * Set the current page number
	 *
	 * @param int $page
	 * @throws Exception
	 */
	public function setCurrentPage($currentPage)
	{
		if(is_int($page) === false) {
			throw new Exception('Invalid parameter type.');
		}
		$this->_page = $currentPage;
	}

	/**
	 * Returns a slice of the resultset to show in the pagination
	 *
	 * @return stdClass
	 */
	public function getPaginate()
	{
		/* Clone the original builder */
		$builder = clone $this->_builder;
		$total_builder = clone $builder;

		$limit = $this->_limitRows;
		$number_page = $this->_page;

		if(is_null($number_page) === true) {
			$number_page = 1;
		}

		$prev_number_page = $number_page - 1;
		$number = $limit * $prev_number_page;

		//Set the limit clause avoiding negative offsets
		if($number < $limit) {
			$builder->limit($limit);
		} else {
			$builder->limit($limit, $number);
		}

		$query = $builder->getQuery();

		//Change the queried columns by a COUNT(*)
		$total_builder->columns('COUNT(*) [rowcount]');

		//Remove the 'ORDER BY' clause, PostgreSQL requires this
		$total_builder->orderBy(null);

		//Obtain the PHQL for the total query
		$total_query = $total_builder->getQuery();

		//Obtain the result of the total query
		$result = $total_query->execute();
		$row = $result->getFirst();

		$total_pages = $row['rowcount'] / $limit;
		$int_total_pages = (int)$total_pages;

		if($int_total_pages !== $total_pages) {
			$total_pages = $int_total_pages + 1;
		}

		$page = new stdClass();
		$page->first = 1;
		$page->before = ($number_page === 1 ? 1 : ($number_page - 1));
		$page->items = $query->execute();
		$page->next = ($number_page < $total_pages ? ($number_page + 1) : $total_pages);
		$page->last = $total_pages;
		$page->current = $number_page;
		$page->total_pages = $total_pages;
		$page->total_items = (int)$row['rowcount'];

		return $page;
	}
}