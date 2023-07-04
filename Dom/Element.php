<?php 
/**
 * @Package: 	PHPFuse - DOM Element class
 * @Author: 	Daniel Ronkainen
 * @Licence: 	The MIT License (MIT), Copyright © Daniel Ronkainen
 				Don't delete this comment, its part of the license.
 * @Version: 	1.0.0
 */

namespace PHPFuse\Output\Dom;

use BadMethodCallException;

class Element extends Document {

	private $inst;
	private $el;
	private $attr = array();
	private $snippet;
	private $value;
	private $node;


	function __construct(string $el, ?string $value, bool $snippet = false) {
		//this->inst = $inst;
		$this->el = $el;
		$this->value = $value;
		$this->snippet = $snippet;
	}
	
	/**
	 * Overwrite the current element
	 * @param string $el HTML Tag name
	 */
	function setElement(string $el): self
	{
		$this->el = $el;
		return $this;
	}

	/**
	 * Set html attribute
	 * @param  string      $key attr key
	 * @param  string|null $val attr value
	 * @return self
	 */
	function attr(string $key, ?string $val = NULL) {
		$this->attr[$key] = $val;
		return $this;
	}

	/**
	 * Set multiple html attributes
	 * @param  array 	[key => value]
	 * @return self
	 */
	function attrArr(?array $arr) {
		if(is_array($arr)) $this->attr = array_merge($this->attr, $arr);
		return $this;
	}

	function attrAddTo(string $key, string $value, string $sep = " ") {
		

		if(isset($this->attr[$key])) {
			$this->attr[$key] .= "{$sep}{$value}";
		} else {
			$this->attr[$key] = $value;
		}

		return $this;
	}


	/**
	 * Set el value <elem>[VALUE]</elem>
	 * @param self
	 */
	function setValue(?string $value) {
		$this->value = $value;
		return $this;
	}

	/**
	 * Set el value
	 * @param string
	 */
	function getValue() {
		return $this->value;
	}

	/**
	 * Get el/HTML tag
	 * @return [type] [description]
	 */
	function getEl() {
		return $this->el;
	}
	
	/**
	 * Array attr to string
	 * @return string
	 */
	function buildAttr() {
		$attr = "";
		if(count($this->attr) > 0) foreach($this->attr as $k => $v) {
			$attr .= " {$k}";
			if(!is_null($v)) $attr .= "=\"{$v}\"";
		}
		return $attr;
	}

	function withElement() {
		if(!is_null($this->el)) {
			return clone $this;
		}
		return false;
	}


	/*
	function execute(?callable $call = NULL) {
		return $this->inst->execute($call);
	}

	function __call($a, $b) {

		if(method_exists($this->inst, $a)) {
			return call_user_func_array([$this->inst, $a], $b);
		} else {
			throw new BadMethodCallException("The method \"{$a}\" does not exists in \"".get_class($this->inst)."\".", 1);	
		}

	}
	 */
	
}
