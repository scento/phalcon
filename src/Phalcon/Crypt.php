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

use \Phalcon\CryptInterface;
use \Phalcon\Crypt\Exception as CryptException;

/**
 * Phalcon\Crypt
 *
 * Provides encryption facilities to phalcon applications
 *
 *<code>
 *  $crypt = new Phalcon\Crypt();
 *
 *  $key = 'le password';
 *  $text = 'This is a secret text';
 *
 *  $encrypted = $crypt->encrypt($text, $key);
 *
 *  echo $crypt->decrypt($encrypted, $key);
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
        if (is_string($cipher) === false) {
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
        if (is_string($mode) === false) {
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
     * @param scalar $key
     * @return \Phalcon\Crypt
     * @throws CryptException
     */
    public function setKey($key)
    {
        if (is_scalar($key) === false) {
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
        if (is_int($scheme) === false) {
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
        if ($strong === false) {
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
        if (is_string($text) === false || is_string($mode) === false ||
            is_int($blockSize) === false || is_int($paddingType) === false) {
            throw new CryptException('Invalid parameter type.');
        }

        $textLen = strlen($text);

        if (($textLen % $blockSize === 0) && ($mode === 'ecb' || $mode === 'cbc')) {
            switch ($paddingType) {
                case self::PADDING_ANSI_X_923:
                    if (ord($text[$textLen-1]) <= $blockSize) {
                        $paddingSize = ord($text[$textLen-1]);
                        $padding = str_repeat(chr(0), ($paddingSize -1));
                        $padding[$paddingSize - 1] = $paddingSize;

                        if (strncmp($padding, $text.($textLen-$paddingSize), $paddingSize)) {
                            $paddingSize = 0;
                        }
                    }
                    break;
                case self::PADDING_PKCS7:
                    if ($text[$textLen-1] <= $blockSize) {
                        $paddingSize = ord($text[$textLen-1]);
                        $padding = str_repeat($paddingSize, $paddingSize);
                        if (strncmp($padding, $text.($textLen-$paddingSize), $paddingSize)) {
                            $paddingSize = 0;
                        }
                    }
                    break;
                case self::PADDING_ISO_10126:
                    $paddingSize = ord($text[$textLen-1]);
                    break;
                case self::PADDING_ISO_IEC_7816_4:
                    $paddingSize = 0;
                    $i = $textLen - 1;
                    while ($i > 0 && $text[$i] === chr(0x00) && $paddingSize < $blockSize) {
                        ++$paddingSize;
                        --$i;
                    }

                    $paddingSize = ($text[$i] === chr(0x80)) ? ($paddingSize + 1) : 0;
                    break;
                case self::PADDING_ZERO:
                    $paddingSize = 0;
                    $i = $textLen - 1;
                    while ($i >= 0 && $text[$i] === chr(0x00) && $paddingSize <= $blockSize) {
                        ++$paddingSize;
                        --$i;
                    }
                    break;
                case self::PADDING_SPACE:
                    $paddingSize = 0;
                    $i = $textLen - 1;
                    while ($i >= 0 && $text[$i] === chr(0x20) && $paddingSize <= $blockSize) {
                        ++$paddingSize;
                        --$i;
                    }
                    break;
                default:
                    break;
            }

            if (isset($paddingSize) && $paddingSize <= $blockSize) {
                if ($paddingSize > $textLen) {
                    throw new CryptException('Invalid state.');
                }

                if ($paddingSize <= $textLen) {
                    return substr($text, 0, ($textLen - $paddingSize));
                } else {
                    return '';
                }
            } else {
                $paddingSize = 0;
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
        if (is_string($text) === false || is_string($mode) === false ||
            is_int($blockSize) === false || is_int($paddingType) === false) {
            throw new CryptException('Invalid parameter type.');
        }

        $paddingSize = 0;
        $padding = '';

        if ($mode === 'ecb' || $mode === 'cbc') {
            $paddingSize = $blockSize - (strlen($text) % $blockSize);

            switch ($paddingType) {
                case self::PADDING_ANSI_X_923:
                    $padding = str_repeat(chr(0), $paddingSize - 1).chr($paddingSize);
                    break;

                case self::PADDING_PKCS7:
                    $padding = str_repeat(chr($paddingSize), $paddingSize);
                    break;

                case self::PADDING_ISO_10126:
                    $padding = self::cryptoRand($paddingSize - 1).chr($paddingSize);
                    break;

                case self::PADDING_ISO_IEC_7816_4:
                    $padding = chr(0x80).str_repeat(chr(0), ($paddingSize - 1));
                    break;

                case self::PADDING_ZERO:
                    if ($paddingSize === $blockSize) {
                        $padding = '';
                    } else {
                        $padding = str_repeat(chr(0), $paddingSize);
                    }
                    break;

                case self::PADDING_SPACE:
                    $padding = str_repeat(chr(0x20), $paddingSize);
                    break;

                default:
                    break;
                default:
            }
        }

        if ($paddingSize === 0) {
            return $text;
        } else {
            if ($paddingSize <= $blockSize) {
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
     *  $encrypted = $crypt->encrypt("Ultra-secret text", "encrypt password");
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

        if (function_exists('mcrypt_get_iv_size') === false) {
            throw new CryptException('mcrypt extension is required');
        }

        if ($key === null) {
            $encryptKey = $this->_key;
        } else {
            $encryptKey = (string)$key;
        }

        if (empty($encryptKey) === true) {
            throw new CryptException('Encryption key cannot be empty');
        }

        $ivSize = (int)mcrypt_get_iv_size($this->_cipher, $this->_mode);

        if (strlen($encryptKey) > $ivSize) {
            throw new CryptException('Size of key too large for this algorithm');
        }

        //C++ source is always using \MCRYPT_RAND which is sometimes considered
        //as insecure. This might be because of windows compatibility with
        //PHP < 5.3.0
        if (\DIRECTORY_SEPARATOR === '/' && version_compare(\PHP_VERSION, '5.3.0', '<') === true) {
            $iv = (string)mcrypt_create_iv($ivSize, \MCRYPT_RAND);
        } else {
            $iv = (string)mcrypt_create_iv($ivSize, \MCRYPT_DEV_URANDOM);
        }

        $blockSize = (int)mcrypt_get_block_size($this->_cipher, $this->_mode);

        $padded = $this->padText($text, $this->_mode, $blockSize, $this->_padding);

        if (is_string($padded) === false) {
            throw new CryptException('Invalid type.');
        }

        return $iv.mcrypt_encrypt($this->_cipher, $encryptKey, $padded, $this->_mode, $iv);
    }

    /**
     * Decrypts an encrypted text
     *
     *<code>
     *  echo $crypt->decrypt($encrypted, "decrypt password");
     *</code>
     *
     * @param string $text
     * @param string|null $key
     * @return string
     * @throws CryptException
     */
    public function decrypt($text, $key = null)
    {
        if (is_string($text) === false) {
            throw new CryptException('Invalid parameter type.');
        }

        if (function_exists('mcrypt_get_iv_size') === false) {
            throw new CryptException('mcrypt extension is required');
        }

        if (is_null($key) === true) {
            $decryptKey = $this->_key;
        } elseif (is_string($key) === true) {
            $decryptKey = $key;
        } else {
            throw new CryptException('Invalid parameter type.');
        }

        if (empty($decryptKey) === true) {
            throw new CryptException('Decryption key cannot be empty');
        }

        $ivSize = mcrypt_get_iv_size($this->_cipher, $this->_mode);
        if ($ivSize === false) {
            throw new CryptException('Error while determining the IV size.');
        } else {
            $ivSize = (int)$ivSize;
        }

        $keySize = strlen($decryptKey);
        if ($keySize > $ivSize) {
            throw new CryptException('Size of key is too large for this algorithm');
        }

        $textSize = strlen($text);
        if ($keySize > $textSize) {
            throw new CryptException('Size of IV is larger than text to decrypt');
        }

        $iv = substr($text, 0, $ivSize);
        $textToDecipher = substr($text, $ivSize);
        $decrypted = (string)mcrypt_decrypt($this->_cipher, $decryptKey, $textToDecipher, $this->_mode, $iv);
        $blockSize = (int)mcrypt_get_block_size($this->_cipher, $this->_mode);

        if (is_int($this->_padding) === false ||
            is_string($this->_mode) === false ||
            is_string($decrypted) === false) {
            throw new CryptException('Invalid type.');
        }

        return self::unpadText($decrypted, $this->_mode, $blockSize, $this->_padding);
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
        if (is_string($text) === false ||
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
        if (is_string($text) === false ||
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
