<?php
/**
 * Crypt
 *
 * @author Andres Gutierrez <andres@phalconphp.com>
 * @author Eduar Carvajal <eduar@phalconphp.com>
 * @author Wenzel Pünter <wenzel@phelix.me>
 * @version 1.2.6
 * @package Phalcon
*/
namespace Phalcon;

use \Phalcon\CryptInterface,
	\Phalcon\Crypt\Exception as CryptException;

/**
 * Phalcon\Crypt
 *
 * Provides encryption facilities to phalcon applications
 *
 *<code>
 *	$crypt = new Phalcon\Crypt();
 *
 *	$key = 'le password';
 *	$text = 'This is a secret text';
 *
 *	$encrypted = $crypt->encrypt($text, $key);
 *
 *	echo $crypt->decrypt($encrypted, $key);
 *</code>
 * 
 * @see https://github.com/scento/cphalcon/blob/1.2.6/ext/crypt.c
 */
class Crypt implements CryptInterface
{
	/**
	 * Padding: Default
	 * 
	 * @var int
	*/
	const PADDING_DEFAULT = 0;

	/**
	 * Padding: ANSI X923
	 * 
	 * @var int
	*/
	const PADDING_ANSI_X_923 = 1;

	/**
	 * Padding: PKCS7
	 * 
	 * @var int
	*/
	const PADDING_PKCS7 = 2;

	/**
	 * Padding: ISO 10126
	 * 
	 * @var int
	*/
	const PADDING_ISO_10126 = 3;

	/**
	 * Padding: ISO IEC 7816-4
	 * 
	 * @var int
	*/
	const PADDING_ISO_IEC_7816_4 = 4;

	/**
	 * Padding: Zero
	 * 
	 * @var int
	*/
	const PADDING_ZERO = 5;

	/**
	 * Padding: Space
	 * 
	 * @var int
	*/
	const PADDING_SPACE = 6;

	/**
	 * Key
	 * 
	 * @var null
	 * @access protected
	*/
	protected $_key = null;

	/**
	 * Mode
	 * 
	 * @var string
	 * @access protected
	*/
	protected $_mode = 'cbc';

	/**
	 * Cipher
	 * 
	 * @var string
	 * @access protected
	*/
	protected $_cipher = 'rijndael-256';

	/**
	 * Padding
	 * 
	 * @var int
	 * @access protected
	*/
	protected $_padding = 0;

	/**
	 * Sets the cipher algorithm
	 *
	 * @param string $cipher
	 * @return \Phalcon\Crypt
	 * @throws CryptException
	 */
	public function setCipher($cipher)
	{
		if(is_string($cipher) === false) {
			throw new CryptException('Invalid parameter type.');
		}

		$this->_cipher = $cipher;

		return $this;
	}

	/**
	 * Returns the current cipher
	 *
	 * @return string
	 */
	public function getCipher()
	{
		return $this->_cipher;
	}

	/**
	 * Sets the encrypt/decrypt mode
	 *
	 * @param string $cipher
	 * @return \Phalcon\Crypt
	 * @throws CryptException
	 */
	public function setMode($mode)
	{
		if(is_string($mode) === false) {
			throw new CryptException('Invalid parameter type.');
		}

		$this->_mode = $mode;

		return $this;
	}

	/**
	 * Returns the current encryption mode
	 *
	 * @return string
	 */
	public function getMode()
	{
		return $this->_mode;
	}

	/**
	 * Sets the encryption key
	 *
	 * @param string $key
	 * @return \Phalcon\Crypt
	 * @throws CryptException
	 */
	public function setKey($key)
	{
		if(is_string($key) === false) {
			throw new CryptException('Invalid parameter type.');
		}

		$this->_key = $key;

		return $this;
	}

	/**
	 * Returns the encryption key
	 *
	 * @return string|null
	 */
	public function getKey()
	{
		return $this->_key;
	}

	/**
	 * @brief \Phalcon\CryptInterface \Phalcon\Crypt::setPadding(int $scheme)
	 *
	 * @param int scheme Padding scheme
	 * @return \Phalcon\CryptInterface
	 * @throws CryptException
	 */
	public function setPadding($scheme)
	{
		if(is_int($scheme) === false) {
			throw new CryptException('Invalid parameter type.');
		}

		$this->_padding = $scheme;

		return $this;
	}

	/**
	 * Returns the padding scheme
	 *
	 * @brief int \Phalcon\Crypt::getPadding()
	 * @return int
	 */
	public function getPadding()
	{
		return $this->_padding;
	}

	/**
	 * Secure random Bytes
	 * 
	 * @param int $length
	 * @return string
	 * @throws CryptException
	*/
	private static function cryptoRand($length)
	{
		$bytes = openssl_random_pseudo_bytes($length, $strong);
		if($strong === false) {
			throw new CryptException('Unable to get secure bytes.');
		}

		return $bytes;
	}

