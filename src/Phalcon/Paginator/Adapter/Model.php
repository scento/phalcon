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

use \Phalcon\Paginator\AdapterInterface;
use \Phalcon\Paginator\Exception;
use \stdClass;

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
        if (is_array($config) === false) {
            throw new Exception('Invalid parameter type.');
        }

        $this->_config = $config;

        if (isset($this->_config['limit']) === true) {
            $this->_limitRows = $this->_config['limit'];
        }

        if (isset($this->_config['page']) === true) {
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
        if (is_int($page) === false) {
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
        $pageNumber = $this->_page;
        $show = $this->_limitRows;
        $items = $this->_config['data'];

        if (is_int($pageNumber) === false) {
            $pageNumber = 1;
        }

        if ($show < 0) {
            throw new Exception('The start page number is zero or less');
        }

        $n =  count($items);
        $lastShowPage = $pageNumber - 1;
        $start = $show * $lastShowPage;
        $possiblePages = $n / $show;
        $totalPages = ceil($possiblePages);

        if (is_object($items) === false) {
            throw new Exception('Invalid data for paginator');
        }

        $pageItems = array();
        $page = new stdClass();

        if ($n > 0) {
            //Seek to the desired position
            if ($start < $n) {
                $items->seek($start);
            } else {
                $items->seek(0);
                $pageNumber = 1;
                $lastShowPage = 0;
                $start = 0;
            }

            //The record must be iterable
            $i = 1;
            while ($items->valid() === true) {
                $pageItems[] = $items->current();

                if ($i > $show) {
                    break;
                }

                ++$i;
            }

        }

        //Add items to page object
        $page->items = $pageItems;

        $maximumPages = $start + $show;
        if ($maximumPages < $n) {
            $next = $pageNumber + 1;
        } else {
            if ($maximumPages === $n) {
                $next = $n;
            } else {
                $possiblePages = $n / $show;
                $additionalPage = $possiblePages + 1;
                $next = (int)$additionalPage;
            }
        }

        if ($next > $totalPages) {
            $next = $totalPages;
        }

        $page->next = $next;

        if ($pageNumber > 0) {
            $before = $pageNumber - 1;
        } else {
            $before = 1;
        }

        $page->first = 1;
        $page->before = $before;
        $page->current = $pageNumber;

        $reminder = $n % $show;
        $possiblePages = $n / $show;

        if (is_int($reminder) === false) {
            $next = $possiblePages + 1;
            $pagesTotal = (int)$next;
        } else {
            $pagesTotal = $possiblePages;
        }

        $page->last = $pagesTotal;
        $page->total_pages = $totalPages;
        $page->total_items = $n;

        return $page;
    }
}
