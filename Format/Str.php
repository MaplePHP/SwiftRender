<?php
/**
 * @Package: 	PHPFuse Format string
 * @Author: 	Daniel Ronkainen
 * @Licence: 	The MIT License (MIT), Copyright © Daniel Ronkainen
 				Don't delete this comment, its part of the license.
 * @Version: 	1.0.0
 */

namespace PHPFuse\Output\Format;

class Str implements FormatInterface {

	protected $value;


	/**
	 * Init format by adding data to modify/format/traverse
	 * @param  array  $arr
	 * @return self
	 */
	static function value($value): FormatInterface 
	{
		$inst = new static();
		$inst->value = $value;
		return $inst;
	}

	function get() {
		return $this->value;
	}

	/**
	 * Excerpt/shorten down text/string 
	 * @param  integer $length total length
	 * @param  string  $ending When break text add a ending (...) 
	 * @return string
	 */
	function excerpt($length = 40, $ending = "...") {
		$this->stripTags()->entityDecode();
		$this->value = str_replace(array("'", '"', "”"), array("", "", ""), $this->value);
		$strlen = strlen($this->value);
		$this->value = trim(mb_substr($this->value, 0, $length));
		if($strlen > $length) $this->value .= $ending;
		return $this;
	}
	
	/**
	 * Convert new line to html <br>
	 * @return [type] [description]
	 */
	function nl2br() {
		$this->value = nl2br($this->value);
		return $this;
	}

	/**
	 * Make sure string allways end with a trailing slash (will only add slash if it does not exist)
	 * @return self§
	 */
	function trailingSlash() {
		$this->value = rtrim($this->value, "/").'/';
		return $this;
	}

	/**
	 * Strip html tags from string
	 * @param  string $whitelist "<em><strong>"
	 * @return self
	 */
	function stripTags(string $whitelist = "") {
		$this->value = strip_tags($this->value, $whitelist);
		return $this;
	}

	/**
	 * Cleans GET/POST data (XSS protection)
	 * @return self
	 */
	function specialchars() {
		$this->value = htmlspecialchars($this->value, ENT_QUOTES, 'UTF-8');
		return $this;
	}

	/**
	 * Clears soft breaks
	 * @return self
	 */
	function clearBreaks() {
		$this->value = preg_replace('/(\v|\s)+/', ' ', $this->value);
		return $this;
	}

	/**
	 * Entity Decode
	 * @return self
	 */
	function entityDecode() {
		$this->value = html_entity_decode($this->value);
		return $this;
	}

	/**
	 * Trim
	 * @return self
	 */
	function trim() {
		$this->value = trim($this->value);
		return $this;
	}

	/**
	 * strtolower
	 * @return self
	 */
	function toLower() {
		$this->value = strtolower($this->value);
		return $this;
	}

	/**
	 * strtoupper
	 * @return self
	 */
	function toUpper() {
		$this->value = strtoupper($this->value);
		return $this;
	}

	/**
	 * ucfirst
	 * @return self
	 */
	function ucfirst() {
		$this->value = ucfirst($this->value);
		return $this;
	}

	/**
	 * Add leading zero to string
	 * @return self
	 */
	function leadingZero() {
		$this->value = str_pad($this->value, 2, '0', STR_PAD_LEFT);
		return $this;
	}

	/**
	 * Replace spaces
	 * @param  string $replaceWith
	 * @return self
	 */
	function replaceSpaces(string $replaceWith = "-") {
		$this->value = preg_replace("/\s/", $replaceWith, $this->value);
		return $this;
	}

	/**
	 * Remove unwanted characters from string/mail and make it consistent
	 * @return self
	 */
	function formatEmail() {
		return $this->trim()->replaceSpecialChar()->toLower();
	}


	/**
	 * Replace multiple space between words with a single space
	 * @return self
	 */
	function trimSpaces() {
		 $this->value = preg_replace("/[\s-]+/", " ", $this->value);
		 return $this;
	}

	/**
	 * Remove unwanted characters from string/slug and make it consistent
	 * @return self
	 */
	function formatSlug() {
		$this->clearBreaks("-")->trim()->replaceSpecialChar()->trimSpaces()->replaceSpaces("-")->tolower();
	    $this->value = preg_replace("/[^a-z0-9\s-]/", "", $this->value);
	    return $this;
	}

	/**
	 * Replaces special characters to it's counter part to "A" or "O"
	 * @param  string $str
	 * @return string
	 */
	function replaceSpecialChar() {
	   $pattern = array('é','è','ë','ê','É','È','Ë','Ê','á','à','ä','â','å','Á','À','Ä','Â','Å','ó','ò','ö','ô','Ó','Ò','Ö','Ô','í','ì','ï','î','Í','Ì','Ï','Î','ú','ù','ü','û','Ú','Ù','Ü','Û','ý','ÿ','Ý','ø','Ø','œ','Œ','Æ','ç','Ç');
	   $replace = array('e','e','e','e','E','E','E','E','a','a','a','a','a','A','A','A','A','A','o','o','o','o','O','O','O','O','i','i','i','I','I','I','I','I','u','u','u','u','U','U','U','U','y','y','Y','o','O','a','A','A','c','C');
	   $this->value = str_replace($pattern, $replace, $this->value);

	   return $this;
	}

	function urldecode(?array $find = NULL, ?array $replace = NULL) {
		$this->value = urldecode($this->value);
		return $this;
	}

	function urlencode(?array $find = NULL, ?array $replace = NULL) {
		$this->value = urlencode($this->value);
		if(!is_null($find) && !is_null($replace)) $this->replace($find, $replace);
		return $this;
	}

	function rawurldecode(?array $find = NULL, ?array $replace = NULL) {
		$this->value = rawurldecode($this->value);
		return $this;
	}

	function rawurlencode(?array $find = NULL, ?array $replace = NULL) {
		$this->value = rawurlencode($this->value);
		if(!is_null($find) && !is_null($replace)) $this->replace($find, $replace);
		return $this;
	}

	function replace($find, $replace) {
		$this->value = str_replace($find, $replace, $this->value);
		return $this;
	}

	function toggleUrlencode(?array $find = NULL, ?array $replace = NULL) {
		return $this->urldecode()->rawurlencode($find, $replace);
	}

}
