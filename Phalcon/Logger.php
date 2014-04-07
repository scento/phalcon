<?php
/**
 * Logger
 *
 * @author Andres Gutierrez <andres@phalconphp.com>
 * @author Eduar Carvajal <eduar@phalconphp.com>
 * @version 1.2.6
 * @package Phalcon
*/
namespace Phalcon;

/**
 * Phalcon\Logger
 *
 * Phalcon\Logger is a component whose purpose is create logs using
 * different backends via adapters, generating options, formats and filters
 * also implementing transactions.
 *
 *<code>
 *	$logger = new Phalcon\Logger\Adapter\File("app/logs/test.log");
 *	$logger->log("This is a message");
 *	$logger->log("This is an error", Phalcon\Logger::ERROR);
 *	$logger->error("This is another error");
 *</code>
 * 
 * @see https://github.com/phalcon/cphalcon/blob/1.2.6/ext/logger.c
 */
abstract class Logger
{
	/**
	 * Special
	 * 
	 * @var int
	*/
	const SPECIAL = 9;

	/**
	 * Custom
	 * 
	 * @var int
	*/
	const CUSTOM = 8;

	/**
	 * Debug
	 * 
	 * @var int
	*/
	const DEBUG = 7;

	/**
	 * Info
	 * 
	 * @var int
	*/
	const INFO = 6;

	/**
	 * Notice
	 * 
	 * @var int
	*/
	const NOTICE = 5;

	/**
	 * Warning
	 * 
	 * @var int
	*/
	const WARNING = 4;

	/**
	 * Error
	 * 
	 * @var int
	*/
	const ERROR = 3;

	/**
	 * Alert
	 * 
	 * @var int
	*/
	const ALERT = 2;

	/**
	 * Critical
	 * 
	 * @var int
	*/
	const CRITICAL = 1;

	/**
	 * Emergence
	 * 
	 * @var int
	*/
	const EMERGENCE = 0;
}