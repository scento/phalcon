<?php
/**
 * File
 *
 * @author Andres Gutierrez <andres@phalconphp.com>
 * @author Eduar Carvajal <eduar@phalconphp.com>
 * @author Wenzel Pünter <wenzel@phelix.me>
 * @version 1.2.6
 * @package Phalcon
*/
namespace Phalcon\Http\Request;

use \Phalcon\Http\Request\FileInterface,
	\Phalcon\Http\Request\Exception;

/**
 * Phalcon\Http\Request\File
 *
 * Provides OO wrappers to the $_FILES superglobal
 *
 *<code>
 *	class PostsController extends \Phalcon\Mvc\Controller
 *	{
 *
 *		public function uploadAction()
 *		{
 *			//Check if the user has uploaded files
 *			if ($this->request->hasFiles() == true) {
 *				//Print the real file names and their sizes
 *				foreach ($this->request->getUploadedFiles() as $file){
 *					echo $file->getName(), " ", $file->getSize(), "\n";
 *				}
 *			}
 *		}
 *
 *	}
 *</code>
 * 
 * @see https://github.com/phalcon/cphalcon/blob/1.2.6/ext/http/request/file.c
 */
class File implements FileInterface
{
	/**
	 * Name
	 * 
	 * @var null|string
	 * @access protected
	*/
	protected $_name;

	/**
	 * Temp
	 * 
	 * @var null|string
	 * @access protected
	*/
	protected $_tmp;

	/**
	 * Size
	 * 
	 * @var null|int
	 * @access protected
	*/
	protected $_size;

	/**
	 * Type
	 * 
	 * @var null|string
	 * @access protected
	*/
	protected $_type;

	/**
	 * Error
	 * 
	 * @var null|array
	 * @access protected
	*/
	protected $_error;

	/**
	 * Key
	 * 
	 * @var null|string
	 * @access protected
	*/
	protected $_key;

	/**
	 * \Phalcon\Http\Request\File constructor
	 *
	 * @param array $file
	 * @param string|null $key
	 * @throws Exception
	 */
	public function __construct($file, $key = null)
	{
		if(is_array($file) === false) {
			throw new Exception("Phalcon\\Http\\Request\\File requires a valid uploaded file");
		}

		//@note no type checks
		if(isset($file['name']) === true) {
			$this->_name = (string)$file['name'];
		}

		if(isset($file['tmp_name']) === true) {
			$this->_tmp = (string)$file['tmp_name'];
		}

		if(isset($file['size']) === true) {
			$this->_size = (int)$file['size'];
		}

		if(isset($file['type']) === true) {
			$this->_type = (string)$file['type'];
		}

		if(isset($file['error']) === true) {
			$this->_error = $file['error'];
		}

		if(is_string($key) === true) {
			$this->_key = $key;
		} elseif(is_null($key) === false) {
			throw new Exception('Invalid parameter type.');
		}
	}

	/**
	 * Returns the file size of the uploaded file
	 *
	 * @return int|null
	 */
	public function getSize()
	{
		return $this->_size;
	}

	/**
	 * Returns the real name of the uploaded file
	 *
	 * @return string|null
	 */
	public function getName()
	{
		return $this->_name;
	}

	/**
	 * Returns the temporal name of the uploaded file
	 *
	 * @return string|null
	 */
	public function getTempName()
	{
		return $this->_tmp;
	}

	/**
	 * Returns the mime type reported by the browser
	 * This mime type is not completely secure, use getRealType() instead
	 *
	 * @return string|null
	 */
	public function getType()
	{
		return $this->_type;
	}

	/**
	 * Gets the real mime type of the upload file using finfo
	 *
	 * @todo Not implemented
	 * @return null
	 */
	public function getRealType()
	{

	}

	/**
	 * Returns the error code
	 *
	 * @return string|null
	 */
	public function getError()
	{
		return $this->_error;
	}

	/**
	 * Returns the file key
	 *
	 * @return string|null
	 */
	public function getKey()
	{
		return $this->_key;
	}

	/**
	 * Is Uploaded File?
	 * 
	 * @return boolean
	*/
	public function isUploadedFile()
	{
		$tmpName = $this->getTempName();
		if(is_string($tmpName) === true) {
			return is_uploaded_file($tmpName);
		}
		
		return false;
	}

	/**
	 * Moves the temporary file to a destination within the application
	 *
	 * @param string $destination
	 * @return boolean
	 * @throws Exception
	 */
	public function moveTo($destination)
	{
		//@note no path check
		if(is_string($destination) === false) {
			throw new Exception('Invalid parameter type.');
		}

		//@note _tmp can be NULL
		return move_uploaded_file($this->_tmp, $destination);
	}

	/**
	 * Set State
	 * 
	 * @return \Phalcon\Http\Request\FileInterface
	*/
	public static function __set_state($data)
	{
		//@note this function does not respect _key
		return new File($data);
	}
}