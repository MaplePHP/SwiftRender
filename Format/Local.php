<?php
/**
 * @Package: 	PHPFuse Format array
 * @Author: 	Daniel Ronkainen
 * @Licence: 	The MIT License (MIT), Copyright © Daniel Ronkainen
 				Don't delete this comment, its part of the license.
 * @Version: 	1.0.0
 */
namespace PHPFuse\Output\Format;

class Local implements FormatInterface {

	static protected $prefix;
	protected $value;
	protected $sprint = array();

	/**
	 * Init format by adding data to modify/format/traverse
	 * @param  array  $arr
	 * @return self
	 */
	static function value($arr) {
		$inst = new static();
		$inst->value = $arr;
		return $inst;
	}

	static function setLang(string $prefix): void 
	{
		static::$prefix = $prefix;
	}

	function lang(string $prefix) 
	{
		$this::$prefix = $prefix;
		return $this;
	}

	function sprint(array $sprint) {
		$this->sprint = $sprint;
		return $this;
	}
	
	function get(string $key, ?string $fallback = NULL, ?array $sprint = NULL) {
		if(is_null($this::$prefix)) throw new \Exception("Lang prefix is null.", 1);
		if(!is_null($sprint)) $this->sprint($sprint);
		return  vsprintf(($this->value[$key][$this::$prefix] ?? $fallback), $this->sprint);
	}
}
