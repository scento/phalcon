<?php
/**
 * This {@link aCssMinifierFilter minifier filter} triggers on CSS Level 3 properties and will add declaration tokens
 * with browser-specific properties.
 * 
 * @package		CssMin/Minifier/Filters
 * @link		http://code.google.com/p/cssmin/
 * @author		Joe Scylla <joe.scylla@gmail.com>
 * @copyright	2008 - 2011 Joe Scylla <joe.scylla@gmail.com>
 * @license		http://opensource.org/licenses/mit-license.php MIT License
 * @version		3.0.1
 */
class CssConvertLevel3PropertiesMinifierFilter extends aCssMinifierFilter
	{
	/**
	 * Css property transformations table. Used to convert CSS3 and proprietary properties to the browser-specific 
	 * counterparts.
	 * 
	 * @var array
	 */
	private $transformations = array
		(
		// Property						Array(Mozilla, Webkit, Opera, Internet Explorer); NULL values are placeholders and will get ignored
		"animation"						=> array(null, "-webkit-animation", null, null),
		"animation-delay"				=> array(null, "-webkit-animation-delay", null, null),
		"animation-direction" 			=> array(null, "-webkit-animation-direction", null, null),
		"animation-duration"			=> array(null, "-webkit-animation-duration", null, null),
		"animation-fill-mode"			=> array(null, "-webkit-animation-fill-mode", null, null),
		"animation-iteration-count"		=> array(null, "-webkit-animation-iteration-count", null, null),
		"animation-name"				=> array(null, "-webkit-animation-name", null, null),
		"animation-play-state"			=> array(null, "-webkit-animation-play-state", null, null),
		"animation-timing-function"		=> array(null, "-webkit-animation-timing-function", null, null),
		"appearance"					=> array("-moz-appearance", "-webkit-appearance", null, null),
		"backface-visibility"			=> array(null, "-webkit-backface-visibility", null, null),
		"background-clip"				=> array(null, "-webkit-background-clip", null, null),
		"background-composite"			=> array(null, "-webkit-background-composite", null, null),
		"background-inline-policy"		=> array("-moz-background-inline-policy", null, null, null),
		"background-origin"				=> array(null, "-webkit-background-origin", null, null),
		"background-position-x"			=> array(null, null, null, "-ms-background-position-x"),
		"background-position-y"			=> array(null, null, null, "-ms-background-position-y"),
		"background-size"				=> array(null, "-webkit-background-size", null, null),
		"behavior"						=> array(null, null, null, "-ms-behavior"),
		"binding"						=> array("-moz-binding", null, null, null),
		"border-after"					=> array(null, "-webkit-border-after", null, null),
		"border-after-color"			=> array(null, "-webkit-border-after-color", null, null),
		"border-after-style"			=> array(null, "-webkit-border-after-style", null, null),
		"border-after-width"			=> array(null, "-webkit-border-after-width", null, null),
		"border-before"					=> array(null, "-webkit-border-before", null, null),
		"border-before-color"			=> array(null, "-webkit-border-before-color", null, null),
		"border-before-style"			=> array(null, "-webkit-border-before-style", null, null),
		"border-before-width"			=> array(null, "-webkit-border-before-width", null, null),
		"border-border-bottom-colors"	=> array("-moz-border-bottom-colors", null, null, null),
		"border-bottom-left-radius"		=> array("-moz-border-radius-bottomleft", "-webkit-border-bottom-left-radius", null, null),
		"border-bottom-right-radius"	=> array("-moz-border-radius-bottomright", "-webkit-border-bottom-right-radius", null, null),
		"border-end"					=> array("-moz-border-end", "-webkit-border-end", null, null),
		"border-end-color"				=> array("-moz-border-end-color", "-webkit-border-end-color", null, null),
		"border-end-style"				=> array("-moz-border-end-style", "-webkit-border-end-style", null, null),
		"border-end-width"				=> array("-moz-border-end-width", "-webkit-border-end-width", null, null),
		"border-fit"					=> array(null, "-webkit-border-fit", null, null),
		"border-horizontal-spacing"		=> array(null, "-webkit-border-horizontal-spacing", null, null),
		"border-image"					=> array("-moz-border-image", "-webkit-border-image", null, null),
		"border-left-colors"			=> array("-moz-border-left-colors", null, null, null),
		"border-radius"					=> array("-moz-border-radius", "-webkit-border-radius", null, null),
		"border-border-right-colors"	=> array("-moz-border-right-colors", null, null, null),
		"border-start"					=> array("-moz-border-start", "-webkit-border-start", null, null),
		"border-start-color"			=> array("-moz-border-start-color", "-webkit-border-start-color", null, null),
		"border-start-style"			=> array("-moz-border-start-style", "-webkit-border-start-style", null, null),
		"border-start-width"			=> array("-moz-border-start-width", "-webkit-border-start-width", null, null),
		"border-top-colors"				=> array("-moz-border-top-colors", null, null, null),
		"border-top-left-radius"		=> array("-moz-border-radius-topleft", "-webkit-border-top-left-radius", null, null),
		"border-top-right-radius"		=> array("-moz-border-radius-topright", "-webkit-border-top-right-radius", null, null),
		"border-vertical-spacing"		=> array(null, "-webkit-border-vertical-spacing", null, null),
		"box-align"						=> array("-moz-box-align", "-webkit-box-align", null, null),
		"box-direction"					=> array("-moz-box-direction", "-webkit-box-direction", null, null),
		"box-flex"						=> array("-moz-box-flex", "-webkit-box-flex", null, null),
		"box-flex-group"				=> array(null, "-webkit-box-flex-group", null, null),
		"box-flex-lines"				=> array(null, "-webkit-box-flex-lines", null, null),
		"box-ordinal-group"				=> array("-moz-box-ordinal-group", "-webkit-box-ordinal-group", null, null),
		"box-orient"					=> array("-moz-box-orient", "-webkit-box-orient", null, null),
		"box-pack"						=> array("-moz-box-pack", "-webkit-box-pack", null, null),
		"box-reflect"					=> array(null, "-webkit-box-reflect", null, null),
		"box-shadow"					=> array("-moz-box-shadow", "-webkit-box-shadow", null, null),
		"box-sizing"					=> array("-moz-box-sizing", null, null, null),
		"color-correction"				=> array(null, "-webkit-color-correction", null, null),
		"column-break-after"			=> array(null, "-webkit-column-break-after", null, null),
		"column-break-before"			=> array(null, "-webkit-column-break-before", null, null),
		"column-break-inside"			=> array(null, "-webkit-column-break-inside", null, null),
		"column-count"					=> array("-moz-column-count", "-webkit-column-count", null, null),
		"column-gap"					=> array("-moz-column-gap", "-webkit-column-gap", null, null),
		"column-rule"					=> array("-moz-column-rule", "-webkit-column-rule", null, null),
		"column-rule-color"				=> array("-moz-column-rule-color", "-webkit-column-rule-color", null, null),
		"column-rule-style"				=> array("-moz-column-rule-style", "-webkit-column-rule-style", null, null),
		"column-rule-width"				=> array("-moz-column-rule-width", "-webkit-column-rule-width", null, null),
		"column-span"					=> array(null, "-webkit-column-span", null, null),
		"column-width"					=> array("-moz-column-width", "-webkit-column-width", null, null),
		"columns"						=> array(null, "-webkit-columns", null, null),
		"filter"						=> array(__CLASS__, "filter"),
		"float-edge"					=> array("-moz-float-edge", null, null, null),
		"font-feature-settings"			=> array("-moz-font-feature-settings", null, null, null),
		"font-language-override"		=> array("-moz-font-language-override", null, null, null),
		"font-size-delta"				=> array(null, "-webkit-font-size-delta", null, null),
		"font-smoothing"				=> array(null, "-webkit-font-smoothing", null, null),
		"force-broken-image-icon"		=> array("-moz-force-broken-image-icon", null, null, null),
		"highlight"						=> array(null, "-webkit-highlight", null, null),
		"hyphenate-character"			=> array(null, "-webkit-hyphenate-character", null, null),
		"hyphenate-locale"				=> array(null, "-webkit-hyphenate-locale", null, null),
		"hyphens"						=> array(null, "-webkit-hyphens", null, null),
		"force-broken-image-icon"		=> array("-moz-image-region", null, null, null),
		"ime-mode"						=> array(null, null, null, "-ms-ime-mode"),
		"interpolation-mode"			=> array(null, null, null, "-ms-interpolation-mode"),
		"layout-flow"					=> array(null, null, null, "-ms-layout-flow"),
		"layout-grid"					=> array(null, null, null, "-ms-layout-grid"),
		"layout-grid-char"				=> array(null, null, null, "-ms-layout-grid-char"),
		"layout-grid-line"				=> array(null, null, null, "-ms-layout-grid-line"),
		"layout-grid-mode"				=> array(null, null, null, "-ms-layout-grid-mode"),
		"layout-grid-type"				=> array(null, null, null, "-ms-layout-grid-type"),
		"line-break"					=> array(null, "-webkit-line-break", null, "-ms-line-break"),
		"line-clamp"					=> array(null, "-webkit-line-clamp", null, null),
		"line-grid-mode"				=> array(null, null, null, "-ms-line-grid-mode"),
		"logical-height"				=> array(null, "-webkit-logical-height", null, null),
		"logical-width"					=> array(null, "-webkit-logical-width", null, null),
		"margin-after"					=> array(null, "-webkit-margin-after", null, null),
		"margin-after-collapse"			=> array(null, "-webkit-margin-after-collapse", null, null),
		"margin-before"					=> array(null, "-webkit-margin-before", null, null),
		"margin-before-collapse"		=> array(null, "-webkit-margin-before-collapse", null, null),
		"margin-bottom-collapse"		=> array(null, "-webkit-margin-bottom-collapse", null, null),
		"margin-collapse"				=> array(null, "-webkit-margin-collapse", null, null),
		"margin-end"					=> array("-moz-margin-end", "-webkit-margin-end", null, null),
		"margin-start"					=> array("-moz-margin-start", "-webkit-margin-start", null, null),
		"margin-top-collapse"			=> array(null, "-webkit-margin-top-collapse", null, null),
		"marquee "						=> array(null, "-webkit-marquee", null, null),
		"marquee-direction"				=> array(null, "-webkit-marquee-direction", null, null),
		"marquee-increment"				=> array(null, "-webkit-marquee-increment", null, null),
		"marquee-repetition"			=> array(null, "-webkit-marquee-repetition", null, null),
		"marquee-speed"					=> array(null, "-webkit-marquee-speed", null, null),
		"marquee-style"					=> array(null, "-webkit-marquee-style", null, null),
		"mask"							=> array(null, "-webkit-mask", null, null),
		"mask-attachment"				=> array(null, "-webkit-mask-attachment", null, null),
		"mask-box-image"				=> array(null, "-webkit-mask-box-image", null, null),
		"mask-clip"						=> array(null, "-webkit-mask-clip", null, null),
		"mask-composite"				=> array(null, "-webkit-mask-composite", null, null),
		"mask-image"					=> array(null, "-webkit-mask-image", null, null),
		"mask-origin"					=> array(null, "-webkit-mask-origin", null, null),
		"mask-position"					=> array(null, "-webkit-mask-position", null, null),
		"mask-position-x"				=> array(null, "-webkit-mask-position-x", null, null),
		"mask-position-y"				=> array(null, "-webkit-mask-position-y", null, null),
		"mask-repeat"					=> array(null, "-webkit-mask-repeat", null, null),
		"mask-repeat-x"					=> array(null, "-webkit-mask-repeat-x", null, null),
		"mask-repeat-y"					=> array(null, "-webkit-mask-repeat-y", null, null),
		"mask-size"						=> array(null, "-webkit-mask-size", null, null),
		"match-nearest-mail-blockquote-color" => array(null, "-webkit-match-nearest-mail-blockquote-color", null, null),
		"max-logical-height"			=> array(null, "-webkit-max-logical-height", null, null),
		"max-logical-width"				=> array(null, "-webkit-max-logical-width", null, null),
		"min-logical-height"			=> array(null, "-webkit-min-logical-height", null, null),
		"min-logical-width"				=> array(null, "-webkit-min-logical-width", null, null),
		"object-fit"					=> array(null, null, "-o-object-fit", null),
		"object-position"				=> array(null, null, "-o-object-position", null),
		"opacity"						=> array(__CLASS__, "opacity"),
		"outline-radius"				=> array("-moz-outline-radius", null, null, null),
		"outline-bottom-left-radius"	=> array("-moz-outline-radius-bottomleft", null, null, null),
		"outline-bottom-right-radius"	=> array("-moz-outline-radius-bottomright", null, null, null),
		"outline-top-left-radius"		=> array("-moz-outline-radius-topleft", null, null, null),
		"outline-top-right-radius"		=> array("-moz-outline-radius-topright", null, null, null),
		"padding-after"					=> array(null, "-webkit-padding-after", null, null),
		"padding-before"				=> array(null, "-webkit-padding-before", null, null),
		"padding-end"					=> array("-moz-padding-end", "-webkit-padding-end", null, null),
		"padding-start"					=> array("-moz-padding-start", "-webkit-padding-start", null, null),
		"perspective"					=> array(null, "-webkit-perspective", null, null),
		"perspective-origin"			=> array(null, "-webkit-perspective-origin", null, null),
		"perspective-origin-x"			=> array(null, "-webkit-perspective-origin-x", null, null),
		"perspective-origin-y"			=> array(null, "-webkit-perspective-origin-y", null, null),
		"rtl-ordering"					=> array(null, "-webkit-rtl-ordering", null, null),
		"scrollbar-3dlight-color"		=> array(null, null, null, "-ms-scrollbar-3dlight-color"),
		"scrollbar-arrow-color"			=> array(null, null, null, "-ms-scrollbar-arrow-color"),
		"scrollbar-base-color"			=> array(null, null, null, "-ms-scrollbar-base-color"),
		"scrollbar-darkshadow-color"	=> array(null, null, null, "-ms-scrollbar-darkshadow-color"),
		"scrollbar-face-color"			=> array(null, null, null, "-ms-scrollbar-face-color"),
		"scrollbar-highlight-color"		=> array(null, null, null, "-ms-scrollbar-highlight-color"),
		"scrollbar-shadow-color"		=> array(null, null, null, "-ms-scrollbar-shadow-color"),
		"scrollbar-track-color"			=> array(null, null, null, "-ms-scrollbar-track-color"),
		"stack-sizing"					=> array("-moz-stack-sizing", null, null, null),
		"svg-shadow"					=> array(null, "-webkit-svg-shadow", null, null),
		"tab-size"						=> array("-moz-tab-size", null, "-o-tab-size", null),
		"table-baseline"				=> array(null, null, "-o-table-baseline", null),
		"text-align-last"				=> array(null, null, null, "-ms-text-align-last"),
		"text-autospace"				=> array(null, null, null, "-ms-text-autospace"),
		"text-combine"					=> array(null, "-webkit-text-combine", null, null),
		"text-decorations-in-effect"	=> array(null, "-webkit-text-decorations-in-effect", null, null),
		"text-emphasis"					=> array(null, "-webkit-text-emphasis", null, null),
		"text-emphasis-color"			=> array(null, "-webkit-text-emphasis-color", null, null),
		"text-emphasis-position"		=> array(null, "-webkit-text-emphasis-position", null, null),
		"text-emphasis-style"			=> array(null, "-webkit-text-emphasis-style", null, null),
		"text-fill-color"				=> array(null, "-webkit-text-fill-color", null, null),
		"text-justify"					=> array(null, null, null, "-ms-text-justify"),
		"text-kashida-space"			=> array(null, null, null, "-ms-text-kashida-space"),
		"text-overflow"					=> array(null, null, "-o-text-overflow", "-ms-text-overflow"),
		"text-security"					=> array(null, "-webkit-text-security", null, null),
		"text-size-adjust"				=> array(null, "-webkit-text-size-adjust", null, "-ms-text-size-adjust"),
		"text-stroke"					=> array(null, "-webkit-text-stroke", null, null),
		"text-stroke-color"				=> array(null, "-webkit-text-stroke-color", null, null),
		"text-stroke-width"				=> array(null, "-webkit-text-stroke-width", null, null),
		"text-underline-position"		=> array(null, null, null, "-ms-text-underline-position"),
		"transform"						=> array("-moz-transform", "-webkit-transform", "-o-transform", null),
		"transform-origin"				=> array("-moz-transform-origin", "-webkit-transform-origin", "-o-transform-origin", null),
		"transform-origin-x"			=> array(null, "-webkit-transform-origin-x", null, null),
		"transform-origin-y"			=> array(null, "-webkit-transform-origin-y", null, null),
		"transform-origin-z"			=> array(null, "-webkit-transform-origin-z", null, null),
		"transform-style"				=> array(null, "-webkit-transform-style", null, null),
		"transition"					=> array("-moz-transition", "-webkit-transition", "-o-transition", null),
		"transition-delay"				=> array("-moz-transition-delay", "-webkit-transition-delay", "-o-transition-delay", null),
		"transition-duration"			=> array("-moz-transition-duration", "-webkit-transition-duration", "-o-transition-duration", null),
		"transition-property"			=> array("-moz-transition-property", "-webkit-transition-property", "-o-transition-property", null),
		"transition-timing-function"	=> array("-moz-transition-timing-function", "-webkit-transition-timing-function", "-o-transition-timing-function", null),
		"user-drag"						=> array(null, "-webkit-user-drag", null, null),
		"user-focus"					=> array("-moz-user-focus", null, null, null),
		"user-input"					=> array("-moz-user-input", null, null, null),
		"user-modify"					=> array("-moz-user-modify", "-webkit-user-modify", null, null),
		"user-select"					=> array("-moz-user-select", "-webkit-user-select", null, null),
		"white-space"					=> array(__CLASS__, "whiteSpace"),
		"window-shadow"					=> array("-moz-window-shadow", null, null, null),
		"word-break"					=> array(null, null, null, "-ms-word-break"),
		"word-wrap"						=> array(null, null, null, "-ms-word-wrap"),
		"writing-mode"					=> array(null, "-webkit-writing-mode", null, "-ms-writing-mode"),
		"zoom"							=> array(null, null, null, "-ms-zoom")
		);
	/**
	 * Implements {@link aCssMinifierFilter::filter()}.
	 * 
	 * @param array $tokens Array of objects of type aCssToken
	 * @return integer Count of added, changed or removed tokens; a return value large than 0 will rebuild the array
	 */
	public function apply(array &$tokens)
		{
		$r = 0;
		$transformations = &$this->transformations;
		for ($i = 0, $l = count($tokens); $i < $l; $i++)
			{
			if (get_class($tokens[$i]) === "CssRulesetDeclarationToken")
				{
				$tProperty = $tokens[$i]->Property;
				if (isset($transformations[$tProperty]))
					{
					$result = array();
					if (is_callable($transformations[$tProperty]))
						{
						$result = call_user_func_array($transformations[$tProperty], array($tokens[$i]));
						if (!is_array($result) && is_object($result))
							{
							$result = array($result);
							}
						}
					else
						{
						$tValue			= $tokens[$i]->Value;
						$tMediaTypes	= $tokens[$i]->MediaTypes;
						foreach ($transformations[$tProperty] as $property)
							{
							if ($property !== null)
								{
								$result[] = new CssRulesetDeclarationToken($property, $tValue, $tMediaTypes);
								}
							}
						}
					if (count($result) > 0)
						{
						array_splice($tokens, $i + 1, 0, $result);
						$i += count($result);
						$l += count($result);
						}
					}
				}
			}
		return $r;
		}
	/**
	 * Transforms the Internet Explorer specific declaration property "filter" to Internet Explorer 8+ compatible 
	 * declaratiopn property "-ms-filter". 
	 * 
	 * @param aCssToken $token
	 * @return array
	 */
	private static function filter($token)
		{
		$r = array
			(
			new CssRulesetDeclarationToken("-ms-filter", "\"" . $token->Value . "\"", $token->MediaTypes),
			);
		return $r;
		}
	/**
	 * Transforms "opacity: {value}" into browser specific counterparts.
	 * 
	 * @param aCssToken $token
	 * @return array
	 */
	private static function opacity($token)
		{
		// Calculate the value for Internet Explorer filter statement
		$ieValue = (int) ((float) $token->Value * 100);
		$r = array
			(
			// Internet Explorer >= 8
			new CssRulesetDeclarationToken("-ms-filter", "\"alpha(opacity=" . $ieValue . ")\"", $token->MediaTypes),
			// Internet Explorer >= 4 <= 7
			new CssRulesetDeclarationToken("filter", "alpha(opacity=" . $ieValue . ")", $token->MediaTypes),
			new CssRulesetDeclarationToken("zoom", "1", $token->MediaTypes)
			);
		return $r;
		}
	/**
	 * Transforms "white-space: pre-wrap" into browser specific counterparts.
	 * 
	 * @param aCssToken $token
	 * @return array
	 */
	private static function whiteSpace($token)
		{
		if (strtolower($token->Value) === "pre-wrap")
			{
			$r = array
				(
				// Firefox < 3
				new CssRulesetDeclarationToken("white-space", "-moz-pre-wrap", $token->MediaTypes),
				// Webkit
				new CssRulesetDeclarationToken("white-space", "-webkit-pre-wrap", $token->MediaTypes),
				// Opera >= 4 <= 6
				new CssRulesetDeclarationToken("white-space", "-pre-wrap", $token->MediaTypes),
				// Opera >= 7
				new CssRulesetDeclarationToken("white-space", "-o-pre-wrap", $token->MediaTypes),
				// Internet Explorer >= 5.5
				new CssRulesetDeclarationToken("word-wrap", "break-word", $token->MediaTypes)
				);
			return $r;
			}
		else
			{
			return array();
			}
		}
	}
?>