	/**
	 * Unpad Message
	 * 
	 * @brief Removes padding @a padding_type from @a text
	 * @param string $text Message to be unpadded
	 * @param string $mode Encryption mode; unpadding is applied only in CBC or ECB mode
	 * @param int $blockSize Cipher block size
	 * @param int $paddingType Padding scheme
	 * @note If the function detects that the text was not padded, it will return it unmodified
	 * @return string
	 * @throws CryptException
	*/
	private static function unpadText($text, $mode, $blockSize, $paddingType)
	{
		if(is_string($text) === false || is_string($mode) === false ||
			is_int($blockSize) === false || is_int($paddingType) === false) {
			throw new CryptException('Invalid parameter type.');
		}

		$text_len = strlen($text);

		if(($text_len % $blockSize === 0) && ($mode === 'ecb' || $mode === 'cbc')) {
			switch($paddingType) {
				case self::PADDING_ANSI_X_923:
					if(ord($text[$text_len-1]) <= $blockSize) {
						$padding_size = ord($text[$text_len-1]);
						$padding = str_repeat(chr(0), ($padding_size -1));
						$padding[$padding_size - 1] = $padding_size;

						if(strncmp($padding, $text.($text_len-$padding_size), $padding_size)) {
							$padding_size = 0;
						}
					}
					break;
				case self::PADDING_PKCS7:
					if($text[$text_len-1] <= $blockSize) {
						$padding_size = ord($text[$text_len-1]);
						$padding = str_repeat($padding_size, $padding_size);
						if(strncmp($padding, $text.($text_len-$padding_size), $padding_size)) {
							$padding_size = 0;
						}
					}
					break;
				case self::PADDING_ISO_10126:
					$padding_size = ord($text[$text_len-1]);
					break;
				case self::PADDING_ISO_IEC_7816_4:
					$padding_size = 0;
					$i = $text_len - 1;
					while($i > 0 && $text[$i] === chr(0x00) && $padding_size < $blockSize) {
						++$padding_size;
						--$i;
					}

					$padding_size = ($text[$i] === chr(0x80)) ? ($padding_size + 1) : 0;
					break;
				case self::PADDING_ZERO:
					$padding_size = 0;
					$i = $text_len - 1;
					while($i >= 0 && $text[$i] === chr(0x00) && $padding_size <= $blockSize) {
						++$padding_size;
						--$i;
					}
					break;
				case self::PADDING_SPACE:
					$padding_size = 0;
					$i = $text_len - 1;
					while($i >= 0 && $text[$i] === chr(0x20) && $padding_size <= $blockSize) {
						++$padding_size;
						--$i;
					}
					break;
				default:
					break;
			}

			if(isset($padding_size) && $padding_size <= $blockSize) {
				if($padding_size > $text_len) {
					throw new CryptException('Invalid state.');
				}

				if($padding_size <= $text_len) {
					return substr($text, 0, ($text_len - $padding_size));
				} else {
					return '';
				}
			} else {
				$padding_size = 0;
			}
		}

		return $text;
	}

	/**
	 * Pad Message
	 * 
	 * @param string $text Message to be padded
	 * @param string $mode Encryption mode; padding is applied only in CBC or ECB mode
	 * @param int $blockSize Cipher block size
	 * @param int $paddingType Padding scheme
	 * @return string
	 * @throws CryptException
	 * 
	 * @see http://www.di-mgt.com.au/cryptopad.html
	 * @see http://en.wikipedia.org/wiki/Padding_%28cryptography%29
	*/
	private static function padText($text, $mode, $blockSize, $paddingType)
	{
		if(is_string($text) === false || is_string($mode) === false ||
			is_int($blockSize) === false || is_int($paddingType) === false) {
			throw new CryptException('Invalid parameter type.');
		}

		$padding_size = 0;
		$padding = '';

		if($mode === 'ecb' || $mode === 'cbc') {
			$padding_size = $blockSize - (strlen($text) % $blockSize);

			switch($paddingType) {
				case self::PADDING_ANSI_X_923:
       				$padding = str_repeat(chr(0), $padding_size - 1).chr($padding_size);
					break;

				case self::PADDING_PKCS7:
					$padding = str_repeat(chr($padding_size), $padding_size);
					break;

				case self::PADDING_ISO_10126:
					$padding = self::cryptoRand($padding_size - 1).chr($padding_size);
					break;

				case self::PADDING_ISO_IEC_7816_4:
					$padding = chr(0x80).str_repeat(chr(0), ($padding_size - 1));
					break;

				case self::PADDING_ZERO:
					if($padding_size === $blockSize) {
						$padding = '';
					} else {
						$padding = str_repeat(chr(0), $padding_size);
					}
					break;

				case self::PADDING_SPACE:
					$padding = str_repeat(chr(0x20), $padding_size);
					break;

				default:
					break;
				default:
			}
		}

		if($padding_size === 0) {
			return $text;
		} else {
			if($padding_size <= $blockSize) {
				return $text.$padding;
			} else {
				throw new CryptException('Precondition failed.');
			}
		}
	}

