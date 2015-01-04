<?php
/**
 * Escaper Interface
 *
 * @author Andres Gutierrez <andres@phalconphp.com>
 * @author Eduar Carvajal <eduar@phalconphp.com>
 * @version 1.2.6
 * @package Phalcon
*/
namespace Phalcon;

/**
 * Phalcon\EscaperInterface initializer
 *
 * @see https://github.com/phalcon/cphalcon/1.2.6/master/ext/escaperinterface.c
 */
interface EscaperInterface
{
    /**
     * Sets the encoding to be used by the escaper
     *
     * @param string $encoding
     */
    public function setEncoding($encoding);

    /**
     * Returns the internal encoding used by the escaper
     *
     * @return string
     */
    public function getEncoding();

    /**
     * Sets the HTML quoting type for htmlspecialchars
     *
     * @param int $quoteType
     */
    public function setHtmlQuoteType($quoteType);

    /**
     * Escapes a HTML string
     *
     * @param string $text
     * @return string
     */
    public function escapeHtml($text);

    /**
     * Escapes a HTML attribute string
     *
     * @param string $text
     * @return string
     */
    public function escapeHtmlAttr($text);

    /**
     * Escape CSS strings by replacing non-alphanumeric chars by their hexadecimal representation
     *
     * @param string $css
     * @return string
     */
    public function escapeCss($css);

    /**
     * Escape Javascript strings by replacing non-alphanumeric chars by their hexadecimal representation
     *
     * @param string $js
     * @return string
     */
    public function escapeJs($js);

    /**
     * Escapes a URL. Internally uses rawurlencode
     *
     * @param string $url
     * @return string
     */
    public function escapeUrl($url);
}
