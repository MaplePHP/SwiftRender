<?php
/**
 * @Package: 	PHPFuse Format numbers
 * @Author: 	Daniel Ronkainen
 * @Licence: 	The MIT License (MIT), Copyright © Daniel Ronkainen
 				Don't delete this comment, its part of the license.
 * @Version: 	1.0.0
 */

namespace PHPFuse\Output\Format;

class Num implements FormatInterface {

	private $value;

	/**
	 * Init format by adding data to modify/format/traverse
	 * @param  array  $arr
	 * @return self
	 */
	static function value($value) {
		$inst = new static();
		$inst->value = $value;
		return $inst;
	}


	function get() {
		return $this->value;
	}

	/**
	 * Convert to float number
	 * @return float
	 */
	function float() {
		$this->value = (float)$this->value;
		return $this;
	}

	/**
	 * Convert to integer
	 * @return int
	 */
	function int() {
		$this->value = (int)$this->value;
		return $this;
	}

	/**
	 * Round number
	 * @param  int    $dec Set decimals
	 * @return float
	 */
	function round(int $dec = 2) {
		$this->value = round($this->float()->get(), $dec);
		return $this;
	}

	/**
	 * Floor float
	 * @return int
	 */
	function floor() {
		$this->value = floor($this->float()->get());
		return $this;
	}

	/**
	 * Ceil float
	 * @return int
	 */
	function ceil() {
		$this->value = ceil($this->float()->get());
		return $this;
	}

	
	/**
	 * Get file size in KB
	 * @return slef
	 */
	function toKb() {
		$this->value = round(($this->float()->get()/1024), 2);
		return $this;
	}

	/**
	 * Formats the bytes to appropiate ending (k,M,G,T)
	 * @param  float  $size	bytesum
	 * @param  integer $precision float precision (decimal count)
	 * @return float
	 */
	function toFilesize() {
		$precision = 2;
		$base = log($this->float()->get()) / log(1024);
		$suffixes = array('', ' kb', ' mb', ' g', ' t');
		$this->value = round(pow(1024, $base - floor($base)), $precision).$suffixes[floor($base)];
		return $this;
	}

	function toBytes() {
        $val = $this->value;
        
        preg_match('#([0-9]+)[\s]*([a-z]+)#i', $val, $matches);
		$last = isset($matches[2]) ? $matches[2] : "";
		if(isset($matches[1])) $val = (int)$matches[1];

        switch(strtolower($last)) {
            case 'g': case 'gb': $val *= 1024;
            case 'm': case 'mb': $val *= 1024;
            case 'k': case 'kb': $val *= 1024;
        }
        $this->value = $val;

        return $this;
    }
}
