<?php
/**
 * @Package: 	PHPFuse Format URI strings
 * @Author: 	Daniel Ronkainen
 * @Licence: 	The MIT License (MIT), Copyright © Daniel Ronkainen
 				Don't delete this comment, its part of the license.
 * @Version: 	1.0.0
 */

namespace PHPFuse\Output\Format;

class Uri extends Str implements FormatInterface {

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

	function extractPath() {
		$this->value = (string)parse_url($this->value, PHP_URL_PATH);
		return $this;
	}

	function dirname() {
		$this->value = dirname($this->value);
		return $this;
	}

	function trimTrailingSlash() {
		$this->value = ltrim($this->value, '/');
		return $this;
	}

	/**
     * XXS protection
     * @param  string $str
     * @return string
     */
    function xxs() {
        if(is_null($this->value)) {
        	$this->value = NULL;
        } else {
        	$this->value = Str::value($this->value)->specialchars()->get();	
        }
        return $this;
    }
	
	/**
	 * Remove unwanted characters from string/slug and make it consistent
	 * @return self
	 */
	function formatSlug() {
		$this->clearBreaks("-")->trim()->replaceSpecialChar()->trimSpaces()->replaceSpaces("-")->toLower();
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
