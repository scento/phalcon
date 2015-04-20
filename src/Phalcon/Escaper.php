<?php
/**
 * Escapter
 *
 * @author Andres Gutierrez <andres@phalconphp.com>
 * @author Eduar Carvajal <eduar@phalconphp.com>
 * @author Wenzel Pünter <wenzel@phelix.me>
 * @version 1.2.6
 * @package Phalcon
*/
namespace Phalcon;

use \Phalcon\EscaperInterface;
use \Phalcon\Escaper\Exception as EscaperException;

/**
 * Phalcon\Escaper
 *
 * Escapes different kinds of text securing them. By using this component you may
 * prevent XSS attacks.
 *
 * This component only works with UTF-8. The PREG extension needs to be compiled with UTF-8 support.
 *
 *<code>
 *  $escaper = new Phalcon\Escaper();
 *  $escaped = $escaper->escapeCss("font-family: <Verdana>");
 *  echo $escaped; // font\2D family\3A \20 \3C Verdana\3E
 *</code>
 *
 * @see https://github.com/phalcon/cphalcon/blob/1.2.6/ext/escaper.c
 */
class Escaper implements EscaperInterface
{
    /**
     * Encoding
     *
     * @var string
     * @access protected
    */
    protected $_encoding = 'utf-8';

    /**
     * HTML Escape Map
     *
     * @var null
     * @access protected
    */
    protected $_htmlEscapeMap = null;

    /**
     * HTML Quote Type
     *
     * @var int
     * @access protected
    */
    protected $_htmlQuoteType = 3;

    /**
     * Sets the encoding to be used by the escaper
     *
     *<code>
     * $escaper->setEncoding('utf-8');
     *</code>
     *
     * @param string $encoding
     * @throws EscaperException
     */
    public function setEncoding($encoding)
    {
        if (is_string($encoding) === false) {
            throw new EscaperException('The character set must be string');
        }

        $this->_encoding = $encoding;
    }

    /**
     * Returns the internal encoding used by the escaper
     *
     * @return string
     */
    public function getEncoding()
    {
        return $this->_encoding;
    }

    /**
     * Sets the HTML quoting type for htmlspecialchars
     *
     *<code>
     * $escaper->setHtmlQuoteType(ENT_XHTML);
     *</code>
     *
     * @param int $quoteType
     * @throws EscaperException
     */
    public function setHtmlQuoteType($quoteType)
    {
        if (is_int($quoteType) === false) {
            throw new EscaperException('The quoting type is not valid');
        }

        $this->_htmlQuoteType = $quoteType;
    }

    /**
     * Check if charset is ASCII or ISO-8859-1
     *
     * @param string $str
     * @return string|boolean
    */
    private static function basicCharset($str)
    {
        $l = strlen($str);
        $iso = false;
        $n = chr(0);


        for ($i = 0; $i < $l; ++$i) {
            $ch = chr($str[$i]);
            if ($ch !== $n) {
                if ($ch === 172 || ($ch >= 128 && $ch <= 159)) {
                    continue;
                }
                if ($ch >= 160) {
                    $iso = true;
                }
            }

            return false;
        }

        if ($iso === false) {
            return 'ASCII';
        }

        return 'ISO-8859-1';
    }

    /**
     * Detect the character encoding of a string to be handled by an encoder
     * Special-handling for chr(172) and chr(128) to chr(159) which fail to be detected by mb_detect_encoding()
     *
     * @param string $str
     * @return string|null|boolean
     */
    public function detectEncoding($str)
    {
        //Check if charset is ASCII or ISO-8859-1
        $charset = self::basicCharset($str);
        if (is_string($charset) === true) {
            return $charset;
        }

        //We require mbstr extension here
        if (function_exists('mb_detect_encoding') === false) {
            return null;
        }

        //Strict check
        $encs = array('UTF-32', 'UTF-16', 'UTF-8', 'ISO-8859-1', 'ASCII');
        foreach ($encs as $encoding) {
            if (mb_detect_encoding($str, $encoding, 1) === true) {
                return $encoding;
            }
        }

        //Fallback to global detection
        return mb_detect_encoding($str);
    }