	/**
	 * Encrypts a text
	 *
	 *<code>
	 *	$encrypted = $crypt->encrypt("Ultra-secret text", "encrypt password");
	 *</code>
	 *
	 * @param string $text
	 * @param string|null $key
	 * @return string
	 * @throws CryptException
	 */
	public function encrypt($text, $key = null)
	{
		//I am not responsible for the strange type casting and missing
		//exception handeling of "false"-returning mcrypt_*-functions.
		//This behavior is copied from the c++ sources.

		$text = (string)$text;

		//Type checking for padding, mode and cipher is not required
		//since these types are checked in setters and the fields
		//are already predefined

		if(function_exists('mcrypt_get_iv_size') === false) {
			throw new CryptException('mcrypt extension is required');
		}

		if($key === null) {
			$encrypt_key = $this->_key;
		} else {
			$encrypt_key = (string)$key;
		}

		if(empty($encrypt_key) === true) {
			throw new CryptException('Encryption key cannot be empty');
		}

		$iv_size = (int)mcrypt_get_iv_size($this->_cipher, $this->_mode);

		if(strlen($encrypt_key) > $iv_size) {
			throw new CryptException('Size of key too large for this algorithm');
		}

		//C++ source is always using \MCRYPT_RAND which is sometimes considered
		//as insecure. This might be because of windows compatibility with
		//PHP < 5.3.0
		if(\DIRECTORY_SEPARATOR === '/' && version_compare(\PHP_VERSION, '5.3.0', '<') === true) {
			$iv = (string)mcrypt_create_iv($iv_size, \MCRYPT_RAND);
		} else {
			$iv = (string)mcrypt_create_iv($iv_size, \ MCRYPT_DEV_URANDOM);
		}

		$block_size = (int)mcrypt_get_block_size($this->_cipher, $this->_mode);

		$padded = $this->padText($text, $this->_mode, $block_size, $this->_padding);

		if(is_string($padded) === false) {
			throw new CryptException('Invalid type.');
		}

		return $iv.mcrypt_encrypt($this->_cipher, $encrypt_key, $padded, $this->_mode, $iv);
	}

	/**
	 * Decrypts an encrypted text
	 *
	 *<code>
	 *	echo $crypt->decrypt($encrypted, "decrypt password");
	 *</code>
	 *
	 * @param string $text
	 * @param string|null $key
	 * @return string
	 * @throws CryptException
	 */
	public function decrypt($text, $key = null)
	{
		if(is_string($text) === false) {
			throw new CryptException('Invalid parameter type.');
		}

		if(function_exists('mcrypt_get_iv_size') === false) {
			throw new CryptException('mcrypt extension is required');
		}

		if(is_null($key) === true) {
			$decrypt_key = $this->_key;
		} elseif(is_string($key) === true) {
			$decrypt_key = $key;
		} else {
			throw new CryptException('Invalid parameter type.');
		}

		if(empty($decrypt_key) === true) {
			throw new CryptException('Decryption key cannot be empty');
		}

		$iv_size = mcrypt_get_iv_size($this->_cipher, $this->_mode);
		if($iv_size === false) {
			throw new CryptException('Error while determining the IV size.');
		} else {
			$iv_size = (int)$iv_size;
		}

		$key_size = strlen($decrypt_key);
		if($key_size > $iv_size) {
			throw new CryptException('Size of key is too large for this algorithm');
		}

		$text_size = strlen($text);
		if($key_size > $text_size) {
			throw new CryptException('Size of IV is larger than text to decrypt');
		}

		$iv = substr($text, 0, $iv_size);
		$text_to_decipher = substr($text, $iv_size);
		$decrypted = (string)mcrypt_decrypt($this->_cipher, $decrypt_key, $text_to_decipher, $this->_mode, $iv);
		$block_size = (int)mcrypt_get_block_size($this->_cipher, $this->_mode);

		if(is_int($this->_padding) === false || 
			is_string($this->_mode) === false ||
			is_string($decrypted) === false) {
			throw new CryptException('Invalid type.');
		}

		return self::unpadText($decrypted, $this->_mode, $block_size, $this->_padding);
	}

	/**
	 * Encrypts a text returning the result as a base64 string
	 *
	 * @param string $text
	 * @param string|null $key
	 * @return string
	 * @throws CryptException
	 */
	public function encryptBase64($text, $key = null)
	{
		if(is_string($text) === false || 
			(is_string($key) === false && is_null($key) === false)) {
			throw new CryptException('Invalid parameter type.');
		}

		return base64_encode($this->encrypt($text, $key));
	}

	/**
	 * Decrypt a text that is coded as a base64 string
	 *
	 * @param string $text
	 * @param string|null $key
	 * @return string
	 * @throws CryptException
	 */
	public function decryptBase64($text, $key = null)
	{
		if(is_string($text) === false || 
			(is_string($key) === false && is_null($key) === false)) {
			throw new CryptException('Invalid parameter type.');
		}

		return $this->decrypt(base64_decode($text), $key);
	}

	/**
	 * Returns a list of available cyphers
	 *
	 * @return array
	 */
	public function getAvailableCiphers()
	{
		return mcrypt_list_algorithms();
	}

	/**
	 * Returns a list of available modes
	 *
	 * @return array
	 */
	public function getAvailableModes()
	{
		return mcrypt_list_modes();
	}
}