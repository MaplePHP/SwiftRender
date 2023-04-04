<?php
/**
 * @Package: 	PHPFuse Format array
 * @Author: 	Daniel Ronkainen
 * @Licence: 	The MIT License (MIT), Copyright © Daniel Ronkainen
 				Don't delete this comment, its part of the license.
 * @Version: 	1.0.0
 */
namespace PHPFuse\Output\Format;

class Arr implements FormatInterface {

	protected $arr;

	/**
	 * Init format by adding data to modify/format/traverse
	 * @param  array  $arr
	 * @return self
	 */
	static function value($arr) {
		$inst = new static();
		$inst->arr = $arr;
		return $inst;
	}

	/**
	 * Return array
	 * @return array
	 */
	function get() {
		return $this->arr;
	}

	/**
	 * Unset array
	 * @param  keys    Keys that you want to unset (@unset("username", "password", "email", ....))
	 * @return self
	 */
	function unset() {
		$args = func_get_args();
		foreach($args as $v) unset($this->arr[$v]);
		return $this;
	}

	/**
	 * Get array keys
	 * @return self
	 */
	function arrayKeys() {
		$this->arr = array_keys($this->arr);
		return $this;
	}

	function wildcardSearch($search) {
		$search = str_replace( '\*', '.*?', preg_quote($search, '/'));
		$result = preg_grep( '/^'.$search.'$/i', array_keys($this->arr));
		$this->arr = array_intersect_key($this->arr, array_flip($result));
		return $this;
	}
	 

	function fill(int $index, int $times, string $value = "&nbsp;") {
		$this->arr = array_fill($index, $times, $value);
		return $this;
	}

	/**
	 * Return count/length
	 * @return int
	 */
	function count() {
		return count($this->arr);
	}


	function walk(callable $call) {

		$value = $this->arr;
        array_walk_recursive($value, function(&$value) use($call) {
        	$value = $call($value);
        });
        $this->arr = $value;


        return $this;
    }

}