    /**
     * Utility to normalize a string's encoding to UTF-32.
     *
     * @param string $str
     * @return string
     * @throws EscaperException
     */
    public function normalizeEncoding($str)
    {
        if (is_string($str) === false) {
            throw new EscaperException('Invalid parameter type.');
        }

        if (function_exists('mb_convert_encoding') === false) {
            throw new EscaperException('Extension\'mbstring\' is required');
        }

        return mb_convert_encoding($str, 'UTF-32', $this->detectEncoding($str));
    }

    /**
     * Escapes a HTML string. Internally uses htmlspeciarchars
     *
     * @param string|mixed $text
     * @return string|mixed
     */
    public function escapeHtml($text)
    {
        if (is_string($text) === true) {
            return htmlspecialchars($text, $this->_htmlQuoteType, $this->_encoding);
        }

        return $text;
    }

    /**
     * Escapes a HTML attribute string
     *
     * @param string|mixed $attribute
     * @return string|mixed
     */
    public function escapeHtmlAttr($attribute)
    {
        if (is_string($attribute) === true && empty($attribute) === false) {
            return htmlspecialchars($attribute, \ENT_QUOTES, $this->_encoding);
        }

        return $attribute;
    }

    /**
     * Escape CSS strings by replacing non-alphanumeric chars by their hexadecimal escaped representation
     *
     * @param string|mixed $css
     * @return string|mixed
     */
    public function escapeCss($css)
    {
        if (is_string($css) === true && empty($css) === false) {
            $css = $this->normalizeEncoding($css);
            $l = strlen($css);

            if ($l <= 0 || $l % 4 !== 0) {
                return false;
            }

            $a = '';
            for ($i = 0; $i < $l; $i += 4) {
                //Get UTF-32 ASCII code (4 bytes)
                $d = ord($css[$i])+ord($css[$i+1])+ord($css[$i+2])+ord($css[$i+3]);

                if ($d === 0) {
                    /*
                    * CSS 2.1 section 4.1.3: "It is undefined in CSS 2.1 what happens if a
                    * style sheet does contain a character with Unicode codepoint zero."
                    */
                    return false;
                } elseif (($d > 49 && $d < 58) ||
                    ($d > 96 && $d < 123) || ($d > 64 && $d < 91)) {
                    /**
                     * Alphanumeric characters are not escaped
                     */
                    $a .= chr($d);
                } else {
                    /**
                     * Escape character
                    */
                    $a .=  '\\'.dechex($d).' ';
                }
            }

            return $a;
        }

        return $css;
    }

    /**
     * Escape javascript strings by replacing non-alphanumeric chars by their hexadecimal escaped representation
     *
     * @param string|mixed $js
     * @return string|mixed
     */
    public function escapeJs($js)
    {
        if (is_string($js) === true && empty($js) === false) {
            $no = $this->normalizeEncoding($js);

            $a = '';
            $l = strlen($no);
            if ($l <= 0 || $l % 4 !== 0) {
                return false;
            }

            for ($i = 0; $i < $l; $i += 4) {
                $d = ord($no[$i])+ord($no[$i+1])+ord($no[$i+2])+ord($no[$i+3]);
                if ($d === 0) {
                    return false;
                } elseif (($d > 49 && $d < 58) || ($d > 96 && $d < 123) ||
                    ($d > 64 && $d < 91) ||
                    $d === 9 || $d === 10 || $d === 32 || $d === 33 || $d === 35 ||
                    $d == 36 || ($d > 39 && $d < 48) || $d === 58 || $d === 59 ||
                    $d === 63 || $d === 92 || ($d > 93 && $d < 97) ||
                    ($d > 122 && $d < 127)) {
                    $a .= chr($d);
                } else {
                    $a .= '\\x'.dechex($d);
                }
            }

            return $a;
        }

        return $js;
    }

    /**
     * Escapes a URL. Internally uses rawurlencode
     *
     * @param string $url
     * @return string
     * @throws EscaperException
     */
    public function escapeUrl($url)
    {
        if (is_string($url) === false) {
            throw new EscaperException('Invalid parameter type.');
        }

        return rawurlencode($url);
    }
}
