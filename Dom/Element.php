<?php 
/**
 * @Package: 	PHPFuse - DOM Element class
 * @Author: 	Daniel Ronkainen
 * @Licence: 	The MIT License (MIT), Copyright © Daniel Ronkainen
 				Don't delete this comment, its part of the license.
 * @Version: 	1.0.0
 */

namespace PHPFuse\Output\Dom;

class Element {

	private $inst;
	private $element;
	private $attr = array();
	private $snippet;
	private $value;
	private $node;


	function __construct(Document $inst, string $element, ?string $value, bool $snippet = false) {
		$this->inst = $inst;
		$this->element = $element;
		$this->value = $value;
		$this->snippet = $snippet;
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
		$this->attr = array_merge($this->attr, $arr);
		return $this;
	}

	/**
	 * Set element value <elem>[VALUE]</elem>
	 * @param self
	 */
	function setValue(?string $value) {
		$this->value = $value;
		return $this;
	}

	/**
	 * Set element value
	 * @param string
	 */
	function getValue() {
		return $this->value;
	}

	/**
	 * Get element/HTML tag
	 * @return [type] [description]
	 */
	function getElement() {
		return $this->element;
	}

	function getTag() {
		return $this->getElement();
	}

	/**
	 * Circle back to DOM instrance and execute
	 * @return string
	 */
	function execute(?callable $call = NULL) {
		return $this->inst->execute($call);
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
}
